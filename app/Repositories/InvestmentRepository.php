<?php

namespace App\Repositories;

use App\DTO\InvestmentDTO;
use App\Models\Investment;
use Illuminate\Support\Collection;

class InvestmentRepository implements InvestmentRepositoryInterface
{
    public function create(InvestmentDTO $investmentDTO): Investment
    {
        return Investment::query()->create($investmentDTO->toArray());
    }

    public function update(Investment $investment, InvestmentDTO $investmentDTO): Investment
    {
        $investment->update($investmentDTO->toArray());

        return $investment->refresh();
    }

    public function forWallet(int $walletId): Collection
    {
        return Investment::query()->where('wallet_id', $walletId)->latest()->get();
    }

    public function delete(Investment $investment): void
    {
        $investment->delete();
    }

    public function find(int $investmentId): ?Investment
    {
        return Investment::query()->find($investmentId);
    }

    public function activeCdi(): Collection
    {
        return Investment::query()->where('cdi_linked', true)->where('active', true)->get();
    }

    public function accrueCdi(Investment $investment, string $date, float $dailyRate, int $yieldAmount, int $closingValue): bool
    {
        $yield = $investment->yields()->firstOrCreate(['reference_date' => $date], ['cdi_daily_rate' => $dailyRate, 'cdi_percentage' => $investment->cdi_percentage, 'opening_value' => $investment->current_value, 'yield_amount' => $yieldAmount, 'closing_value' => $closingValue]);
        if (! $yield->wasRecentlyCreated) {
            return false;
        }

        $investment->update(['current_value' => $closingValue, 'last_yield_date' => $date]);

        return true;
    }
}
