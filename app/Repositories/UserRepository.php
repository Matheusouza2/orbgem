<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository implements UserRepositoryInterface
{
    public function create(string $name, string $email, string $password): User
    {
        return User::query()->create(['name' => $name, 'email' => $email, 'password' => $password]);
    }

    public function findById(int $userId): ?User
    {
        return User::query()->find($userId);
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }
}
