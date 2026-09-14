<?php

namespace App\Services;

use App\DTO\MonthlySummaryDTO;
use App\DTO\TransactionDTO;
use App\DTO\TransactionListFilterDTO;
use App\Models\Transaction;
use App\Repositories\TransactionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TransactionService
{
    public function __construct(private TransactionRepositoryInterface $transactionRepository) {}

    public function create(TransactionDTO $dto): Transaction
    {
        return $this->transactionRepository->create($dto);
    }

    public function find(int $transactionId): ?Transaction
    {
        return $this->transactionRepository->find($transactionId);
    }

    public function findForReversal(int $transactionId): ?Transaction
    {
        return $this->transactionRepository->findForReversal($transactionId);
    }

    public function lockForUpdate(int $transactionId): ?Transaction
    {
        return $this->transactionRepository->lockForUpdate($transactionId);
    }

    public function delete(Transaction $transaction): void
    {
        $this->transactionRepository->delete($transaction);
    }

    public function lockForReversal(int $transactionId): ?Transaction
    {
        return $this->transactionRepository->lockForReversal($transactionId);
    }

    public function lockTransferGroupForReversal(string $transferGroupId): Collection
    {
        return $this->transactionRepository->lockTransferGroupForReversal($transferGroupId);
    }

    /** @param array<int, int> $transactionIds */
    public function lockReversalsForOriginals(array $transactionIds): Collection
    {
        return $this->transactionRepository->lockReversalsForOriginals($transactionIds);
    }

    public function listForWallet(TransactionListFilterDTO $filters): LengthAwarePaginator
    {
        return $this->transactionRepository->forWallet($filters);
    }

    public function monthlyTotals(MonthlySummaryDTO $summary): array
    {
        return $this->transactionRepository->totalsForMonth($summary);
    }

    public function postedAmountsForAccount(int $accountId, bool $includeThirdParty = true): Collection
    {
        return $this->transactionRepository->postedAmountsForAccount($accountId, $includeThirdParty);
    }

    public function recurringOccurrenceExists(int $recurringId, string $date): bool
    {
        return $this->transactionRepository->recurringOccurrenceExists($recurringId, $date);
    }

    public function commitmentOccurrenceExists(int $commitmentId, int $number): bool
    {
        return $this->transactionRepository->commitmentOccurrenceExists($commitmentId, $number);
    }

    public function postDueAutomatically(string $date): int
    {
        return $this->transactionRepository->postDueAutomatically($date);
    }

    public function effectivateForInvoice(int $invoiceId, Carbon $paidAt): int
    {
        return $this->transactionRepository->effectivateForInvoice($invoiceId, $paidAt);
    }
}
