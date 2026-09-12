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

class CreateRecurringTransactionUseCase
{
    public function __construct(private RecurringTransactionService $recurrences, private WalletService $wallets, private WalletMembershipAuthorization $auth) {}

    public function execute(RecurringTransactionDTO $dto, User $user): RecurringTransaction
    {
        $wallet = $this->wallets->find($dto->walletId);
        if (! $wallet) {
            throw new ModelNotFoundException;
        }$this->auth->authorize($user, $wallet, WalletMemberRole::OWNER, WalletMemberRole::EDITOR);

        return $this->recurrences->create($dto);
    }
}
