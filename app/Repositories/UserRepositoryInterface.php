<?php

namespace App\Repositories;

use App\Models\User;

interface UserRepositoryInterface
{
    public function create(string $name, string $email, string $password): User;

    public function findById(int $userId): ?User;

    public function findByEmail(string $email): ?User;
}
