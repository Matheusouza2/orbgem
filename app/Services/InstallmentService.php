<?php

namespace App\Services;

use App\Models\Installment;
use App\Repositories\InstallmentRepositoryInterface;
use Illuminate\Support\Collection;

class InstallmentService
{
    public function __construct(private InstallmentRepositoryInterface $repository) {}

    public function create(array $data): Installment
    {
        return $this->repository->create($data);
    }

    public function pendingForInvoice(int $id): Collection
    {
        return $this->repository->pendingForInvoice($id);
    }

    public function lockForPurchase(int $purchaseId): Collection
    {
        return $this->repository->lockForPurchase($purchaseId);
    }
}
