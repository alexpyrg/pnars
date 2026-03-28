<?php

declare(strict_types=1);

namespace App\Modules\Admin;

use PDO;

final class InvitationRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function markExpiredPending(): int
    {
        $stmt = $this->pdo->prepare(
            'UPDATE invitations
             SET status = :expired_status, updated_at = NOW()
             WHERE status = :pending_status
               AND accepted_at IS NULL
               AND expires_at < NOW()'
        );
        $stmt->execute([
            ':expired_status' => 'expired',
            ':pending_status' => 'pending',
        ]);

        return $stmt->rowCount();
    }

    /** @return array<int, array<string, mixed>> */
    public function latest(int $limit = 50): array
    {
        $this->markExpiredPending();

        $stmt = $this->pdo->prepare(
            'SELECT i.id, i.email, i.status, i.expires_at, i.accepted_at, i.created_at, r.label_el AS role_label
             FROM invitations i
             JOIN roles r ON r.id = i.role_id
             ORDER BY i.created_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }

    public function create(string $email, string $roleId, string $tokenHash, string $invitedBy, string $expiresAt): string
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO invitations (email, role_id, token_hash, status, expires_at, invited_by, created_at, updated_at)
             VALUES (:email, :role_id, :token_hash, :status, :expires_at, :invited_by, NOW(), NOW())
             RETURNING id'
        );
        $stmt->execute([
            ':email' => $email,
            ':role_id' => $roleId,
            ':token_hash' => $tokenHash,
            ':status' => 'pending',
            ':expires_at' => $expiresAt,
            ':invited_by' => $invitedBy,
        ]);

        return (string) $stmt->fetchColumn();
    }

    public function roleExists(string $roleId): bool
    {
        $stmt = $this->pdo->prepare('SELECT EXISTS(SELECT 1 FROM roles WHERE id = :id)');
        $stmt->execute([':id' => $roleId]);

        return (bool) $stmt->fetchColumn();
    }

    public function hasActiveUserWithEmail(string $email): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT EXISTS(
                SELECT 1
                FROM users
                WHERE lower(email) = lower(:email) AND is_active = TRUE
            )'
        );
        $stmt->execute([':email' => $email]);

        return (bool) $stmt->fetchColumn();
    }

    /** @return array<int, array<string, mixed>> */
    public function roleOptions(): array
    {
        $stmt = $this->pdo->query('SELECT id, code, label_el FROM roles ORDER BY label_el ASC');

        return $stmt->fetchAll() ?: [];
    }

    /** @return array<string, mixed>|null */
    public function findById(string $id): ?array
    {
        $this->markExpiredPending();

        $stmt = $this->pdo->prepare(
            'SELECT i.id, i.email, i.status, i.expires_at, i.accepted_at, i.role_id, r.label_el AS role_label
             FROM invitations i
             JOIN roles r ON r.id = i.role_id
             WHERE i.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function cancelPending(string $id): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE invitations
             SET status = :revoked_status, updated_at = NOW()
             WHERE id = :id
               AND status = :pending_status
               AND accepted_at IS NULL
               AND expires_at >= NOW()'
        );
        $stmt->execute([
            ':id' => $id,
            ':revoked_status' => 'revoked',
            ':pending_status' => 'pending',
        ]);

        return $stmt->rowCount() === 1;
    }

    public function findPendingByTokenHash(string $tokenHash): ?array
    {
        $this->pdo->prepare(
            'UPDATE invitations
             SET status = :expired_status, updated_at = NOW()
             WHERE token_hash = :token_hash
               AND status = :pending_status
               AND accepted_at IS NULL
               AND expires_at < NOW()'
        )->execute([
            ':expired_status' => 'expired',
            ':pending_status' => 'pending',
            ':token_hash' => $tokenHash,
        ]);

        $stmt = $this->pdo->prepare(
            'SELECT i.*, r.code AS role_code, r.label_el AS role_label
             FROM invitations i
             JOIN roles r ON r.id = i.role_id
             WHERE i.token_hash = :token_hash
               AND i.status = :status
               AND i.expires_at >= NOW()
             LIMIT 1'
        );
        $stmt->execute([
            ':token_hash' => $tokenHash,
            ':status' => 'pending',
        ]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findPendingByTokenHashForUpdate(string $tokenHash): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT i.*, r.code AS role_code, r.label_el AS role_label
             FROM invitations i
             JOIN roles r ON r.id = i.role_id
             WHERE i.token_hash = :token_hash
               AND i.status = :status
               AND i.expires_at >= NOW()
             FOR UPDATE
             LIMIT 1'
        );
        $stmt->execute([
            ':token_hash' => $tokenHash,
            ':status' => 'pending',
        ]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function markAccepted(string $invitationId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE invitations
             SET status = :status, accepted_at = NOW(), updated_at = NOW()
             WHERE id = :id AND status = :pending_status'
        );
        $stmt->execute([
            ':status' => 'accepted',
            ':pending_status' => 'pending',
            ':id' => $invitationId,
        ]);

        return $stmt->rowCount() === 1;
    }
}