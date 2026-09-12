<?php

namespace App\UseCases\CreditCard;

use App\DTO\CreditCardDTO;
use App\Enums\WalletMemberRole;
use App\Models\CreditCard;
use App\Models\User;
use App\Services\CreditCardService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class CreateCreditCardUseCase
{
    public function __construct(private CreditCardService $cards, private WalletService $wallets, private WalletMembershipAuthorization $auth) {}

    public function execute(CreditCardDTO $dto, User $user): CreditCard
    {
        $wallet = $this->wallets->find($dto->walletId);
        if (! $wallet) {
            throw new ModelNotFoundException;
        } $this->auth->authorize($user, $wallet, WalletMemberRole::OWNER, WalletMemberRole::EDITOR);
        if ($dto->ownerWalletMemberId !== null && ! $this->auth->memberBelongsToWallet($wallet, $dto->ownerWalletMemberId)) {
            throw ValidationException::withMessages(['owner_wallet_member_id' => 'The owner must belong to the wallet.']);
        }

        return $this->cards->create($dto);
    }
}
