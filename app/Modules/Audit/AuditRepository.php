<?php

declare(strict_types=1);

namespace App\Modules\Audit;

use PDO;

final class AuditRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    public function search(array $filters, int $page, int $perPage = 30): array
    {
        [$whereSql, $params] = $this->buildWhere($filters);

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM audit_logs l {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = max(0, ($page - 1) * $perPage);

        $stmt = $this->pdo->prepare(
            "SELECT l.*, u.full_name AS actor_name
             FROM audit_logs l
             LEFT JOIN users u ON u.id = l.actor_user_id
             {$whereSql}
             ORDER BY l.created_at DESC, l.id DESC
             LIMIT :limit OFFSET :offset"
        );

        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
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
     * @return array{items: array<int, array<string, mixed>>, next_cursor_created_at: ?string, next_cursor_id: ?int}
     */
    public function searchWindow(array $filters, ?string $cursorCreatedAt, ?int $cursorId, int $perPage = 30): array
    {
        [$whereSql, $params] = $this->buildWhere($filters);

        if ($cursorCreatedAt !== null && $cursorCreatedAt !== '' && $cursorId !== null && $cursorId > 0) {
            $whereSql .= ' AND (l.created_at < :cursor_created_at OR (l.created_at = :cursor_created_at AND l.id < :cursor_id))';
            $params[':cursor_created_at'] = $cursorCreatedAt;
            $params[':cursor_id'] = $cursorId;
        }

        $limit = min(max($perPage, 1), 200);

        $stmt = $this->pdo->prepare(
            "SELECT l.*, u.full_name AS actor_name
             FROM audit_logs l
             LEFT JOIN users u ON u.id = l.actor_user_id
             {$whereSql}
             ORDER BY l.created_at DESC, l.id DESC
             LIMIT :limit_plus_one"
        );

        foreach ($params as $k => $v) {
            if ($k === ':cursor_id') {
                $stmt->bindValue($k, (int) $v, PDO::PARAM_INT);
                continue;
            }

            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit_plus_one', $limit + 1, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll() ?: [];
        $hasMore = count($rows) > $limit;

        if ($hasMore) {
            array_pop($rows);
        }

        $nextCursorCreatedAt = null;
        $nextCursorId = null;

        if ($hasMore && $rows !== []) {
            $last = $rows[array_key_last($rows)];
            $nextCursorCreatedAt = (string) $last['created_at'];
            $nextCursorId = (int) $last['id'];
        }

        return [
            'items' => $rows,
            'next_cursor_created_at' => $nextCursorCreatedAt,
            'next_cursor_id' => $nextCursorId,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildWhere(array $filters): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['action_type'])) {
            $where[] = 'l.action_type = :action_type';
            $params[':action_type'] = (string) $filters['action_type'];
        }

        if (!empty($filters['entity_type'])) {
            $where[] = 'l.entity_type = :entity_type';
            $params[':entity_type'] = (string) $filters['entity_type'];
        }

        if (!empty($filters['actor_user_id'])) {
            $where[] = 'l.actor_user_id = :actor_user_id';
            $params[':actor_user_id'] = (string) $filters['actor_user_id'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'l.created_at >= (:date_from::date)';
            $params[':date_from'] = (string) $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "l.created_at < (:date_to::date + INTERVAL '1 day')";
            $params[':date_to'] = (string) $filters['date_to'];
        }

        return ['WHERE ' . implode(' AND ', $where), $params];
    }
}
