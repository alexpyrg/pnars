<?php

declare(strict_types=1);

namespace App\Core\Http;

use App\Core\Auth\AuthService;
use App\Core\Support\AppContext;
use App\Core\Support\AuditLogger;
use PDO;

abstract class Controller
{
    protected function auth(): AuthService
    {
        /** @var AuthService $auth */
        $auth = AppContext::get('auth');

        return $auth;
    }

    protected function db(): PDO
    {
        /** @var PDO $pdo */
        $pdo = AppContext::get('pdo');

        return $pdo;
    }

    protected function audit(): AuditLogger
    {
        /** @var AuditLogger $audit */
        $audit = AppContext::get('audit');

        return $audit;
    }
}
