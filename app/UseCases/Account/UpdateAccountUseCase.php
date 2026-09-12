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
use Illuminate\Validation\ValidationException;

class UpdateAccountUseCase
{
    public function __construct(
        private AccountService $accountService,
        private WalletService $walletService,
        private WalletMembershipAuthorization $membershipAuthorization,
    ) {}

    public function execute(int $accountId, AccountDTO $accountDTO, User $user): Account
    {
        $account = $this->accountService->find($accountId);
        if ($account === null) {
            throw new ModelNotFoundException;
        }

        $wallet = $this->walletService->find($account->wallet_id);
        if ($wallet === null) {
            throw new ModelNotFoundException;
        }

        $this->membershipAuthorization->authorize($user, $wallet, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);
        if ($accountDTO->walletId !== $account->wallet_id) {
            throw ValidationException::withMessages(['wallet_id' => 'The account wallet cannot be changed.']);
        }

        if ($accountDTO->ownerWalletMemberId !== null && ! $this->membershipAuthorization->memberBelongsToWallet($wallet, $accountDTO->ownerWalletMemberId)) {
            throw ValidationException::withMessages(['owner_wallet_member_id' => 'The account owner must belong to the account wallet.']);
        }

        return $this->accountService->update($account, $accountDTO);
    }
}
