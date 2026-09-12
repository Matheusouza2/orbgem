<?php

namespace App\Repositories;

use App\Models\InvoicePayment;

class InvoicePaymentRepository implements InvoicePaymentRepositoryInterface
{
    /**
     * Create a new class instance.
     */
    public function create(array $data): InvoicePayment
    {
        return InvoicePayment::query()->create($data);
    }

    public function totalForInvoice(int $invoiceId): int
    {
        return (int) InvoicePayment::query()->where('credit_card_invoice_id', $invoiceId)->sum('amount');
    }
}
