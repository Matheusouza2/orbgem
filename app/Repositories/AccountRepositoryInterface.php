<?php

namespace App\Repositories;

use App\DTO\AccountDTO;
use App\Models\Account;
use Illuminate\Support\Collection;

interface AccountRepositoryInterface
{
    public function create(AccountDTO $accountDTO): Account;

    public function update(Account $account, AccountDTO $accountDTO): Account;

    public function forWallet(int $walletId): Collection;

    public function find(int $accountId): ?Account;

    public function syncPluggyAccount(int $walletId, int $pluggyItemId, array $attributes): Account;

    /** @param array<int, int> $accountIds */
    public function lockForTransfer(array $accountIds): Collection;
}
