<?php

namespace App\UseCases\Transaction;

use App\Enums\FinancialInstrumentType;
use App\Enums\TransactionEffect;
use App\Enums\TransactionType;
use App\Enums\WalletMemberRole;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AccountService;
use App\Services\CategoryService;
use App\Services\MerchantService;
use App\Services\TransactionService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateTransactionUseCase
{
    public function __construct(
        private TransactionService $transactionService,
        private WalletService $walletService,
        private AccountService $accountService,
        private CategoryService $categoryService,
        private MerchantService $merchantService,
        private WalletMembershipAuthorization $membershipAuthorization,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function execute(int $transactionId, array $attributes, User $user): Transaction
    {
        return DB::transaction(function () use ($transactionId, $attributes, $user): Transaction {
            $transaction = $this->transactionService->lockForUpdate($transactionId);
            if ($transaction === null) {
                throw (new ModelNotFoundException)->setModel(Transaction::class, [$transactionId]);
            }

            $this->ensureEditable($transaction);
            $wallet = $this->walletService->find($transaction->wallet_id);
            if ($wallet === null || (int) $attributes['wallet_id'] !== $transaction->wallet_id) {
                throw (new ModelNotFoundException)->setModel(Transaction::class, [$transactionId]);
            }
            $member = $this->membershipAuthorization->authorize($user, $wallet, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);
            $this->validateDependencies($attributes, $transaction->wallet_id);

            $transaction->fill($this->editableAttributes($attributes, $member->id));
            $transaction->save();

            return $transaction->refresh();
        });
    }

    private function ensureEditable(Transaction $transaction): void
    {
        if ($transaction->financial_instrument_type !== FinancialInstrumentType::ACCOUNT
            || $transaction->type === TransactionType::TRANSFER
            || $transaction->effect === TransactionEffect::NONE
            || $transaction->reversal_of_transaction_id !== null
            || $transaction->transfer_group_id !== null
            || $transaction->installment_id !== null
            || $transaction->credit_card_invoice_id !== null
            || $transaction->recurring_transaction_id !== null
            || $transaction->financial_commitment_id !== null
            || $transaction->reversals()->exists()
            || $transaction->invoicePayments()->exists()) {
            throw ValidationException::withMessages(['transaction' => 'Este lançamento não pode ser editado neste fluxo.']);
        }
    }

    /** @param array<string, mixed> $attributes */
    private function validateDependencies(array $attributes, int $walletId): void
    {
        $account = $this->accountService->find((int) $attributes['account_id']);
        if ($account === null || $account->wallet_id !== $walletId) {
            throw ValidationException::withMessages(['account_id' => 'A conta deve pertencer à carteira do lançamento.']);
        }

        $type = TransactionType::from($attributes['type']);
        if (($attributes['category_id'] ?? null) !== null) {
            $category = $this->categoryService->find((int) $attributes['category_id']);
            if ($category === null) {
                throw ValidationException::withMessages(['category_id' => 'A categoria deve existir.']);
            }
            $this->categoryService->validateForTransaction($category, $walletId, $type);
        }

        if (($attributes['merchant_id'] ?? null) !== null) {
            $merchant = $this->merchantService->find((int) $attributes['merchant_id']);
            if ($merchant === null || ! $this->merchantService->belongsToWallet($merchant, $walletId)) {
                throw ValidationException::withMessages(['merchant_id' => 'O estabelecimento deve pertencer à carteira.']);
            }
        }
    }

    /** @param array<string, mixed> $attributes @return array<string, mixed> */
    private function editableAttributes(array $attributes, int $memberId): array
    {
        return [
            'account_id' => (int) $attributes['account_id'],
            'category_id' => ($attributes['category_id'] ?? null) !== null ? (int) $attributes['category_id'] : null,
            'merchant_id' => ($attributes['merchant_id'] ?? null) !== null ? (int) $attributes['merchant_id'] : null,
            'description' => $attributes['description'],
            'type' => $attributes['type'],
            'effect' => $attributes['type'] === TransactionType::INCOME->value ? TransactionEffect::CREDIT->value : TransactionEffect::DEBIT->value,
            'amount' => (int) $attributes['amount'],
            'transaction_date' => $attributes['transaction_date'],
            'competence_date' => $attributes['competence_date'],
            'due_date' => $attributes['due_date'] ?? null,
            'auto_post_on_due_date' => (bool) ($attributes['auto_post_on_due_date'] ?? false),
            'paid_at' => $attributes['paid_at'] ?? null,
            'status' => $attributes['status'],
            'payment_channel' => $attributes['payment_channel'] ?? null,
            'notes' => $attributes['notes'] ?? null,
            'is_third_party' => (bool) ($attributes['is_third_party'] ?? false),
            'updated_by_member_id' => $memberId,
        ];
    }
}
