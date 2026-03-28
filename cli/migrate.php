<?php

declare(strict_types=1);

use App\Core\Database\Connection;
use App\Core\Support\Config;
use App\Core\Support\Env;

require __DIR__ . '/../bootstrap/autoload.php';
require __DIR__ . '/../bootstrap/helpers.php';

Env::load(base_path('.env'));
Config::load('database', require base_path('config/database.php'));

$pdo = Connection::make(Config::all('database'));
$pdo->exec('SET TIME ZONE "Europe/Athens"');

$fresh = in_array('--fresh', $argv, true);

if ($fresh) {
    $pdo->exec('DROP SCHEMA IF EXISTS public CASCADE; CREATE SCHEMA public;');
    echo "[ok] Έγινε καθαρισμός schema public.\n";
}

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        id BIGSERIAL PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        checksum CHAR(64) NOT NULL,
        applied_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
    )'
);

$applied = [];
$stmt = $pdo->query('SELECT migration, checksum FROM schema_migrations');
foreach ($stmt->fetchAll() as $row) {
    $applied[$row['migration']] = $row['checksum'];
}

$migrationFiles = glob(base_path('database/migrations/*.sql')) ?: [];
sort($migrationFiles, SORT_STRING);

foreach ($migrationFiles as $path) {
    $name = basename($path);
    $sql = file_get_contents($path);
    if ($sql === false) {
        throw new RuntimeException("Αδυναμία ανάγνωσης migration: {$name}");
    }

    $checksum = hash('sha256', $sql);
    if (isset($applied[$name]) && $applied[$name] === $checksum) {
        echo "[skip] {$name}\n";
        continue;
    }

    if (isset($applied[$name]) && $applied[$name] !== $checksum) {
        throw new RuntimeException("Το migration {$name} έχει τροποποιηθεί μετά την εφαρμογή του.");
    }

    $pdo->exec($sql);

    $insert = $pdo->prepare('INSERT INTO schema_migrations (migration, checksum) VALUES (:migration, :checksum)');
    $insert->execute([':migration' => $name, ':checksum' => $checksum]);

    echo "[ok] {$name}\n";
}

echo "Ολοκληρώθηκε η εφαρμογή migrations.\n";
