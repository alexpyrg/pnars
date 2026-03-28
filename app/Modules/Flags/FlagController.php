<?php

declare(strict_types=1);

namespace App\Modules\Flags;

use App\Core\Http\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Support\Flash;
use App\Core\Support\Input;
use App\Modules\Accidents\AccidentRepository;
use App\Modules\Lookup\LookupRepository;

final class FlagController extends Controller
{
    private FlagRepository $flags;
    private AccidentRepository $accidents;
    private LookupRepository $lookups;

    public function __construct()
    {
        $pdo = $this->db();
        $this->flags = new FlagRepository($pdo);
        $this->accidents = new AccidentRepository($pdo);
        $this->lookups = new LookupRepository($pdo);
    }

    public function store(Request $request, Response $response): void
    {
        $user = $this->requireUser();
        $accidentId = (string) $request->route('accident_id');

        if (!in_array($user['role_code'] ?? '', ['expert', 'administrator'], true)) {
            $response->view('errors/403', ['title' => 'Μη εξουσιοδοτημένη πρόσβαση'], 403);
            return;
        }

        $accident = $this->accidents->findById($accidentId, $user);
        if ($accident === null) {
            $response->view('errors/404', ['title' => 'Το ατύχημα δεν βρέθηκε'], 404);
            return;
        }

        $flagTypeId = Input::nullableInt($request->input('flag_type_lookup_id'));
        if ($flagTypeId === null || !$this->lookups->isValueInDomain($flagTypeId, 'flag_type')) {
            Flash::set('error', 'Επιλέξτε έγκυρο τύπο σήμανσης.');
            $response->redirect(url('/accidents/' . $accidentId));
            return;
        }

        $note = Input::nullableString($request->input('note'));
        $db = $this->db();

        try {
            $db->beginTransaction();

            $flagId = $this->flags->create($accidentId, $flagTypeId, $note, (string) $user['id']);

            $flaggedStatus = $this->lookups->idByCode('accident_status', 'flagged');
            if ($flaggedStatus !== null) {
                $oldStatus = $this->accidents->statusId($accidentId);
                $this->accidents->setStatus($accidentId, $flaggedStatus, (string) $user['id']);
                $this->accidents->addStatusHistory($accidentId, $oldStatus, $flaggedStatus, (string) $user['id'], 'Η εγγραφή τέθηκε σε σήμανση.');

                if ($oldStatus !== $flaggedStatus) {
                    $this->audit()->log('accident.status_change', 'accident', $accidentId, 'Αλλαγή κατάστασης ατυχήματος λόγω σήμανσης.');
                }
            }

            $this->audit()->log('flag.create', 'accident_flag', $flagId, 'Δημιουργήθηκε νέα σήμανση σε ατύχημα.');

            $db->commit();
        } catch (\Throwable) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            Flash::set('error', 'Η καταχώρηση σήμανσης απέτυχε. Δοκιμάστε ξανά.');
            $response->redirect(url('/accidents/' . $accidentId));
            return;
        }

        Flash::set('success', 'Η σήμανση καταχωρήθηκε.');
        $response->redirect(url('/accidents/' . $accidentId));
    }

    public function resolve(Request $request, Response $response): void
    {
        $user = $this->requireUser();
        if (($user['role_code'] ?? '') !== 'administrator') {
            $response->view('errors/403', ['title' => 'Μη εξουσιοδοτημένη πρόσβαση'], 403);
            return;
        }

        $flagId = (string) $request->route('id');
        $accidentId = $this->flags->accidentIdByFlag($flagId);

        if ($accidentId === null) {
            $response->view('errors/404', ['title' => 'Η σήμανση δεν βρέθηκε'], 404);
            return;
        }

        $note = Input::nullableString($request->input('resolution_note')) ?? 'Η σήμανση επιλύθηκε από διαχειριστή.';

        $db = $this->db();
        try {
            $db->beginTransaction();

            $resolved = $this->flags->resolve($flagId, $note, (string) $user['id']);
            if (!$resolved) {
                $db->rollBack();
                Flash::set('error', 'Η σήμανση είναι ήδη κλειστή ή δεν μπορεί να επιλυθεί.');
                $response->redirect(url('/accidents/' . $accidentId));
                return;
            }

            if (!$this->flags->hasOpenFlags($accidentId)) {
                $resolvedStatus = $this->lookups->idByCode('accident_status', 'resolved');
                if ($resolvedStatus !== null) {
                    $oldStatus = $this->accidents->statusId($accidentId);
                    $this->accidents->setStatus($accidentId, $resolvedStatus, (string) $user['id']);
                    $this->accidents->addStatusHistory($accidentId, $oldStatus, $resolvedStatus, (string) $user['id'], 'Επίλυση όλων των ανοικτών σημάνσεων.');

                    if ($oldStatus !== $resolvedStatus) {
                        $this->audit()->log('accident.status_change', 'accident', $accidentId, 'Αλλαγή κατάστασης ατυχήματος λόγω επίλυσης σημάνσεων.');
                    }
                }
            }

            $this->audit()->log('flag.resolve', 'accident_flag', $flagId, 'Η σήμανση επιλύθηκε.');

            $db->commit();
        } catch (\Throwable) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            Flash::set('error', 'Η επίλυση σήμανσης απέτυχε. Δοκιμάστε ξανά.');
            $response->redirect(url('/accidents/' . $accidentId));
            return;
        }

        Flash::set('success', 'Η σήμανση επιλύθηκε επιτυχώς.');
        $response->redirect(url('/accidents/' . $accidentId));
    }

    /** @return array<string, mixed> */
    private function requireUser(): array
    {
        $user = $this->auth()->user();
        if ($user === null) {
            throw new \RuntimeException('Μη έγκυρη συνεδρία χρήστη.');
        }

        return $user;
    }
}