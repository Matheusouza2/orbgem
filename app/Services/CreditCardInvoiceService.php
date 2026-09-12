<?php

namespace App\Services;

use App\DTO\CreditCardInvoiceListDTO;
use App\Models\CreditCardInvoice;
use App\Repositories\CreditCardInvoiceRepositoryInterface;
use Illuminate\Support\Collection;

class CreditCardInvoiceService
{
    public function __construct(private CreditCardInvoiceRepositoryInterface $repository) {}

    public function forWallet(CreditCardInvoiceListDTO $dto): Collection
    {
        return $this->repository->forWallet($dto);
    }

    public function create(array $data): CreditCardInvoice
    {
        return $this->repository->create($data);
    }

    public function find(int $id): ?CreditCardInvoice
    {
        return $this->repository->find($id);
    }

    public function lock(int $id): ?CreditCardInvoice
    {
        return $this->repository->lock($id);
    }

    public function save(CreditCardInvoice $invoice): CreditCardInvoice
    {
        return $this->repository->save($invoice);
    }
}
