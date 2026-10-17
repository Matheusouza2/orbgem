<?php

namespace App\UseCases\Account;

use App\DTO\TransactionListFilterDTO;
use App\Enums\WalletMemberRole;
use App\Models\User;
use App\Services\AccountService;
use App\Services\TransactionService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ListAccountMovementsUseCase
{
    public function __construct(
        private AccountService $accounts,
        private WalletService $wallets,
        private TransactionService $transactions,
        private WalletMembershipAuthorization $membershipAuthorization,
    ) {}

    /** @param array<string, mixed> $filters
     * @return array{movements: LengthAwarePaginator, total_amount: int}
     */
    public function execute(int $accountId, array $filters, User $user): array
    {
        $account = $this->accounts->find($accountId);
        if ($account === null) {
            throw new ModelNotFoundException;
        }

        $wallet = $this->wallets->find($account->wallet_id);
        if ($wallet === null) {
            throw new ModelNotFoundException;
        }

        $this->membershipAuthorization->authorize(
            $user,
            $wallet,
            WalletMemberRole::VIEWER,
            WalletMemberRole::EDITOR,
            WalletMemberRole::OWNER,
        );

        $query = TransactionListFilterDTO::fromArray([
            ...$filters,
            'wallet_id' => $account->wallet_id,
            'account_id' => $account->id,
        ]);

        return $this->transactions->accountMovements($account, $query);
    }
}
