<?php

namespace App\UseCases\Investment;

use App\Models\User;
use App\Services\InvestmentService;
use App\Services\WalletMembershipAuthorization;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

class ListInvestmentsUseCase
{
    public function __construct(private InvestmentService $investments, private WalletMembershipAuthorization $auth) {}

    public function execute(int $walletId, User $user): Collection
    {
        if (! $this->auth->walletsFor($user)->firstWhere('id', $walletId)) {
            throw new AuthorizationException;
        }

        return $this->investments->forWallet($walletId);
    }
}
