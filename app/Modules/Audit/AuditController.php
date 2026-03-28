<?php

declare(strict_types=1);

namespace App\Modules\Audit;

use App\Core\Http\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;

final class AuditController extends Controller
{
    public function index(Request $request, Response $response): void
    {
        $user = $this->auth()->user();
        if ($user === null || ($user['role_code'] ?? '') !== 'administrator') {
            $response->view('errors/403', ['title' => 'Μη εξουσιοδοτημένη πρόσβαση'], 403);
            return;
        }

        $filters = [
            'action_type' => $request->input('action_type'),
            'entity_type' => $request->input('entity_type'),
            'actor_user_id' => $request->input('actor_user_id'),
            'date_from' => $this->validDateFilter($request->input('date_from')),
            'date_to' => $this->validDateFilter($request->input('date_to')),
        ];

        $cursorCreatedAtRaw = $request->input('cursor_created_at');
        $cursorCreatedAt = is_string($cursorCreatedAtRaw) && $cursorCreatedAtRaw !== '' ? $cursorCreatedAtRaw : null;

        $cursorIdRaw = $request->input('cursor_id');
        $cursorId = is_numeric((string) $cursorIdRaw) ? (int) $cursorIdRaw : null;

        $repo = new AuditRepository($this->db());
        $result = $repo->searchWindow($filters, $cursorCreatedAt, $cursorId, 30);

        $users = $this->db()->query('SELECT id, full_name FROM users ORDER BY full_name ASC')->fetchAll() ?: [];

        $response->view('admin/audit/index', [
            'title' => 'Αρχεία Ελέγχου',
            'filters' => $filters,
            'items' => $result['items'],
            'users' => $users,
            'nextCursorCreatedAt' => $result['next_cursor_created_at'],
            'nextCursorId' => $result['next_cursor_id'],
        ]);
    }

    private function validDateFilter(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $normalized = trim($value);
        if ($normalized === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalized) !== 1) {
            return null;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $normalized));

        return checkdate($month, $day, $year) ? $normalized : null;
    }
}