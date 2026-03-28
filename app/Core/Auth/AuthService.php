<?php

declare(strict_types=1);

namespace App\Core\Auth;

use App\Core\Security\Session;
use App\Core\Support\Config;
use PDO;

final class AuthService
{
    /** @var array<string, mixed>|null */
    private ?array $cachedUser = null;

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function check(): bool
    {
        return $this->id() !== null;
    }

    public function id(): ?string
    {
        $id = $_SESSION['auth_user_id'] ?? null;

        return is_string($id) ? $id : null;
    }

    /** @return array<string, mixed>|null */
    public function user(): ?array
    {
        if (!$this->check()) {
            return null;
        }

        if ($this->cachedUser !== null) {
            return $this->cachedUser;
        }

        $stmt = $this->pdo->prepare(
            'SELECT u.id, u.email, u.full_name, u.is_active, r.code AS role_code, r.label_el AS role_label_el
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id'
        );
        $stmt->execute([':id' => $this->id()]);

        $user = $stmt->fetch();
        if (!$user || !(bool) $user['is_active']) {
            $this->logout();
            return null;
        }

        $this->cachedUser = $user;

        return $this->cachedUser;
    }

    public function roleCode(): ?string
    {
        $user = $this->user();

        return $user['role_code'] ?? null;
    }

    public function hasRole(string ...$allowed): bool
    {
        $role = $this->roleCode();

        return $role !== null && in_array($role, $allowed, true);
    }

    public function attempt(string $email, string $password): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.id, u.password_hash, u.is_active
             FROM users u
             WHERE lower(u.email) = lower(:email)
             LIMIT 1'
        );
        $stmt->execute([':email' => $email]);

        $row = $stmt->fetch();
        if (!$row || !(bool) $row['is_active']) {
            return false;
        }

        if (!password_verify($password, (string) $row['password_hash'])) {
            return false;
        }

        $_SESSION['auth_user_id'] = (string) $row['id'];
        Session::regenerate();

        $update = $this->pdo->prepare('UPDATE users SET last_login_at = NOW(), updated_at = NOW() WHERE id = :id');
        $update->execute([':id' => $row['id']]);

        $this->cachedUser = null;

        return true;
    }

    public function logout(): void
    {
        $this->cachedUser = null;

        Session::destroy();
        Session::start((array) Config::get('app.session', []));
    }
}
