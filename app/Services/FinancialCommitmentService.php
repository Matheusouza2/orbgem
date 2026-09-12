<?php

namespace App\Services;

use App\DTO\FinancialCommitmentDTO;
use App\DTO\TransactionDTO;
use App\Enums\FinancialInstrumentType;
use App\Enums\TransactionEffect;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\FinancialCommitment;
use App\Models\Transaction;
use App\Repositories\FinancialCommitmentRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinancialCommitmentService
{
    public function __construct(private FinancialCommitmentRepositoryInterface $repository, private TransactionService $transactions) {}

    public function create(FinancialCommitmentDTO $dto): FinancialCommitment
    {
        return $this->repository->create($dto);
    }

    public function forWallet(int $id): Collection
    {
        return $this->repository->forWallet($id);
    }

    public function find(int $id): ?FinancialCommitment
    {
        return $this->repository->find($id);
    }

    public function update(FinancialCommitment $commitment, FinancialCommitmentDTO $dto): FinancialCommitment
    {
        return $this->repository->update($commitment, $dto);
    }

    public function delete(FinancialCommitment $commitment): void
    {
        $this->repository->delete($commitment);
    }

    /** @return array<int, Transaction> */
    public function generate(FinancialCommitment $commitment, string $until, int $memberId): array
    {
        return DB::transaction(function () use ($commitment, $until, $memberId): array {
            $lockedCommitment = $this->repository->findForUpdate($commitment->id);
            if ($lockedCommitment === null || ! $lockedCommitment->active) {
                return [];
            }

            $created = [];
            $limit = Carbon::parse($until);
            $lastInstallment = $lockedCommitment->current_installment;
            for ($number = $lastInstallment + 1; $number <= $lockedCommitment->installment_count; $number++) {
                $date = Carbon::parse($lockedCommitment->start_date)->addMonths($number - 1);
                if ($date->greaterThan($limit) || $date->greaterThan($lockedCommitment->end_date)) {
                    break;
                }
                if (! $this->transactions->commitmentOccurrenceExists($lockedCommitment->id, $number)) {
                    $dateString = $date->toDateString();
                    $created[] = $this->transactions->create(TransactionDTO::fromArray(['wallet_id' => $lockedCommitment->wallet_id, 'account_id' => null, 'category_id' => null, 'description' => $lockedCommitment->description.' (parcela '.$number.'/'.$lockedCommitment->installment_count.')', 'type' => TransactionType::EXPENSE->value, 'effect' => TransactionEffect::NONE->value, 'amount' => $lockedCommitment->installment_amount, 'financial_instrument_type' => FinancialInstrumentType::NONE->value, 'transaction_date' => $dateString, 'competence_date' => $dateString, 'due_date' => $dateString, 'status' => TransactionStatus::PROJECTED->value, 'notes' => 'parcela '.$number.'/'.$lockedCommitment->installment_count, 'financial_commitment_id' => $lockedCommitment->id], $memberId));
                }
                $lastInstallment = $number;
            }

            if ($lastInstallment !== $lockedCommitment->current_installment) {
                $this->repository->updateProgress($lockedCommitment, $lastInstallment, $lastInstallment < $lockedCommitment->installment_count);
            }

            return $created;
        });
    }
}
