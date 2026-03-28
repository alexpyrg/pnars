<?php

declare(strict_types=1);

namespace App\Core\Support;

final class CsvFileReader
{
    /** @return array<int, array<int, string|null>> */
    public function readRows(string $absolutePath): array
    {
        $content = file_get_contents($absolutePath);
        if ($content === false) {
            throw new \RuntimeException("Αδυναμία ανάγνωσης CSV: {$absolutePath}");
        }

        $normalized = $this->normalizeEncoding($content);
        $lines = preg_split('/\r\n|\n|\r/', $normalized) ?: [];

        $rows = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $values = str_getcsv($line);
            if ($values === [null] || $values === false) {
                continue;
            }

            $rows[] = array_map(static fn ($v) => $v !== null ? trim((string) $v) : null, $values);
        }

        return $rows;
    }

    private function normalizeEncoding(string $content): string
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;

        if (mb_check_encoding($content, 'UTF-8')) {
            return $content;
        }

        $encodings = ['Windows-1253', 'ISO-8859-7', 'ISO-8859-1'];
        foreach ($encodings as $encoding) {
            $converted = @mb_convert_encoding($content, 'UTF-8', $encoding);
            if (is_string($converted) && mb_check_encoding($converted, 'UTF-8')) {
                return $converted;
            }
        }

        return $content;
    }
}
