<?php

namespace App\DTO;

final readonly class RegisterPluggyConnectionDTO
{
    public function __construct(public string $itemId, public int $walletId) {}

    /** @param array<string, mixed> $attributes */
    public static function fromArray(array $attributes): self
    {
        return new self((string) $attributes['item_id'], (int) $attributes['wallet_id']);
    }

    /** @return array{item_id: string, wallet_id: int} */
    public function toArray(): array
    {
        return ['item_id' => $this->itemId, 'wallet_id' => $this->walletId];
    }
}
