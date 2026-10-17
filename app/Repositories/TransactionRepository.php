<?php

namespace App\Repositories;

use App\DTO\MonthlySummaryDTO;
use App\DTO\TransactionDTO;
use App\DTO\TransactionListFilterDTO;
use App\Enums\FinancialInstrumentType;
use App\Enums\TransactionEffect;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\CreditCardInvoice;
use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TransactionRepository implements TransactionRepositoryInterface
{
    public function create(TransactionDTO $transactionDTO): Transaction
    {
        return Transaction::query()->create($transactionDTO->toArray());
    }

    public function find(int $transactionId): ?Transaction
    {
        return Transaction::query()
            ->withExists('reversals')
            ->with(['wallet', 'account', 'category', 'merchant'])
            ->find($transactionId);
    }

    public function findForReversal(int $transactionId): ?Transaction
    {
        return Transaction::query()->find($transactionId);
    }

    public function lockForUpdate(int $transactionId): ?Transaction
    {
        return Transaction::query()->lockForUpdate()->find($transactionId);
    }

    public function delete(Transaction $transaction): void
    {
        $transaction->delete();
    }

    public function lockForReversal(int $transactionId): ?Transaction
    {
        return Transaction::query()->lockForUpdate()->find($transactionId);
    }

    public function lockTransferGroupForReversal(string $transferGroupId): Collection
    {
        return Transaction::query()
            ->where('transfer_group_id', $transferGroupId)
            ->whereNull('reversal_of_transaction_id')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    public function lockReversalsForOriginals(array $transactionIds): Collection
    {
        return Transaction::query()
            ->whereIn('reversal_of_transaction_id', $transactionIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    public function forWallet(TransactionListFilterDTO $filters): LengthAwarePaginator
    {
        return $this->queryForWallet($filters)->orderBy($filters->sortBy, $filters->sortDirection)->orderBy('id', $filters->sortDirection)->paginate($filters->perPage, ['*'], 'page', $filters->page);
    }

    /** @return array{movements: LengthAwarePaginator, total_amount: int} */
    public function accountMovements(Account $account, TransactionListFilterDTO $filters): array
    {
        $normalQuery = $this->queryForWallet($filters)
            ->whereNull('credit_card_invoice_id');
        $normalCount = (clone $normalQuery)->count();
        $normalAmount = (int) (clone $this->queryForWallet($filters, withDetails: false))
            ->whereNull('credit_card_invoice_id')
            ->toBase()
            ->selectRaw('COALESCE(SUM(CASE WHEN effect = ? THEN amount ELSE -amount END), 0) AS total_amount', [TransactionEffect::CREDIT->value])
            ->value('total_amount');

        $offset = ($filters->page - 1) * $filters->perPage;
        $candidateLimit = $offset + $filters->perPage;
        $normalRows = (clone $normalQuery)
            ->orderBy($filters->sortBy, $filters->sortDirection)
            ->orderBy('id', $filters->sortDirection)
            ->limit($candidateLimit)
            ->get();

        $summaries = $this->invoicePaymentSummaries($account, $filters)
            ->concat($this->invoiceForecastSummaries($account, $filters));
        $signedSummaryAmount = $summaries->sum(fn (array $row): int => $row['effect'] === TransactionEffect::CREDIT->value ? $row['amount'] : -$row['amount']);

        $all = $normalRows->concat($summaries)->sort(function ($first, $second) use ($filters): int {
            $field = $filters->sortBy;
            $firstValue = $this->movementValue($first, $field);
            $secondValue = $this->movementValue($second, $field);
            $comparison = ($firstValue ?? '') <=> ($secondValue ?? '');
            if ($comparison !== 0) {
                return $filters->sortDirection === 'desc' ? -$comparison : $comparison;
            }

            $firstId = $this->movementValue($first, 'id');
            $secondId = $this->movementValue($second, 'id');
            $tieBreak = is_numeric($firstId) && is_numeric($secondId)
                ? (int) $firstId <=> (int) $secondId
                : (string) $firstId <=> (string) $secondId;

            return $filters->sortDirection === 'desc' ? -$tieBreak : $tieBreak;
        })->values();

        $paginator = new Paginator(
            $all->slice($offset, $filters->perPage)->values(),
            $normalCount + $summaries->count(),
            $filters->perPage,
            $filters->page,
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'page'],
        );

        return ['movements' => $paginator, 'total_amount' => $normalAmount + $signedSummaryAmount];
    }

    /** @return Collection<int, array<string, mixed>> */
    private function invoicePaymentSummaries(Account $account, TransactionListFilterDTO $filters): Collection
    {
        if ($filters->type !== null && $filters->type !== TransactionType::EXPENSE) {
            return collect();
        }
        if ($filters->categoryId !== null || $filters->merchantId !== null) {
            return collect();
        }

        $payments = Transaction::query()
            ->with([
                'creditCardInvoice.creditCard',
                'creditCardInvoice.transactions' => fn (HasMany $query) => $query
                    ->whereDoesntHave('invoicePayments')
                    ->where('status', '!=', TransactionStatus::CANCELLED),
                'invoicePayments',
            ])
            ->where('wallet_id', $account->wallet_id)
            ->where('account_id', $account->id)
            ->whereNotNull('credit_card_invoice_id')
            ->whereHas('invoicePayments')
            ->where('status', TransactionStatus::POSTED)
            ->when($filters->month !== null, fn (Builder $query) => $query->where('competence_date', '>=', $this->monthBounds($filters->month)[0])->where('competence_date', '<', $this->monthBounds($filters->month)[1]))
            ->when($filters->status !== null, fn (Builder $query) => $query->where('status', $filters->status))
            ->when($filters->status === null && $filters->activeStatusesOnly, fn (Builder $query) => $query->whereIn('status', [TransactionStatus::POSTED, TransactionStatus::PROJECTED]))
            ->when($filters->transactionDateFrom !== null, fn (Builder $query) => $query->where('transaction_date', '>=', $filters->transactionDateFrom))
            ->when($filters->transactionDateTo !== null, fn (Builder $query) => $query->where('transaction_date', '<=', $filters->transactionDateTo))
            ->when($filters->competenceDateFrom !== null, fn (Builder $query) => $query->where('competence_date', '>=', $filters->competenceDateFrom))
            ->when($filters->competenceDateTo !== null, fn (Builder $query) => $query->where('competence_date', '<=', $filters->competenceDateTo))
            ->get();

        return $payments
            ->groupBy(fn (Transaction $payment): string => $payment->credit_card_invoice_id.'|'.$payment->competence_date->format('Y-m'))
            ->map(function (Collection $rows) use ($filters): ?array {
                /** @var Transaction $first */
                $first = $rows->first();
                $invoice = $first->creditCardInvoice;
                $date = $rows->min('transaction_date');

                $amount = (int) $rows->sum(fn (Transaction $row): int => (int) $row->invoicePayments->sum('amount'));
                if (! $filters->includeThirdParty) {
                    $amount = $this->amountExcludingThirdParty($amount, $invoice->transactions);
                }
                if ($amount === 0) {
                    return null;
                }

                return $this->invoiceSummaryRow(
                    id: 'invoice-payment-'.$invoice->id.'-'.$first->competence_date->format('Y-m'),
                    invoice: $invoice,
                    accountId: (int) $first->account_id,
                    amount: $amount,
                    status: TransactionStatus::POSTED->value,
                    transactionDate: $date?->toDateString(),
                    competenceDate: $first->competence_date->toDateString(),
                    isThirdParty: false,
                );
            })
            ->filter()
            ->values();
    }

    /** @return Collection<int, array<string, mixed>> */
    private function invoiceForecastSummaries(Account $account, TransactionListFilterDTO $filters): Collection
    {
        if (($filters->type !== null && $filters->type !== TransactionType::EXPENSE)
            || ($filters->status !== null && $filters->status !== TransactionStatus::PROJECTED)
            || $filters->categoryId !== null || $filters->merchantId !== null) {
            return collect();
        }

        $invoices = CreditCardInvoice::query()
            ->with([
                'creditCard:id,name,account_id',
                'transactions' => fn (HasMany $query) => $query
                    ->whereDoesntHave('invoicePayments')
                    ->where('status', '!=', TransactionStatus::CANCELLED)
                    ->select(['id', 'credit_card_invoice_id', 'amount', 'is_third_party']),
                'payments:id,credit_card_invoice_id,amount',
            ])
            ->where('wallet_id', $account->wallet_id)
            ->whereHas('creditCard', fn (Builder $query) => $query->where('account_id', $account->id))
            ->where('status', '!=', 'PAID')
            ->when($filters->month !== null, fn (Builder $query) => $query->where('reference_month', $filters->month))
            ->get();

        return $invoices->map(function (CreditCardInvoice $invoice) use ($filters): ?array {
            $components = $invoice->transactions;
            $invoiceAmount = (int) ($filters->includeThirdParty
                ? $components->sum('amount')
                : $components->where('is_third_party', false)->sum('amount'));
            $paidAmount = (int) $invoice->payments->sum('amount');
            if (! $filters->includeThirdParty) {
                $paidAmount = $this->amountExcludingThirdParty($paidAmount, $components);
            }
            $remaining = max(0, $invoiceAmount - $paidAmount);
            if ($remaining === 0) {
                return null;
            }

            $referenceDate = Carbon::createFromFormat('!Y-m', $invoice->reference_month)->toDateString();
            $dueDate = $invoice->due_date?->toDateString();
            if (($filters->transactionDateFrom !== null && ($dueDate === null || $dueDate < $filters->transactionDateFrom))
                || ($filters->transactionDateTo !== null && ($dueDate === null || $dueDate > $filters->transactionDateTo))
                || ($filters->competenceDateFrom !== null && $referenceDate < $filters->competenceDateFrom)
                || ($filters->competenceDateTo !== null && $referenceDate > $filters->competenceDateTo)) {
                return null;
            }

            return $this->invoiceSummaryRow(
                id: 'invoice-forecast-'.$invoice->id,
                invoice: $invoice,
                accountId: (int) $invoice->creditCard->account_id,
                amount: $remaining,
                status: TransactionStatus::PROJECTED->value,
                transactionDate: $invoice->due_date?->toDateString() ?? $referenceDate,
                competenceDate: $referenceDate,
                isThirdParty: $filters->includeThirdParty && $components->contains(fn (Transaction $transaction): bool => (bool) $transaction->is_third_party),
            );
        })->filter()->values();
    }

    /** @param Collection<int, Transaction> $components */
    private function amountExcludingThirdParty(int $amount, Collection $components): int
    {
        $grossAmount = (int) $components->sum('amount');
        $ownAmount = (int) $components->where('is_third_party', false)->sum('amount');

        return $grossAmount > 0 ? (int) round($amount * $ownAmount / $grossAmount) : 0;
    }

    /** @return array<string, mixed> */
    private function invoiceSummaryRow(string $id, CreditCardInvoice $invoice, int $accountId, int $amount, string $status, ?string $transactionDate, string $competenceDate, bool $isThirdParty): array
    {
        return [
            'id' => $id,
            'wallet_id' => $invoice->wallet_id,
            'account_id' => $accountId,
            'category_id' => null,
            'merchant_id' => null,
            'description' => 'Pagamento de fatura '.$invoice->creditCard->name.' '.Carbon::createFromFormat('!Y-m', $invoice->reference_month)->format('m/Y'),
            'type' => TransactionType::EXPENSE->value,
            'effect' => TransactionEffect::DEBIT->value,
            'amount' => $amount,
            'financial_instrument_type' => FinancialInstrumentType::ACCOUNT->value,
            'transaction_date' => $transactionDate,
            'competence_date' => $competenceDate,
            'due_date' => $invoice->due_date?->toDateString(),
            'recurrence_type' => null,
            'installment_initial' => null,
            'installment_count' => null,
            'installment_periodicity' => null,
            'auto_post_on_due_date' => false,
            'paid_at' => $status === TransactionStatus::POSTED->value ? $invoice->paid_at : null,
            'status' => $status,
            'payment_channel' => null,
            'notes' => null,
            'is_third_party' => $isThirdParty,
            'recurring_transaction_id' => null,
            'credit_card_invoice_id' => $invoice->id,
            'installment_id' => null,
            'transfer_group_id' => null,
            'reversal_of_transaction_id' => null,
            'has_reversal' => false,
            'source' => 'internal',
            'created_by_member_id' => null,
            'updated_by_member_id' => null,
            'created_at' => null,
            'is_invoice_summary' => true,
        ];
    }

    private function movementValue(Transaction|array $movement, string $field): mixed
    {
        $value = is_array($movement) ? ($movement[$field] ?? null) : $movement->{$field};

        return $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : $value;
    }

    public function totalAmountForWallet(TransactionListFilterDTO $filters): int
    {
        return (int) $this->queryForWallet($filters, withDetails: false)
            ->toBase()
            ->selectRaw('COALESCE(SUM(CASE WHEN effect = ? THEN amount ELSE -amount END), 0) AS total_amount', [TransactionEffect::CREDIT->value])
            ->value('total_amount');
    }

    private function queryForWallet(TransactionListFilterDTO $filters, bool $withDetails = true): Builder
    {
        $query = Transaction::query()
            ->when($withDetails, fn (Builder $query): Builder => $query->withExists('reversals')->with(['externalTransactions:id,transaction_id,source']))
            ->where('wallet_id', $filters->walletId)
            ->when(! $filters->includeThirdParty, fn ($query) => $query->where('is_third_party', false));
        if ($filters->accountId !== null) {
            $query->where(function (Builder $query) use ($filters): void {
                $query->where('account_id', $filters->accountId)
                    ->orWhere(function (Builder $query) use ($filters): void {
                        $query->whereNull('account_id')
                            ->where('financial_instrument_type', FinancialInstrumentType::CREDIT_CARD)
                            ->where('status', TransactionStatus::PROJECTED)
                            ->whereHas('creditCardInvoice', function (Builder $invoiceQuery) use ($filters): void {
                                $invoiceQuery->whereDoesntHave('payments')
                                    ->whereHas('creditCard', fn (Builder $cardQuery) => $cardQuery->where('account_id', $filters->accountId));
                            });
                    });
            });
        }
        if ($filters->merchantId !== null) {
            $query->where('merchant_id', $filters->merchantId);
        }
        if ($filters->categoryId !== null) {
            $query->where('category_id', $filters->categoryId);
        }
        if ($filters->type !== null) {
            $query->where(function (Builder $query) use ($filters): void {
                $query->where('type', $filters->type);

                if ($filters->type === TransactionType::EXPENSE) {
                    $query->orWhere(function (Builder $query): void {
                        $query->where('type', TransactionType::TRANSFER)
                            ->where('effect', TransactionEffect::DEBIT)
                            ->whereHas('invoicePayments');
                    });
                }
            });
        }
        if ($filters->status !== null) {
            $query->where('status', $filters->status);
        } elseif ($filters->activeStatusesOnly) {
            $query->whereIn('status', [TransactionStatus::POSTED, TransactionStatus::PROJECTED]);
        }
        if ($filters->month !== null) {
            [$start, $end] = $this->monthBounds($filters->month);
            $query->where('competence_date', '>=', $start)->where('competence_date', '<', $end);
        }

        if ($filters->transactionDateFrom !== null) {
            $query->where('transaction_date', '>=', $filters->transactionDateFrom);
        }
        if ($filters->transactionDateTo !== null) {
            $query->where('transaction_date', '<=', $filters->transactionDateTo);
        }
        if ($filters->competenceDateFrom !== null) {
            $query->where('competence_date', '>=', $filters->competenceDateFrom);
        }
        if ($filters->competenceDateTo !== null) {
            $query->where('competence_date', '<=', $filters->competenceDateTo);
        }

        return $query;
    }

    public function totalsForMonth(MonthlySummaryDTO $summary): array
    {
        [$start, $end] = $this->monthBounds($summary->month);
        $rows = Transaction::query()->with('category')->includedInTotals()->when(! $summary->includeThirdParty, fn ($query) => $query->where('is_third_party', false))->where('wallet_id', $summary->walletId)->where('competence_date', '>=', $start)->where('competence_date', '<', $end)->whereIn('status', [TransactionStatus::POSTED, TransactionStatus::PROJECTED])->get();

        return [
            'actual_expenses' => $rows->where('type', TransactionType::EXPENSE)->where('status', TransactionStatus::POSTED)->sum('amount'),
            'forecast_expenses' => $rows->where('type', TransactionType::EXPENSE)->sum('amount'),
            'actual_income' => $rows->where('type', TransactionType::INCOME)->where('status', TransactionStatus::POSTED)->sum('amount'),
            'forecast_income' => $rows->where('type', TransactionType::INCOME)->sum('amount'),
            'expense_by_category' => $this->expensesByCategory($rows),
            'income_status_breakdown' => $this->statusBreakdown($rows, TransactionType::INCOME),
            'expense_status_breakdown' => $this->statusBreakdown($rows, TransactionType::EXPENSE),
        ];
    }

    /** @return array<int, array{category_id: int|null, category_name: string, amount: int}> */
    private function expensesByCategory(Collection $rows): array
    {
        return $rows
            ->where('type', TransactionType::EXPENSE)
            ->where('status', TransactionStatus::POSTED)
            ->groupBy(fn (Transaction $transaction): ?int => $transaction->category_id)
            ->map(function (Collection $categoryRows, $categoryId): array {
                $category = $categoryRows->first()->category;

                return [
                    'category_id' => $categoryId === '' || $categoryId === null ? null : (int) $categoryId,
                    'category_name' => $category?->name ?? 'Sem categoria',
                    'amount' => (int) $categoryRows->sum('amount'),
                ];
            })
            ->sort(function (array $first, array $second): int {
                $amountOrder = $second['amount'] <=> $first['amount'];
                if ($amountOrder !== 0) {
                    return $amountOrder;
                }

                if ($first['category_id'] === null) {
                    return 1;
                }
                if ($second['category_id'] === null) {
                    return -1;
                }

                return $first['category_id'] <=> $second['category_id'];
            })
            ->values()
            ->all();
    }

    /** @return array{posted: int, upcoming: int, overdue: int, distant: int} */
    private function statusBreakdown(Collection $rows, TransactionType $type): array
    {
        $today = Carbon::today();
        $limit = $today->copy()->addDays(7);
        $breakdown = ['posted' => 0, 'upcoming' => 0, 'overdue' => 0, 'distant' => 0];

        $rows->where('type', $type)->each(function (Transaction $transaction) use (&$breakdown, $today, $limit): void {
            if ($transaction->status === TransactionStatus::POSTED) {
                $breakdown['posted'] += (int) $transaction->amount;

                return;
            }

            $dueDate = $transaction->due_date;
            if ($dueDate !== null && $dueDate->isBefore($today)) {
                $breakdown['overdue'] += (int) $transaction->amount;
            } elseif ($dueDate !== null && $dueDate->lessThanOrEqualTo($limit)) {
                $breakdown['upcoming'] += (int) $transaction->amount;
            } else {
                $breakdown['distant'] += (int) $transaction->amount;
            }
        });

        return $breakdown;
    }

    /** @return array{string, string} */
    private function monthBounds(string $month): array
    {
        $start = Carbon::createFromFormat('!Y-m', $month)->startOfMonth();

        return [$start->toDateString(), $start->copy()->addMonth()->toDateString()];
    }

    public function postedAmountsForAccount(int $accountId, bool $includeThirdParty = true): Collection
    {
        return Transaction::query()->when(! $includeThirdParty, fn ($query) => $query->where('is_third_party', false))->where('account_id', $accountId)->where('status', TransactionStatus::POSTED)->get(['effect', 'amount']);
    }

    public function postedAmountsForAccountThroughMonth(int $accountId, string $month, bool $includeThirdParty = true): Collection
    {
        [, $end] = $this->monthBounds($month);

        return Transaction::query()
            ->when(! $includeThirdParty, fn ($query) => $query->where('is_third_party', false))
            ->where('account_id', $accountId)
            ->where('status', TransactionStatus::POSTED)
            ->where('competence_date', '<', $end)
            ->get(['effect', 'amount']);
    }

    public function recurringOccurrenceExists(int $recurringId, string $date): bool
    {
        return Transaction::query()->where('recurring_transaction_id', $recurringId)->whereDate('transaction_date', $date)->exists();
    }

    public function commitmentOccurrenceExists(int $commitmentId, int $number): bool
    {
        return Transaction::query()->where('financial_commitment_id', $commitmentId)->where('notes', 'like', '%parcela '.$number.'/%')->exists();
    }

    public function postDueAutomatically(string $date): int
    {
        return Transaction::query()
            ->where('status', TransactionStatus::PROJECTED)
            ->where('auto_post_on_due_date', true)
            ->whereDate('due_date', '<=', $date)
            ->update(['status' => TransactionStatus::POSTED, 'paid_at' => now()]);
    }

    public function effectivateForInvoice(int $invoiceId, Carbon $paidAt): int
    {
        return Transaction::query()
            ->where('credit_card_invoice_id', $invoiceId)
            ->where('status', TransactionStatus::PROJECTED)
            ->update(['status' => TransactionStatus::POSTED, 'paid_at' => $paidAt]);
    }
}
