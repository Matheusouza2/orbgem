<?php

namespace App\Repositories;

use App\DTO\MonthlySummaryDTO;
use App\DTO\TransactionDTO;
use App\DTO\TransactionListFilterDTO;
use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface TransactionRepositoryInterface
{
    public function create(TransactionDTO $transactionDTO): Transaction;

    public function findForReversal(int $transactionId): ?Transaction;

    public function lockForReversal(int $transactionId): ?Transaction;

    public function lockTransferGroupForReversal(string $transferGroupId): Collection;

    /** @param array<int, int> $transactionIds */
    public function lockReversalsForOriginals(array $transactionIds): Collection;

    public function forWallet(TransactionListFilterDTO $filters): LengthAwarePaginator;

    public function totalsForMonth(MonthlySummaryDTO $summary): array;

    public function postedAmountsForAccount(int $accountId, bool $includeThirdParty = true): Collection;

    public function recurringOccurrenceExists(int $recurringId, string $date): bool;

    public function commitmentOccurrenceExists(int $commitmentId, int $number): bool;

    public function postDueAutomatically(string $date): int;
}
