<?php

namespace App\Repositories;

use App\Enums\InvestmentPositionType;
use App\Models\InvestmentPosition;
use Illuminate\Support\Carbon;
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
        $date = Carbon::parse($attributes['position_date'])->toDateString();
        $position = InvestmentPosition::query()
            ->where('investment_id', $investmentId)
            ->whereDate('position_date', $date)
            ->where('entry_type', $attributes['entry_type'] ?? InvestmentPositionType::SNAPSHOT->value)
            ->first();

        if ($position === null) {
            return InvestmentPosition::query()->create([...$attributes, 'investment_id' => $investmentId, 'position_date' => $date]);
        }

        $position->update([...$attributes, 'position_date' => $date]);

        return $position->refresh();
    }

    /** @param array<string, mixed> $attributes */
    public function update(InvestmentPosition $position, array $attributes): InvestmentPosition
    {
        $position->update([
            ...$attributes,
            'position_date' => Carbon::parse($attributes['position_date'])->toDateString(),
        ]);

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
            ->orderByRaw("CASE WHEN entry_type = 'SNAPSHOT' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->get(['investment_id', 'position_date', 'entry_type', 'value']);
        $latestByInvestment = [];

        return $positions
            ->groupBy(fn (InvestmentPosition $position): string => $position->position_date->toDateString())
            ->map(function (Collection $datePositions, string $date) use (&$latestByInvestment): array {
                $contributionAmount = 0;
                foreach ($datePositions as $position) {
                    if ($position->entry_type === InvestmentPositionType::SNAPSHOT) {
                        $latestByInvestment[$position->investment_id] = (int) $position->value;
                    } else {
                        $contributionAmount += (int) $position->value;
                        $latestByInvestment[$position->investment_id] = ($latestByInvestment[$position->investment_id] ?? 0) + (int) $position->value;
                    }
                }

                return ['position_date' => $date, 'total_value' => array_sum($latestByInvestment), 'contribution_amount' => $contributionAmount];
            })
            ->values();
    }
}
