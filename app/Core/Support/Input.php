<?php

declare(strict_types=1);

namespace App\Core\Support;

final class Input
{
    public static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    public static function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric((string) $value)) {
            return null;
        }

        return (int) $value;
    }

    public static function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric((string) $value)) {
            return null;
        }

        return (float) $value;
    }

    public static function boolInt(mixed $value): int
    {
        return ((string) $value === '1' || $value === 1 || $value === true) ? 1 : 0;
    }
}
