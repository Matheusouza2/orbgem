<?php

namespace App\UseCases\Investment;

use App\Enums\WalletMemberRole;
use App\Models\Investment;
use App\Models\InvestmentPosition;
use App\Models\User;
use App\Services\InvestmentPositionService;
use App\Services\WalletMembershipAuthorization;
use Illuminate\Auth\Access\AuthorizationException;

class UpsertInvestmentPositionUseCase
{
    public function __construct(private InvestmentPositionService $positions, private WalletMembershipAuthorization $auth) {}

    public function execute(Investment $investment, array $attributes, User $user): InvestmentPosition
    {
        $wallet = $this->auth->walletsFor($user)->firstWhere('id', $investment->wallet_id);
        if (! $wallet) {
            throw new AuthorizationException;
        }

        $this->auth->authorize($user, $wallet, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);

        return $this->positions->upsert($investment->id, $attributes);
    }
}
