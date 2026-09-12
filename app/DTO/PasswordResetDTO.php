<?php

namespace App\DTO;

final readonly class PasswordResetDTO
{
    public function __construct(public string $email, public string $token, public string $password) {}

    public static function fromArray(array $attributes): self
    {
        return new self($attributes['email'], $attributes['token'], $attributes['password']);
    }
}
