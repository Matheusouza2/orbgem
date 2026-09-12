<?php

namespace App\Services;

use App\DTO\LoginDTO;
use App\DTO\PasswordResetDTO;
use App\DTO\RegisterDTO;
use App\Models\User;
use App\Repositories\UserRepositoryInterface;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthService
{
    public function __construct(private UserRepositoryInterface $userRepository) {}

    /** @return array{user: User, token: string} */
    public function login(LoginDTO $loginDTO): array
    {
        $user = $this->userRepository->findByEmail($loginDTO->email);

        if ($user === null || ! Hash::check($loginDTO->password, $user->password)) {
            throw new AuthenticationException('Invalid credentials.');
        }

        Auth::guard('web')->login($user);

        return [
            'user' => $user,
            'token' => $user->createToken($loginDTO->deviceName)->plainTextToken,
        ];
    }

    /** @return array{user: User, token: string} */
    public function register(RegisterDTO $dto): array
    {
        $user = $this->userRepository->create($dto->name, $dto->email, $dto->password);
        Auth::guard('web')->login($user);

        return ['user' => $user, 'token' => $user->createToken($dto->deviceName)->plainTextToken];
    }

    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();
        if ($token !== null && method_exists($token, 'delete')) {
            $token->delete();
        }

        Auth::guard('web')->logout();
    }

    public function logoutAll(User $user): void
    {
        $user->tokens()->delete();
    }

    public function tokens(User $user): Collection
    {
        return $user->tokens()->latest()->get();
    }

    public function revokeToken(User $user, int $tokenId): void
    {
        $token = $user->tokens()->findOrFail($tokenId);
        $token->delete();
    }

    public function sendPasswordReset(string $email): string
    {
        return Password::sendResetLink(['email' => $email]);
    }

    public function resetPassword(PasswordResetDTO $dto): string
    {
        return Password::reset(['email' => $dto->email, 'password' => $dto->password, 'password_confirmation' => $dto->password, 'token' => $dto->token], function (User $user) use ($dto): void {
            $user->forceFill(['password' => $dto->password, 'remember_token' => null])->save();
            $user->tokens()->delete();
        });
    }
}
