<?php

declare(strict_types=1);

namespace App\Modules\Admin;

use App\Core\Http\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Support\Flash;

final class UserController extends Controller
{
    public function index(Request $request, Response $response): void
    {
        $page = max(1, (int) $request->input('page', 1));
        $repository = new UserRepository($this->db());

        $response->view('admin/users/index', [
            'title' => 'Χρήστες',
            'users' => $repository->paginate($page),
            'page' => $page,
        ]);
    }

    public function toggleActive(Request $request, Response $response): void
    {
        $targetUserId = (string) $request->route('id');
        $currentUser = $this->auth()->user();
        if ($currentUser === null) {
            $response->view('errors/403', ['title' => 'Μη εξουσιοδοτημένη πρόσβαση'], 403);
            return;
        }

        if ((string) $currentUser['id'] === $targetUserId) {
            Flash::set('error', 'Δεν μπορείτε να αλλάξετε τη δική σας κατάσταση ενεργοποίησης.');
            $response->redirect(url('/admin/users'));
            return;
        }

        $repository = new UserRepository($this->db());
        $targetUser = $repository->findByIdWithRole($targetUserId);
        if ($targetUser === null) {
            Flash::set('error', 'Ο χρήστης δεν βρέθηκε.');
            $response->redirect(url('/admin/users'));
            return;
        }

        $isAdmin = (string) ($targetUser['role_code'] ?? '') === 'administrator';
        $isActive = (bool) ($targetUser['is_active'] ?? false);
        if ($isAdmin && $isActive && $repository->countActiveAdministrators() <= 1) {
            Flash::set('error', 'Δεν μπορείτε να απενεργοποιήσετε τον τελευταίο ενεργό διαχειριστή.');
            $response->redirect(url('/admin/users'));
            return;
        }

        $stmt = $this->db()->prepare('UPDATE users SET is_active = NOT is_active, updated_by = :updated_by, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':updated_by' => $currentUser['id'],
            ':id' => $targetUserId,
        ]);

        $this->audit()->log('user.toggle_active', 'user', $targetUserId, 'Αλλαγή ενεργοποίησης χρήστη από διαχειριστή.');

        Flash::set('success', 'Η κατάσταση του χρήστη ενημερώθηκε.');
        $response->redirect(url('/admin/users'));
    }
}
