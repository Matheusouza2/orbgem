<?php

namespace App\Repositories;

use App\DTO\CreditCardDTO;
use App\DTO\CreditCardTransactionListDTO;
use App\Models\CreditCard;
use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
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
        return CreditCard::query()->with(['invoices.installments'])->where('wallet_id', $walletId)->latest()->get();
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

    public function transactions(CreditCardTransactionListDTO $dto): LengthAwarePaginator
    {
        $query = Transaction::query()
            ->with(['creditCardInvoice', 'installment.purchase', 'externalTransactions'])
            ->where('wallet_id', $dto->walletId)
            ->where(function ($query) use ($dto): void {
                $query->whereHas('creditCardInvoice', fn ($invoice): mixed => $invoice->where('credit_card_id', $dto->creditCardId))
                    ->orWhereHas('externalTransactions.externalAccount', fn ($account): mixed => $account->where('accountable_type', CreditCard::class)->where('accountable_id', $dto->creditCardId));
            })
            ->when(! $dto->includeThirdParty, fn ($query) => $query->where('is_third_party', false))
            ->when($dto->status !== null, fn ($query) => $query->where('status', $dto->status))
            ->orderByDesc('competence_date')
            ->orderByDesc('id');

        if ($dto->month !== null) {
            $start = Carbon::createFromFormat('!Y-m', $dto->month)->startOfMonth();
            $query->whereBetween('competence_date', [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()]);
        }

        return $query->paginate($dto->perPage, ['*'], 'page', $dto->page);
    }
}
