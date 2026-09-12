<?php

namespace App\DTO;

final readonly class MerchantDTO
{
    public function __construct(
        public int $walletId,
        public string $name,
        public ?string $normalizedName,
        public bool $active,
    ) {}

    /** @param array<string, mixed> $attributes */
    public static function fromArray(array $attributes): self
    {
        return new self($attributes['wallet_id'], $attributes['name'], null, $attributes['active'] ?? true);
    }

    public function withNormalizedName(string $normalizedName): self
    {
        return new self($this->walletId, $this->name, $normalizedName, $this->active);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'wallet_id' => $this->walletId,
            'name' => $this->name,
            'normalized_name' => $this->normalizedName,
            'active' => $this->active,
        ];
    }
}
