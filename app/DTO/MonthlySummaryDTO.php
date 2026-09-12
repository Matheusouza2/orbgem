<?php

namespace App\DTO;

final readonly class MonthlySummaryDTO
{
    public function __construct(public int $walletId, public string $month) {}

    /** @param array{wallet_id: int, month: string} $attributes */
    public static function fromArray(array $attributes): self
    {
        return new self($attributes['wallet_id'], $attributes['month']);
    }
}
