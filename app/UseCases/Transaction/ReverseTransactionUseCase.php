<?php

namespace App\UseCases\Transaction;

use App\DTO\TransactionDTO;
use App\DTO\TransactionReversalDTO;
use App\Enums\FinancialInstrumentType;
use App\Enums\TransactionEffect;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\WalletMemberRole;
use App\Exceptions\TransactionAlreadyReversedException;
use App\Exceptions\TransactionNotReversibleException;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReverseTransactionUseCase
{
    public function __construct(
        private TransactionService $transactionService,
        private WalletService $walletService,
        private WalletMembershipAuthorization $membershipAuthorization,
    ) {}

    public function execute(int $transactionId, TransactionReversalDTO $dto, User $user): Transaction
    {
        return DB::transaction(function () use ($transactionId, $dto, $user): Transaction {
            $original = $this->transactionService->findForReversal($transactionId);
            if ($original === null) {
                throw (new ModelNotFoundException)->setModel(Transaction::class, [$transactionId]);
            }

            $wallet = $this->walletService->find($original->wallet_id);
            if ($wallet === null) {
                throw (new ModelNotFoundException)->setModel(Transaction::class, [$transactionId]);
            }

            $member = $this->membershipAuthorization->authorize($user, $wallet, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);
            $originals = $this->lockOriginalsForReversal($original);
            $existingReversals = $this->transactionService->lockReversalsForOriginals($originals->pluck('id')->all());

            if ($existingReversals->isNotEmpty()) {
                throw new TransactionAlreadyReversedException;
            }

            $reversal = null;

            foreach ($originals as $groupOriginal) {
                $this->ensureReversible($groupOriginal);
                $created = $this->createReversal($groupOriginal, $dto, $member->id);
                if ($groupOriginal->id === $original->id) {
                    $reversal = $created;
                }
            }

            return $reversal ?? throw new TransactionNotReversibleException;
        });
    }

    private function createReversal(Transaction $original, TransactionReversalDTO $dto, int $memberId): Transaction
    {
        return $this->transactionService->create(TransactionDTO::fromArray([
            'wallet_id' => $original->wallet_id,
            'account_id' => $original->account_id,
            'category_id' => $original->category_id,
            'merchant_id' => $original->merchant_id,
            'description' => Str::limit('Estorno: '.$original->description, 255, ''),
            'type' => $this->inverseType($original->type)->value,
            'effect' => $this->inverseEffect($original->effect)->value,
            'amount' => $original->amount,
            'financial_instrument_type' => $original->financial_instrument_type->value,
            'transaction_date' => $dto->transactionDate ?? $original->transaction_date->toDateString(),
            'competence_date' => $dto->competenceDate ?? $original->competence_date->toDateString(),
            'due_date' => $original->due_date?->toDateString(),
            'status' => $original->status->value,
            'payment_channel' => $original->payment_channel?->value,
            'notes' => $dto->notes,
            'transfer_group_id' => $original->transfer_group_id,
            'reversal_of_transaction_id' => $original->id,
        ], $memberId));
    }

    /** @return Collection<int, Transaction> */
    private function lockOriginalsForReversal(Transaction $original): Collection
    {
        if ($original->type !== TransactionType::TRANSFER) {
            $lockedOriginal = $this->transactionService->lockForReversal($original->id);

            return $lockedOriginal === null ? collect() : collect([$lockedOriginal]);
        }

        if ($original->transfer_group_id === null) {
            throw new TransactionNotReversibleException;
        }

        $originals = $this->transactionService->lockTransferGroupForReversal($original->transfer_group_id);
        $effects = $originals->pluck('effect')->map(fn (TransactionEffect $effect): string => $effect->value)->sort()->values()->all();
        $amounts = $originals->pluck('amount')->unique()->values();
        $wallets = $originals->pluck('wallet_id')->unique()->values();
        $accounts = $originals->pluck('account_id')->unique()->values();
        $statuses = $originals->pluck('status')->unique()->values();

        if ($originals->count() !== 2
            || $effects !== [TransactionEffect::CREDIT->value, TransactionEffect::DEBIT->value]
            || $amounts->count() !== 1
            || $wallets->count() !== 1
            || $wallets->first() !== $original->wallet_id
            || $originals->contains(fn (Transaction $transaction): bool => $transaction->account_id === null)
            || $accounts->count() !== 2
            || $statuses->count() !== 1
            || $originals->contains(fn (Transaction $transaction): bool => $transaction->type !== TransactionType::TRANSFER)
            || $originals->contains(fn (Transaction $transaction): bool => $transaction->financial_instrument_type !== FinancialInstrumentType::ACCOUNT)
        ) {
            throw new TransactionNotReversibleException;
        }

        return $originals;
    }

    private function ensureReversible(Transaction $transaction): void
    {
        if ($transaction->status === TransactionStatus::CANCELLED || $transaction->reversal_of_transaction_id !== null || $transaction->effect === TransactionEffect::NONE) {
            throw new TransactionNotReversibleException;
        }

    }

    private function inverseType(TransactionType $type): TransactionType
    {
        return match ($type) {
            TransactionType::INCOME => TransactionType::EXPENSE,
            TransactionType::EXPENSE => TransactionType::INCOME,
            TransactionType::TRANSFER => TransactionType::TRANSFER,
        };
    }

    private function inverseEffect(TransactionEffect $effect): TransactionEffect
    {
        return match ($effect) {
            TransactionEffect::DEBIT => TransactionEffect::CREDIT,
            TransactionEffect::CREDIT => TransactionEffect::DEBIT,
            TransactionEffect::NONE => throw new TransactionNotReversibleException,
        };
    }
}
