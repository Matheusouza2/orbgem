<?php

namespace App\DTO;

use App\Enums\RecurringFrequency;
use App\Enums\TransactionType;

final readonly class RecurringTransactionDTO
{
    public function __construct(public int $walletId, public ?int $accountId, public ?int $categoryId, public string $description, public TransactionType $type, public int $amount, public RecurringFrequency $frequency, public string $startDate, public ?string $endDate, public ?int $dueDay, public bool $autoCreate, public bool $active) {}

    public static function fromArray(array $a): self
    {
        return new self($a['wallet_id'], $a['account_id'] ?? null, $a['category_id'] ?? null, $a['description'], TransactionType::from($a['type']), $a['amount'], RecurringFrequency::from($a['frequency']), $a['start_date'], $a['end_date'] ?? null, $a['due_day'] ?? null, $a['auto_create'] ?? false, $a['active'] ?? true);
    }

    public function toArray(): array
    {
        return ['wallet_id' => $this->walletId, 'account_id' => $this->accountId, 'category_id' => $this->categoryId, 'description' => $this->description, 'type' => $this->type, 'amount' => $this->amount, 'frequency' => $this->frequency, 'start_date' => $this->startDate, 'end_date' => $this->endDate, 'due_day' => $this->dueDay, 'auto_create' => $this->autoCreate, 'active' => $this->active];
    }
}
