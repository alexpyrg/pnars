<?php

declare(strict_types=1);

namespace App\Modules\Admin;

use App\Core\Http\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Support\Flash;
use App\Core\Support\SmtpMailer;
use App\Core\Support\Validator;

final class InvitationController extends Controller
{
    public function index(Request $request, Response $response): void
    {
        $repository = new InvitationRepository($this->db());

        $response->view('admin/invitations/index', [
            'title' => 'Προσκλήσεις',
            'invitations' => $repository->latest(),
            'roles' => $repository->roleOptions(),
        ]);
    }

    public function store(Request $request, Response $response): void
    {
        $currentUser = $this->auth()->user();
        if ($currentUser === null) {
            $response->view('errors/403', ['title' => 'Μη εξουσιοδοτημένη πρόσβαση'], 403);
            return;
        }

        $input = [
            'email' => trim((string) $request->input('email', '')),
            'role_id' => trim((string) $request->input('role_id', '')),
        ];

        $validator = new Validator();
        $errors = $validator->validate($input, [
            'email' => ['required', 'email'],
            'role_id' => ['required'],
        ]);

        $repository = new InvitationRepository($this->db());

        if (!isset($errors['role_id']) && !$repository->roleExists($input['role_id'])) {
            $errors['role_id'] = 'Επιλέξτε έγκυρο ρόλο.';
        }

        if (!isset($errors['email']) && $repository->hasActiveUserWithEmail($input['email'])) {
            $errors['email'] = 'Υπάρχει ήδη ενεργός χρήστης με αυτό το email.';
        }

        if ($errors !== []) {
            Flash::setErrors($errors);
            Flash::keepInput($input);
            Flash::set('error', 'Συμπληρώστε σωστά τα στοιχεία πρόσκλησης.');
            $response->redirect(url('/admin/invitations'));
            return;
        }

        $tokenPlain = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $tokenPlain);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));

        $invitationId = $repository->create(
            $input['email'],
            $input['role_id'],
            $tokenHash,
            (string) $currentUser['id'],
            $expiresAt
        );

        $invitationLink = rtrim((string) config('app.url', ''), '/') . '/invitation/accept?token=' . $tokenPlain;

        $this->audit()->log('invitation.create', 'invitation', $invitationId, 'Δημιουργήθηκε πρόσκληση νέου χρήστη.');

        $mailError = null;
        $mailSent = $this->sendInvitationEmail($input['email'], $invitationLink, $expiresAt, $mailError);

        if ($mailSent) {
            Flash::set('success', 'Η πρόσκληση δημιουργήθηκε και στάλθηκε email στο ' . $input['email'] . '.');
        } else {
            $this->audit()->log(
                'invitation.email_failed',
                'invitation',
                $invitationId,
                'Αποτυχία αποστολής email πρόσκλησης.',
                null,
                ['reason' => $mailError]
            );

            $suffix = ($mailError !== null && $mailError !== '') ? ' Αιτία: ' . $mailError . '.' : '';
            Flash::set('success', 'Η πρόσκληση δημιουργήθηκε, αλλά δεν στάλθηκε email.' . $suffix . ' Σύνδεσμος πρόσκλησης: ' . $invitationLink);
        }

        $response->redirect(url('/admin/invitations'));
    }

    public function cancel(Request $request, Response $response): void
    {
        $currentUser = $this->auth()->user();
        if ($currentUser === null) {
            $response->view('errors/403', ['title' => 'Μη εξουσιοδοτημένη πρόσβαση'], 403);
            return;
        }

        $invitationId = (string) $request->route('id');
        $repository = new InvitationRepository($this->db());
        $invitation = $repository->findById($invitationId);

        if ($invitation === null) {
            Flash::set('error', 'Η πρόσκληση δεν βρέθηκε.');
            $response->redirect(url('/admin/invitations'));
            return;
        }

        if ((string) $invitation['status'] !== 'pending') {
            Flash::set('error', 'Η πρόσκληση δεν μπορεί να ακυρωθεί γιατί έχει ήδη χρησιμοποιηθεί ή κλείσει.');
            $response->redirect(url('/admin/invitations'));
            return;
        }

        $canceled = $repository->cancelPending($invitationId);
        if (!$canceled) {
            Flash::set('error', 'Η ακύρωση απέτυχε. Παρακαλώ δοκιμάστε ξανά.');
            $response->redirect(url('/admin/invitations'));
            return;
        }

        $this->audit()->log(
            'invitation.cancel',
            'invitation',
            $invitationId,
            'Ακυρώθηκε πρόσκληση χρήστη.',
            ['status' => 'pending'],
            ['status' => 'revoked']
        );

        Flash::set('success', 'Η πρόσκληση ακυρώθηκε επιτυχώς.');
        $response->redirect(url('/admin/invitations'));
    }

    private function sendInvitationEmail(string $email, string $invitationLink, string $expiresAt, ?string &$mailError = null): bool
    {
        $mailer = new SmtpMailer((array) config('app.mail', []));

        $subject = 'Πρόσκληση εγγραφής στο σύστημα ατυχημάτων';
        $message = implode("\n", [
            'Έχετε λάβει πρόσκληση για δημιουργία λογαριασμού στο σύστημα.',
            '',
            'Σύνδεσμος αποδοχής πρόσκλησης:',
            $invitationLink,
            '',
            'Η πρόσκληση λήγει στις: ' . $expiresAt,
            '',
            'Αν δεν περιμένατε αυτό το μήνυμα, αγνοήστε το email.',
        ]);

        $sent = $mailer->send($email, $subject, $message);
        if (!$sent) {
            $mailError = $mailer->lastError();
        }

        return $sent;
    }
}