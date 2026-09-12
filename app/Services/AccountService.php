<?php

namespace App\Services;

use App\DTO\AccountDTO;
use App\Models\Account;
use App\Repositories\AccountRepositoryInterface;
use Illuminate\Support\Collection;

class AccountService
{
    public function __construct(private AccountRepositoryInterface $accountRepository) {}

    public function create(AccountDTO $accountDTO): Account
    {
        return $this->accountRepository->create($accountDTO);
    }

    public function update(Account $account, AccountDTO $accountDTO): Account
    {
        return $this->accountRepository->update($account, $accountDTO);
    }

    public function listForWallet(int $walletId): Collection
    {
        return $this->accountRepository->forWallet($walletId);
    }

    public function find(int $accountId): ?Account
    {
        return $this->accountRepository->find($accountId);
    }

    public function syncPluggyAccount(int $walletId, int $pluggyItemId, array $attributes): Account
    {
        return $this->accountRepository->syncPluggyAccount($walletId, $pluggyItemId, $attributes);
    }

    /** @param array<int, int> $accountIds */
    public function lockForTransfer(array $accountIds): Collection
    {
        return $this->accountRepository->lockForTransfer($accountIds);
    }
}
