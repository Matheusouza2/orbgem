<?php

namespace App\Services;

use App\Repositories\PlanningReportRepositoryInterface;
use Illuminate\Support\Collection;

class PlanningReportService
{
    public function __construct(private PlanningReportRepositoryInterface $repository) {}

    /** @return array<string, mixed> */
    public function summary(int $walletId, string $month): array
    {
        return $this->repository->summary($walletId, $month);
    }

    /** @return array<string, mixed> */
    public function consolidated(Collection $walletIds, string $month): array
    {
        $reports = $walletIds->map(fn (int $walletId): array => $this->summary($walletId, $month));
        $futureByMonth = [];

        foreach ($reports as $report) {
            foreach ($report['future_commitments_by_month'] as $period => $amount) {
                $futureByMonth[$period] = ($futureByMonth[$period] ?? 0) + $amount;
            }
        }

        $budgetTotal = (int) $reports->sum('budget_total');
        $budgetConsumed = (int) $reports->sum('budget_consumed');
        $futureCommitments = (int) $reports->sum('future_commitments');
        $financialCommitments = (int) $reports->sum('financial_commitments');

        return [
            'future_commitments' => $futureCommitments,
            'future_commitments_by_month' => $futureByMonth,
            'financial_commitments' => $financialCommitments,
            'budget_total' => $budgetTotal,
            'budget_consumed' => $budgetConsumed,
            'budget_remaining' => $budgetTotal - $budgetConsumed,
            'free_amount' => $budgetTotal - $budgetConsumed - $futureCommitments - $financialCommitments,
            'planning_consolidated' => [
                'budget' => ['total' => $budgetTotal, 'consumed' => $budgetConsumed, 'remaining' => $budgetTotal - $budgetConsumed],
                'commitments' => ['installments' => $futureCommitments, 'financial' => $financialCommitments, 'total' => $futureCommitments + $financialCommitments],
                'future_installments_by_month' => $futureByMonth,
                'free_amount' => $budgetTotal - $budgetConsumed - $futureCommitments - $financialCommitments,
            ],
        ];
    }
}
