<?php

namespace App\Repositories;

use App\DTO\CreditCardDTO;
use App\DTO\CreditCardTransactionListDTO;
use App\Enums\TransactionEffect;
use App\Enums\TransactionStatus;
use App\Models\CreditCard;
use App\Models\CreditCardInvoice;
use App\Models\ExternalAccount;
use App\Models\ExternalTransaction;
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
        return $this->transactionsSummary($dto)['amount'];
    }

    public function transactionsNetAmount(CreditCardTransactionListDTO $dto): int
    {
        return $this->transactionsSummary($dto)['net_amount'];
    }

    /** @return array{amount: int, net_amount: int} */
    public function transactionsSummary(CreditCardTransactionListDTO $dto): array
    {
        $row = $this->transactionQuery($dto, withDetails: false)
            ->reorder()
            ->toBase()
            ->selectRaw('COALESCE(SUM(amount), 0) AS amount, COALESCE(SUM(CASE WHEN effect = ? THEN amount ELSE -amount END), 0) AS net_amount', [TransactionEffect::CREDIT->value])
            ->first();

        return ['amount' => (int) $row->amount, 'net_amount' => (int) $row->net_amount];
    }

    private function transactionQuery(CreditCardTransactionListDTO $dto, bool $withDetails = true): Builder
    {
        $query = Transaction::query()
            ->when($withDetails, fn (Builder $query): Builder => $query->with([
                'creditCardInvoice:id,credit_card_id,reference_month,due_date,status',
                'installment:id,credit_card_purchase_id,credit_card_invoice_id,number,status',
                'installment.purchase:id,description,purchase_date,total_amount,installment_count,category_id,merchant_id',
                'externalTransactions:id,transaction_id,source',
            ]))
            ->where('wallet_id', $dto->walletId)
            ->where(function (Builder $query) use ($dto): void {
                $query->whereIn('credit_card_invoice_id', CreditCardInvoice::query()
                    ->select('id')
                    ->where('credit_card_id', $dto->creditCardId))
                    ->orWhereIn('id', ExternalTransaction::query()
                        ->select('transaction_id')
                        ->whereNotNull('transaction_id')
                        ->whereIn('external_account_id', ExternalAccount::query()
                            ->select('id')
                            ->where('accountable_type', CreditCard::class)
                            ->where('accountable_id', $dto->creditCardId)));
            })
            ->whereDoesntHave('invoicePayments')
            ->when(! $dto->includeThirdParty, fn ($query) => $query->where('is_third_party', false))
            ->when($dto->activeStatusesOnly, fn ($query) => $query->whereIn('status', [TransactionStatus::POSTED, TransactionStatus::PROJECTED]))
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
