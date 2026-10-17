<?php

namespace App\UseCases\Investment;

use App\Enums\WalletMemberRole;
use App\Models\InvestmentIncome;
use App\Models\User;
use App\Services\ExternalInvestmentTransactionService;
use App\Services\InvestmentService;
use App\Services\WalletMembershipAuthorization;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class UpdateInvestmentIncomeUseCase
{
    public function __construct(
        private ExternalInvestmentTransactionService $income,
        private InvestmentService $investments,
        private WalletMembershipAuthorization $auth,
    ) {}

    /** @param array{investment_id: int, amount: int, transaction_date: string} $attributes */
    public function execute(InvestmentIncome $income, array $attributes, User $user): InvestmentIncome
    {
        $income->loadMissing('investment');
        $wallet = $this->auth->walletsFor($user)->firstWhere('id', $income->investment->wallet_id);
        if (! $wallet) {
            throw new AuthorizationException;
        }
        $this->auth->authorize($user, $wallet, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);

        $investment = $this->investments->find($attributes['investment_id']);
        if ($investment === null || $investment->wallet_id !== $wallet->id) {
            throw ValidationException::withMessages(['investment_id' => 'O investimento deve pertencer à mesma carteira do rendimento.']);
        }

        return $this->income->updateManual($income, $investment, $attributes['amount'], $attributes['transaction_date']);
    }
}
