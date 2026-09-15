<?php

namespace App\Repositories;

use App\Models\InvestmentPosition;
use Illuminate\Support\Collection;

class InvestmentPositionRepository implements InvestmentPositionRepositoryInterface
{
    public function forInvestment(int $investmentId): Collection
    {
        return InvestmentPosition::query()
            ->where('investment_id', $investmentId)
            ->orderByDesc('position_date')
            ->orderByDesc('id')
            ->get();
    }

    public function upsert(int $investmentId, array $attributes): InvestmentPosition
    {
        $date = \Illuminate\Support\Carbon::parse($attributes['position_date'])->toDateString();
        $position = InvestmentPosition::query()
            ->where('investment_id', $investmentId)
            ->whereDate('position_date', $date)
            ->first();

        if ($position === null) {
            return InvestmentPosition::query()->create([...$attributes, 'investment_id' => $investmentId, 'position_date' => $date]);
        }

        $position->update([...$attributes, 'position_date' => $date]);

        return $position->refresh();
    }

    public function delete(InvestmentPosition $position): void
    {
        $position->delete();
    }

    public function historyForWallet(int $walletId): Collection
    {
        $positions = InvestmentPosition::query()
            ->whereHas('investment', fn ($query) => $query->where('wallet_id', $walletId))
            ->orderBy('position_date')
            ->orderBy('id')
            ->get(['investment_id', 'position_date', 'value']);
        $latestByInvestment = [];

        return $positions
            ->groupBy(fn (InvestmentPosition $position): string => $position->position_date->toDateString())
            ->map(function (Collection $datePositions, string $date) use (&$latestByInvestment): array {
                foreach ($datePositions as $position) {
                    $latestByInvestment[$position->investment_id] = (int) $position->value;
                }

                return ['position_date' => $date, 'total_value' => array_sum($latestByInvestment)];
            })
            ->values();
    }
}
