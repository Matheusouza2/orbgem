<?php

namespace App\UseCases\Auth;

use App\Models\User;
use App\Services\AuthService;

class LogoutUseCase
{
    public function __construct(private AuthService $authService) {}

    public function execute(User $user): void
    {
        $this->authService->logout($user);
    }

    public function executeAll(User $user): void
    {
        $this->authService->logoutAll($user);
    }
}
