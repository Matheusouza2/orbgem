<?php

namespace App\DTO;

final readonly class BudgetDTO
{
    public function __construct(public int $walletId, public int $categoryId, public string $referenceMonth, public int $amount, public bool $active) {}

    public static function fromArray(array $a): self
    {
        return new self($a['wallet_id'], $a['category_id'], $a['reference_month'], $a['amount'], $a['active'] ?? true);
    }

    public function toArray(): array
    {
        return ['wallet_id' => $this->walletId, 'category_id' => $this->categoryId, 'reference_month' => $this->referenceMonth, 'amount' => $this->amount, 'active' => $this->active];
    }
}
