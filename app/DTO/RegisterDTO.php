<?php

namespace App\DTO;

final readonly class RegisterDTO
{
    public function __construct(public string $name, public string $email, public string $password, public string $deviceName) {}

    public static function fromArray(array $attributes): self
    {
        return new self($attributes['name'], $attributes['email'], $attributes['password'], $attributes['device_name'] ?? 'api-client');
    }
}
