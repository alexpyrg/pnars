<?php

declare(strict_types=1);

namespace App\Core\Support;

use InvalidArgumentException;

final class Config
{
    /** @var array<string, mixed> */
    private static array $items = [];

    public static function load(string $name, array $config): void
    {
        self::$items[$name] = $config;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $root = array_shift($segments);

        if ($root === null || !array_key_exists($root, self::$items)) {
            return $default;
        }

        $value = self::$items[$root];

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    /** @return array<string, mixed> */
    public static function all(string $name): array
    {
        if (!isset(self::$items[$name]) || !is_array(self::$items[$name])) {
            throw new InvalidArgumentException("Missing config group: {$name}");
        }

        return self::$items[$name];
    }
}
