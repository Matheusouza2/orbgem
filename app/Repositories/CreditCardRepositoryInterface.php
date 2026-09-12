<?php

namespace App\Repositories;

use App\DTO\CreditCardDTO;
use App\Models\CreditCard;
use Illuminate\Support\Collection;

interface CreditCardRepositoryInterface
{
    public function create(CreditCardDTO $dto): CreditCard;

    public function forWallet(int $walletId): Collection;

    public function find(int $id): ?CreditCard;

    public function lock(int $id): ?CreditCard;

    public function update(CreditCard $card, CreditCardDTO $dto): CreditCard;

    public function delete(CreditCard $card): void;
}
