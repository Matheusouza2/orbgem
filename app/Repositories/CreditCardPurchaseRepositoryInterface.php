<?php

namespace App\Repositories;

use App\DTO\CreditCardPurchaseDTO;
use App\Models\CreditCardPurchase;

interface CreditCardPurchaseRepositoryInterface
{
    public function create(CreditCardPurchaseDTO $dto): CreditCardPurchase;

    public function lockForUpdate(int $id): ?CreditCardPurchase;
}
