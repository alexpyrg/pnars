<?php

declare(strict_types=1);

namespace App\Modules\Lookup;

use PDO;

final class LookupRepository
{
    /** @var array<string, array<int, array<string, mixed>>> */
    private array $cache = [];

    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function options(string $domainCode): array
    {
        if (isset($this->cache[$domainCode])) {
            return $this->cache[$domainCode];
        }

        $stmt = $this->pdo->prepare(
            'SELECT lv.id, lv.code, lv.label_el, lv.sort_order
             FROM lookup_values lv
             JOIN lookup_domains ld ON ld.id = lv.domain_id
             WHERE ld.code = :domain_code AND lv.is_active = TRUE
             ORDER BY lv.sort_order ASC, lv.id ASC'
        );
        $stmt->execute([':domain_code' => $domainCode]);

        $this->cache[$domainCode] = $stmt->fetchAll() ?: [];

        return $this->cache[$domainCode];
    }

    public function idByCode(string $domainCode, string $code): ?int
    {
        $stmt = $this->pdo->prepare(
            'SELECT lv.id
             FROM lookup_values lv
             JOIN lookup_domains ld ON ld.id = lv.domain_id
             WHERE ld.code = :domain_code AND lv.code = :code
             LIMIT 1'
        );
        $stmt->execute([
            ':domain_code' => $domainCode,
            ':code' => $code,
        ]);

        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;
    }

    public function labelById(?int $id): ?string
    {
        if ($id === null) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT label_el FROM lookup_values WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $label = $stmt->fetchColumn();

        return is_string($label) ? $label : null;
    }

    public function codeById(?int $id): ?string
    {
        if ($id === null) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT code FROM lookup_values WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $code = $stmt->fetchColumn();

        return is_string($code) ? $code : null;
    }

    public function isValueInDomain(?int $id, string $domainCode): bool
    {
        if ($id === null) {
            return true;
        }

        $stmt = $this->pdo->prepare(
            'SELECT EXISTS(
                SELECT 1
                FROM lookup_values lv
                JOIN lookup_domains ld ON ld.id = lv.domain_id
                WHERE lv.id = :id
                  AND ld.code = :domain_code
                  AND lv.is_active = TRUE
            )'
        );
        $stmt->execute([
            ':id' => $id,
            ':domain_code' => $domainCode,
        ]);

        return (bool) $stmt->fetchColumn();
    }
}
