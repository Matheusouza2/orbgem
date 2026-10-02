<?php

namespace App\UseCases\Investment;

use App\Enums\WalletMemberRole;
use App\Models\InvestmentPosition;
use App\Models\User;
use App\Services\InvestmentPositionService;
use App\Services\WalletMembershipAuthorization;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateInvestmentPositionUseCase
{
    public function __construct(private InvestmentPositionService $positions, private WalletMembershipAuthorization $auth) {}

    /** @param array<string, mixed> $attributes */
    public function execute(InvestmentPosition $position, array $attributes, User $user): InvestmentPosition
    {
        $wallet = $this->auth->walletsFor($user)->firstWhere('id', $position->investment->wallet_id);
        if (! $wallet) {
            throw new AuthorizationException;
        }

        $this->auth->authorize($user, $wallet, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);

        return $this->positions->update($position, $attributes);
    }
}
