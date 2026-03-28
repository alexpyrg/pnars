<?php

declare(strict_types=1);

namespace App\Modules\Admin;

use PDO;

final class UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function paginate(int $page = 1, int $perPage = 20): array
    {
        $offset = max(0, ($page - 1) * $perPage);

        $stmt = $this->pdo->prepare(
            'SELECT u.id, u.email, u.full_name, u.is_active, u.last_login_at, u.created_at, r.label_el AS role_label
             FROM users u
             JOIN roles r ON r.id = u.role_id
             ORDER BY u.created_at DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }

    /** @return array<string, mixed>|null */
    public function findByIdWithRole(string $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.id, u.is_active, r.code AS role_code
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function countActiveAdministrators(): int
    {
        $stmt = $this->pdo->query(
            "SELECT COUNT(*)
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE r.code = 'administrator' AND u.is_active = TRUE"
        );

        return (int) $stmt->fetchColumn();
    }
}
