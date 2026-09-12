<?php

namespace App\DTO;

final readonly class TransactionReversalDTO
{
    public function __construct(
        public ?string $transactionDate,
        public ?string $competenceDate,
        public ?string $notes,
    ) {}

    /** @param array<string, mixed> $attributes */
    public static function fromArray(array $attributes): self
    {
        return new self(
            $attributes['transaction_date'] ?? null,
            $attributes['competence_date'] ?? null,
            $attributes['notes'] ?? null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'transaction_date' => $this->transactionDate,
            'competence_date' => $this->competenceDate,
            'notes' => $this->notes,
        ];
    }
}
