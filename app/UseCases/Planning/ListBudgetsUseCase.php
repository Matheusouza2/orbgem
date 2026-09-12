<?php

namespace App\UseCases\Planning;

use App\Enums\WalletMemberRole;
use App\Models\User;
use App\Services\BudgetService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

class ListBudgetsUseCase
{
    public function __construct(private BudgetService $budgets, private WalletService $wallets, private WalletMembershipAuthorization $auth) {}

    public function execute(int $walletId, ?string $month, User $user): Collection
    {
        $wallet = $this->wallets->find($walletId);
        if (! $wallet) {
            throw new ModelNotFoundException;
        }$this->auth->authorize($user, $wallet, WalletMemberRole::OWNER, WalletMemberRole::EDITOR, WalletMemberRole::VIEWER);

        return $this->budgets->forWallet($walletId, $month);
    }
}
