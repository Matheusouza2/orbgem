<?php

namespace App\Services;

use App\DTO\RecurringTransactionDTO;
use App\DTO\TransactionDTO;
use App\Enums\FinancialInstrumentType;
use App\Enums\TransactionEffect;
use App\Enums\TransactionStatus;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Repositories\RecurringTransactionRepositoryInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RecurringTransactionService
{
    public function __construct(private RecurringTransactionRepositoryInterface $repository, private TransactionService $transactions) {}

    public function create(RecurringTransactionDTO $dto): RecurringTransaction
    {
        return $this->repository->create($dto);
    }

    public function forWallet(int $id): Collection
    {
        return $this->repository->forWallet($id);
    }

    public function find(int $id): ?RecurringTransaction
    {
        return $this->repository->find($id);
    }

    public function update(RecurringTransaction $transaction, RecurringTransactionDTO $dto): RecurringTransaction
    {
        return $this->repository->update($transaction, $dto);
    }

    public function delete(RecurringTransaction $transaction): void
    {
        $this->repository->delete($transaction);
    }

    /** @return array<int, Transaction> */
    public function generate(RecurringTransaction $rule, string $until, int $memberId): array
    {
        $period = $rule->frequency->value === 'WEEKLY' ? '1 week' : ($rule->frequency->value === 'YEARLY' ? '1 year' : '1 month');
        $created = [];
        foreach (CarbonPeriod::create(Carbon::parse($rule->start_date), $period, Carbon::parse($until)) as $date) {
            if ($rule->end_date && $date->greaterThan($rule->end_date)) {
                break;
            }
            $day = $date->copy();
            if ($rule->due_day !== null) {
                $day->day = min($rule->due_day, $day->endOfMonth()->day);
            }
            $dateString = $day->toDateString();
            if ($this->transactions->recurringOccurrenceExists($rule->id, $dateString)) {
                continue;
            }
            $created[] = $this->transactions->create(TransactionDTO::fromArray(['wallet_id' => $rule->wallet_id, 'account_id' => $rule->account_id, 'category_id' => $rule->category_id, 'description' => $rule->description, 'type' => $rule->type->value, 'effect' => $rule->type->value === 'INCOME' ? TransactionEffect::CREDIT->value : TransactionEffect::DEBIT->value, 'amount' => $rule->amount, 'financial_instrument_type' => FinancialInstrumentType::ACCOUNT->value, 'transaction_date' => $dateString, 'competence_date' => $dateString, 'due_date' => $dateString, 'status' => $rule->auto_create ? TransactionStatus::POSTED->value : TransactionStatus::PROJECTED->value, 'recurring_transaction_id' => $rule->id], $memberId));
        }

        return $created;
    }
}
