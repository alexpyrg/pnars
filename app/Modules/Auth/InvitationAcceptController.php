<?php

declare(strict_types=1);

namespace App\Modules\Auth;

use App\Core\Http\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Security\RateLimiter;
use App\Core\Support\Flash;
use App\Core\Support\Validator;
use App\Modules\Admin\InvitationRepository;

final class InvitationAcceptController extends Controller
{
    public function show(Request $request, Response $response): void
    {
        $token = trim((string) $request->input('token', ''));
        if ($token === '') {
            $response->view('errors/404', ['title' => 'Η πρόσκληση δεν είναι έγκυρη'], 404);
            return;
        }

        $repo = new InvitationRepository($this->db());
        $invitation = $repo->findPendingByTokenHash(hash('sha256', $token));
        if ($invitation === null) {
            $response->view('errors/404', ['title' => 'Η πρόσκληση έχει λήξει ή δεν είναι έγκυρη'], 404);
            return;
        }

        $response->view('auth/accept_invitation', [
            'title' => 'Αποδοχή Πρόσκλησης',
            'invitation' => $invitation,
            'token' => $token,
        ]);
    }

    public function accept(Request $request, Response $response): void
    {
        $token = trim((string) $request->input('token', ''));
        $fullName = trim((string) $request->input('full_name', ''));
        $password = (string) $request->input('password', '');

        $limiter = new RateLimiter($this->db());
        $identifier = $this->clientIdentifier($request);
        $limitConfig = (array) config('app.rate_limit.invitation_accept', []);
        $limitResult = $limiter->consume(
            'invitation_accept',
            $identifier,
            (int) ($limitConfig['max_attempts'] ?? 10),
            (int) ($limitConfig['window_seconds'] ?? 900),
            (int) ($limitConfig['lockout_seconds'] ?? 900)
        );

        if (!$limitResult['allowed']) {
            Flash::set('error', 'Πολλές προσπάθειες αποδοχής πρόσκλησης. Προσπαθήστε ξανά σε ' . $limitResult['retry_after'] . ' δευτερόλεπτα.');
            Flash::keepInput(['full_name' => $fullName]);
            $response->redirect(url('/invitation/accept?token=' . urlencode($token)));
            return;
        }

        $validator = new Validator();
        $errors = $validator->validate([
            'token' => $token,
            'full_name' => $fullName,
            'password' => $password,
        ], [
            'token' => ['required'],
            'full_name' => ['required', 'min:3'],
            'password' => ['required', 'min:8'],
        ]);

        if ($errors !== []) {
            Flash::setErrors($errors);
            Flash::keepInput(['full_name' => $fullName]);
            Flash::set('error', 'Παρακαλώ ελέγξτε τα στοιχεία εγγραφής.');
            $response->redirect(url('/invitation/accept?token=' . urlencode($token)));
            return;
        }

        $repo = new InvitationRepository($this->db());
        $tokenHash = hash('sha256', $token);
        $invitationIdForAudit = null;

        $this->db()->beginTransaction();

        try {
            $invitation = $repo->findPendingByTokenHashForUpdate($tokenHash);
            if ($invitation === null) {
                throw new \RuntimeException('Η πρόσκληση δεν είναι πλέον διαθέσιμη.');
            }

            $invitationIdForAudit = (string) $invitation['id'];

            $findUser = $this->db()->prepare('SELECT id, is_active FROM users WHERE lower(email) = lower(:email) LIMIT 1');
            $findUser->execute([':email' => $invitation['email']]);
            $existing = $findUser->fetch();

            if ($existing && (bool) $existing['is_active']) {
                throw new \RuntimeException('Υπάρχει ήδη ενεργός χρήστης με αυτό το email.');
            }

            if ($existing) {
                $update = $this->db()->prepare(
                    'UPDATE users
                     SET full_name = :full_name,
                         password_hash = :password_hash,
                         role_id = :role_id,
                         is_active = TRUE,
                         updated_at = NOW()
                     WHERE id = :id'
                );
                $update->execute([
                    ':full_name' => $fullName,
                    ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    ':role_id' => $invitation['role_id'],
                    ':id' => $existing['id'],
                ]);

                $userId = (string) $existing['id'];
                $this->audit()->log('user.reactivate', 'user', $userId, 'Επανενεργοποιήθηκε χρήστης μέσω πρόσκλησης.');
            } else {
                $insert = $this->db()->prepare(
                    'INSERT INTO users (email, password_hash, full_name, role_id, is_active, created_at, updated_at)
                     VALUES (:email, :password_hash, :full_name, :role_id, TRUE, NOW(), NOW())
                     RETURNING id'
                );
                $insert->execute([
                    ':email' => $invitation['email'],
                    ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    ':full_name' => $fullName,
                    ':role_id' => $invitation['role_id'],
                ]);

                $userId = (string) $insert->fetchColumn();
                $this->audit()->log('user.create', 'user', $userId, 'Δημιουργήθηκε νέος χρήστης μέσω πρόσκλησης.');
            }

            if (!$repo->markAccepted((string) $invitation['id'])) {
                throw new \RuntimeException('Η πρόσκληση δεν μπόρεσε να σημειωθεί ως αποδεκτή.');
            }

            $this->db()->commit();

            $limiter->clear('invitation_accept', $identifier);

            $this->audit()->log('invitation.accept', 'invitation', (string) $invitation['id'], 'Η πρόσκληση αποδεκτή και δημιουργήθηκε/ενημερώθηκε χρήστης.');

            $this->auth()->attempt((string) $invitation['email'], $password);
            Flash::set('success', 'Ο λογαριασμός σας ενεργοποιήθηκε επιτυχώς.');
            $response->redirect(url('/'));
            return;
        } catch (\Throwable $e) {
            if ($this->db()->inTransaction()) {
                $this->db()->rollBack();
            }

            $this->audit()->log(
                'invitation.accept_failed',
                'invitation',
                $invitationIdForAudit,
                'Αποτυχία αποδοχής πρόσκλησης.',
                null,
                ['reason' => $e->getMessage()]
            );

            Flash::set('error', 'Η αποδοχή πρόσκλησης απέτυχε. Ελέγξτε τα στοιχεία σας και δοκιμάστε ξανά.');
            $response->redirect(url('/invitation/accept?token=' . urlencode($token)));
            return;
        }
    }

    private function clientIdentifier(Request $request): string
    {
        $ip = trim((string) $request->server('REMOTE_ADDR', ''));

        return $ip !== '' ? $ip : 'unknown-ip';
    }
}