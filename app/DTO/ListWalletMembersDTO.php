<?php

namespace App\DTO;

final readonly class ListWalletMembersDTO
{
    public function __construct(public int $walletId) {}

    /** @param array<string, mixed> $attributes */
    public static function fromArray(array $attributes): self
    {
        return new self($attributes['wallet_id']);
    }

    /** @return array{wallet_id: int} */
    public function toArray(): array
    {
        return ['wallet_id' => $this->walletId];
    }
}
