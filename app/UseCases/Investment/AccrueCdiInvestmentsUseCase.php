<?php

namespace App\UseCases\Investment;

use App\Services\BrapiClient;
use App\Services\InvestmentService;
use Illuminate\Support\Carbon;

class AccrueCdiInvestmentsUseCase
{
    public function __construct(private InvestmentService $investments, private BrapiClient $brapi) {}

    public function execute(string $date): int
    {
        $target = Carbon::parse($date);
        $investments = $this->investments->activeCdi()->filter(fn ($investment): bool => ! $investment->acquired_at || $investment->acquired_at->lessThanOrEqualTo($target));
        if ($investments->isEmpty()) {
            return 0;
        }

        $startDate = $investments->map(fn ($investment): string => ($investment->last_yield_date ? $investment->last_yield_date->copy()->addDay() : ($investment->acquired_at ?? $target))->toDateString())->sort()->first();
        $observations = $this->brapi->cdi($startDate, $target->toDateString());
        $processed = 0;
        foreach ($investments as $investment) {
            foreach ($observations as $observationDate => $dailyRate) {
                if (! Carbon::parse($observationDate)->isWeekday()) {
                    continue;
                }
                if ($investment->last_yield_date && $observationDate <= $investment->last_yield_date->toDateString()) {
                    continue;
                }
                if ($investment->acquired_at && $observationDate < $investment->acquired_at->toDateString()) {
                    continue;
                }
                $openingValue = (int) $investment->current_value;
                $dailyYield = (int) round($openingValue * (pow(1 + ($dailyRate / 100), ((float) $investment->cdi_percentage / 100)) - 1));
                if ($this->investments->accrueCdi($investment, $observationDate, $dailyRate, $dailyYield, $openingValue + $dailyYield)) {
                    $processed++;
                }
            }
        }

        return $processed;
    }
}
