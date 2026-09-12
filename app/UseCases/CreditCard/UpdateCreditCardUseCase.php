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

class UpdateCreditCardUseCase
{
    public function __construct(private CreditCardService $cards, private WalletService $wallets, private WalletMembershipAuthorization $auth) {}

    public function execute(CreditCard $card, CreditCardDTO $dto, User $user): CreditCard
    {
        if ($card->wallet_id !== $dto->walletId || ($wallet = $this->wallets->find($card->wallet_id)) === null) {
            throw new ModelNotFoundException;
        }
        $this->auth->authorize($user, $wallet, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);

        return $this->cards->update($card, $dto);
    }
}
