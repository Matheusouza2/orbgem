<?php

namespace App\Services;

use App\DTO\CreditCardPurchaseDTO;
use App\Models\CreditCardPurchase;
use App\Repositories\CreditCardPurchaseRepositoryInterface;

class CreditCardPurchaseService
{
    public function __construct(private CreditCardPurchaseRepositoryInterface $repository) {}

    public function create(CreditCardPurchaseDTO $dto): CreditCardPurchase
    {
        return $this->repository->create($dto);
    }

    public function lockForUpdate(int $id): ?CreditCardPurchase
    {
        return $this->repository->lockForUpdate($id);
    }
}
