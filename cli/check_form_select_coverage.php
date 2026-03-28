<?php

declare(strict_types=1);

/**
 * Ελέγχει ότι:
 * 1) Όλα τα *_lookup_id (και make/model) πεδία των accidents/roads/vehicles υπάρχουν στις φόρμες ως select fields.
 * 2) Όλα τα CSV lookup domains ανά scope χρησιμοποιούνται από τα αντίστοιχα controllers.
 */

$root = dirname(__DIR__);
$schemaPath = $root . '/database/migrations/001_base_schema.sql';
$lookupMapPath = $root . '/config/lookup_import_map.php';

$formPaths = [
    'accidents' => $root . '/app/Views/accidents/_form.php',
    'roads' => $root . '/app/Views/roads/_form.php',
    'vehicles' => $root . '/app/Views/vehicles/_form.php',
];

$controllerPaths = [
    'accident' => $root . '/app/Modules/Accidents/AccidentController.php',
    'road' => $root . '/app/Modules/Roads/RoadController.php',
    'vehicle' => $root . '/app/Modules/Vehicles/VehicleController.php',
];

$errors = [];

$schemaSql = @file_get_contents($schemaPath);
if ($schemaSql === false) {
    fwrite(STDERR, "Αποτυχία ανάγνωσης schema: {$schemaPath}\n");
    exit(2);
}

foreach ($formPaths as $table => $formPath) {
    $tableColumns = extractSelectBackedColumnsFromSchema($schemaSql, $table);
    $formFields = extractSelectFieldNamesFromForm($formPath);

    $missing = array_values(array_diff($tableColumns, $formFields));
    if ($missing !== []) {
        $errors[] = sprintf(
            'Λείπουν select fields από φόρμα %s: %s',
            $table,
            implode(', ', $missing)
        );
    }
}

$mapping = require $lookupMapPath;
if (!is_array($mapping)) {
    fwrite(STDERR, "Μη έγκυρο lookup map: {$lookupMapPath}\n");
    exit(2);
}

$csvDomainsByScope = [
    'accident' => [],
    'road' => [],
    'vehicle' => [],
];

foreach ($mapping as $item) {
    if (!is_array($item)) {
        continue;
    }

    if (($item['target'] ?? null) !== 'lookup') {
        continue;
    }

    $scope = (string) ($item['source_scope'] ?? '');
    $domain = (string) ($item['domain_code'] ?? '');

    if (!isset($csvDomainsByScope[$scope]) || $domain === '') {
        continue;
    }

    $csvDomainsByScope[$scope][] = $domain;
}

foreach ($csvDomainsByScope as $scope => &$domains) {
    $domains = array_values(array_unique($domains));
    sort($domains);
}
unset($domains);

foreach ($controllerPaths as $scope => $controllerPath) {
    $usedDomains = extractLookupDomainsFromController($controllerPath);
    $missingDomains = array_values(array_diff($csvDomainsByScope[$scope], $usedDomains));

    if ($missingDomains !== []) {
        $errors[] = sprintf(
            'CSV domains χωρίς χρήση στο controller %s: %s',
            $scope,
            implode(', ', $missingDomains)
        );
    }
}

if ($errors !== []) {
    echo "ΑΠΟΤΥΧΙΑ ΕΛΕΓΧΟΥ\n";
    foreach ($errors as $error) {
        echo '- ', $error, "\n";
    }
    exit(1);
}

echo "OK: Οι φόρμες καλύπτουν όλα τα select-backed πεδία των schemas και όλα τα CSV lookup domains χρησιμοποιούνται.\n";
exit(0);

/**
 * @return array<int, string>
 */
function extractSelectBackedColumnsFromSchema(string $schemaSql, string $table): array
{
    if (!preg_match('/CREATE TABLE IF NOT EXISTS ' . preg_quote($table, '/') . '\s*\((.*?)\n\);/s', $schemaSql, $matches)) {
        return [];
    }

    $columns = [];
    $lines = preg_split('/\R/', (string) $matches[1]) ?: [];

    foreach ($lines as $line) {
        if (!preg_match('/^\s*([a-z_]+)\s+/i', $line, $colMatch)) {
            continue;
        }

        $name = (string) $colMatch[1];

        if (str_ends_with($name, '_lookup_id') || in_array($name, ['vehicle_make_id', 'vehicle_model_id'], true)) {
            $columns[] = $name;
        }
    }

    $columns = array_values(array_unique($columns));
    sort($columns);

    return $columns;
}

/**
 * @return array<int, string>
 */
function extractSelectFieldNamesFromForm(string $formPath): array
{
    $content = @file_get_contents($formPath);
    if ($content === false) {
        return [];
    }

    $names = [];

    if (preg_match_all('/<select[^>]*name="([^"]+)"/i', $content, $matches)) {
        foreach ($matches[1] as $name) {
            $names[] = (string) $name;
        }
    }

    // Καλύπτει helper style: $select('field_name', ...)
    if (preg_match_all('/\$select\(\s*\'([^\']+)\'\s*,/i', $content, $matches)) {
        foreach ($matches[1] as $name) {
            $names[] = (string) $name;
        }
    }

    $names = array_values(array_unique($names));
    sort($names);

    return $names;
}

/**
 * @return array<int, string>
 */
function extractLookupDomainsFromController(string $controllerPath): array
{
    $content = @file_get_contents($controllerPath);
    if ($content === false) {
        return [];
    }

    $domains = [];

    if (preg_match_all('/options\(\'([^\']+)\'\)/', $content, $matches)) {
        foreach ($matches[1] as $domain) {
            $domains[] = (string) $domain;
        }
    }

    $domains = array_values(array_unique($domains));
    sort($domains);

    return $domains;
}