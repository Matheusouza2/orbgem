<?php

namespace App\UseCases\Account;

use App\DTO\AccountDTO;
use App\Enums\WalletMemberRole;
use App\Models\Account;
use App\Models\User;
use App\Services\AccountService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateAccountUseCase
{
    public function __construct(
        private AccountService $accountService,
        private WalletService $walletService,
        private WalletMembershipAuthorization $membershipAuthorization,
    ) {}

    public function execute(AccountDTO $accountDTO, User $user): Account
    {
        return DB::transaction(function () use ($accountDTO, $user): Account {
            $wallet = $this->walletService->find($accountDTO->walletId);

            if ($wallet === null) {
                throw new ModelNotFoundException;
            }

            $this->membershipAuthorization->authorize($user, $wallet, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);

            if ($accountDTO->ownerWalletMemberId !== null) {
                $ownerMember = $this->membershipAuthorization->memberBelongsToWallet($wallet, $accountDTO->ownerWalletMemberId);

                if (! $ownerMember) {
                    throw ValidationException::withMessages([
                        'owner_wallet_member_id' => 'The account owner must belong to the account wallet.',
                    ]);
                }
            }

            return $this->accountService->create($accountDTO);
        });
    }
}
