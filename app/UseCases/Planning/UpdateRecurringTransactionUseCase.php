<?php

namespace App\UseCases\Planning;

use App\DTO\RecurringTransactionDTO;
use App\Enums\WalletMemberRole;
use App\Models\RecurringTransaction;
use App\Models\User;
use App\Services\RecurringTransactionService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateRecurringTransactionUseCase
{
    public function __construct(private RecurringTransactionService $service, private WalletService $wallets, private WalletMembershipAuthorization $auth) {}

    public function execute(RecurringTransaction $transaction, RecurringTransactionDTO $dto, User $user): RecurringTransaction
    {
        if ($transaction->wallet_id !== $dto->walletId || ($wallet = $this->wallets->find($transaction->wallet_id)) === null) {
            throw new ModelNotFoundException;
        }
        $this->auth->authorize($user, $wallet, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);

        return $this->service->update($transaction, $dto);
    }
}
