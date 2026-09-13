<?php

namespace App\UseCases\CreditCard;

use App\Models\CreditCardPurchase;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CreditCardPurchaseService;
use App\Services\InstallmentService;
use Illuminate\Support\Facades\DB;

class UpdateCreditCardPurchaseUseCase
{
    use CreditCardMovementSupport;

    public function __construct(private CreditCardPurchaseService $purchases, private InstallmentService $installments) {}

    public function execute(int $purchaseId, array $attributes, User $user): CreditCardPurchase
    {
        return DB::transaction(function () use ($purchaseId, $attributes, $user): CreditCardPurchase {
            $purchase = $this->purchases->lockForUpdate($purchaseId);
            if ($purchase === null) {
                abort(404);
            }
            $purchase->load('wallet');
            $this->authorizePurchase($purchase, $user);
            $installments = $this->installments->lockForPurchase($purchase->id)->load('invoice');
            $this->ensureOpenInvoices($installments);
            $count = $installments->count();
            $purchase->update($attributes + ['installment_count' => $count]);
            $base = intdiv((int) $attributes['total_amount'], $count);
            $remainder = (int) $attributes['total_amount'] - ($base * $count);
            foreach ($installments as $index => $installment) {
                $amount = $base + ($index === $count - 1 ? $remainder : 0);
                $installment->update(['amount' => $amount]);
                Transaction::query()->where('installment_id', $installment->id)->update([
                    'description' => $attributes['description'].' ('.($index + 1).'/'.$count.')', 'amount' => $amount,
                    'transaction_date' => $attributes['purchase_date'], 'category_id' => $attributes['category_id'] ?? null,
                    'merchant_id' => $attributes['merchant_id'] ?? null, 'is_third_party' => (bool) ($attributes['is_third_party'] ?? false),
                ]);
            }

            return $purchase->fresh('installments');
        });
    }
}
