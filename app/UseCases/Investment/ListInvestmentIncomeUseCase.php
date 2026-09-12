<?php

namespace App\UseCases\Investment;

use App\Models\User;
use App\Services\ExternalInvestmentTransactionService;
use App\Services\WalletMembershipAuthorization;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

class ListInvestmentIncomeUseCase
{
    public function __construct(private ExternalInvestmentTransactionService $transactions, private WalletMembershipAuthorization $auth) {}

    public function execute(int $walletId, User $user, ?int $investmentId = null, ?string $from = null, ?string $to = null): Collection
    {
        if (! $this->auth->walletsFor($user)->firstWhere('id', $walletId)) {
            throw new AuthorizationException;
        }

        return $this->transactions->forWallet($walletId, $investmentId, $from, $to);
    }
}
