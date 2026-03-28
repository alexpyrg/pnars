<?php

declare(strict_types=1);

namespace App\Modules\Attachments;

use PDO;

final class AttachmentRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function listForAccident(string $accidentId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT at.*, u.full_name AS uploader_name
             FROM attachments at
             JOIN users u ON u.id = at.uploaded_by
             JOIN accidents a ON a.id = at.accident_id
             WHERE at.accident_id = :accident_id
               AND at.deleted_at IS NULL
               AND a.deleted_at IS NULL
             ORDER BY at.created_at DESC'
        );
        $stmt->execute([':accident_id' => $accidentId]);

        return $stmt->fetchAll() ?: [];
    }

    /** @return array<int, array<string, mixed>> */
    public function listForRoad(string $roadId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT at.*, u.full_name AS uploader_name
             FROM attachments at
             JOIN users u ON u.id = at.uploaded_by
             JOIN roads r ON r.id = at.road_id
             JOIN accident_roads ar ON ar.road_id = r.id
             JOIN accidents a ON a.id = ar.accident_id
             WHERE at.road_id = :road_id
               AND at.deleted_at IS NULL
               AND r.deleted_at IS NULL
               AND a.deleted_at IS NULL
             ORDER BY at.created_at DESC'
        );
        $stmt->execute([':road_id' => $roadId]);

        return $stmt->fetchAll() ?: [];
    }

    /** @return array<int, array<string, mixed>> */
    public function listForVehicle(string $vehicleId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT at.*, u.full_name AS uploader_name
             FROM attachments at
             JOIN users u ON u.id = at.uploaded_by
             JOIN vehicles v ON v.id = at.vehicle_id
             JOIN accidents a ON a.id = v.accident_id
             WHERE at.vehicle_id = :vehicle_id
               AND at.deleted_at IS NULL
               AND v.deleted_at IS NULL
               AND a.deleted_at IS NULL
             ORDER BY at.created_at DESC'
        );
        $stmt->execute([':vehicle_id' => $vehicleId]);

        return $stmt->fetchAll() ?: [];
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): string
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO attachments (
                accident_id,
                road_id,
                vehicle_id,
                original_name,
                stored_name,
                mime_type,
                file_size_bytes,
                storage_path,
                uploaded_by,
                created_at
            ) VALUES (
                :accident_id,
                :road_id,
                :vehicle_id,
                :original_name,
                :stored_name,
                :mime_type,
                :file_size_bytes,
                :storage_path,
                :uploaded_by,
                NOW()
            )
            RETURNING id'
        );

        $stmt->execute($data);

        return (string) $stmt->fetchColumn();
    }

    public function findById(string $attachmentId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                at.*,
                COALESCE(a.created_by, ra.created_by, va.created_by) AS owner_user_id
             FROM attachments at
             LEFT JOIN accidents a ON a.id = at.accident_id
             LEFT JOIN roads r ON r.id = at.road_id
             LEFT JOIN accident_roads ar ON ar.road_id = r.id
             LEFT JOIN accidents ra ON ra.id = ar.accident_id
             LEFT JOIN vehicles v ON v.id = at.vehicle_id
             LEFT JOIN accidents va ON va.id = v.accident_id
             WHERE at.id = :id
               AND at.deleted_at IS NULL
               AND (at.accident_id IS NULL OR a.deleted_at IS NULL)
               AND (at.road_id IS NULL OR (r.deleted_at IS NULL AND ra.deleted_at IS NULL))
               AND (at.vehicle_id IS NULL OR (v.deleted_at IS NULL AND va.deleted_at IS NULL))
             LIMIT 1'
        );
        $stmt->execute([':id' => $attachmentId]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function softDelete(string $attachmentId, string $userId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE attachments
             SET deleted_at = NOW(), deleted_by = :deleted_by
             WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute([
            ':id' => $attachmentId,
            ':deleted_by' => $userId,
        ]);
    }
}