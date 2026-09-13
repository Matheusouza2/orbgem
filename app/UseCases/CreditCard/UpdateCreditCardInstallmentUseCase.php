<?php

namespace App\UseCases\CreditCard;

use App\Models\Installment;
use App\Models\Transaction;
use App\Models\User;
use App\Services\InstallmentService;
use Illuminate\Support\Facades\DB;

class UpdateCreditCardInstallmentUseCase
{
    use CreditCardMovementSupport;

    public function __construct(private InstallmentService $installments) {}

    public function execute(int $transactionId, array $attributes, User $user): Transaction
    {
        return DB::transaction(function () use ($transactionId, $attributes, $user): Transaction {
            $transaction = Transaction::query()->lockForUpdate()->find($transactionId);
            if ($transaction === null || $transaction->installment_id === null) {
                abort(404);
            }
            $installment = Installment::query()->lockForUpdate()->with(['purchase.wallet', 'invoice'])->findOrFail($transaction->installment_id);
            $this->authorizePurchase($installment->purchase, $user);
            $this->ensureOpenInvoices([$installment]);
            $purchase = $installment->purchase;
            $count = $purchase->installments()->count();
            $purchase->update(['total_amount' => (int) $purchase->total_amount - (int) $installment->amount + (int) $attributes['amount']]);
            $installment->update(['amount' => $attributes['amount']]);
            $transaction->update([
                'description' => $attributes['description'].' ('.$installment->number.'/'.$count.')', 'amount' => $attributes['amount'],
                'transaction_date' => $attributes['transaction_date'], 'category_id' => $attributes['category_id'] ?? null,
                'merchant_id' => $attributes['merchant_id'] ?? null, 'is_third_party' => (bool) ($attributes['is_third_party'] ?? false),
            ]);

            return $transaction->fresh();
        });
    }
}
