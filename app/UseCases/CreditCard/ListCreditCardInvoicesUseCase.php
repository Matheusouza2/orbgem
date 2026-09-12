<?php

namespace App\UseCases\CreditCard;

use App\DTO\CreditCardInvoiceListDTO;
use App\Enums\WalletMemberRole;
use App\Models\User;
use App\Services\CreditCardInvoiceService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

class ListCreditCardInvoicesUseCase
{
    public function __construct(private CreditCardInvoiceService $invoices, private WalletService $wallets, private WalletMembershipAuthorization $auth) {}

    public function execute(CreditCardInvoiceListDTO $dto, User $user): Collection
    {
        $wallet = $this->wallets->find($dto->walletId);
        if (! $wallet) {
            throw new ModelNotFoundException;
        } $this->auth->authorize($user, $wallet, WalletMemberRole::OWNER, WalletMemberRole::EDITOR, WalletMemberRole::VIEWER);

        return $this->invoices->forWallet($dto);
    }
}
