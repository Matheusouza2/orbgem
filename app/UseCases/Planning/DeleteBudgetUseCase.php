<?php

namespace App\UseCases\Planning;

use App\Enums\WalletMemberRole;
use App\Models\Budget;
use App\Models\User;
use App\Services\BudgetService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DeleteBudgetUseCase
{
    public function __construct(private BudgetService $budgets, private WalletService $wallets, private WalletMembershipAuthorization $auth) {}

    public function execute(Budget $budget, User $user): void
    {
        $wallet = $this->wallets->find($budget->wallet_id);
        if ($wallet === null) {
            throw new ModelNotFoundException;
        }
        $this->auth->authorize($user, $wallet, WalletMemberRole::OWNER, WalletMemberRole::EDITOR);
        $this->budgets->delete($budget);
    }
}
