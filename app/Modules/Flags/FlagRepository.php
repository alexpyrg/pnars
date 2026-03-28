<?php

declare(strict_types=1);

namespace App\Modules\Flags;

use PDO;

final class FlagRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function listByAccident(string $accidentId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                f.*, 
                ft.label_el AS flag_type_label,
                creator.full_name AS created_by_name,
                resolver.full_name AS resolved_by_name
             FROM accident_flags f
             JOIN lookup_values ft ON ft.id = f.flag_type_lookup_id
             JOIN users creator ON creator.id = f.created_by
             LEFT JOIN users resolver ON resolver.id = f.resolved_by
             WHERE f.accident_id = :accident_id
             ORDER BY f.created_at DESC'
        );
        $stmt->execute([':accident_id' => $accidentId]);

        return $stmt->fetchAll() ?: [];
    }

    public function create(string $accidentId, int $flagTypeId, ?string $note, string $userId): string
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO accident_flags (accident_id, flag_type_lookup_id, note, is_open, created_by, created_at)
             VALUES (:accident_id, :flag_type_lookup_id, :note, TRUE, :created_by, NOW())
             RETURNING id'
        );
        $stmt->execute([
            ':accident_id' => $accidentId,
            ':flag_type_lookup_id' => $flagTypeId,
            ':note' => $note,
            ':created_by' => $userId,
        ]);

        return (string) $stmt->fetchColumn();
    }

    public function resolve(string $flagId, string $resolutionNote, string $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE accident_flags
             SET is_open = FALSE,
                 resolved_by = :resolved_by,
                 resolved_at = NOW(),
                 resolution_note = :resolution_note
             WHERE id = :id AND is_open = TRUE'
        );
        $stmt->execute([
            ':id' => $flagId,
            ':resolved_by' => $userId,
            ':resolution_note' => $resolutionNote,
        ]);

        return $stmt->rowCount() === 1;
    }

    public function accidentIdByFlag(string $flagId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT accident_id FROM accident_flags WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $flagId]);
        $value = $stmt->fetchColumn();

        return is_string($value) ? $value : null;
    }

    public function hasOpenFlags(string $accidentId): bool
    {
        $stmt = $this->pdo->prepare('SELECT EXISTS(SELECT 1 FROM accident_flags WHERE accident_id = :accident_id AND is_open = TRUE)');
        $stmt->execute([':accident_id' => $accidentId]);

        return (bool) $stmt->fetchColumn();
    }
}
