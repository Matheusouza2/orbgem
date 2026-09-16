<?php

namespace App\Repositories;

use App\DTO\CreditCardDTO;
use App\DTO\CreditCardTransactionListDTO;
use App\Models\CreditCard;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CreditCardRepositoryInterface
{
    public function create(CreditCardDTO $dto): CreditCard;

    public function forWallet(int $walletId): Collection;

    public function find(int $id): ?CreditCard;

    public function lock(int $id): ?CreditCard;

    public function update(CreditCard $card, CreditCardDTO $dto): CreditCard;

    public function delete(CreditCard $card): void;

    public function transactions(CreditCardTransactionListDTO $dto): LengthAwarePaginator;

    public function transactionsAmount(CreditCardTransactionListDTO $dto): int;

    public function transactionsNetAmount(CreditCardTransactionListDTO $dto): int;
}
