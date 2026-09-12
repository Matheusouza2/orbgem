<?php

namespace App\UseCases\Auth;

use App\Services\AuthService;

class RequestPasswordResetUseCase
{
    public function __construct(private AuthService $authService) {}

    public function execute(string $email): string
    {
        return $this->authService->sendPasswordReset($email);
    }
}
