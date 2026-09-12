<?php

namespace App\UseCases\Planning;

use App\Enums\WalletMemberRole;
use App\Models\User;
use App\Services\RecurringTransactionService;
use App\Services\TransactionService;
use App\Services\WalletMembershipAuthorization;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GenerateRecurringTransactionUseCase
{
    public function __construct(private RecurringTransactionService $recurrences, private TransactionService $transactions, private WalletMembershipAuthorization $auth) {}

    public function execute(int $id, string $until, User $user): array
    {
        $rule = $this->recurrences->find($id);
        if (! $rule) {
            throw new ModelNotFoundException;
        }$member = $this->auth->authorize($user, $rule->wallet, WalletMemberRole::OWNER, WalletMemberRole::EDITOR);

        return $this->recurrences->generate($rule, $until, $member->id);
    }
}
