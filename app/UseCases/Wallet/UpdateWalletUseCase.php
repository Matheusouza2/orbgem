<?php

namespace App\UseCases\Wallet;

use App\DTO\WalletDTO;
use App\Enums\WalletMemberRole;
use App\Models\User;
use App\Models\Wallet;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateWalletUseCase
{
    public function __construct(
        private WalletService $walletService,
        private WalletMembershipAuthorization $authorization,
    ) {}

    public function execute(int $walletId, WalletDTO $walletDTO, User $user): Wallet
    {
        $wallet = $this->walletService->find($walletId);

        if ($wallet === null) {
            throw new ModelNotFoundException;
        }

        $this->authorization->authorize($user, $wallet, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);

        return $this->walletService->updateWallet($wallet, $walletDTO);
    }
}
