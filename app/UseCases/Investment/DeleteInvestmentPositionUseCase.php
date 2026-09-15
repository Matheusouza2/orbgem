<?php

namespace App\UseCases\Investment;

use App\Enums\WalletMemberRole;
use App\Models\InvestmentPosition;
use App\Models\User;
use App\Services\InvestmentPositionService;
use App\Services\WalletMembershipAuthorization;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteInvestmentPositionUseCase
{
    public function __construct(private InvestmentPositionService $positions, private WalletMembershipAuthorization $auth) {}

    public function execute(InvestmentPosition $position, User $user): void
    {
        $wallet = $this->auth->walletsFor($user)->firstWhere('id', $position->investment->wallet_id);
        if (! $wallet) {
            throw new AuthorizationException;
        }

        $this->auth->authorize($user, $wallet, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);
        $this->positions->delete($position);
    }
}
