<?php

namespace App\UseCases\Category;

use App\Enums\WalletMemberRole;
use App\Models\User;
use App\Services\CategoryService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

class ListCategoryUseCase
{
    public function __construct(
        private CategoryService $categoryService,
        private WalletService $walletService,
        private WalletMembershipAuthorization $membershipAuthorization,
    ) {}

    public function execute(int $walletId, User $user): Collection
    {
        $wallet = $this->walletService->find($walletId);

        if ($wallet === null) {
            throw new ModelNotFoundException;
        }

        $this->membershipAuthorization->authorize(
            $user,
            $wallet,
            WalletMemberRole::VIEWER,
            WalletMemberRole::EDITOR,
            WalletMemberRole::OWNER,
        );

        return $this->categoryService->listForWalletIncludingGlobal($walletId);
    }
}
