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

class CreateInvestmentUseCase
{
    public function __construct(private InvestmentService $investments, private WalletService $wallets, private WalletMembershipAuthorization $auth) {}

    public function execute(InvestmentDTO $dto, User $user): Investment
    {
        $wallet = $this->wallets->find($dto->walletId);
        if ($wallet === null) {
            throw new ModelNotFoundException;
        }
        $this->auth->authorize($user, $wallet, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);

        return $this->investments->create($dto);
    }
}
