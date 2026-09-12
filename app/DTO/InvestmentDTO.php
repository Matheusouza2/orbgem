<?php

namespace App\DTO;

use App\Enums\InvestmentType;

final readonly class InvestmentDTO
{
    public function __construct(
        public int $walletId,
        public string $name,
        public ?string $ticker,
        public InvestmentType $type,
        public ?string $institution,
        public string $quantity,
        public int $averagePrice,
        public int $investedAmount,
        public int $currentValue,
        public ?string $acquiredAt,
        public bool $active,
        public bool $cdiLinked,
        public ?string $cdiPercentage,
        public ?string $lastYieldDate,
    ) {}

    /** @param array<string, mixed> $attributes */
    public static function fromArray(array $attributes): self
    {
        return new self(
            (int) $attributes['wallet_id'],
            $attributes['name'],
            $attributes['ticker'] ?? null,
            InvestmentType::from($attributes['type']),
            $attributes['institution'] ?? null,
            (string) $attributes['quantity'],
            (int) $attributes['average_price'],
            (int) $attributes['invested_amount'],
            (int) $attributes['current_value'],
            $attributes['acquired_at'] ?? null,
            $attributes['active'] ?? true,
            (bool) ($attributes['cdi_linked'] ?? false),
            isset($attributes['cdi_percentage']) ? (string) $attributes['cdi_percentage'] : null,
            $attributes['last_yield_date'] ?? null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'wallet_id' => $this->walletId,
            'name' => $this->name,
            'ticker' => $this->ticker,
            'type' => $this->type,
            'institution' => $this->institution,
            'quantity' => $this->quantity,
            'average_price' => $this->averagePrice,
            'invested_amount' => $this->investedAmount,
            'current_value' => $this->currentValue,
            'acquired_at' => $this->acquiredAt,
            'active' => $this->active,
            'cdi_linked' => $this->cdiLinked,
            'cdi_percentage' => $this->cdiPercentage,
            'last_yield_date' => $this->lastYieldDate,
        ];
    }
}
