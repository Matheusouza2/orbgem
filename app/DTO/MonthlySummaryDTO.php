<?php

namespace App\DTO;

final readonly class MonthlySummaryDTO
{
    public function __construct(public int $walletId, public string $month, public bool $includeThirdParty = true) {}

    /** @param array{wallet_id: int, month: string} $attributes */
    public static function fromArray(array $attributes): self
    {
        return new self($attributes['wallet_id'], $attributes['month'], (bool) ($attributes['include_third_party'] ?? true));
    }
}
