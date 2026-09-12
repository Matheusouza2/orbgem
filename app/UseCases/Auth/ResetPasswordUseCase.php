<?php

namespace App\UseCases\Auth;

use App\DTO\PasswordResetDTO;
use App\Services\AuthService;

class ResetPasswordUseCase
{
    public function __construct(private AuthService $authService) {}

    public function execute(PasswordResetDTO $dto): string
    {
        return $this->authService->resetPassword($dto);
    }
}
