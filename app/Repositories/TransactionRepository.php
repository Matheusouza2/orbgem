<?php

namespace App\Repositories;

use App\DTO\MonthlySummaryDTO;
use App\DTO\TransactionDTO;
use App\DTO\TransactionListFilterDTO;
use App\Enums\TransactionStatus;
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
        $rows = Transaction::query()->includedInTotals()->where('wallet_id', $summary->walletId)->where('competence_date', '>=', $start)->where('competence_date', '<', $end)->whereIn('status', [TransactionStatus::POSTED, TransactionStatus::PROJECTED])->get();

        return ['actual_expenses' => $rows->where('type', 'EXPENSE')->where('status', TransactionStatus::POSTED)->sum('amount'), 'forecast_expenses' => $rows->where('type', 'EXPENSE')->sum('amount'), 'actual_income' => $rows->where('type', 'INCOME')->where('status', TransactionStatus::POSTED)->sum('amount'), 'forecast_income' => $rows->where('type', 'INCOME')->sum('amount')];
    }

    /** @return array{string, string} */
    private function monthBounds(string $month): array
    {
        $start = Carbon::createFromFormat('!Y-m', $month)->startOfMonth();

        return [$start->toDateString(), $start->copy()->addMonth()->toDateString()];
    }

    public function postedAmountsForAccount(int $accountId): Collection
    {
        return Transaction::query()->where('account_id', $accountId)->where('status', TransactionStatus::POSTED)->get(['effect', 'amount']);
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
