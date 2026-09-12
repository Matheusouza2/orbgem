<?php

namespace App\UseCases\CreditCard;

use App\Models\User;
use App\Services\CreditCardService;
use App\Services\WalletMembershipAuthorization;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

class ListCreditCardsUseCase
{
    public function __construct(private CreditCardService $cards, private WalletMembershipAuthorization $auth) {}

    public function execute(int $walletId, User $user): Collection
    {
        $wallet = $this->auth->walletsFor($user)->firstWhere('id', $walletId);
        if (! $wallet) {
            throw new AuthorizationException;
        }

        return $this->cards->forWallet($walletId);
    }
}
