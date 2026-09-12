<?php

namespace App\Repositories;

use App\DTO\RecurringTransactionDTO;
use App\Models\RecurringTransaction;
use Illuminate\Support\Collection;

interface RecurringTransactionRepositoryInterface
{
    public function create(RecurringTransactionDTO $dto): RecurringTransaction;

    public function forWallet(int $walletId): Collection;

    public function find(int $id): ?RecurringTransaction;

    public function update(RecurringTransaction $transaction, RecurringTransactionDTO $dto): RecurringTransaction;

    public function delete(RecurringTransaction $transaction): void;
}
