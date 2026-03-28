<?php

declare(strict_types=1);

namespace App\Modules\Accidents;

use PDO;

final class AccidentRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @param array<string, mixed> $filters
     * @param array<string, mixed> $actor
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    public function search(array $filters, array $actor, int $page, int $perPage = 20): array
    {
        [$whereSql, $params] = $this->buildSearchWhere($filters, $actor);

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM accidents a {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = max(0, ($page - 1) * $perPage);

        $sql = "
            SELECT
                a.id,
                a.case_number,
                a.accident_datetime,
                a.entry_completed,
                a.longitude,
                a.latitude,
                a.participants_total,
                a.summary,
                a.created_by,
                creator.full_name AS creator_name,
                status_lv.label_el AS status_label,
                status_lv.code AS status_code,
                severity_lv.label_el AS severity_label,
                EXISTS (
                    SELECT 1 FROM accident_flags af
                    WHERE af.accident_id = a.id AND af.is_open = TRUE
                ) AS has_open_flag,
                (
                    SELECT string_agg(v.plate_number, ', ' ORDER BY v.plate_number)
                    FROM vehicles v
                    WHERE v.accident_id = a.id AND v.deleted_at IS NULL AND v.plate_number IS NOT NULL
                ) AS plate_numbers
            FROM accidents a
            JOIN users creator ON creator.id = a.created_by
            LEFT JOIN lookup_values status_lv ON status_lv.id = a.status_lookup_id
            LEFT JOIN lookup_values severity_lv ON severity_lv.id = a.severity_lookup_id
            {$whereSql}
            ORDER BY a.accident_datetime DESC, a.created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll() ?: [],
            'total' => $total,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @param array<string, mixed> $actor
     * @return array{items: array<int, array<string, mixed>>, recordsTotal: int, recordsFiltered: int}
     */
    public function searchForDataTable(
        array $filters,
        array $actor,
        int $start,
        int $length,
        int $orderColumn,
        string $orderDirection,
        ?string $globalSearch = null
    ): array {
        [$baseWhereSql, $baseParams] = $this->buildBaseWhere($actor);
        [$whereSql, $params] = $this->buildSearchWhere($filters, $actor, $globalSearch);

        $totalStmt = $this->pdo->prepare("SELECT COUNT(*) FROM accidents a {$baseWhereSql}");
        $totalStmt->execute($baseParams);
        $recordsTotal = (int) $totalStmt->fetchColumn();

        $filteredStmt = $this->pdo->prepare("SELECT COUNT(*) FROM accidents a {$whereSql}");
        $filteredStmt->execute($params);
        $recordsFiltered = (int) $filteredStmt->fetchColumn();

        $limit = min(max($length, 1), 200);
        $offset = max($start, 0);
        $orderBySql = $this->buildOrderByForDataTable($orderColumn, $orderDirection);

        $sql = "
            SELECT
                a.id,
                a.case_number,
                a.accident_datetime,
                a.entry_completed,
                a.longitude,
                a.latitude,
                a.participants_total,
                a.summary,
                a.created_by,
                creator.full_name AS creator_name,
                status_lv.label_el AS status_label,
                status_lv.code AS status_code,
                severity_lv.label_el AS severity_label,
                EXISTS (
                    SELECT 1 FROM accident_flags af
                    WHERE af.accident_id = a.id AND af.is_open = TRUE
                ) AS has_open_flag,
                (
                    SELECT string_agg(v.plate_number, ', ' ORDER BY v.plate_number)
                    FROM vehicles v
                    WHERE v.accident_id = a.id AND v.deleted_at IS NULL AND v.plate_number IS NOT NULL
                ) AS plate_numbers
            FROM accidents a
            JOIN users creator ON creator.id = a.created_by
            LEFT JOIN lookup_values status_lv ON status_lv.id = a.status_lookup_id
            LEFT JOIN lookup_values severity_lv ON severity_lv.id = a.severity_lookup_id
            {$whereSql}
            ORDER BY {$orderBySql}
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll() ?: [],
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
        ];
    }

    /** @param array<string, mixed> $actor */
    public function findById(string $id, array $actor): ?array
    {
        $sql = "
            SELECT
                a.*,
                status_lv.code AS status_code,
                status_lv.label_el AS status_label,
                severity_lv.label_el AS severity_label,
                creator.full_name AS creator_name
            FROM accidents a
            LEFT JOIN lookup_values status_lv ON status_lv.id = a.status_lookup_id
            LEFT JOIN lookup_values severity_lv ON severity_lv.id = a.severity_lookup_id
            JOIN users creator ON creator.id = a.created_by
            WHERE a.id = :id AND a.deleted_at IS NULL
        ";

        if (($actor['role_code'] ?? '') === 'registrar') {
            $sql .= ' AND a.created_by = :actor_id';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id);
        if (($actor['role_code'] ?? '') === 'registrar') {
            $stmt->bindValue(':actor_id', (string) $actor['id']);
        }
        $stmt->execute();

        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data, string $userId): string
    {
        $sql = '
            INSERT INTO accidents (
                case_number,
                entry_completed,
                accident_datetime,
                accident_day_lookup_id,
                expert_arrival_datetime,
                longitude,
                latitude,
                incident_identifier,
                severity_lookup_id,
                drugs_involved_lookup_id,
                alcohol_involved_lookup_id,
                hit_and_run_lookup_id,
                animal_collision_lookup_id,
                separate_events_count,
                gdv_type_lookup_id,
                gadas_type_lookup_id,
                sequence_of_events,
                first_harmful_event_lookup_id,
                most_harmful_event_lookup_id,
                participants_total,
                summary,
                information_source_lookup_id,
                confidence_level_lookup_id,
                confidence_description,
                investigation_method_lookup_id,
                investigation_confidence_lookup_id,
                investigation_confidence_description,
                status_lookup_id,
                created_by,
                updated_by,
                created_at,
                updated_at
            ) VALUES (
                :case_number,
                :entry_completed,
                :accident_datetime,
                :accident_day_lookup_id,
                :expert_arrival_datetime,
                :longitude,
                :latitude,
                :incident_identifier,
                :severity_lookup_id,
                :drugs_involved_lookup_id,
                :alcohol_involved_lookup_id,
                :hit_and_run_lookup_id,
                :animal_collision_lookup_id,
                :separate_events_count,
                :gdv_type_lookup_id,
                :gadas_type_lookup_id,
                :sequence_of_events,
                :first_harmful_event_lookup_id,
                :most_harmful_event_lookup_id,
                :participants_total,
                :summary,
                :information_source_lookup_id,
                :confidence_level_lookup_id,
                :confidence_description,
                :investigation_method_lookup_id,
                :investigation_confidence_lookup_id,
                :investigation_confidence_description,
                :status_lookup_id,
                :created_by,
                :updated_by,
                NOW(),
                NOW()
            )
            RETURNING id
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data + [
            ':created_by' => $userId,
            ':updated_by' => $userId,
        ]);

        return (string) $stmt->fetchColumn();
    }

    /** @param array<string, mixed> $data */
    public function update(string $id, array $data, string $userId): void
    {
        $sql = '
            UPDATE accidents
            SET
                case_number = :case_number,
                entry_completed = :entry_completed,
                accident_datetime = :accident_datetime,
                accident_day_lookup_id = :accident_day_lookup_id,
                expert_arrival_datetime = :expert_arrival_datetime,
                longitude = :longitude,
                latitude = :latitude,
                incident_identifier = :incident_identifier,
                severity_lookup_id = :severity_lookup_id,
                drugs_involved_lookup_id = :drugs_involved_lookup_id,
                alcohol_involved_lookup_id = :alcohol_involved_lookup_id,
                hit_and_run_lookup_id = :hit_and_run_lookup_id,
                animal_collision_lookup_id = :animal_collision_lookup_id,
                separate_events_count = :separate_events_count,
                gdv_type_lookup_id = :gdv_type_lookup_id,
                gadas_type_lookup_id = :gadas_type_lookup_id,
                sequence_of_events = :sequence_of_events,
                first_harmful_event_lookup_id = :first_harmful_event_lookup_id,
                most_harmful_event_lookup_id = :most_harmful_event_lookup_id,
                participants_total = :participants_total,
                summary = :summary,
                information_source_lookup_id = :information_source_lookup_id,
                confidence_level_lookup_id = :confidence_level_lookup_id,
                confidence_description = :confidence_description,
                investigation_method_lookup_id = :investigation_method_lookup_id,
                investigation_confidence_lookup_id = :investigation_confidence_lookup_id,
                investigation_confidence_description = :investigation_confidence_description,
                status_lookup_id = :status_lookup_id,
                updated_by = :updated_by,
                updated_at = NOW()
            WHERE id = :id AND deleted_at IS NULL
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data + [
            ':id' => $id,
            ':updated_by' => $userId,
        ]);
    }

    public function softDelete(string $id, string $userId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE accidents
             SET deleted_at = NOW(), updated_by = :updated_by, updated_at = NOW()
             WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute([
            ':id' => $id,
            ':updated_by' => $userId,
        ]);
    }

    public function statusId(string $accidentId): ?int
    {
        $stmt = $this->pdo->prepare('SELECT status_lookup_id FROM accidents WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute([':id' => $accidentId]);
        $statusId = $stmt->fetchColumn();

        return $statusId !== false ? (int) $statusId : null;
    }

    public function setStatus(string $accidentId, int $statusId, string $userId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE accidents
             SET status_lookup_id = :status_id, updated_by = :updated_by, updated_at = NOW()
             WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute([
            ':status_id' => $statusId,
            ':updated_by' => $userId,
            ':id' => $accidentId,
        ]);
    }

    public function addStatusHistory(string $accidentId, ?int $fromStatus, int $toStatus, string $userId, ?string $note = null): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO accident_status_history (accident_id, from_status_lookup_id, to_status_lookup_id, changed_by, note)
             VALUES (:accident_id, :from_status, :to_status, :changed_by, :note)'
        );
        $stmt->execute([
            ':accident_id' => $accidentId,
            ':from_status' => $fromStatus,
            ':to_status' => $toStatus,
            ':changed_by' => $userId,
            ':note' => $note,
        ]);
    }

    /** @return array<int, int> */
    public function factorIds(string $accidentId): array
    {
        $stmt = $this->pdo->prepare('SELECT factor_lookup_id FROM accident_factors WHERE accident_id = :accident_id');
        $stmt->execute([':accident_id' => $accidentId]);

        return array_map('intval', array_column($stmt->fetchAll() ?: [], 'factor_lookup_id'));
    }

    /** @param array<int, int> $factorIds */
    public function syncFactors(string $accidentId, array $factorIds, string $userId): void
    {
        $this->pdo->prepare('DELETE FROM accident_factors WHERE accident_id = :accident_id')
            ->execute([':accident_id' => $accidentId]);

        if ($factorIds === []) {
            return;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO accident_factors (accident_id, factor_lookup_id, created_by)
             VALUES (:accident_id, :factor_lookup_id, :created_by)'
        );

        foreach (array_unique($factorIds) as $factorId) {
            if ($factorId <= 0) {
                continue;
            }

            $stmt->execute([
                ':accident_id' => $accidentId,
                ':factor_lookup_id' => $factorId,
                ':created_by' => $userId,
            ]);
        }
    }

    /** @return array<int, int> */
    public function participantCounts(string $accidentId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT participant_category_lookup_id, participant_count
             FROM accident_participant_counts
             WHERE accident_id = :accident_id'
        );
        $stmt->execute([':accident_id' => $accidentId]);

        $out = [];
        foreach ($stmt->fetchAll() ?: [] as $row) {
            $out[(int) $row['participant_category_lookup_id']] = (int) $row['participant_count'];
        }

        return $out;
    }

    /** @param array<int, int> $counts */
    public function syncParticipantCounts(string $accidentId, array $counts): void
    {
        $this->pdo->prepare('DELETE FROM accident_participant_counts WHERE accident_id = :accident_id')
            ->execute([':accident_id' => $accidentId]);

        if ($counts === []) {
            return;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO accident_participant_counts (accident_id, participant_category_lookup_id, participant_count)
             VALUES (:accident_id, :category_id, :participant_count)'
        );

        foreach ($counts as $categoryId => $count) {
            $stmt->execute([
                ':accident_id' => $accidentId,
                ':category_id' => $categoryId,
                ':participant_count' => max(0, (int) $count),
            ]);
        }
    }

    /** @param array<string, mixed> $actor
     *  @return array{0: string, 1: array<string, mixed>}
     */
    private function buildBaseWhere(array $actor): array
    {
        $where = ['a.deleted_at IS NULL'];
        $params = [];

        if (($actor['role_code'] ?? '') === 'registrar') {
            $where[] = 'a.created_by = :actor_user_id';
            $params[':actor_user_id'] = (string) $actor['id'];
        }

        return ['WHERE ' . implode(' AND ', $where), $params];
    }

    private function buildOrderByForDataTable(int $column, string $direction): string
    {
        $map = [
            0 => 'a.case_number',
            1 => 'a.accident_datetime',
            2 => 'status_lv.label_el',
            3 => 'severity_lv.label_el',
            4 => 'creator.full_name',
        ];

        $orderBy = $map[$column] ?? 'a.accident_datetime';
        $orderDirection = strtolower($direction) === 'asc' ? 'ASC' : 'DESC';

        return sprintf('%s %s, a.created_at DESC', $orderBy, $orderDirection);
    }

    /**
     * @param array<string, mixed> $filters
     * @param array<string, mixed> $actor
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildSearchWhere(array $filters, array $actor, ?string $globalSearch = null): array
    {
        [$baseWhereSql, $baseParams] = $this->buildBaseWhere($actor);
        $where = [substr($baseWhereSql, 6)];
        $params = $baseParams;

        if (!empty($filters['status_id'])) {
            $where[] = 'a.status_lookup_id = :status_id';
            $params[':status_id'] = (int) $filters['status_id'];
        }

        if (isset($filters['flagged']) && $filters['flagged'] !== '') {
            if ((string) $filters['flagged'] === '1') {
                $where[] = 'EXISTS (SELECT 1 FROM accident_flags af WHERE af.accident_id = a.id AND af.is_open = TRUE)';
            }
            if ((string) $filters['flagged'] === '0') {
                $where[] = 'NOT EXISTS (SELECT 1 FROM accident_flags af WHERE af.accident_id = a.id AND af.is_open = TRUE)';
            }
        }

        if (!empty($filters['creator_id']) && ($actor['role_code'] ?? '') !== 'registrar') {
            $where[] = 'a.created_by = :creator_id';
            $params[':creator_id'] = (string) $filters['creator_id'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'a.accident_datetime >= (:date_from::date)';
            $params[':date_from'] = (string) $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "a.accident_datetime < (:date_to::date + INTERVAL '1 day')";
            $params[':date_to'] = (string) $filters['date_to'];
        }

        if (!empty($filters['accident_date'])) {
            $where[] = "a.accident_datetime >= (:accident_date::date) AND a.accident_datetime < (:accident_date::date + INTERVAL '1 day')";
            $params[':accident_date'] = (string) $filters['accident_date'];
        }

        if (!empty($filters['severity_id'])) {
            $where[] = 'a.severity_lookup_id = :severity_id';
            $params[':severity_id'] = (int) $filters['severity_id'];
        }

        if (!empty($filters['vehicle_type_id'])) {
            $where[] = 'EXISTS (
                SELECT 1 FROM vehicles v
                WHERE v.accident_id = a.id
                  AND v.deleted_at IS NULL
                  AND v.vehicle_type_lookup_id = :vehicle_type_id
            )';
            $params[':vehicle_type_id'] = (int) $filters['vehicle_type_id'];
        }

        if (!empty($filters['plate_number'])) {
            $where[] = 'EXISTS (
                SELECT 1 FROM vehicles v
                WHERE v.accident_id = a.id
                  AND v.deleted_at IS NULL
                  AND v.plate_number ILIKE :plate_number
            )';
            $params[':plate_number'] = '%' . trim((string) $filters['plate_number']) . '%';
        }

        if (isset($filters['has_coordinates']) && $filters['has_coordinates'] !== '') {
            if ((string) $filters['has_coordinates'] === '1') {
                $where[] = 'a.latitude IS NOT NULL AND a.longitude IS NOT NULL';
            }
            if ((string) $filters['has_coordinates'] === '0') {
                $where[] = '(a.latitude IS NULL OR a.longitude IS NULL)';
            }
        }

        if (isset($filters['entry_completed']) && $filters['entry_completed'] !== '') {
            $where[] = 'a.entry_completed = :entry_completed';
            $params[':entry_completed'] = ((string) $filters['entry_completed'] === '1') ? 1 : 0;
        }

        if (!empty($filters['information_source_id'])) {
            $where[] = 'a.information_source_lookup_id = :information_source_id';
            $params[':information_source_id'] = (int) $filters['information_source_id'];
        }

        if (!empty($filters['confidence_level_id'])) {
            $where[] = 'a.confidence_level_lookup_id = :confidence_level_id';
            $params[':confidence_level_id'] = (int) $filters['confidence_level_id'];
        }

        if (!empty($filters['case_number'])) {
            $where[] = 'a.case_number ILIKE :case_number';
            $params[':case_number'] = '%' . trim((string) $filters['case_number']) . '%';
        }

        if (!empty($filters['q'])) {
            $where[] = "(to_tsvector('simple', COALESCE(a.summary, '')) @@ plainto_tsquery('simple', :query_ts) OR a.case_number ILIKE :query OR a.incident_identifier ILIKE :query)";
            $params[':query'] = '%' . trim((string) $filters['q']) . '%';
            $params[':query_ts'] = trim((string) $filters['q']);
        }

        $globalSearch = trim((string) $globalSearch);
        if ($globalSearch !== '') {
                        $where[] = "(
                a.case_number ILIKE :dt_query
                OR a.incident_identifier ILIKE :dt_query
                OR to_tsvector('simple', COALESCE(a.summary, '')) @@ plainto_tsquery('simple', :dt_query_ts)
                OR EXISTS (
                    SELECT 1 FROM vehicles v
                    WHERE v.accident_id = a.id
                      AND v.deleted_at IS NULL
                      AND v.plate_number ILIKE :dt_query
                )
            )";
            $params[':dt_query'] = '%' . $globalSearch . '%';
            $params[':dt_query_ts'] = $globalSearch;
        }

        return ['WHERE ' . implode(' AND ', $where), $params];
    }
}
