<?php

declare(strict_types=1);

namespace App\Core\Support;

final class AppContext
{
    /** @var array<string, mixed> */
    private static array $services = [];

    public static function set(string $key, mixed $value): void
    {
        self::$services[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$services[$key] ?? $default;
    }
}
