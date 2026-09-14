<?php

namespace App\UseCases\CreditCard;

use App\Enums\CreditCardInvoiceStatus;
use App\Enums\WalletMemberRole;
use App\Models\CreditCardPurchase;
use App\Models\User;
use App\Services\WalletMembershipAuthorization;
use Illuminate\Validation\ValidationException;

trait CreditCardMovementSupport
{
    private function authorizePurchase(CreditCardPurchase $purchase, User $user): void
    {
        app(WalletMembershipAuthorization::class)->authorize($user, $purchase->wallet, WalletMemberRole::OWNER, WalletMemberRole::EDITOR);
    }

    private function ensureOpenInvoices(iterable $installments): void
    {
        foreach ($installments as $installment) {
            if ($installment->invoice?->status === CreditCardInvoiceStatus::PAID) {
                throw ValidationException::withMessages(['purchase' => 'Movimentos de faturas pagas não podem ser alterados.']);
            }
        }
    }
}
