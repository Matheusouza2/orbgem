<?php

namespace App\UseCases\Ai;

use App\Enums\WalletMemberRole;
use App\Models\FinancialSummary;
use App\Models\User;
use App\Services\Ai\FinancialSummaryService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GetFinancialSummaryUseCase
{
    public function __construct(private FinancialSummaryService $summaries, private WalletService $wallets, private WalletMembershipAuthorization $auth) {}

    public function execute(int $walletId, User $user): FinancialSummary
    {
        $wallet = $this->wallets->find($walletId) ?? throw new ModelNotFoundException;
        $this->auth->authorize($user, $wallet, WalletMemberRole::OWNER, WalletMemberRole::EDITOR, WalletMemberRole::VIEWER);

        return $this->summaries->forWallet($wallet->id);
    }
}
