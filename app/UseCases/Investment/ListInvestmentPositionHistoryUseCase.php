<?php

namespace App\UseCases\Investment;

use App\Enums\WalletMemberRole;
use App\Models\User;
use App\Services\InvestmentPositionService;
use App\Services\WalletMembershipAuthorization;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

class ListInvestmentPositionHistoryUseCase
{
    public function __construct(private InvestmentPositionService $positions, private WalletMembershipAuthorization $auth) {}

    public function execute(int $walletId, User $user): Collection
    {
        $wallet = $this->auth->walletsFor($user)->firstWhere('id', $walletId);
        if (! $wallet) {
            throw new AuthorizationException;
        }

        $this->auth->authorize($user, $wallet, WalletMemberRole::VIEWER, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);

        return $this->positions->historyForWallet($walletId);
    }
}
