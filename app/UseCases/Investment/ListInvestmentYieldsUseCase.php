<?php

namespace App\UseCases\Investment;

use App\Models\Investment;
use App\Models\User;
use App\Services\InvestmentService;
use App\Services\WalletMembershipAuthorization;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

class ListInvestmentYieldsUseCase
{
    public function __construct(private InvestmentService $investments, private WalletMembershipAuthorization $auth) {}

    public function execute(Investment $investment, User $user, ?string $from = null, ?string $to = null): Collection
    {
        if (! $this->auth->walletsFor($user)->firstWhere('id', $investment->wallet_id)) {
            throw new AuthorizationException;
        }

        return $this->investments->yields($investment, $from, $to);
    }
}
