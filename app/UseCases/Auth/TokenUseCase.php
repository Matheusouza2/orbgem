<?php

namespace App\UseCases\Auth;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Support\Collection;

class TokenUseCase
{
    public function __construct(private AuthService $authService) {}

    public function list(User $user): Collection
    {
        return $this->authService->tokens($user);
    }

    public function revoke(User $user, int $tokenId): void
    {
        $this->authService->revokeToken($user, $tokenId);
    }
}
