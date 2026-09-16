<?php

namespace App\UseCases\Transaction;

use App\Enums\TransactionStatus;
use App\Enums\WalletMemberRole;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EffectivateTransactionUseCase
{
    public function __construct(
        private TransactionService $transactions,
        private WalletService $wallets,
        private WalletMembershipAuthorization $authorization,
    ) {}

    public function execute(int $transactionId, User $user): Transaction
    {
        return DB::transaction(function () use ($transactionId, $user): Transaction {
            $transaction = $this->transactions->lockForUpdate($transactionId);
            if ($transaction === null) {
                throw (new ModelNotFoundException)->setModel(Transaction::class, [$transactionId]);
            }
            if ($transaction->status !== TransactionStatus::PROJECTED) {
                throw ValidationException::withMessages(['status' => 'Somente transações previstas podem ser efetivadas.']);
            }

            $wallet = $this->wallets->find($transaction->wallet_id);
            if ($wallet === null) {
                throw (new ModelNotFoundException)->setModel(Transaction::class, [$transactionId]);
            }
            $member = $this->authorization->authorize($user, $wallet, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);
            $transaction->update([
                'status' => TransactionStatus::POSTED,
                'paid_at' => now(),
                'updated_by_member_id' => $member->id,
            ]);

            return $transaction->refresh();
        });
    }
}
