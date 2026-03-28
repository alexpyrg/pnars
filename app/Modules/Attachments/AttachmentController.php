<?php

declare(strict_types=1);

namespace App\Modules\Attachments;

use App\Core\Http\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Support\Flash;

final class AttachmentController extends Controller
{
    private AttachmentRepository $attachments;
    private AttachmentService $service;

    public function __construct()
    {
        $this->attachments = new AttachmentRepository($this->db());
        $this->service = new AttachmentService($this->attachments);
    }

    public function upload(Request $request, Response $response): void
    {
        $user = $this->requireUser();

        $entityType = (string) $request->input('entity_type');
        $entityId = (string) $request->input('entity_id');
        $redirectTarget = $this->resolveRedirectTarget((string) $request->input('redirect_to', ''), $entityType, $entityId);
        $ownerId = $this->resolveOwnerId($entityType, $entityId);

        if ($ownerId === null || !$this->canUpload($user, $ownerId)) {
            $response->view('errors/403', ['title' => 'Μη εξουσιοδοτημένη πρόσβαση'], 403);
            return;
        }

        $files = $this->normalizeUploadEntries($request->file('attachment'));
        if ($files === []) {
            Flash::set('error', 'Δεν επιλέχθηκε αρχείο.');
            $response->redirect($redirectTarget);
            return;
        }

        $successCount = 0;
        $failureCount = 0;

        foreach ($files as $file) {
            try {
                $attachmentId = $this->service->upload($file, [
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                ], (string) $user['id']);

                $this->audit()->log('attachment.upload', 'attachment', $attachmentId, 'Ανέβηκε νέο συνημμένο.');
                $successCount++;
            } catch (\Throwable $e) {
                $failureCount++;

                $this->audit()->log(
                    'attachment.upload_failed',
                    'attachment',
                    null,
                    'Αποτυχία μεταφόρτωσης συνημμένου.',
                    null,
                    [
                        'reason' => $e->getMessage(),
                        'entity_type' => $entityType,
                        'entity_id' => $entityId,
                        'file_name' => (string) ($file['name'] ?? ''),
                    ]
                );
            }
        }

        if ($successCount > 0 && $failureCount === 0) {
            Flash::set('success', 'Η μεταφόρτωση ολοκληρώθηκε επιτυχώς. Αρχεία: ' . $successCount . '.');
        } elseif ($successCount > 0 && $failureCount > 0) {
            Flash::set('error', 'Ολοκληρώθηκε μερικώς. Επιτυχείς μεταφορτώσεις: ' . $successCount . ', αποτυχημένες: ' . $failureCount . '.');
        } else {
            Flash::set('error', 'Η μεταφόρτωση απέτυχε. Ελέγξτε τύπο/μέγεθος αρχείων και δοκιμάστε ξανά.');
        }

        $response->redirect($redirectTarget);
    }

    public function delete(Request $request, Response $response): void
    {
        $user = $this->requireUser();
        $attachmentId = (string) $request->route('id');

        $attachment = $this->attachments->findById($attachmentId);
        if ($attachment === null) {
            $response->view('errors/404', ['title' => 'Το συνημμένο δεν βρέθηκε'], 404);
            return;
        }

        $ownerId = (string) ($attachment['owner_user_id'] ?? '');
        if (!$this->canDelete($user, $ownerId)) {
            $response->view('errors/403', ['title' => 'Μη εξουσιοδοτημένη πρόσβαση'], 403);
            return;
        }

        $this->attachments->softDelete($attachmentId, (string) $user['id']);
        $this->audit()->log('attachment.delete', 'attachment', $attachmentId, 'Το συνημμένο διαγράφηκε λογικά.');

        Flash::set('success', 'Το συνημμένο διαγράφηκε.');

        $entityType = $this->entityTypeFromAttachment($attachment);
        $entityId = $this->entityIdFromAttachment($attachment);
        $redirectTarget = $this->resolveRedirectTarget((string) $request->input('redirect_to', ''), $entityType, $entityId);

        $response->redirect($redirectTarget);
    }

    public function download(Request $request, Response $response): void
    {
        $user = $this->requireUser();
        $attachmentId = (string) $request->route('id');

        $attachment = $this->attachments->findById($attachmentId);
        if ($attachment === null) {
            $response->view('errors/404', ['title' => 'Το συνημμένο δεν βρέθηκε'], 404);
            return;
        }

        $ownerId = (string) ($attachment['owner_user_id'] ?? '');
        if (!$this->canView($user, $ownerId)) {
            $response->view('errors/403', ['title' => 'Μη εξουσιοδοτημένη πρόσβαση'], 403);
            return;
        }

        $absolutePath = base_path((string) $attachment['storage_path']);
        if (!is_file($absolutePath)) {
            $response->view('errors/404', ['title' => 'Το αρχείο δεν βρέθηκε στο αποθηκευτικό χώρο'], 404);
            return;
        }

        $mimeType = $this->safeMimeType((string) ($attachment['mime_type'] ?? 'application/octet-stream'));
        $fileSize = max(0, (int) ($attachment['file_size_bytes'] ?? 0));
        $fileName = $this->safeDownloadFilename((string) ($attachment['original_name'] ?? 'attachment'));

        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . $fileSize);
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: ' . $this->buildContentDisposition($fileName));

        readfile($absolutePath);
        exit;
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

