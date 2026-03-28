<?php

declare(strict_types=1);

use App\Core\Auth\AuthService;
use App\Core\Security\Csrf;
use App\Core\Support\AppContext;
use App\Core\Support\Config;

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $base = dirname(__DIR__);

        return $path === '' ? $base : $base . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('app_context')) {
    function app_context(string $key, mixed $default = null): mixed
    {
        return AppContext::get($key, $default);
    }
}

if (!function_exists('auth')) {
    function auth(): AuthService
    {
        /** @var AuthService $auth */
        $auth = AppContext::get('auth');

        return $auth;
    }
}

if (!function_exists('e')) {
    function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url(string $path = '/'): string
    {
        $basePath = (string) AppContext::get('base_path', '');
        $normalizedPath = '/' . ltrim($path, '/');

        if ($basePath !== '' && $basePath !== '/') {
            return rtrim($basePath, '/') . $normalizedPath;
        }

        return $normalizedPath;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        $old = AppContext::get('old_input', []);

        return is_array($old) && array_key_exists($key, $old) ? $old[$key] : $default;
    }
}

if (!function_exists('error_for')) {
    function error_for(string $key): ?string
    {
        $errors = AppContext::get('errors', []);

        if (!is_array($errors) || !isset($errors[$key])) {
            return null;
        }

        return is_string($errors[$key]) ? $errors[$key] : null;
    }
}

if (!function_exists('flash_message')) {
    function flash_message(string $key): ?string
    {
        $flash = AppContext::get('flash', []);

        if (!is_array($flash) || !isset($flash[$key])) {
            return null;
        }

        return is_string($flash[$key]) ? $flash[$key] : null;
    }
}

if (!function_exists('invitation_status_label')) {
    function invitation_status_label(string $status): string
    {
        return match ($status) {
            'pending' => 'Σε εκκρεμότητα',
            'accepted' => 'Αποδεκτή',
            'expired' => 'Ληγμένη',
            'revoked' => 'Ακυρωμένη',
            default => 'Άγνωστη κατάσταση',
        };
    }
}

if (!function_exists('old_array')) {
    /** @return array<string, mixed> */
    function old_array(string $key): array
    {
        $old = AppContext::get('old_input', []);
        if (!is_array($old) || !isset($old[$key]) || !is_array($old[$key])) {
            return [];
        }

        return $old[$key];
    }
}

if (!function_exists('datetime_local_value')) {
    function datetime_local_value(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return '';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $raw) === 1) {
            return $raw;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $raw) === 1) {
            return substr($raw, 0, 16);
        }

        $appTimezone = new \DateTimeZone((string) config('app.timezone', 'Europe/Athens'));

        if (preg_match('/^\d{13}$/', $raw) === 1) {
            $raw = (string) intdiv((int) $raw, 1000);
        }

        try {
            if (preg_match('/^\d{10}$/', $raw) === 1) {
                $dateTime = (new \DateTimeImmutable('@' . $raw))->setTimezone($appTimezone);
            } else {
                $dateTime = (new \DateTimeImmutable($raw, $appTimezone))->setTimezone($appTimezone);
            }
        } catch (\Exception) {
            return '';
        }

        return $dateTime->format('Y-m-d\\TH:i');
    }
}