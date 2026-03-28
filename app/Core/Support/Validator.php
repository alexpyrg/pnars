<?php

declare(strict_types=1);

namespace App\Core\Support;

final class Validator
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, array<int, string>> $rules
     * @return array<string, string>
     */
    public function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                if ($rule === 'required' && ($value === null || $value === '')) {
                    $errors[$field] = 'Το πεδίο είναι υποχρεωτικό.';
                    break;
                }

                if ($rule === 'email' && $value !== null && $value !== '' && !filter_var((string) $value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = 'Δώστε έγκυρη διεύθυνση email.';
                    break;
                }

                if (str_starts_with($rule, 'min:') && $value !== null) {
                    $min = (int) substr($rule, 4);
                    if (mb_strlen((string) $value) < $min) {
                        $errors[$field] = "Ελάχιστο μήκος {$min} χαρακτήρες.";
                        break;
                    }
                }
            }
        }

        return $errors;
    }
}
