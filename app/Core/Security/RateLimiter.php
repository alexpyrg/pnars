<?php

declare(strict_types=1);

namespace App\Core\Security;

use PDO;
use PDOException;

final class RateLimiter
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return array{allowed: bool, retry_after: int}
     */
    public function consume(
        string $bucket,
        string $identifier,
        int $maxAttempts,
        int $windowSeconds,
        int $lockoutSeconds
    ): array {
        $key = $this->buildKey($bucket, $identifier);
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare(
                'SELECT key, attempt_count, first_attempt_at, blocked_until
                 FROM rate_limit_buckets
                 WHERE key = :key
                 FOR UPDATE'
            );
            $stmt->execute([':key' => $key]);
            $row = $stmt->fetch();

            if (!$row) {
                $this->insertFresh($key, $now);
                $this->pdo->commit();

                return ['allowed' => true, 'retry_after' => 0];
            }

            $blockedUntil = $row['blocked_until'] !== null ? new \DateTimeImmutable((string) $row['blocked_until']) : null;
            if ($blockedUntil !== null && $blockedUntil > $now) {
                $retryAfter = max(1, $blockedUntil->getTimestamp() - $now->getTimestamp());
                $this->touch($key);
                $this->pdo->commit();

                return ['allowed' => false, 'retry_after' => $retryAfter];
            }

            $firstAttemptAt = new \DateTimeImmutable((string) $row['first_attempt_at']);
            $attemptCount = (int) $row['attempt_count'];

            if (($now->getTimestamp() - $firstAttemptAt->getTimestamp()) >= $windowSeconds) {
                $this->resetWindow($key, $now);
                $this->pdo->commit();

                return ['allowed' => true, 'retry_after' => 0];
            }

            $attemptCount++;
            if ($attemptCount > $maxAttempts) {
                $newBlockedUntil = $now->modify('+' . $lockoutSeconds . ' seconds');
                $retryAfter = max(1, $newBlockedUntil->getTimestamp() - $now->getTimestamp());

                $this->pdo->prepare(
                    'UPDATE rate_limit_buckets
                     SET attempt_count = :attempt_count,
                         blocked_until = :blocked_until,
                         updated_at = NOW()
                     WHERE key = :key'
                )->execute([
                    ':attempt_count' => $attemptCount,
                    ':blocked_until' => $newBlockedUntil->format('Y-m-d H:i:sP'),
                    ':key' => $key,
                ]);

                $this->pdo->commit();

                return ['allowed' => false, 'retry_after' => $retryAfter];
            }

            $this->pdo->prepare(
                'UPDATE rate_limit_buckets
                 SET attempt_count = :attempt_count,
                     updated_at = NOW(),
                     blocked_until = NULL
                 WHERE key = :key'
            )->execute([
                ':attempt_count' => $attemptCount,
                ':key' => $key,
            ]);

            $this->pdo->commit();

            return ['allowed' => true, 'retry_after' => 0];
        } catch (PDOException) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            // Fail-open for availability if limiter storage is temporarily unavailable.
            return ['allowed' => true, 'retry_after' => 0];
        }
    }

    public function clear(string $bucket, string $identifier): void
    {
        $key = $this->buildKey($bucket, $identifier);

        try {
            $stmt = $this->pdo->prepare('DELETE FROM rate_limit_buckets WHERE key = :key');
            $stmt->execute([':key' => $key]);
        } catch (PDOException) {
            // Intentionally ignored; auth flow should not fail because of limiter cleanup.
        }
    }

    private function insertFresh(string $key, \DateTimeImmutable $now): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO rate_limit_buckets (key, attempt_count, first_attempt_at, blocked_until, updated_at)
             VALUES (:key, 1, :first_attempt_at, NULL, NOW())'
        );
        $stmt->execute([
            ':key' => $key,
            ':first_attempt_at' => $now->format('Y-m-d H:i:sP'),
        ]);
    }

    private function resetWindow(string $key, \DateTimeImmutable $now): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE rate_limit_buckets
             SET attempt_count = 1,
                 first_attempt_at = :first_attempt_at,
                 blocked_until = NULL,
                 updated_at = NOW()
             WHERE key = :key'
        );
        $stmt->execute([
            ':first_attempt_at' => $now->format('Y-m-d H:i:sP'),
            ':key' => $key,
        ]);
    }

    private function touch(string $key): void
    {
        $stmt = $this->pdo->prepare('UPDATE rate_limit_buckets SET updated_at = NOW() WHERE key = :key');
        $stmt->execute([':key' => $key]);
    }

    private function buildKey(string $bucket, string $identifier): string
    {
        $normalizedBucket = strtolower(trim($bucket));
        $normalizedIdentifier = strtolower(trim($identifier));

        return hash('sha256', $normalizedBucket . '|' . $normalizedIdentifier);
    }
}
