<?php

namespace App\UseCases\Transaction;

use App\Enums\WalletMemberRole;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ShowTransactionUseCase
{
    public function __construct(
        private TransactionService $transactionService,
        private WalletService $walletService,
        private WalletMembershipAuthorization $membershipAuthorization,
    ) {}

    public function execute(int $transactionId, User $user): Transaction
    {
        $transaction = $this->transactionService->find($transactionId);
        if ($transaction === null) {
            throw (new ModelNotFoundException)->setModel(Transaction::class, [$transactionId]);
        }

        $wallet = $this->walletService->find($transaction->wallet_id);
        if ($wallet === null) {
            throw (new ModelNotFoundException)->setModel(Transaction::class, [$transactionId]);
        }

        $this->membershipAuthorization->authorize($user, $wallet, WalletMemberRole::VIEWER, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);

        return $transaction;
    }
}
