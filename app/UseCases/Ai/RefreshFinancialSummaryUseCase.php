<?php

namespace App\UseCases\Ai;

use App\Enums\WalletMemberRole;
use App\Jobs\RefreshFinancialSummary;
use App\Models\User;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RefreshFinancialSummaryUseCase
{
    public function __construct(private WalletService $wallets, private WalletMembershipAuthorization $auth) {}

    public function execute(int $walletId, User $user): void
    {
        $wallet = $this->wallets->find($walletId) ?? throw new ModelNotFoundException;
        $this->auth->authorize($user, $wallet, WalletMemberRole::OWNER, WalletMemberRole::EDITOR);

        RefreshFinancialSummary::dispatch($wallet->id, true);
    }
}
