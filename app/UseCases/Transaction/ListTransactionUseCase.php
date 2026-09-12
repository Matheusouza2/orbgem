<?php

namespace App\UseCases\Transaction;

use App\DTO\TransactionListFilterDTO;
use App\Enums\WalletMemberRole;
use App\Models\User;
use App\Services\TransactionService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ListTransactionUseCase
{
    public function __construct(private TransactionService $transactionService, private WalletService $walletService, private WalletMembershipAuthorization $membershipAuthorization) {}

    public function execute(TransactionListFilterDTO $filters, User $user): LengthAwarePaginator
    {
        $wallet = $this->walletService->find($filters->walletId);
        if ($wallet === null) {
            throw new ModelNotFoundException;
        }
        $this->membershipAuthorization->authorize($user, $wallet, WalletMemberRole::VIEWER, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);

        return $this->transactionService->listForWallet($filters);
    }
}
