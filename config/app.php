<?php

declare(strict_types=1);

return [
    'name' => getenv('APP_NAME') ?: 'Σύστημα Τροχαίων Ατυχημάτων',
    'env' => getenv('APP_ENV') ?: 'production',
    'debug' => filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOL),
    'url' => getenv('APP_URL') ?: 'http://localhost',
    'timezone' => getenv('APP_TIMEZONE') ?: 'Europe/Athens',
    'session' => [
        'name' => getenv('SESSION_NAME') ?: 'ptixiaki_session',
        'secure' => filter_var(getenv('SESSION_SECURE') ?: false, FILTER_VALIDATE_BOOL),
        'http_only' => filter_var(getenv('SESSION_HTTP_ONLY') ?: true, FILTER_VALIDATE_BOOL),
        'same_site' => getenv('SESSION_SAME_SITE') ?: 'Lax',
    ],
    'uploads' => [
        'max_size_bytes' => ((int) (getenv('UPLOAD_MAX_SIZE_MB') ?: 10)) * 1024 * 1024,
        'allowed_mime' => array_map('trim', explode(',', getenv('UPLOAD_ALLOWED_MIME') ?: 'image/jpeg,image/png,image/webp,application/pdf')),
        'base_dir' => getenv('UPLOAD_BASE_DIR') ?: 'storage/uploads',
    ],
    'rate_limit' => [
        'login' => [
            'max_attempts' => (int) (getenv('RATE_LIMIT_LOGIN_MAX_ATTEMPTS') ?: 8),
            'window_seconds' => (int) (getenv('RATE_LIMIT_LOGIN_WINDOW_SECONDS') ?: 900),
            'lockout_seconds' => (int) (getenv('RATE_LIMIT_LOGIN_LOCKOUT_SECONDS') ?: 900),
        ],
        'invitation_accept' => [
            'max_attempts' => (int) (getenv('RATE_LIMIT_INVITATION_MAX_ATTEMPTS') ?: 10),
            'window_seconds' => (int) (getenv('RATE_LIMIT_INVITATION_WINDOW_SECONDS') ?: 900),
            'lockout_seconds' => (int) (getenv('RATE_LIMIT_INVITATION_LOCKOUT_SECONDS') ?: 900),
        ],
    ],
    'mail' => [
        'enabled' => filter_var(getenv('MAIL_ENABLED') ?: false, FILTER_VALIDATE_BOOL),
        'host' => getenv('MAIL_HOST') ?: '127.0.0.1',
        'port' => (int) (getenv('MAIL_PORT') ?: 1025),
        'encryption' => strtolower((string) (getenv('MAIL_ENCRYPTION') ?: 'none')),
        'username' => getenv('MAIL_USERNAME') ?: '',
        'password' => getenv('MAIL_PASSWORD') ?: '',
        'from_address' => getenv('MAIL_FROM_ADDRESS') ?: 'no-reply@traffic-accidents.local',
        'from_name' => getenv('MAIL_FROM_NAME') ?: 'Σύστημα Τροχαίων Ατυχημάτων',
        'helo' => getenv('MAIL_HELO') ?: 'localhost',
        'timeout' => (int) (getenv('MAIL_TIMEOUT') ?: 10),
    ],
];
