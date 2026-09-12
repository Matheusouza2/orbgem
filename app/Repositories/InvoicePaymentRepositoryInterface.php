<?php

namespace App\Repositories;

use App\Models\InvoicePayment;

interface InvoicePaymentRepositoryInterface
{
    public function create(array $data): InvoicePayment;

    public function totalForInvoice(int $invoiceId): int;
}
