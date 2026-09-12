<?php

namespace App\Repositories;

use App\Models\Installment;
use Illuminate\Support\Collection;

interface InstallmentRepositoryInterface
{
    public function create(array $data): Installment;

    public function pendingForInvoice(int $invoiceId): Collection;
}
