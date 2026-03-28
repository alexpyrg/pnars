<?php

declare(strict_types=1);

use App\Core\Database\Connection;
use App\Core\Support\Config;
use App\Core\Support\CsvFileReader;
use App\Core\Support\Env;

require __DIR__ . '/../bootstrap/autoload.php';
require __DIR__ . '/../bootstrap/helpers.php';

Env::load(base_path('.env'));
Config::load('database', require base_path('config/database.php'));

$pdo = Connection::make(Config::all('database'));
$pdo->exec('SET TIME ZONE "Europe/Athens"');

$mapping = require base_path('config/lookup_import_map.php');
$reader = new CsvFileReader();

$fresh = in_array('--fresh', $argv, true);
$actorEmail = null;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--actor-email=')) {
        $actorEmail = substr($arg, strlen('--actor-email='));
    }
}

$checksumBuffer = json_encode($mapping, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
foreach ($mapping as $item) {
    $fullPath = base_path((string) $item['path']);
    if (!is_file($fullPath)) {
        throw new RuntimeException("Λείπει το CSV αρχείο: {$fullPath}");
    }

    $content = file_get_contents($fullPath);
    if ($content === false) {
        throw new RuntimeException("Αδυναμία ανάγνωσης CSV: {$fullPath}");
    }

    $checksumBuffer .= $content;
}
$checksum = hash('sha256', $checksumBuffer);

$importedBy = null;
if (is_string($actorEmail) && $actorEmail !== '') {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE lower(email) = lower(:email) LIMIT 1');
    $stmt->execute([':email' => $actorEmail]);
    $importedBy = $stmt->fetchColumn() ?: null;
}

$startRun = $pdo->prepare('INSERT INTO lookup_import_runs (source_path, checksum, status, imported_by, notes) VALUES (:source_path, :checksum, :status, :imported_by, :notes) RETURNING id');
$startRun->execute([
    ':source_path' => 'accident_csv_renamed, road_csv_renamed, vehicle_csv_renamed',
    ':checksum' => $checksum,
    ':status' => 'running',
    ':imported_by' => $importedBy,
    ':notes' => $fresh ? 'fresh=true' : null,
]);
$runId = (int) $startRun->fetchColumn();

try {
    $pdo->beginTransaction();

    if ($fresh) {
        $pdo->exec(
            "DELETE FROM lookup_values
             WHERE domain_id IN (
                 SELECT id FROM lookup_domains WHERE source_scope IN ('accident', 'road', 'vehicle')
             )"
        );
        $pdo->exec("DELETE FROM lookup_domains WHERE source_scope IN ('accident', 'road', 'vehicle')");
        $pdo->exec('TRUNCATE TABLE vehicle_models, vehicle_manufacturers RESTART IDENTITY CASCADE');
        echo "[ok] Καθαρίστηκαν lookup domains για accident/road/vehicle και πίνακες οχημάτων.\n";
    }

    foreach ($mapping as $item) {
        $target = (string) ($item['target'] ?? 'lookup');
        $path = base_path((string) $item['path']);

        if ($target === 'lookup') {
            importLookupFile($pdo, $reader, $item, $path);
            echo '[ok] ' . $item['path'] . "\n";
            continue;
        }

        if ($target === 'vehicle_manufacturers') {
            importVehicleManufacturers($pdo, $reader, $item, $path);
            echo '[ok] ' . $item['path'] . "\n";
            continue;
        }

        if ($target === 'vehicle_models') {
            importVehicleModels($pdo, $reader, $item, $path);
            echo '[ok] ' . $item['path'] . "\n";
            continue;
        }

        throw new RuntimeException("Μη υποστηριζόμενο target: {$target}");
    }

    $pdo->commit();

    $finish = $pdo->prepare('UPDATE lookup_import_runs SET status = :status, notes = COALESCE(notes, :notes) WHERE id = :id');
    $finish->execute([
        ':status' => 'completed',
        ':notes' => 'Ολοκληρώθηκε επιτυχώς',
        ':id' => $runId,
    ]);

    echo "Ολοκληρώθηκε η εισαγωγή lookup δεδομένων.\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $fail = $pdo->prepare('UPDATE lookup_import_runs SET status = :status, notes = :notes WHERE id = :id');
    $fail->execute([
        ':status' => 'failed',
        ':notes' => mb_substr($e->getMessage(), 0, 2000),
        ':id' => $runId,
    ]);

    throw $e;
}

function importLookupFile(PDO $pdo, CsvFileReader $reader, array $item, string $path): void
{
    $rows = $reader->readRows($path);
    if ($rows === []) {
        return;
    }

    $hasHeader = (bool) ($item['has_header'] ?? true);
    $header = [];

    if ($hasHeader) {
        $header = array_shift($rows) ?? [];
        if ($header === []) {
            throw new RuntimeException("Λείπει header στη δομή CSV: {$path}");
        }
    }

    $domainId = resolveDomain(
        $pdo,
        (string) $item['domain_code'],
        (string) $item['domain_label_el'],
        (string) $item['source_scope']
    );

    $sortFallback = 0;

    foreach ($rows as $row) {
        $sortFallback++;

        $code = extractField($row, $header, (string) $item['code_column']);
        $label = extractField($row, $header, (string) $item['label_column']);

        if ($code === null || $label === null || trim($label) === '') {
            continue;
        }

        $normalizedLabel = normalizeLabel($label);
        if ($normalizedLabel === '') {
            continue;
        }

        $sortOrder = extractField($row, $header, $item['sort_column'] ?? null);
        $sortOrderNumeric = is_numeric((string) $sortOrder) ? (int) $sortOrder : $sortFallback;

        $metadata = [];
        foreach (($item['meta_columns'] ?? []) as $metaColumn) {
            $metaValue = extractField($row, $header, (string) $metaColumn);
            if ($metaValue !== null && $metaValue !== '' && strtoupper($metaValue) !== 'NULL') {
                $metadata[$metaColumn] = $metaValue;
            }
        }

        $stmt = $pdo->prepare(
            'INSERT INTO lookup_values (domain_id, code, label_el, sort_order, metadata)
             VALUES (:domain_id, :code, :label_el, :sort_order, :metadata::jsonb)
             ON CONFLICT (domain_id, code) DO UPDATE
             SET label_el = EXCLUDED.label_el,
                 sort_order = EXCLUDED.sort_order,
                 metadata = EXCLUDED.metadata,
                 is_active = TRUE,
                 updated_at = NOW()'
        );

        $stmt->execute([
            ':domain_id' => $domainId,
            ':code' => trim($code),
            ':label_el' => $normalizedLabel,
            ':sort_order' => $sortOrderNumeric,
            ':metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }
}

function importVehicleManufacturers(PDO $pdo, CsvFileReader $reader, array $item, string $path): void
{
    $rows = $reader->readRows($path);
    if ($rows === []) {
        return;
    }

    $header = (bool) ($item['has_header'] ?? true) ? array_shift($rows) : [];

    foreach ($rows as $row) {
        $externalCode = extractField($row, $header, (string) $item['code_column']);
        $name = extractField($row, $header, (string) $item['label_column']);

        if ($externalCode === null || !is_numeric($externalCode) || $name === null || trim($name) === '') {
            continue;
        }

        $normalizedName = normalizeLabel($name);
        if ($normalizedName === '') {
            continue;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO vehicle_manufacturers (external_code, name)
             VALUES (:external_code, :name)
             ON CONFLICT (external_code) DO UPDATE
             SET name = EXCLUDED.name,
                 is_active = TRUE,
                 updated_at = NOW()'
        );
        $stmt->execute([
            ':external_code' => (int) $externalCode,
            ':name' => $normalizedName,
        ]);
    }
}

function importVehicleModels(PDO $pdo, CsvFileReader $reader, array $item, string $path): void
{
    $rows = $reader->readRows($path);
    if ($rows === []) {
        return;
    }

    foreach ($rows as $row) {
        $externalCode = $row[(int) $item['code_index']] ?? null;
        $manufacturerExternalCode = $row[(int) $item['manufacturer_code_index']] ?? null;
        $name = $row[(int) $item['label_index']] ?? null;

        if (!is_numeric((string) $externalCode) || !is_numeric((string) $manufacturerExternalCode) || trim((string) $name) === '') {
            continue;
        }

        $normalizedName = normalizeLabel((string) $name);
        if ($normalizedName === '') {
            continue;
        }

        $manufacturerIdStmt = $pdo->prepare('SELECT id FROM vehicle_manufacturers WHERE external_code = :code LIMIT 1');
        $manufacturerIdStmt->execute([':code' => (int) $manufacturerExternalCode]);
        $manufacturerId = $manufacturerIdStmt->fetchColumn();

        if (!$manufacturerId) {
            throw new RuntimeException("Λείπει manufacturer για model {$externalCode} (manufacturer code {$manufacturerExternalCode}).");
        }

        $stmt = $pdo->prepare(
            'INSERT INTO vehicle_models (external_code, manufacturer_id, name)
             VALUES (:external_code, :manufacturer_id, :name)
             ON CONFLICT (external_code) DO UPDATE
             SET manufacturer_id = EXCLUDED.manufacturer_id,
                 name = EXCLUDED.name,
                 is_active = TRUE,
                 updated_at = NOW()'
        );
        $stmt->execute([
            ':external_code' => (int) $externalCode,
            ':manufacturer_id' => (int) $manufacturerId,
            ':name' => $normalizedName,
        ]);
    }
}

function resolveDomain(PDO $pdo, string $domainCode, string $domainLabel, string $sourceScope): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO lookup_domains (code, label_el, source_scope)
         VALUES (:code, :label_el, :source_scope)
         ON CONFLICT (code) DO UPDATE
         SET label_el = EXCLUDED.label_el,
             source_scope = EXCLUDED.source_scope,
             updated_at = NOW()
         RETURNING id'
    );

    $stmt->execute([
        ':code' => $domainCode,
        ':label_el' => $domainLabel,
        ':source_scope' => $sourceScope,
    ]);

    return (int) $stmt->fetchColumn();
}

function extractField(array $row, array $header, ?string $column): ?string
{
    if ($column === null || $column === '') {
        return null;
    }

    $index = array_search($column, $header, true);
    if ($index === false) {
        return null;
    }

    return isset($row[$index]) ? trim((string) $row[$index]) : null;
}

function normalizeLabel(string $label): string
{
    $label = trim($label);
    if (strtoupper($label) === 'NULL') {
        return '';
    }

    return $label;
}







