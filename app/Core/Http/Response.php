<?php

declare(strict_types=1);

namespace App\Core\Http;

use App\Core\Support\View;

final class Response
{
    public function __construct(private readonly View $view)
    {
    }

    /** @param array<string, mixed> $data */
    public function view(string $template, array $data = [], int $status = 200): void
    {
        http_response_code($status);
        echo $this->view->render($template, $data);
    }

    public function redirect(string $url, int $status = 302): void
    {
        header('Location: ' . $url, true, $status);
        exit;
    }

    /** @param array<string, mixed> $payload */
    public function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
