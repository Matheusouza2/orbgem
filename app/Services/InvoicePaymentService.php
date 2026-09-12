<?php

namespace App\Services;

use App\Models\InvoicePayment;
use App\Repositories\InvoicePaymentRepositoryInterface;

class InvoicePaymentService
{
    public function __construct(private InvoicePaymentRepositoryInterface $repository) {}

    public function create(array $data): InvoicePayment
    {
        return $this->repository->create($data);
    }

    public function totalForInvoice(int $id): int
    {
        return $this->repository->totalForInvoice($id);
    }
}
