<?php

namespace App\Repositories;

use App\Models\ExternalInvestmentTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ExternalInvestmentTransactionRepository implements ExternalInvestmentTransactionRepositoryInterface
{
    public function upsert(string $source, string $externalId, array $attributes): ExternalInvestmentTransaction
    {
        return ExternalInvestmentTransaction::query()->updateOrCreate(
            ['source' => $source, 'external_id' => $externalId],
            $attributes,
        );
    }

    public function forWallet(int $walletId, ?int $investmentId = null, ?string $from = null, ?string $to = null): Collection
    {
        return ExternalInvestmentTransaction::query()
            ->with('investment:id,name,ticker')
            ->whereHas('investment', function (Builder $query) use ($walletId): void {
                $query->where('wallet_id', $walletId);
            })
            ->when($investmentId, fn (Builder $query): Builder => $query->where('investment_id', $investmentId))
            ->when($from, fn (Builder $query): Builder => $query->whereDate('transaction_date', '>=', $from))
            ->when($to, fn (Builder $query): Builder => $query->whereDate('transaction_date', '<=', $to))
            ->latest('transaction_date')
            ->get();
    }
}
