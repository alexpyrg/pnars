<?php

declare(strict_types=1);

namespace App\Modules\Auth;

use App\Core\Http\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Security\RateLimiter;
use App\Core\Support\Flash;
use App\Core\Support\Validator;

final class AuthController extends Controller
{
    public function showLogin(Request $request, Response $response): void
    {
        $response->view('auth/login', [
            'title' => 'Σύνδεση',
        ]);
    }

    public function login(Request $request, Response $response): void
    {
        $input = [
            'email' => trim((string) $request->input('email', '')),
            'password' => (string) $request->input('password', ''),
        ];

        $limiter = new RateLimiter($this->db());
        $ipIdentifier = $this->clientIdentifier($request);
        $accountIdentifier = strtolower($input['email']) !== ''
            ? $ipIdentifier . '|' . strtolower($input['email'])
            : $ipIdentifier . '|unknown';

        $globalConfig = [
            'max_attempts' => 25,
            'window_seconds' => 900,
            'lockout_seconds' => 900,
        ];
        $accountConfig = (array) config('app.rate_limit.login', []);

        $globalRate = $limiter->consume(
            'login_ip',
            $ipIdentifier,
            (int) ($globalConfig['max_attempts'] ?? 25),
            (int) ($globalConfig['window_seconds'] ?? 900),
            (int) ($globalConfig['lockout_seconds'] ?? 900)
        );

        if (!$globalRate['allowed']) {
            Flash::set('error', 'Πολλές αποτυχημένες προσπάθειες. Προσπαθήστε ξανά σε ' . $globalRate['retry_after'] . ' δευτερόλεπτα.');
            $response->redirect(url('/login'));
            return;
        }

        $accountRate = $limiter->consume(
            'login_account',
            $accountIdentifier,
            (int) ($accountConfig['max_attempts'] ?? 8),
            (int) ($accountConfig['window_seconds'] ?? 900),
            (int) ($accountConfig['lockout_seconds'] ?? 900)
        );

        if (!$accountRate['allowed']) {
            Flash::set('error', 'Πολλές αποτυχημένες προσπάθειες. Προσπαθήστε ξανά σε ' . $accountRate['retry_after'] . ' δευτερόλεπτα.');
            Flash::keepInput(['email' => $input['email']]);
            $response->redirect(url('/login'));
            return;
        }

        $validator = new Validator();
        $errors = $validator->validate($input, [
            'email' => ['required', 'email'],
            'password' => ['required', 'min:8'],
        ]);

        if ($errors !== []) {
            Flash::setErrors($errors);
            Flash::keepInput(['email' => $input['email']]);
            Flash::set('error', 'Παρακαλώ ελέγξτε τα στοιχεία που δώσατε.');
            $response->redirect(url('/login'));
            return;
        }

        if (!$this->auth()->attempt($input['email'], $input['password'])) {
            Flash::set('error', 'Λάθος email ή κωδικός πρόσβασης.');
            Flash::keepInput(['email' => $input['email']]);
            $response->redirect(url('/login'));
            return;
        }

        $limiter->clear('login_account', $accountIdentifier);
        $limiter->clear('login_ip', $ipIdentifier);

        $this->audit()->log('login', 'session', null, 'Επιτυχής σύνδεση χρήστη.');
        Flash::set('success', 'Συνδεθήκατε επιτυχώς.');

        $response->redirect(url('/'));
    }

    public function logout(Request $request, Response $response): void
    {
        $this->audit()->log('logout', 'session', null, 'Αποσύνδεση χρήστη.');
        $this->auth()->logout();

        Flash::set('success', 'Αποσυνδεθήκατε επιτυχώς.');
        $response->redirect(url('/login'));
    }

    private function clientIdentifier(Request $request): string
    {
        $ip = trim((string) $request->server('REMOTE_ADDR', ''));

        return $ip !== '' ? $ip : 'unknown-ip';
    }
}