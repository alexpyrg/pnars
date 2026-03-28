<?php

declare(strict_types=1);

namespace App\Core\Support;

use RuntimeException;

final class View
{
    public function __construct(
        private readonly string $basePath,
        private readonly string $layout = 'layouts/app'
    ) {
    }

    /** @param array<string, mixed> $data */
    public function render(string $template, array $data = []): string
    {
        $viewPath = $this->resolvePath($template);

        if (!is_file($viewPath)) {
            throw new RuntimeException("View not found: {$template}");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        $layoutPath = $this->resolvePath($this->layout);
        if (!is_file($layoutPath)) {
            return (string) $content;
        }

        ob_start();
        require $layoutPath;

        return (string) ob_get_clean();
    }

    private function resolvePath(string $template): string
    {
        return rtrim($this->basePath, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $template) . '.php';
    }
}
