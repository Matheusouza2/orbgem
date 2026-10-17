<?php

namespace App\UseCases\Investment;

use App\Enums\WalletMemberRole;
use App\Models\InvestmentIncome;
use App\Models\User;
use App\Services\ExternalInvestmentTransactionService;
use App\Services\WalletMembershipAuthorization;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteInvestmentIncomeUseCase
{
    public function __construct(
        private ExternalInvestmentTransactionService $income,
        private WalletMembershipAuthorization $auth,
    ) {}

    public function execute(InvestmentIncome $income, User $user): void
    {
        $income->loadMissing('investment');
        $wallet = $this->auth->walletsFor($user)->firstWhere('id', $income->investment->wallet_id);
        if (! $wallet) {
            throw new AuthorizationException;
        }
        $this->auth->authorize($user, $wallet, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);

        $this->income->deleteManual($income);
    }
}
