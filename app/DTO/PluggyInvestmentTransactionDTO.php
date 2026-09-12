<?php

namespace App\DTO;

final readonly class PluggyInvestmentTransactionDTO
{
    /** @param array<string, mixed> $rawData */
    public function __construct(
        public string $externalId,
        public string $eventType,
        public string $description,
        public int $amount,
        public string $date,
        public array $rawData,
    ) {}
}
