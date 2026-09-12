<?php

namespace App\UseCases\Wallet;

use App\DTO\ChangeWalletMemberRoleDTO;
use App\Enums\WalletMemberRole;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMember;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

class ChangeWalletMemberRoleUseCase
{
    public function __construct(
        private WalletService $walletService,
        private WalletMembershipAuthorization $membershipAuthorization,
    ) {}

    public function execute(ChangeWalletMemberRoleDTO $dto, User $user): WalletMember
    {
        return DB::transaction(function () use ($dto, $user): WalletMember {
            $wallet = $this->walletService->find($dto->walletId);
            if ($wallet === null) {
                throw (new ModelNotFoundException)->setModel(Wallet::class, [$dto->walletId]);
            }

            $this->membershipAuthorization->authorize($user, $wallet, WalletMemberRole::OWNER);
            if ($this->walletService->findMemberInWallet($dto->walletId, $dto->memberId) === null) {
                throw (new ModelNotFoundException)->setModel(WalletMember::class, [$dto->memberId]);
            }

            try {
                return $this->walletService->changeMemberRoleInWallet($dto->walletId, $dto->memberId, $dto->role);
            } catch (LogicException) {
                throw ValidationException::withMessages(['role' => 'The wallet must retain at least one OWNER.']);
            }
        });
    }
}
