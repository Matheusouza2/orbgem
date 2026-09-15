<?php

namespace App\UseCases\Investment;

use App\Enums\WalletMemberRole;
use App\Models\InvestmentIncome;
use App\Models\User;
use App\Services\ExternalInvestmentTransactionService;
use App\Services\InvestmentService;
use App\Services\WalletMembershipAuthorization;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CreateInvestmentIncomeUseCase
{
    public function __construct(private ExternalInvestmentTransactionService $income, private InvestmentService $investments, private WalletMembershipAuthorization $auth) {}

    public function execute(int $investmentId, int $amount, string $date, User $user): InvestmentIncome
    {
        $investment = $this->investments->find($investmentId);
        if ($investment === null) {
            throw new ModelNotFoundException;
        }

        $wallet = $this->auth->walletsFor($user)->firstWhere('id', $investment->wallet_id);
        if (! $wallet) {
            throw new AuthorizationException;
        }

        $this->auth->authorize($user, $wallet, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);

        return $this->income->createManual($investment, $amount, $date);
    }
}
