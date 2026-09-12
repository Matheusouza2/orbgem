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

class CreateBudgetUseCase
{
    public function __construct(private BudgetService $budgets, private WalletService $wallets, private WalletMembershipAuthorization $auth) {}

    public function execute(BudgetDTO $dto, User $user): Budget
    {
        $wallet = $this->wallets->find($dto->walletId);
        if (! $wallet) {
            throw new ModelNotFoundException;
        }$this->auth->authorize($user, $wallet, WalletMemberRole::OWNER, WalletMemberRole::EDITOR);

        return $this->budgets->create($dto);
    }
}
