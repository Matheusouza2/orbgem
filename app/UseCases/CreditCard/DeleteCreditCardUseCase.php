<?php

namespace App\UseCases\CreditCard;

use App\Enums\WalletMemberRole;
use App\Models\CreditCard;
use App\Models\User;
use App\Services\CreditCardService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DeleteCreditCardUseCase
{
    public function __construct(private CreditCardService $cards, private WalletService $wallets, private WalletMembershipAuthorization $auth) {}

    public function execute(CreditCard $card, User $user): void
    {
        $wallet = $this->wallets->find($card->wallet_id);
        if ($wallet === null) {
            throw new ModelNotFoundException;
        }
        $this->auth->authorize($user, $wallet, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);
        $this->cards->delete($card);
    }
}
