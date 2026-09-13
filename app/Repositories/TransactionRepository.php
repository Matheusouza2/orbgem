<?php

namespace App\Repositories;

use App\DTO\MonthlySummaryDTO;
use App\DTO\TransactionDTO;
use App\DTO\TransactionListFilterDTO;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TransactionRepository implements TransactionRepositoryInterface
{
    public function create(TransactionDTO $transactionDTO): Transaction
    {
        return Transaction::query()->create($transactionDTO->toArray());
    }

    public function findForReversal(int $transactionId): ?Transaction
    {
        return Transaction::query()->find($transactionId);
    }

    public function lockForUpdate(int $transactionId): ?Transaction
    {
        return Transaction::query()->lockForUpdate()->find($transactionId);
    }

    public function delete(Transaction $transaction): void
    {
        $transaction->delete();
    }

    public function lockForReversal(int $transactionId): ?Transaction
    {
        return Transaction::query()->lockForUpdate()->find($transactionId);
    }

    public function lockTransferGroupForReversal(string $transferGroupId): Collection
    {
        return Transaction::query()
            ->where('transfer_group_id', $transferGroupId)
            ->whereNull('reversal_of_transaction_id')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    public function lockReversalsForOriginals(array $transactionIds): Collection
    {
        return Transaction::query()
            ->whereIn('reversal_of_transaction_id', $transactionIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    public function forWallet(TransactionListFilterDTO $filters): LengthAwarePaginator
    {
        $query = Transaction::query()
            ->withExists('reversals')
            ->where('wallet_id', $filters->walletId)
            ->when(! $filters->includeThirdParty, fn ($query) => $query->where('is_third_party', false))
            ->orderBy($filters->sortBy, $filters->sortDirection)
            ->orderBy('id', $filters->sortDirection);
        if ($filters->accountId !== null) {
            $query->where('account_id', $filters->accountId);
        }
        if ($filters->merchantId !== null) {
            $query->where('merchant_id', $filters->merchantId);
        }
        if ($filters->type !== null) {
            $query->where('type', $filters->type);
        }
        if ($filters->status !== null) {
            $query->where('status', $filters->status);
        }
        if ($filters->month !== null) {
            [$start, $end] = $this->monthBounds($filters->month);
            $query->where('competence_date', '>=', $start)->where('competence_date', '<', $end);
        }

        if ($filters->transactionDateFrom !== null) {
            $query->whereDate('transaction_date', '>=', $filters->transactionDateFrom);
        }
        if ($filters->transactionDateTo !== null) {
            $query->whereDate('transaction_date', '<=', $filters->transactionDateTo);
        }
        if ($filters->competenceDateFrom !== null) {
            $query->whereDate('competence_date', '>=', $filters->competenceDateFrom);
        }
        if ($filters->competenceDateTo !== null) {
            $query->whereDate('competence_date', '<=', $filters->competenceDateTo);
        }

        return $query->paginate($filters->perPage, ['*'], 'page', $filters->page);
    }

    public function totalsForMonth(MonthlySummaryDTO $summary): array
    {
        [$start, $end] = $this->monthBounds($summary->month);
        $rows = Transaction::query()->with('category')->includedInTotals()->when(! $summary->includeThirdParty, fn ($query) => $query->where('is_third_party', false))->where('wallet_id', $summary->walletId)->where('competence_date', '>=', $start)->where('competence_date', '<', $end)->whereIn('status', [TransactionStatus::POSTED, TransactionStatus::PROJECTED])->get();

        return [
            'actual_expenses' => $rows->where('type', TransactionType::EXPENSE)->where('status', TransactionStatus::POSTED)->sum('amount'),
            'forecast_expenses' => $rows->where('type', TransactionType::EXPENSE)->sum('amount'),
            'actual_income' => $rows->where('type', TransactionType::INCOME)->where('status', TransactionStatus::POSTED)->sum('amount'),
            'forecast_income' => $rows->where('type', TransactionType::INCOME)->sum('amount'),
            'expense_by_category' => $this->expensesByCategory($rows),
            'income_status_breakdown' => $this->statusBreakdown($rows, TransactionType::INCOME),
            'expense_status_breakdown' => $this->statusBreakdown($rows, TransactionType::EXPENSE),
        ];
    }

    /** @return array<int, array{category_id: int|null, category_name: string, amount: int}> */
    private function expensesByCategory(Collection $rows): array
    {
        return $rows
            ->where('type', TransactionType::EXPENSE)
            ->where('status', TransactionStatus::POSTED)
            ->groupBy(fn (Transaction $transaction): ?int => $transaction->category_id)
            ->map(function (Collection $categoryRows, $categoryId): array {
                $category = $categoryRows->first()->category;

                return [
                    'category_id' => $categoryId === '' || $categoryId === null ? null : (int) $categoryId,
                    'category_name' => $category?->name ?? 'Sem categoria',
                    'amount' => (int) $categoryRows->sum('amount'),
                ];
            })
            ->sort(function (array $first, array $second): int {
                $amountOrder = $second['amount'] <=> $first['amount'];
                if ($amountOrder !== 0) {
                    return $amountOrder;
                }

                if ($first['category_id'] === null) {
                    return 1;
                }
                if ($second['category_id'] === null) {
                    return -1;
                }

                return $first['category_id'] <=> $second['category_id'];
            })
            ->values()
            ->all();
    }

    /** @return array{posted: int, upcoming: int, overdue: int, distant: int} */
    private function statusBreakdown(Collection $rows, TransactionType $type): array
    {
        $today = Carbon::today();
        $limit = $today->copy()->addDays(7);
        $breakdown = ['posted' => 0, 'upcoming' => 0, 'overdue' => 0, 'distant' => 0];

        $rows->where('type', $type)->each(function (Transaction $transaction) use (&$breakdown, $today, $limit): void {
            if ($transaction->status === TransactionStatus::POSTED) {
                $breakdown['posted'] += (int) $transaction->amount;

                return;
            }

            $dueDate = $transaction->due_date;
            if ($dueDate !== null && $dueDate->isBefore($today)) {
                $breakdown['overdue'] += (int) $transaction->amount;
            } elseif ($dueDate !== null && $dueDate->lessThanOrEqualTo($limit)) {
                $breakdown['upcoming'] += (int) $transaction->amount;
            } else {
                $breakdown['distant'] += (int) $transaction->amount;
            }
        });

        return $breakdown;
    }

    /** @return array{string, string} */
    private function monthBounds(string $month): array
    {
        $start = Carbon::createFromFormat('!Y-m', $month)->startOfMonth();

        return [$start->toDateString(), $start->copy()->addMonth()->toDateString()];
    }

    public function postedAmountsForAccount(int $accountId, bool $includeThirdParty = true): Collection
    {
        return Transaction::query()->when(! $includeThirdParty, fn ($query) => $query->where('is_third_party', false))->where('account_id', $accountId)->where('status', TransactionStatus::POSTED)->get(['effect', 'amount']);
    }

    public function recurringOccurrenceExists(int $recurringId, string $date): bool
    {
        return Transaction::query()->where('recurring_transaction_id', $recurringId)->whereDate('transaction_date', $date)->exists();
    }

    public function commitmentOccurrenceExists(int $commitmentId, int $number): bool
    {
        return Transaction::query()->where('financial_commitment_id', $commitmentId)->where('notes', 'like', '%parcela '.$number.'/%')->exists();
    }

    public function postDueAutomatically(string $date): int
    {
        return Transaction::query()
            ->where('status', TransactionStatus::PROJECTED)
            ->where('auto_post_on_due_date', true)
            ->whereDate('due_date', '<=', $date)
            ->update(['status' => TransactionStatus::POSTED, 'paid_at' => now()]);
    }
}
