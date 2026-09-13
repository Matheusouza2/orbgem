<?php

namespace App\UseCases\CreditCard;

use App\Models\Installment;
use App\Models\Transaction;
use App\Models\User;
use App\Services\InstallmentService;
use Illuminate\Support\Facades\DB;

class DeleteCreditCardInstallmentUseCase
{
    use CreditCardMovementSupport;

    public function __construct(private InstallmentService $installments) {}

    public function execute(int $transactionId, User $user): void
    {
        DB::transaction(function () use ($transactionId, $user): void {
            $transaction = Transaction::query()->lockForUpdate()->find($transactionId);
            if ($transaction === null || $transaction->installment_id === null) {
                abort(404);
            }
            $installment = Installment::query()->lockForUpdate()->with(['purchase.wallet', 'invoice'])->findOrFail($transaction->installment_id);
            $this->authorizePurchase($installment->purchase, $user);
            $this->ensureOpenInvoices([$installment]);
            $purchase = $installment->purchase;
            $remaining = $purchase->installments()->where('id', '!=', $installment->id)->orderBy('number')->lockForUpdate()->get();
            $transaction->delete();
            $installment->delete();
            if ($remaining->isEmpty()) {
                $purchase->delete();

                return;
            }
            $count = $remaining->count();
            $purchase->update(['total_amount' => $remaining->sum('amount'), 'installment_count' => $count]);
            foreach ($remaining->values() as $index => $remainingInstallment) {
                $remainingInstallment->update(['number' => $index + 1]);
                $remainingTransaction = Transaction::query()->where('installment_id', $remainingInstallment->id)->first();
                if ($remainingTransaction !== null) {
                    $description = preg_replace('/\s\(\d+\/\d+\)$/', '', $remainingTransaction->description);
                    $remainingTransaction->update(['description' => $description.' ('.($index + 1).'/'.$count.')']);
                }
            }
        });
    }
}
