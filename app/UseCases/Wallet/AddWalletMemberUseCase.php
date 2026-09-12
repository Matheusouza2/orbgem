<?php

namespace App\UseCases\Wallet;

use App\DTO\AddWalletMemberDTO;
use App\Enums\WalletMemberRole;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMember;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class AddWalletMemberUseCase
{
    public function __construct(
        private WalletService $walletService,
        private WalletMembershipAuthorization $membershipAuthorization,
    ) {}

    public function execute(AddWalletMemberDTO $dto, User $user): WalletMember
    {
        return DB::transaction(function () use ($dto, $user): WalletMember {
            $wallet = $this->walletService->find($dto->walletId);
            if ($wallet === null) {
                throw (new ModelNotFoundException)->setModel(Wallet::class, [$dto->walletId]);
            }

            $this->membershipAuthorization->authorize($user, $wallet, WalletMemberRole::OWNER);
            $target = $this->walletService->findUser($dto->userId);
            if ($target === null) {
                throw (new ModelNotFoundException)->setModel(User::class, [$dto->userId]);
            }

            return $this->walletService->addMemberToWallet($wallet, $target, $dto->role);
        });
    }
}
