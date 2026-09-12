<?php

namespace App\Repositories;

use App\DTO\RecurringTransactionDTO;
use App\Models\RecurringTransaction;
use Illuminate\Support\Collection;

class RecurringTransactionRepository implements RecurringTransactionRepositoryInterface
{
    public function create(RecurringTransactionDTO $dto): RecurringTransaction
    {
        return RecurringTransaction::query()->create($dto->toArray());
    }

    public function forWallet(int $walletId): Collection
    {
        return RecurringTransaction::query()->where('wallet_id', $walletId)->latest()->get();
    }

    public function find(int $id): ?RecurringTransaction
    {
        return RecurringTransaction::query()->find($id);
    }

    public function update(RecurringTransaction $transaction, RecurringTransactionDTO $dto): RecurringTransaction
    {
        $transaction->update($dto->toArray());

        return $transaction->refresh();
    }

    public function delete(RecurringTransaction $transaction): void
    {
        $transaction->delete();
    }
}
