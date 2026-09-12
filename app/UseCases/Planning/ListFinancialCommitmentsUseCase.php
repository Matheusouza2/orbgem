<?php

namespace App\UseCases\Planning;

use App\Enums\WalletMemberRole;
use App\Models\User;
use App\Services\FinancialCommitmentService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

class ListFinancialCommitmentsUseCase
{
    public function __construct(private FinancialCommitmentService $commitments, private WalletService $wallets, private WalletMembershipAuthorization $auth) {}

    public function execute(int $walletId, User $user): Collection
    {
        $wallet = $this->wallets->find($walletId);
        if (! $wallet) {
            throw new ModelNotFoundException;
        }$this->auth->authorize($user, $wallet, WalletMemberRole::OWNER, WalletMemberRole::EDITOR, WalletMemberRole::VIEWER);

        return $this->commitments->forWallet($walletId);
    }
}
