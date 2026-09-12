<?php

namespace App\Services;

use App\DTO\CreditCardDTO;
use App\Models\CreditCard;
use App\Repositories\CreditCardRepositoryInterface;
use Illuminate\Support\Collection;

class CreditCardService
{
    public function __construct(private CreditCardRepositoryInterface $repository) {}

    public function create(CreditCardDTO $dto): CreditCard
    {
        return $this->repository->create($dto);
    }

    public function forWallet(int $walletId): Collection
    {
        return $this->repository->forWallet($walletId);
    }

    public function find(int $id): ?CreditCard
    {
        return $this->repository->find($id);
    }

    public function lock(int $id): ?CreditCard
    {
        return $this->repository->lock($id);
    }

    public function update(CreditCard $card, CreditCardDTO $dto): CreditCard
    {
        return $this->repository->update($card, $dto);
    }

    public function delete(CreditCard $card): void
    {
        $this->repository->delete($card);
    }
}
