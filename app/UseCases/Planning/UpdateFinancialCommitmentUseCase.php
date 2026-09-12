<?php

namespace App\UseCases\Planning;

use App\DTO\FinancialCommitmentDTO;
use App\Enums\WalletMemberRole;
use App\Models\FinancialCommitment;
use App\Models\User;
use App\Services\FinancialCommitmentService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateFinancialCommitmentUseCase
{
    public function __construct(private FinancialCommitmentService $service, private WalletService $wallets, private WalletMembershipAuthorization $auth) {}

    public function execute(FinancialCommitment $commitment, FinancialCommitmentDTO $dto, User $user): FinancialCommitment
    {
        if ($commitment->wallet_id !== $dto->walletId || ($wallet = $this->wallets->find($commitment->wallet_id)) === null) {
            throw new ModelNotFoundException;
        }
        $this->auth->authorize($user, $wallet, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);

        return $this->service->update($commitment, $dto);
    }
}
