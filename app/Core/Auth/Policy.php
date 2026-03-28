<?php

declare(strict_types=1);

namespace App\Core\Auth;

final class Policy
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function canViewAccident(string $ownerUserId): bool
    {
        if ($this->authService->hasRole('administrator', 'expert')) {
            return true;
        }

        return $this->authService->id() === $ownerUserId;
    }

    public function canEditAccident(string $ownerUserId): bool
    {
        if ($this->authService->hasRole('administrator')) {
            return true;
        }

        return $this->authService->hasRole('registrar') && $this->authService->id() === $ownerUserId;
    }
}