    private function resolveOwnerId(string $entityType, string $entityId): ?string
    {
        if ($entityType === 'accident') {
            $stmt = $this->db()->prepare('SELECT created_by FROM accidents WHERE id = :id AND deleted_at IS NULL LIMIT 1');
            $stmt->execute([':id' => $entityId]);
            $owner = $stmt->fetchColumn();
            return is_string($owner) ? $owner : null;
        }

        if ($entityType === 'road') {
            $stmt = $this->db()->prepare(
                'SELECT a.created_by
                 FROM roads r
                 JOIN accident_roads ar ON ar.road_id = r.id
                 JOIN accidents a ON a.id = ar.accident_id
                 WHERE r.id = :id AND r.deleted_at IS NULL AND a.deleted_at IS NULL
                 LIMIT 1'
            );
            $stmt->execute([':id' => $entityId]);
            $owner = $stmt->fetchColumn();
            return is_string($owner) ? $owner : null;
        }

        if ($entityType === 'vehicle') {
            $stmt = $this->db()->prepare(
                'SELECT a.created_by
                 FROM vehicles v
                 JOIN accidents a ON a.id = v.accident_id
                 WHERE v.id = :id AND v.deleted_at IS NULL AND a.deleted_at IS NULL
                 LIMIT 1'
            );
            $stmt->execute([':id' => $entityId]);
            $owner = $stmt->fetchColumn();
            return is_string($owner) ? $owner : null;
        }

        return null;
    }

    /** @param array<string, mixed> $user */
    private function canView(array $user, string $ownerId): bool
    {
        $role = (string) ($user['role_code'] ?? '');

        return $role === 'administrator'
            || $role === 'expert'
            || ($role === 'registrar' && (string) $user['id'] === $ownerId);
    }

    /** @param array<string, mixed> $user */
    private function canUpload(array $user, string $ownerId): bool
    {
        $role = (string) ($user['role_code'] ?? '');

        return $role === 'administrator'
            || ($role === 'registrar' && (string) $user['id'] === $ownerId);
    }

    /** @param array<string, mixed> $user */
    private function canDelete(array $user, string $ownerId): bool
    {
        return $this->canUpload($user, $ownerId);
    }

    /** @param array<string, mixed> $attachment */
    private function entityTypeFromAttachment(array $attachment): string
    {
        if (!empty($attachment['accident_id'])) {
            return 'accident';
        }

        if (!empty($attachment['road_id'])) {
            return 'road';
        }

        return 'vehicle';
    }

    /** @param array<string, mixed> $attachment */
    private function entityIdFromAttachment(array $attachment): string
    {
        return (string) ($attachment['accident_id'] ?? $attachment['road_id'] ?? $attachment['vehicle_id'] ?? '');
    }

    private function resolveRedirectTarget(string $candidate, string $entityType, string $entityId): string
    {
        $safe = $this->safeRedirectPath($candidate);
        if ($safe !== null) {
            return $safe;
        }

        return $this->redirectBackForEntity($entityType, $entityId);
    }

    private function safeRedirectPath(string $candidate): ?string
    {
        $value = trim($candidate);
        if ($value === '' || !str_starts_with($value, '/') || str_starts_with($value, '//')) {
            return null;
        }

        $parts = parse_url($value);
        if ($parts === false || isset($parts['scheme']) || isset($parts['host'])) {
            return null;
        }

        return $value;
    }

    private function redirectBackForEntity(string $entityType, string $entityId): string
    {
        if ($entityType === 'accident') {
            return url('/accidents/' . $entityId);
        }

        if ($entityType === 'road') {
            return url('/roads/' . $entityId . '/edit');
        }

        return url('/vehicles/' . $entityId . '/edit');
    }

    /** @return array<int, array<string, mixed>> */
    private function normalizeUploadEntries(mixed $raw): array
    {
        if (!is_array($raw) || !isset($raw['name'], $raw['tmp_name'], $raw['error'], $raw['size'])) {
            return [];
        }

        if (!is_array($raw['name'])) {
            return [$raw];
        }

        $files = [];
        foreach ($raw['name'] as $index => $name) {
            $files[] = [
                'name' => (string) $name,
                'type' => is_array($raw['type'] ?? null) ? (string) ($raw['type'][$index] ?? '') : (string) ($raw['type'] ?? ''),
                'tmp_name' => is_array($raw['tmp_name']) ? (string) ($raw['tmp_name'][$index] ?? '') : '',
                'error' => is_array($raw['error']) ? (int) ($raw['error'][$index] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE,
                'size' => is_array($raw['size']) ? (int) ($raw['size'][$index] ?? 0) : 0,
            ];
        }

        return $files;
    }

    private function safeMimeType(string $value): string
    {
        $normalized = strtolower(trim($value));

        if (preg_match('/^[a-z0-9!#$&^_.+-]+\/[a-z0-9!#$&^_.+-]+$/', $normalized) === 1) {
            return $normalized;
        }

        return 'application/octet-stream';
    }

    private function safeDownloadFilename(string $value): string
    {
        $name = trim(str_replace(["\r", "\n"], '', $value));
        $name = preg_replace('/[\\\"\x00-\x1F\x7F]+/u', '_', $name) ?? '';
        $name = preg_replace('/\s+/u', ' ', $name) ?? '';
        $name = mb_substr(trim($name), 0, 200);

        return $name !== '' ? $name : 'attachment';
    }

    private function buildContentDisposition(string $fileName): string
    {
        $asciiFallback = preg_replace('/[^A-Za-z0-9._-]/', '_', $fileName) ?? 'attachment';
        $asciiFallback = trim($asciiFallback, '._-');
        if ($asciiFallback === '') {
            $asciiFallback = 'attachment';
        }

        $utf8 = rawurlencode($fileName);

        return 'attachment; filename="' . $asciiFallback . '"; filename*=UTF-8\'\'' . $utf8;
    }
}