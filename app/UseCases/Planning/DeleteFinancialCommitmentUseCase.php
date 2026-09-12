<?php

namespace App\UseCases\Planning;

use App\Enums\WalletMemberRole;
use App\Models\FinancialCommitment;
use App\Models\User;
use App\Services\FinancialCommitmentService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DeleteFinancialCommitmentUseCase
{
    public function __construct(private FinancialCommitmentService $service, private WalletService $wallets, private WalletMembershipAuthorization $auth) {}

    public function execute(FinancialCommitment $commitment, User $user): void
    {
        $wallet = $this->wallets->find($commitment->wallet_id);
        if ($wallet === null) {
            throw new ModelNotFoundException;
        }
        $this->auth->authorize($user, $wallet, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);
        $this->service->delete($commitment);
    }
}
