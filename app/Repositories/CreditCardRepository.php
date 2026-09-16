<?php

namespace App\Repositories;

use App\DTO\CreditCardDTO;
use App\DTO\CreditCardTransactionListDTO;
use App\Enums\TransactionEffect;
use App\Models\CreditCard;
use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
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
        return $this->transactionQuery($dto)
            ->paginate($dto->perPage, ['*'], 'page', $dto->page);
    }

    public function transactionsAmount(CreditCardTransactionListDTO $dto): int
    {
        return (int) $this->transactionQuery($dto)->sum('amount');
    }

    public function transactionsNetAmount(CreditCardTransactionListDTO $dto): int
    {
        return (int) $this->transactionQuery($dto)->get()->sum(fn (Transaction $transaction): int => $transaction->effect === TransactionEffect::CREDIT ? $transaction->amount : -$transaction->amount);
    }

    private function transactionQuery(CreditCardTransactionListDTO $dto): Builder
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
            ->when($dto->type !== null, fn ($query) => $query->where('type', $dto->type))
            ->when($dto->categoryId !== null, fn ($query) => $query->where('category_id', $dto->categoryId))
            ->orderByDesc('due_date')
            ->orderByDesc('id');

        if ($dto->month !== null) {
            $start = Carbon::createFromFormat('!Y-m', $dto->month)->startOfMonth();
            $query->whereBetween('competence_date', [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()]);
        }

        return $query;
    }
}
