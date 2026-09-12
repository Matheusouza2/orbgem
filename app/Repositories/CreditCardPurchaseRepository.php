<?php

namespace App\Repositories;

use App\DTO\CreditCardPurchaseDTO;
use App\Models\CreditCardPurchase;

class CreditCardPurchaseRepository implements CreditCardPurchaseRepositoryInterface
{
    /**
     * Create a new class instance.
     */
    public function create(CreditCardPurchaseDTO $dto): CreditCardPurchase
    {
        return CreditCardPurchase::query()->create($dto->toArray());
    }
}
