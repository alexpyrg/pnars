<?php

declare(strict_types=1);

use App\Core\Auth\AuthService;
use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Router;
use App\Core\Security\Csrf;
use App\Core\Security\Session;
use App\Core\Support\AppContext;
use App\Core\Support\AuditLogger;
use App\Core\Support\Config;
use App\Core\Support\Env;
use App\Core\Support\Flash;
use App\Core\Support\View;

require __DIR__ . '/autoload.php';
require __DIR__ . '/helpers.php';

Env::load(base_path('.env'));

Config::load('app', require base_path('config/app.php'));
Config::load('database', require base_path('config/database.php'));

date_default_timezone_set((string) Config::get('app.timezone', 'Europe/Athens'));
mb_internal_encoding('UTF-8');
header('Content-Type: text/html; charset=UTF-8');

Session::start((array) Config::get('app.session', []));

$flash = [
    'success' => Flash::get('success'),
    'error' => Flash::get('error'),
];

AppContext::set('flash', $flash);
AppContext::set('old_input', Flash::oldInput());
AppContext::set('errors', Flash::errors());

$basePath = parse_url((string) Config::get('app.url', '/'), PHP_URL_PATH) ?: '';
if ($basePath === '') {
    $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '/');
    $basePath = str_replace('\\', '/', dirname($scriptName));
}
$basePath = rtrim($basePath, '/');
if ($basePath === '.' || $basePath === '/') {
    $basePath = '';
}
AppContext::set('base_path', $basePath);

$pdo = Connection::make(Config::all('database'));
$auth = new AuthService($pdo);
$audit = new AuditLogger($pdo, $auth);
$view = new View(base_path('app/Views'));

AppContext::set('pdo', $pdo);
AppContext::set('auth', $auth);
AppContext::set('audit', $audit);
AppContext::set('view', $view);

$request = Request::capture();
$response = new Response($view);
$router = new Router();

$router->registerMiddleware('guest', static function (Request $request, Response $response): bool {
    if (auth()->check()) {
        $response->redirect(url('/'));
        return false;
    }

    return true;
});

$router->registerMiddleware('auth', static function (Request $request, Response $response): bool {
    if (!auth()->check()) {
        Flash::set('error', 'Η συνεδρία σας έληξε. Συνδεθείτε ξανά.');
        $response->redirect(url('/login'));
        return false;
    }

    return true;
});

$router->registerMiddleware('csrf', static function (Request $request, Response $response): bool {
    if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        return true;
    }

    $token = $request->input('_token');
    if (!is_string($token) || !Csrf::validate($token)) {
        http_response_code(419);
        $response->view('errors/419', ['title' => 'Μη έγκυρο διακριτικό ασφάλειας'], 419);
        return false;
    }

    return true;
});

$router->registerMiddleware('role', static function (Request $request, Response $response, ?string $role): bool {
    if ($role === null || auth()->hasRole($role)) {
        return true;
    }

    $response->view('errors/403', ['title' => 'Μη εξουσιοδοτημένη πρόσβαση'], 403);
    return false;
});

$routeRegistrar = require base_path('config/routes.php');
$routeRegistrar($router);

try {
    $router->dispatch($request, $response);
} catch (\Throwable $exception) {
    if ((bool) Config::get('app.debug', false)) {
        throw $exception;
    }

    http_response_code(500);
    $response->view('errors/500', ['title' => 'Εσωτερικό σφάλμα διακομιστή'], 500);
}