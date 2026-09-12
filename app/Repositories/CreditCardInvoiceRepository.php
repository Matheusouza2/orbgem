<?php

namespace App\Repositories;

use App\DTO\CreditCardInvoiceListDTO;
use App\Models\CreditCardInvoice;
use Illuminate\Support\Collection;

class CreditCardInvoiceRepository implements CreditCardInvoiceRepositoryInterface
{
    /**
     * Create a new class instance.
     */
    public function forWallet(CreditCardInvoiceListDTO $dto): Collection
    {
        $q = CreditCardInvoice::query()->with(['installments', 'creditCard'])->where('wallet_id', $dto->walletId);
        if ($dto->creditCardId !== null) {
            $q->where('credit_card_id', $dto->creditCardId);
        } if ($dto->status !== null) {
            $q->where('status', $dto->status);
        }

        return $q->orderByDesc('reference_month')->orderByDesc('id')->get();
    }

    public function find(int $id): ?CreditCardInvoice
    {
        return CreditCardInvoice::query()->with(['installments', 'payments'])->find($id);
    }

    public function lock(int $id): ?CreditCardInvoice
    {
        return CreditCardInvoice::query()->lockForUpdate()->find($id);
    }

    public function save(CreditCardInvoice $invoice): CreditCardInvoice
    {
        $invoice->save();

        return $invoice;
    }

    public function create(array $data): CreditCardInvoice
    {
        return CreditCardInvoice::query()->create($data);
    }
}
