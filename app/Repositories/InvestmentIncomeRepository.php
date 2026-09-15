<?php

namespace App\Repositories;

use App\Models\InvestmentIncome;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class InvestmentIncomeRepository implements InvestmentIncomeRepositoryInterface
{
    public function create(array $attributes): InvestmentIncome
    {
        return InvestmentIncome::query()->create($attributes);
    }

    public function forWallet(int $walletId, ?int $investmentId = null, ?string $from = null, ?string $to = null): Collection
    {
        return InvestmentIncome::query()
            ->with('investment:id,name,ticker')
            ->whereHas('investment', fn (Builder $query): Builder => $query->where('wallet_id', $walletId))
            ->when($investmentId, fn (Builder $query): Builder => $query->where('investment_id', $investmentId))
            ->when($from, fn (Builder $query): Builder => $query->whereDate('transaction_date', '>=', $from))
            ->when($to, fn (Builder $query): Builder => $query->whereDate('transaction_date', '<=', $to))
            ->latest('transaction_date')
            ->get();
    }
}
