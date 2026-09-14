<?php

namespace App\UseCases\Account;

use App\Enums\WalletMemberRole;
use App\Models\User;
use App\Services\AccountBalanceCalculator;
use App\Services\AccountService;
use App\Services\TransactionService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

class ListAccountUseCase
{
    public function __construct(
        private AccountService $accountService,
        private WalletService $walletService,
        private WalletMembershipAuthorization $membershipAuthorization,
        private TransactionService $transactionService,
        private AccountBalanceCalculator $balanceCalculator,
    ) {}

    public function execute(int $walletId, User $user, ?string $month = null): Collection
    {
        $wallet = $this->walletService->find($walletId);

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

        $accounts = $this->accountService->listForWallet($walletId);

        if ($month === null) {
            return $accounts;
        }

        return $accounts->map(function ($account) use ($month) {
            $account->setAttribute('dashboard_balance', $this->balanceCalculator->calculate(
                $account->initial_balance,
                $this->transactionService->postedAmountsForAccountThroughMonth($account->id, $month),
            ));

            return $account;
        });
    }
}
