<?php

namespace App\UseCases\Auth;

use App\DTO\LoginDTO;
use App\Models\User;
use App\Services\AuthService;

class LoginUseCase
{
    public function __construct(private AuthService $authService) {}

    /** @return array{user: User, token: string} */
    public function execute(LoginDTO $loginDTO): array
    {
        return $this->authService->login($loginDTO);
    }
}
