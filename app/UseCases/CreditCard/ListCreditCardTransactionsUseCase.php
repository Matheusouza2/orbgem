<?php

namespace App\UseCases\CreditCard;

use App\DTO\CreditCardTransactionListDTO;
use App\DTO\CreditCardTransactionListResult;
use App\Enums\WalletMemberRole;
use App\Models\CreditCard;
use App\Models\User;
use App\Services\CreditCardService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ListCreditCardTransactionsUseCase
{
    public function __construct(private CreditCardService $cards, private WalletService $wallets, private WalletMembershipAuthorization $authorization) {}

    public function execute(CreditCardTransactionListDTO $dto, CreditCard $card, User $user): CreditCardTransactionListResult
    {
        if ($card->wallet_id !== $dto->walletId || $this->wallets->find($dto->walletId) === null) {
            throw new ModelNotFoundException;
        }

        $this->authorization->authorize($user, $card->wallet, WalletMemberRole::OWNER, WalletMemberRole::EDITOR, WalletMemberRole::VIEWER);

        return new CreditCardTransactionListResult(
            transactions: $this->cards->transactions($dto),
            amount: $this->cards->transactionsAmount($dto),
        );
    }
}
