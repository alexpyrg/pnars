<?php

declare(strict_types=1);

namespace App\Core\Support;

final class Flash
{
    public static function set(string $key, string $message): void
    {
        $_SESSION['_flash'][$key] = $message;
    }

    public static function get(string $key): ?string
    {
        if (!isset($_SESSION['_flash'][$key])) {
            return null;
        }

        $message = $_SESSION['_flash'][$key];
        unset($_SESSION['_flash'][$key]);

        return is_string($message) ? $message : null;
    }

    public static function keepInput(array $input): void
    {
        $_SESSION['_old_input'] = $input;
    }

    /** @return array<string, mixed> */
    public static function oldInput(): array
    {
        $input = $_SESSION['_old_input'] ?? [];
        unset($_SESSION['_old_input']);

        return is_array($input) ? $input : [];
    }

    /** @param array<string, string> $errors */
    public static function setErrors(array $errors): void
    {
        $_SESSION['_errors'] = $errors;
    }

    /** @return array<string, string> */
    public static function errors(): array
    {
        $errors = $_SESSION['_errors'] ?? [];
        unset($_SESSION['_errors']);

        return is_array($errors) ? $errors : [];
    }
}
