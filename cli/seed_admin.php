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

$email = getenv('DEFAULT_ADMIN_EMAIL') ?: 'admin@example.com';
$password = getenv('DEFAULT_ADMIN_PASSWORD') ?: 'ChangeMe123!';
$fullName = getenv('DEFAULT_ADMIN_NAME') ?: 'Διαχειριστής Συστήματος';
$resetPassword = in_array('--reset-password', $argv, true);

$roleStmt = $pdo->prepare('SELECT id FROM roles WHERE code = :code LIMIT 1');
$roleStmt->execute([':code' => 'administrator']);
$roleId = $roleStmt->fetchColumn();

if (!$roleId) {
    throw new RuntimeException('Δεν βρέθηκε ο ρόλος administrator. Εκτελέστε πρώτα migrations.');
}

$userStmt = $pdo->prepare('SELECT id FROM users WHERE lower(email) = lower(:email) LIMIT 1');
$userStmt->execute([':email' => $email]);
$userId = $userStmt->fetchColumn();

if (!$userId) {
    $insert = $pdo->prepare(
        'INSERT INTO users (email, password_hash, full_name, role_id, is_active, created_at, updated_at)
         VALUES (:email, :password_hash, :full_name, :role_id, TRUE, NOW(), NOW())'
    );
    $insert->execute([
        ':email' => $email,
        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ':full_name' => $fullName,
        ':role_id' => $roleId,
    ]);

    echo "[ok] Δημιουργήθηκε διαχειριστής: {$email}\n";
    exit(0);
}

if ($resetPassword) {
    $update = $pdo->prepare('UPDATE users SET password_hash = :password_hash, updated_at = NOW() WHERE id = :id');
    $update->execute([
        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ':id' => $userId,
    ]);

    echo "[ok] Ενημερώθηκε ο κωδικός του διαχειριστή: {$email}\n";
    exit(0);
}

echo "[skip] Ο διαχειριστής υπάρχει ήδη: {$email}\n";
