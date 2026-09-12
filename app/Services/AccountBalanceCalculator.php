<?php

namespace App\Services;

use App\Enums\TransactionEffect;
use Illuminate\Support\Collection;

class AccountBalanceCalculator
{
    public function calculate(int $initialBalance, Collection $transactions): int
    {
        return $initialBalance + $transactions->sum(fn ($transaction): int => match ($transaction->effect) {
            TransactionEffect::CREDIT => $transaction->amount, TransactionEffect::DEBIT => -$transaction->amount, default => 0
        });
    }
}
