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

    /** @return array{transactions: LengthAwarePaginator, total_amount: int} */
    public function execute(TransactionListFilterDTO $filters, User $user): array
    {
        $wallet = $this->walletService->find($filters->walletId);
        if ($wallet === null) {
            throw new ModelNotFoundException;
        }
        $this->membershipAuthorization->authorize($user, $wallet, WalletMemberRole::VIEWER, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);

        return [
            'transactions' => $this->transactionService->listForWallet($filters),
            'total_amount' => $this->transactionService->totalAmountForWallet($filters),
        ];
    }
}
