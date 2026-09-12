<?php

namespace App\DTO;

final readonly class WalletDTO
{
    public function __construct(public string $name) {}

    /** @param array{name: string} $attributes */
    public static function fromArray(array $attributes): self
    {
        return new self($attributes['name']);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['name' => $this->name];
    }
}
