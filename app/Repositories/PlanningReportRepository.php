<?php

namespace App\Repositories;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Budget;
use App\Models\FinancialCommitment;
use App\Models\Installment;
use App\Models\Transaction;
use Illuminate\Support\Carbon;

class PlanningReportRepository implements PlanningReportRepositoryInterface
{
    public function summary(int $walletId, string $month): array
    {
        $start = Carbon::createFromFormat('!Y-m', $month)->startOfMonth();
        $end = $start->copy()->addMonth();
        $rows = Transaction::query()
            ->includedInTotals()
            ->where('wallet_id', $walletId)
            ->whereBetween('competence_date', [$start->toDateString(), $end->copy()->subDay()->toDateString()])
            ->whereIn('status', [TransactionStatus::POSTED, TransactionStatus::PROJECTED])
            ->get(['amount', 'status', 'type']);
        $futureRows = Installment::query()->whereHas('purchase', fn ($query) => $query->where('wallet_id', $walletId))->where('competence_date', '>=', $end)->where('status', '!=', 'CANCELLED')->get(['amount', 'competence_date']);
        $futureInstallments = $futureRows->sum('amount');
        $futureByMonth = $futureRows->groupBy(fn ($row): string => $row->competence_date->format('Y-m'))->map(fn ($rows): int => (int) $rows->sum('amount'))->all();
        $commitments = FinancialCommitment::query()->where('wallet_id', $walletId)->where('active', true)->whereDate('end_date', '>=', $start)->sum('installment_amount');
        $budgetRows = Budget::query()->where('wallet_id', $walletId)->where('reference_month', $month)->where('active', true)->get();
        $budgetTotal = (int) $budgetRows->sum('amount');
        $budgetConsumed = (int) Transaction::query()->includedInTotals()->where('wallet_id', $walletId)->where('type', TransactionType::EXPENSE)->whereIn('status', [TransactionStatus::POSTED, TransactionStatus::PROJECTED])->whereBetween('competence_date', [$start->toDateString(), $end->copy()->subDay()->toDateString()])->whereIn('category_id', $budgetRows->pluck('category_id'))->sum('amount');

        $realizedExpenses = (int) $rows->where('type', TransactionType::EXPENSE)->where('status', TransactionStatus::POSTED)->sum('amount');
        $projectedExpenses = (int) $rows->where('type', TransactionType::EXPENSE)->where('status', TransactionStatus::PROJECTED)->sum('amount');
        $realizedIncome = (int) $rows->where('type', TransactionType::INCOME)->where('status', TransactionStatus::POSTED)->sum('amount');
        $projectedIncome = (int) $rows->where('type', TransactionType::INCOME)->where('status', TransactionStatus::PROJECTED)->sum('amount');
        $plannedExpenses = $projectedExpenses + (int) $futureInstallments + (int) $commitments;

        return [
            'future_commitments' => (int) $futureInstallments,
            'future_commitments_by_month' => $futureByMonth,
            'financial_commitments' => (int) $commitments,
            'budget_total' => $budgetTotal,
            'budget_consumed' => $budgetConsumed,
            'budget_remaining' => $budgetTotal - $budgetConsumed,
            'free_amount' => $budgetTotal - $budgetConsumed - (int) $futureInstallments - (int) $commitments,
            'planning_consolidated' => [
                'period' => ['month' => $month, 'from' => $start->toDateString(), 'to' => $end->copy()->subDay()->toDateString()],
                'budget' => ['total' => $budgetTotal, 'consumed' => $budgetConsumed, 'remaining' => $budgetTotal - $budgetConsumed],
                'expenses' => ['realized' => $realizedExpenses, 'projected' => $projectedExpenses, 'total' => $realizedExpenses + $projectedExpenses],
                'income' => ['realized' => $realizedIncome, 'projected' => $projectedIncome, 'total' => $realizedIncome + $projectedIncome],
                'commitments' => ['installments' => (int) $futureInstallments, 'financial' => (int) $commitments, 'total' => (int) $futureInstallments + (int) $commitments],
                'planned_expenses' => $plannedExpenses,
                'free_amount' => $budgetTotal - $budgetConsumed - $plannedExpenses,
                'future_installments_by_month' => $futureByMonth,
            ],
        ];
    }
}
