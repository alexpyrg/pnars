<?php

declare(strict_types=1);

namespace App\Core\Database;

use PDO;

final class Connection
{
    public static function make(array $config): PDO
    {
        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s;options=--search_path=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['schema']
        );

        $pdo = new PDO(
            $dsn,
            $config['username'],
            $config['password'],
            $config['options'] ?? []
        );

        $pdo->exec("SET client_encoding TO 'UTF8'");

        return $pdo;
    }
}