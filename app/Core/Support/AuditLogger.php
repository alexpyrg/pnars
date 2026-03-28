<?php

declare(strict_types=1);

namespace App\Core\Support;

use App\Core\Auth\AuthService;
use PDO;
use Throwable;

final class AuditLogger
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly AuthService $authService
    ) {
    }

    /** @param array<string, mixed>|null $beforeData */
    /** @param array<string, mixed>|null $afterData */
    public function log(
        string $actionType,
        string $entityType,
        ?string $entityId,
        string $summary,
        ?array $beforeData = null,
        ?array $afterData = null
    ): void {
        try {
            $actorId = $this->authService->id();
            $stmt = $this->pdo->prepare('INSERT INTO audit_logs (actor_user_id, action_type, entity_type, entity_id, summary, before_data, after_data, ip_address, user_agent, created_at) VALUES (:actor_user_id, :action_type, :entity_type, :entity_id, :summary, :before_data::jsonb, :after_data::jsonb, :ip_address, :user_agent, NOW())');
            $stmt->execute([
                ':actor_user_id' => $actorId,
                ':action_type' => $actionType,
                ':entity_type' => $entityType,
                ':entity_id' => $entityId,
                ':summary' => $summary,
                ':before_data' => $beforeData ? json_encode($beforeData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                ':after_data' => $afterData ? json_encode($afterData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);
        } catch (Throwable) {
            // Δεν διακόπτουμε τη ροή της εφαρμογής αν αποτύχει το audit logging.
        }
    }
}
