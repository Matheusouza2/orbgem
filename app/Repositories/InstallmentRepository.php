<?php

namespace App\Repositories;

use App\Models\Installment;
use Illuminate\Support\Collection;

class InstallmentRepository implements InstallmentRepositoryInterface
{
    /**
     * Create a new class instance.
     */
    public function create(array $data): Installment
    {
        return Installment::query()->create($data);
    }

    public function pendingForInvoice(int $invoiceId): Collection
    {
        return Installment::query()->where('credit_card_invoice_id', $invoiceId)->where('status', 'PENDING')->lockForUpdate()->get();
    }

    public function lockForPurchase(int $purchaseId): Collection
    {
        return Installment::query()->where('credit_card_purchase_id', $purchaseId)->orderBy('number')->lockForUpdate()->get();
    }
}
