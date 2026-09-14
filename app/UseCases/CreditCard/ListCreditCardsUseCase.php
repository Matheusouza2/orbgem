<?php

namespace App\UseCases\CreditCard;

use App\DTO\CreditCardTransactionListDTO;
use App\Models\User;
use App\Services\CreditCardService;
use App\Services\WalletMembershipAuthorization;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

class ListCreditCardsUseCase
{
    public function __construct(private CreditCardService $cards, private WalletMembershipAuthorization $auth) {}

    public function execute(int $walletId, User $user, ?string $month = null): Collection
    {
        $wallet = $this->auth->walletsFor($user)->firstWhere('id', $walletId);
        if (! $wallet) {
            throw new AuthorizationException;
        }

        $cards = $this->cards->forWallet($walletId);

        if ($month === null) {
            return $cards;
        }

        return $cards->map(function ($card) use ($month) {
            $card->setAttribute('dashboard_balance', $this->cards->transactionsAmount(new CreditCardTransactionListDTO(
                walletId: $card->wallet_id,
                month: $month,
                creditCardId: $card->id,
                status: null,
                includeThirdParty: true,
                page: 1,
                perPage: 1,
            )));

            return $card;
        });
    }
}
