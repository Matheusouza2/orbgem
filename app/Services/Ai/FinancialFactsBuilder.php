<?php

namespace App\Services\Ai;

use App\Enums\TransactionType;
use App\Repositories\FinancialSummaryRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Agrega os dados da carteira em números determinísticos (centavos). Nenhuma descrição livre,
 * nome de pessoa, e-mail ou documento sai daqui: só categorias, estabelecimentos cadastrados e valores.
 */
class FinancialFactsBuilder
{
    public const MONTHS = 6;

    public function __construct(private FinancialSummaryRepositoryInterface $repository) {}

    /** @return array<string, mixed> */
    public function build(int $walletId): array
    {
        $currentMonth = Carbon::today()->startOfMonth();
        $transactions = $this->repository->postedTransactionsSince($walletId, $currentMonth->copy()->subMonths(self::MONTHS - 1));

        return [
            'reference_month' => $currentMonth->format('Y-m'),
            'months' => $this->months($transactions, $currentMonth),
            'top_expense_categories' => $this->topExpenses($transactions, fn ($t): string => $t->category?->name ?? 'Sem categoria'),
            'top_expense_merchants' => $this->topExpenses($transactions->filter(fn ($t): bool => $t->merchant !== null), fn ($t): string => $t->merchant->name),
            'portfolio' => $this->portfolio($walletId),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function months(Collection $transactions, Carbon $currentMonth): array
    {
        $months = [];
        for ($i = self::MONTHS - 1; $i >= 0; $i--) {
            $month = $currentMonth->copy()->subMonths($i)->format('Y-m');
            $inMonth = $transactions->filter(fn ($t): bool => $t->transaction_date->format('Y-m') === $month);
            $income = (int) $inMonth->where('type', TransactionType::INCOME)->sum('amount');
            $expense = (int) $inMonth->where('type', TransactionType::EXPENSE)->sum('amount');
            $months[] = ['month' => $month, 'income_cents' => $income, 'expense_cents' => $expense, 'net_cents' => $income - $expense, 'transaction_count' => $inMonth->count()];
        }

        return $months;
    }

    /** @return list<array<string, mixed>> */
    private function topExpenses(Collection $transactions, callable $label, int $limit = 10): array
    {
        $expenses = $transactions->where('type', TransactionType::EXPENSE);
        $total = (int) $expenses->sum('amount');

        return $expenses->groupBy($label)
            ->map(fn (Collection $group, string $name): array => ['name' => $name, 'total_cents' => (int) $group->sum('amount'), 'count' => $group->count(), 'share_percent' => $total > 0 ? round($group->sum('amount') / $total * 100, 1) : 0.0])
            ->sortByDesc('total_cents')->take($limit)->values()->all();
    }

    /** @return array<string, mixed> */
    private function portfolio(int $walletId): array
    {
        $investments = $this->repository->activeInvestments($walletId);
        $current = (int) $investments->sum('current_value');
        $invested = (int) $investments->sum('invested_amount');
        $share = fn (int $value): float => $current > 0 ? round($value / $current * 100, 1) : 0.0;

        return [
            'invested_cents' => $invested,
            'current_value_cents' => $current,
            'profit_cents' => $current - $invested,
            'profit_percent' => $invested > 0 ? round(($current - $invested) / $invested * 100, 2) : 0.0,
            'allocation_by_type' => $investments->groupBy(fn ($i): string => $i->type->value)
                ->map(fn (Collection $group, string $type): array => ['type' => $type, 'current_value_cents' => (int) $group->sum('current_value'), 'share_percent' => $share((int) $group->sum('current_value'))])
                ->sortByDesc('current_value_cents')->values()->all(),
            'positions' => $investments->sortByDesc('current_value')->take(15)->map(fn ($i): array => [
                'name' => $i->name, 'ticker' => $i->ticker, 'type' => $i->type->value,
                'invested_cents' => (int) $i->invested_amount, 'current_value_cents' => (int) $i->current_value,
                'profit_percent' => $i->invested_amount > 0 ? round(($i->current_value - $i->invested_amount) / $i->invested_amount * 100, 2) : 0.0,
                'share_percent' => $share((int) $i->current_value), 'cdi_percentage' => $i->cdi_linked ? (float) $i->cdi_percentage : null,
            ])->values()->all(),
        ];
    }
}
