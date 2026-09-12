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

class CreateFinancialCommitmentUseCase
{
    public function __construct(private FinancialCommitmentService $commitments, private WalletService $wallets, private WalletMembershipAuthorization $auth) {}

    public function execute(FinancialCommitmentDTO $dto, User $user): FinancialCommitment
    {
        $wallet = $this->wallets->find($dto->walletId);
        if (! $wallet) {
            throw new ModelNotFoundException;
        }$this->auth->authorize($user, $wallet, WalletMemberRole::OWNER, WalletMemberRole::EDITOR);

        return $this->commitments->create($dto);
    }
}
