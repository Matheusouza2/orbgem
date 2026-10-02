<?php

namespace App\Services;

use App\Enums\InvestmentPositionType;
use App\Models\Investment;
use App\Models\InvestmentPosition;
use App\Repositories\InvestmentPositionRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvestmentPositionService
{
    public function __construct(private InvestmentPositionRepositoryInterface $repository) {}

    public function forInvestment(int $investmentId): Collection
    {
        return $this->repository->forInvestment($investmentId);
    }

    public function upsert(Investment $investment, array $attributes): InvestmentPosition
    {
        return DB::transaction(function () use ($investment, $attributes): InvestmentPosition {
            $lockedInvestment = Investment::query()->lockForUpdate()->findOrFail($investment->id);
            $attributes['entry_type'] ??= InvestmentPositionType::SNAPSHOT->value;

            $previousValue = $lockedInvestment->positions()
                ->whereDate('position_date', $attributes['position_date'])
                ->where('entry_type', $attributes['entry_type'])
                ->value('value');
            $position = $this->repository->upsert($lockedInvestment->id, $attributes);

            if ($position->entry_type === InvestmentPositionType::CONTRIBUTION) {
                $delta = (int) $position->value - (int) ($previousValue ?? 0);
                if ($delta !== 0) {
                    $lockedInvestment->update([
                        'invested_amount' => (int) $lockedInvestment->invested_amount + $delta,
                        'current_value' => (int) $lockedInvestment->current_value + $delta,
                    ]);
                }
            }

            return $position;
        });
    }

    /** @param array<string, mixed> $attributes */
    public function update(InvestmentPosition $position, array $attributes): InvestmentPosition
    {
        return DB::transaction(function () use ($position, $attributes): InvestmentPosition {
            $investment = Investment::query()->lockForUpdate()->findOrFail($position->investment_id);
            $lockedPosition = InvestmentPosition::query()->lockForUpdate()->findOrFail($position->id);
            $attributes['entry_type'] ??= $lockedPosition->entry_type->value;

            $duplicate = InvestmentPosition::query()
                ->where('investment_id', $investment->id)
                ->whereDate('position_date', $attributes['position_date'])
                ->where('entry_type', $attributes['entry_type'])
                ->where('id', '!=', $lockedPosition->id)
                ->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['position_date' => 'Já existe um lançamento deste tipo nesta data.']);
            }

            $previousContribution = $lockedPosition->entry_type === InvestmentPositionType::CONTRIBUTION ? (int) $lockedPosition->value : 0;

            $updatedPosition = $this->repository->update($lockedPosition, $attributes);
            $updatedContribution = $updatedPosition->entry_type === InvestmentPositionType::CONTRIBUTION ? (int) $updatedPosition->value : 0;
            $delta = $updatedContribution - $previousContribution;

            if ($delta !== 0) {
                $investment->update([
                    'invested_amount' => (int) $investment->invested_amount + $delta,
                    'current_value' => (int) $investment->current_value + $delta,
                ]);
            }

            return $updatedPosition;
        });
    }

    public function delete(InvestmentPosition $position): void
    {
        DB::transaction(function () use ($position): void {
            $investment = Investment::query()->lockForUpdate()->findOrFail($position->investment_id);
            $lockedPosition = InvestmentPosition::query()->lockForUpdate()->findOrFail($position->id);

            if ($lockedPosition->entry_type === InvestmentPositionType::CONTRIBUTION) {
                $investment->update([
                    'invested_amount' => (int) $investment->invested_amount - (int) $lockedPosition->value,
                    'current_value' => (int) $investment->current_value - (int) $lockedPosition->value,
                ]);
            }

            $this->repository->delete($lockedPosition);
        });
    }

    public function historyForWallet(int $walletId): Collection
    {
        return $this->repository->historyForWallet($walletId);
    }
}
