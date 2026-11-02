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

class UpdateRecurringOccurrenceDateUseCase
{
    public function __construct(
        private TransactionService $transactions,
        private WalletService $wallets,
        private WalletMembershipAuthorization $membershipAuthorization,
    ) {}

    public function execute(int $transactionId, string $date, User $user): Transaction
    {
        return DB::transaction(function () use ($transactionId, $date, $user): Transaction {
            $transaction = $this->transactions->lockForUpdate($transactionId);
            if ($transaction === null) {
                throw (new ModelNotFoundException)->setModel(Transaction::class, [$transactionId]);
            }

            if ($transaction->recurring_transaction_id === null
                || $transaction->financial_instrument_type !== FinancialInstrumentType::ACCOUNT
                || $transaction->type === TransactionType::TRANSFER
                || $transaction->reversal_of_transaction_id !== null
                || $transaction->transfer_group_id !== null
                || $transaction->credit_card_invoice_id !== null
                || $transaction->reversals()->exists()) {
                throw ValidationException::withMessages(['transaction' => 'Apenas movimentos lançados por recorrência podem ter a data alterada por este fluxo.']);
            }

            $wallet = $this->wallets->find($transaction->wallet_id);
            if ($wallet === null) {
                throw (new ModelNotFoundException)->setModel(Transaction::class, [$transactionId]);
            }
            $this->membershipAuthorization->authorize($user, $wallet, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);

            // Keep the original recurrence occurrence date so generation stays idempotent.
            $transaction->due_date = $date;
            $transaction->save();

            return $transaction->refresh();
        });
    }
}
