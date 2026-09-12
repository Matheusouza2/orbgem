<?php

namespace App\Repositories;

use App\DTO\CreditCardInvoiceListDTO;
use App\Models\CreditCardInvoice;
use Illuminate\Support\Collection;

interface CreditCardInvoiceRepositoryInterface
{
    public function create(array $data): CreditCardInvoice;

    public function forWallet(CreditCardInvoiceListDTO $dto): Collection;

    public function find(int $id): ?CreditCardInvoice;

    public function lock(int $id): ?CreditCardInvoice;

    public function save(CreditCardInvoice $invoice): CreditCardInvoice;
}
