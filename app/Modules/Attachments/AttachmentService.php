<?php

declare(strict_types=1);

namespace App\Modules\Attachments;

use App\Core\Support\Config;
use RuntimeException;

final class AttachmentService
{
    public function __construct(private readonly AttachmentRepository $repository)
    {
    }

    /**
     * @param array<string, mixed> $file
     * @param array<string, mixed> $target
     */
    public function upload(array $file, array $target, string $uploaderId): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Η μεταφόρτωση απέτυχε.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0) {
            throw new RuntimeException('Το αρχείο είναι κενό.');
        }

        $maxSize = (int) Config::get('app.uploads.max_size_bytes', 10 * 1024 * 1024);
        if ($size > $maxSize) {
            throw new RuntimeException('Το αρχείο υπερβαίνει το επιτρεπτό μέγεθος.');
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new RuntimeException('Μη έγκυρο προσωρινό αρχείο μεταφόρτωσης.');
        }

        $originalName = $this->sanitizeOriginalName((string) ($file['name'] ?? ''));
        $detectedMime = $this->detectMimeType($tmpName);

        $allowedMime = array_filter(array_map('trim', (array) Config::get('app.uploads.allowed_mime', [])));
        if (!in_array($detectedMime, $allowedMime, true)) {
            throw new RuntimeException('Μη επιτρεπτός τύπος αρχείου.');
        }

        $ext = $this->extensionFromMimeType($detectedMime);

        $entityType = (string) ($target['entity_type'] ?? 'unknown');
        $entityId = (string) ($target['entity_id'] ?? '');

        if (!in_array($entityType, ['accident', 'road', 'vehicle'], true) || $entityId === '') {
            throw new RuntimeException('Μη έγκυρος στόχος συνημμένου.');
        }

        $subDir = match ($entityType) {
            'accident' => 'accidents',
            'road' => 'roads',
            'vehicle' => 'vehicles',
        };

        $baseDir = base_path((string) Config::get('app.uploads.base_dir', 'storage/uploads'));
        $datePath = date('Y/m');
        $targetDir = $baseDir . DIRECTORY_SEPARATOR . $subDir . DIRECTORY_SEPARATOR . $datePath;

        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            throw new RuntimeException('Αδυναμία δημιουργίας φακέλου αποθήκευσης.');
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
        $absolutePath = $targetDir . DIRECTORY_SEPARATOR . $storedName;

        if (!move_uploaded_file($tmpName, $absolutePath)) {
            throw new RuntimeException('Αδυναμία αποθήκευσης αρχείου.');
        }

        $relativePath = trim(str_replace(base_path(), '', $absolutePath), '/\\');

        $payload = [
            ':accident_id' => $entityType === 'accident' ? $entityId : null,
            ':road_id' => $entityType === 'road' ? $entityId : null,
            ':vehicle_id' => $entityType === 'vehicle' ? $entityId : null,
            ':original_name' => $originalName,
            ':stored_name' => $storedName,
            ':mime_type' => $detectedMime,
            ':file_size_bytes' => $size,
            ':storage_path' => str_replace('\\', '/', $relativePath),
            ':uploaded_by' => $uploaderId,
        ];

        return $this->repository->create($payload);
    }

    private function sanitizeOriginalName(string $name): string
    {
        $name = preg_replace('/[\x00-\x1F\x7F]+/u', '', trim($name)) ?? '';
        $name = mb_substr($name, 0, 255);

        if ($name !== '') {
            return $name;
        }

        return 'attachment';
    }

    private function detectMimeType(string $tmpName): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            throw new RuntimeException('Αδυναμία ελέγχου τύπου αρχείου.');
        }

        $mime = finfo_file($finfo, $tmpName);
        finfo_close($finfo);

        if (!is_string($mime) || trim($mime) === '') {
            throw new RuntimeException('Αδυναμία αναγνώρισης τύπου αρχείου.');
        }

        return trim(strtolower($mime));
    }

    private function extensionFromMimeType(string $mime): string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            default => 'bin',
        };
    }
}
