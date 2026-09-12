<?php

namespace App\UseCases\Planning;

use App\DTO\BudgetDTO;
use App\Enums\WalletMemberRole;
use App\Models\Budget;
use App\Models\User;
use App\Services\BudgetService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateBudgetUseCase
{
    public function __construct(private BudgetService $budgets, private WalletService $wallets, private WalletMembershipAuthorization $auth) {}

    public function execute(Budget $budget, BudgetDTO $dto, User $user): Budget
    {
        $wallet = $this->wallets->find($budget->wallet_id);
        if ($wallet === null || $budget->wallet_id !== $dto->walletId) {
            throw new ModelNotFoundException;
        }
        $this->auth->authorize($user, $wallet, WalletMemberRole::OWNER, WalletMemberRole::EDITOR);

        return $this->budgets->update($budget, $dto);
    }
}
