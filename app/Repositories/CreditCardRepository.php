<?php

namespace App\Repositories;

use App\DTO\CreditCardDTO;
use App\Models\CreditCard;
use Illuminate\Support\Collection;

class CreditCardRepository implements CreditCardRepositoryInterface
{
    /**
     * Create a new class instance.
     */
    public function create(CreditCardDTO $dto): CreditCard
    {
        return CreditCard::query()->create($dto->toArray());
    }

    public function forWallet(int $walletId): Collection
    {
        return CreditCard::query()->where('wallet_id', $walletId)->latest()->get();
    }

    public function find(int $id): ?CreditCard
    {
        return CreditCard::query()->find($id);
    }

    public function lock(int $id): ?CreditCard
    {
        return CreditCard::query()->lockForUpdate()->find($id);
    }

    public function update(CreditCard $card, CreditCardDTO $dto): CreditCard
    {
        $card->update($dto->toArray());

        return $card->refresh();
    }

    public function delete(CreditCard $card): void
    {
        $card->delete();
    }
}
