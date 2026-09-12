<?php

namespace App\DTO;

final readonly class LoginDTO
{
    public function __construct(
        public string $email,
        public string $password,
        public string $deviceName,
    ) {}

    /** @param array<string, mixed> $attributes */
    public static function fromArray(array $attributes): self
    {
        return new self(
            email: $attributes['email'],
            password: $attributes['password'],
            deviceName: $attributes['device_name'] ?? 'api-client',
        );
    }

    /** @return array{email: string, password: string, device_name: string} */
    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
            'device_name' => $this->deviceName,
        ];
    }
}
