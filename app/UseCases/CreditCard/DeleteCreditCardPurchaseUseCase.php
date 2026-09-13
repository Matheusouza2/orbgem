<?php

namespace App\UseCases\CreditCard;

use App\Models\Transaction;
use App\Models\User;
use App\Services\CreditCardPurchaseService;
use App\Services\InstallmentService;
use Illuminate\Support\Facades\DB;

class DeleteCreditCardPurchaseUseCase
{
    use CreditCardMovementSupport;

    public function __construct(private CreditCardPurchaseService $purchases, private InstallmentService $installments) {}

    public function execute(int $purchaseId, User $user): void
    {
        DB::transaction(function () use ($purchaseId, $user): void {
            $purchase = $this->purchases->lockForUpdate($purchaseId);
            if ($purchase === null) {
                abort(404);
            }
            $purchase->load('wallet');
            $this->authorizePurchase($purchase, $user);
            $installments = $this->installments->lockForPurchase($purchase->id)->load('invoice');
            $this->ensureOpenInvoices($installments);
            Transaction::query()->whereIn('installment_id', $installments->pluck('id'))->delete();
            $purchase->delete();
        });
    }
}
