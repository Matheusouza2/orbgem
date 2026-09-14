<?php

namespace App\UseCases\Transaction;

use App\Enums\FinancialInstrumentType;
use App\Enums\TransactionType;
use App\Enums\WalletMemberRole;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteTransactionUseCase
{
    public function __construct(
        private TransactionService $transactionService,
        private WalletService $walletService,
        private WalletMembershipAuthorization $membershipAuthorization,
    ) {}

    public function execute(int $transactionId, User $user): void
    {
        DB::transaction(function () use ($transactionId, $user): void {
            $transaction = $this->transactionService->lockForUpdate($transactionId);
            if ($transaction === null) {
                throw (new ModelNotFoundException)->setModel(Transaction::class, [$transactionId]);
            }

            $this->ensureDeletable($transaction);
            $wallet = $this->walletService->find($transaction->wallet_id);
            if ($wallet === null) {
                throw (new ModelNotFoundException)->setModel(Transaction::class, [$transactionId]);
            }
            $this->membershipAuthorization->authorize($user, $wallet, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);
            $this->transactionService->delete($transaction);
        });
    }

    private function ensureDeletable(Transaction $transaction): void
    {
        if ($transaction->financial_instrument_type !== FinancialInstrumentType::ACCOUNT
            || $transaction->type === TransactionType::TRANSFER
            || $transaction->reversal_of_transaction_id !== null
            || $transaction->transfer_group_id !== null
            || $transaction->installment_id !== null
            || $transaction->credit_card_invoice_id !== null
            || $transaction->recurring_transaction_id !== null
            || $transaction->financial_commitment_id !== null
            || $transaction->reversals()->exists()
            || $transaction->invoicePayments()->exists()) {
            throw ValidationException::withMessages(['transaction' => 'Este lançamento não pode ser excluído neste fluxo.']);
        }
    }
}
