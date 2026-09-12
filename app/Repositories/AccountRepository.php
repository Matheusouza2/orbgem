<?php

namespace App\Repositories;

use App\DTO\AccountDTO;
use App\Models\Account;
use Illuminate\Support\Collection;

class AccountRepository implements AccountRepositoryInterface
{
    public function create(AccountDTO $accountDTO): Account
    {
        return Account::query()->create($accountDTO->toArray());
    }

    public function update(Account $account, AccountDTO $accountDTO): Account
    {
        $account->update($accountDTO->toArray());

        return $account->refresh();
    }

    public function forWallet(int $walletId): Collection
    {
        return Account::query()->where('wallet_id', $walletId)->latest()->get();
    }

    public function find(int $accountId): ?Account
    {
        return Account::query()->find($accountId);
    }

    public function syncPluggyAccount(int $walletId, int $pluggyItemId, array $attributes): Account
    {
        return Account::query()->updateOrCreate(['wallet_id' => $walletId, 'pluggy_account_id' => $attributes['pluggy_account_id']], ['pluggy_item_id' => $pluggyItemId, ...$attributes]);
    }

    /** @param array<int, int> $accountIds */
    public function lockForTransfer(array $accountIds): Collection
    {
        return Account::query()->whereIn('id', $accountIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
    }
}
