<?php

namespace App\UseCases\Investment;

use App\DTO\InvestmentDTO;
use App\Enums\WalletMemberRole;
use App\Models\Investment;
use App\Models\User;
use App\Services\InvestmentService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateInvestmentUseCase
{
    public function __construct(private InvestmentService $investments, private WalletService $wallets, private WalletMembershipAuthorization $auth) {}

    public function execute(Investment $investment, InvestmentDTO $dto, User $user): Investment
    {
        if ($investment->wallet_id !== $dto->walletId || ($wallet = $this->wallets->find($investment->wallet_id)) === null) {
            throw new ModelNotFoundException;
        }
        $this->auth->authorize($user, $wallet, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);

        return $this->investments->update($investment, $dto);
    }
}
