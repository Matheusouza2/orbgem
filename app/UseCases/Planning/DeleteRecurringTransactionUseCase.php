<?php

namespace App\UseCases\Planning;

use App\Enums\WalletMemberRole;
use App\Models\RecurringTransaction;
use App\Models\User;
use App\Services\RecurringTransactionService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DeleteRecurringTransactionUseCase
{
    public function __construct(private RecurringTransactionService $service, private WalletService $wallets, private WalletMembershipAuthorization $auth) {}

    public function execute(RecurringTransaction $transaction, User $user): void
    {
        $wallet = $this->wallets->find($transaction->wallet_id);
        if ($wallet === null) {
            throw new ModelNotFoundException;
        }
        $this->auth->authorize($user, $wallet, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);
        $this->service->delete($transaction);
    }
}
