<?php

declare(strict_types=1);

$php = PHP_BINARY;
$root = dirname(__DIR__);

$commands = [
    [$php, $root . '/cli/migrate.php'],
    [$php, $root . '/cli/import_lookups.php'],
    [$php, $root . '/cli/seed_admin.php'],
];

foreach ($commands as $parts) {
    $command = implode(' ', array_map('escapeshellarg', $parts));
    passthru($command, $code);

    if ($code !== 0) {
        exit($code);
    }
}

echo "Ολοκληρώθηκε η αρχικοποίηση της εφαρμογής.\n";
