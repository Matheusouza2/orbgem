<?php

namespace App\Repositories;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\FinancialSummary;
use App\Models\Investment;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class FinancialSummaryRepository implements FinancialSummaryRepositoryInterface
{
    public function find(int $walletId): ?FinancialSummary
    {
        return FinancialSummary::query()->where('wallet_id', $walletId)->first();
    }

    public function save(int $walletId, array $facts, ?array $analysis, string $hash): FinancialSummary
    {
        return FinancialSummary::query()->updateOrCreate(
            ['wallet_id' => $walletId],
            ['facts' => $facts, 'analysis' => $analysis, 'data_hash' => $hash, 'generated_at' => now()],
        );
    }

    public function postedTransactionsSince(int $walletId, Carbon $from): Collection
    {
        return Transaction::query()
            ->includedInTotals()
            ->with(['category:id,name', 'merchant:id,name'])
            ->where('wallet_id', $walletId)
            ->where('status', TransactionStatus::POSTED->value)
            ->whereIn('type', [TransactionType::INCOME->value, TransactionType::EXPENSE->value])
            ->whereDate('transaction_date', '>=', $from->toDateString())
            ->get(['id', 'category_id', 'merchant_id', 'type', 'amount', 'transaction_date']);
    }

    public function activeInvestments(int $walletId): Collection
    {
        return Investment::query()->where('wallet_id', $walletId)->where('active', true)->get();
    }

    public function walletIdsWithFinancialData(?int $onlyWalletId = null): Collection
    {
        return Wallet::query()
            ->when($onlyWalletId, fn ($query) => $query->whereKey($onlyWalletId))
            ->where(fn ($query) => $query->whereHas('transactions')->orWhereHas('investments'))
            ->pluck('id');
    }
}
