<?php

namespace App\UseCases\Planning;

use App\Enums\WalletMemberRole;
use App\Models\Transaction;
use App\Models\User;
use App\Services\FinancialCommitmentService;
use App\Services\TransactionService;
use App\Services\WalletMembershipAuthorization;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GenerateFinancialCommitmentUseCase
{
    public function __construct(private FinancialCommitmentService $commitments, private TransactionService $transactions, private WalletMembershipAuthorization $auth) {}

    /** @return array<int, Transaction> */
    public function execute(int $id, string $until, User $user): array
    {
        $commitment = $this->commitments->find($id);
        if ($commitment === null) {
            throw new ModelNotFoundException;
        }
        $member = $this->auth->authorize($user, $commitment->wallet, WalletMemberRole::OWNER, WalletMemberRole::EDITOR);

        return $this->commitments->generate($commitment, $until, $member->id);
    }
}
