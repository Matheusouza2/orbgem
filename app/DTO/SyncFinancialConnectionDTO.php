<?php

namespace App\DTO;

final readonly class SyncFinancialConnectionDTO
{
    public function __construct(public int $connectionId, public ?string $from, public ?string $to) {}

    /** @param array<string, mixed> $attributes */
    public static function fromArray(array $attributes): self
    {
        return new self((int) $attributes['connection_id'], $attributes['from'] ?? null, $attributes['to'] ?? null);
    }
}
