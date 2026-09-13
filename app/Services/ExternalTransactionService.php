<?php

namespace App\Services;

use App\DTO\ImportTransactionDTO;
use App\DTO\TransactionDTO;
use App\Enums\TransactionRecurrence;
use App\Models\ExternalAccount;
use App\Models\ExternalTransaction;
use App\Repositories\ExternalTransactionRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ExternalTransactionService
{
    public function __construct(
        private ExternalTransactionRepositoryInterface $repository,
        private TransactionService $transactions,
        private WalletService $wallets,
    ) {}

    public function sync(ExternalAccount $externalAccount, ImportTransactionDTO $dto): ExternalTransaction
    {
        return DB::transaction(function () use ($externalAccount, $dto): ExternalTransaction {
            $existing = $this->repository->findBySourceAndExternalId('pluggy', $dto->externalId);
            $member = $this->wallets->ownerMember($dto->walletId);

            if ($member === null) {
                throw new \LogicException('A wallet must have an owner to import transactions.');
            }

            if ($existing !== null && $existing->transaction_id === null) {
                return $this->repository->upsert('pluggy', $dto->externalId, [
                    'external_account_id' => $externalAccount->id,
                    'imported_at' => now(),
                    'raw_data' => $dto->rawData,
                ]);
            }

            $transactionData = [
                'wallet_id' => $dto->walletId,
                'account_id' => $dto->accountId,
                'category_id' => null,
                'description' => $dto->description,
                'type' => $dto->type->value,
                'effect' => $dto->effect->value,
                'amount' => $dto->amount,
                'financial_instrument_type' => $dto->financialInstrumentType->value,
                'transaction_date' => $dto->date,
                'competence_date' => $dto->date,
                'due_date' => $dto->date,
                'recurrence_type' => TransactionRecurrence::NONE->value,
                'status' => $dto->status->value,
                'notes' => $this->notes($dto),
            ];
            $transaction = $existing?->transaction;
            if ($transaction === null) {
                $transaction = $this->transactions->create(TransactionDTO::fromArray($transactionData, $member->id));
            }

            return $this->repository->upsert('pluggy', $dto->externalId, [
                'external_account_id' => $externalAccount->id,
                'transaction_id' => $transaction->id,
                'imported_at' => now(),
                'raw_data' => $dto->rawData,
            ]);
        });
    }

    private function notes(ImportTransactionDTO $dto): ?string
    {
        if ($dto->creditCardMetadata === []) {
            return null;
        }

        return 'Importado da Pluggy: '.json_encode($dto->creditCardMetadata, JSON_THROW_ON_ERROR);
    }
}
