<?php

namespace App\UseCases\Investment;

use App\Enums\WalletMemberRole;
use App\Models\Investment;
use App\Models\User;
use App\Services\InvestmentService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DeleteInvestmentUseCase
{
    public function __construct(private InvestmentService $investments, private WalletService $wallets, private WalletMembershipAuthorization $auth) {}

    public function execute(Investment $investment, User $user): void
    {
        $wallet = $this->wallets->find($investment->wallet_id);
        if ($wallet === null) {
            throw new ModelNotFoundException;
        }
        $this->auth->authorize($user, $wallet, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);
        $this->investments->delete($investment);
    }
}
