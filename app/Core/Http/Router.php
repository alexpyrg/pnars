<?php

declare(strict_types=1);

namespace App\Core\Http;

use App\Core\Support\AppContext;
use RuntimeException;

final class Router
{
    /** @var array<string, array<int, array{pattern: string, handler: mixed, middleware: array<int, string>}>> */
    private array $routes = [];

    /** @var array<string, callable> */
    private array $middleware = [];

    public function get(string $pattern, mixed $handler, array $middleware = []): void
    {
        $this->add('GET', $pattern, $handler, $middleware);
    }

    public function post(string $pattern, mixed $handler, array $middleware = []): void
    {
        $this->add('POST', $pattern, $handler, $middleware);
    }

    public function put(string $pattern, mixed $handler, array $middleware = []): void
    {
        $this->add('PUT', $pattern, $handler, $middleware);
    }

    public function delete(string $pattern, mixed $handler, array $middleware = []): void
    {
        $this->add('DELETE', $pattern, $handler, $middleware);
    }

    public function add(string $method, string $pattern, mixed $handler, array $middleware = []): void
    {
        $this->routes[strtoupper($method)][] = [
            'pattern' => $this->normalizePattern($pattern),
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function registerMiddleware(string $name, callable $callback): void
    {
        $this->middleware[$name] = $callback;
    }

    public function dispatch(Request $request, Response $response): void
    {
        $path = $this->normalizeRequestPath($request->path());
        $method = strtoupper($request->method());
        $routes = $this->routes[$method] ?? [];

        foreach ($routes as $route) {
            $params = $this->match($route['pattern'], $path);
            if ($params === null) {
                continue;
            }

            $request->setRouteParams($params);

            foreach ($route['middleware'] as $mw) {
                $continue = $this->runMiddleware($mw, $request, $response);
                if ($continue === false) {
                    return;
                }
            }

            $this->invokeHandler($route['handler'], $request, $response);

            return;
        }

        $response->view('errors/404', ['title' => 'Η σελίδα δεν βρέθηκε'], 404);
    }

    private function normalizePattern(string $pattern): string
    {
        return '/' . trim($pattern, '/');
    }

    private function normalizeRequestPath(string $path): string
    {
        $basePath = (string) AppContext::get('base_path', '');
        $normalized = '/' . ltrim($path, '/');

        if ($basePath !== '' && $basePath !== '/' && str_starts_with($normalized, $basePath)) {
            $normalized = substr($normalized, strlen($basePath));
            if ($normalized === '' || $normalized === false) {
                $normalized = '/';
            }
        }

        return '/' . trim((string) $normalized, '/');
    }

    /** @return array<string, string>|null */
    private function match(string $pattern, string $path): ?array
    {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (!preg_match($regex, $path, $matches)) {
            return null;
        }

        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }

        return $params;
    }

    private function runMiddleware(string $name, Request $request, Response $response): bool
    {
        $parts = explode(':', $name, 2);
        $middlewareName = $parts[0];
        $argument = $parts[1] ?? null;

        if (!isset($this->middleware[$middlewareName])) {
            throw new RuntimeException("Middleware not registered: {$middlewareName}");
        }

        $callback = $this->middleware[$middlewareName];

        return (bool) $callback($request, $response, $argument);
    }

    private function invokeHandler(mixed $handler, Request $request, Response $response): void
    {
        if (is_callable($handler)) {
            $handler($request, $response);
            return;
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            $controller = new $class();
            $controller->{$method}($request, $response);
            return;
        }

        throw new RuntimeException('Invalid route handler');
    }
}
