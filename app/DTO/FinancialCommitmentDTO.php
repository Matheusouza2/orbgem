<?php

namespace App\DTO;

use App\Enums\FinancialCommitmentType;

final readonly class FinancialCommitmentDTO
{
    public function __construct(public int $walletId, public string $description, public FinancialCommitmentType $type, public int $originalAmount, public int $installmentAmount, public int $installmentCount, public int $currentInstallment, public string $startDate, public string $endDate, public ?string $creditor, public bool $active) {}

    public static function fromArray(array $a): self
    {
        return new self($a['wallet_id'], $a['description'], FinancialCommitmentType::from($a['type']), $a['original_amount'], $a['installment_amount'], $a['installment_count'], $a['current_installment'] ?? 0, $a['start_date'], $a['end_date'], $a['creditor'] ?? null, $a['active'] ?? true);
    }

    public function toArray(): array
    {
        return ['wallet_id' => $this->walletId, 'description' => $this->description, 'type' => $this->type, 'original_amount' => $this->originalAmount, 'installment_amount' => $this->installmentAmount, 'installment_count' => $this->installmentCount, 'current_installment' => $this->currentInstallment, 'start_date' => $this->startDate, 'end_date' => $this->endDate, 'creditor' => $this->creditor, 'active' => $this->active];
    }
}
