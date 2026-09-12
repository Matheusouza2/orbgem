<?php

namespace App\UseCases\Category;

use App\DTO\CategoryDTO;
use App\Enums\WalletMemberRole;
use App\Models\Category;
use App\Models\User;
use App\Services\CategoryService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class UpdateCategoryUseCase
{
    public function __construct(
        private CategoryService $categoryService,
        private WalletService $walletService,
        private WalletMembershipAuthorization $membershipAuthorization,
    ) {}

    public function execute(int $categoryId, CategoryDTO $categoryDTO, User $user): Category
    {
        $category = $this->categoryService->find($categoryId);

        if ($category === null) {
            throw new ModelNotFoundException;
        }

        if ($category->wallet_id === null) {
            throw new AuthorizationException('Global categories are system-managed and read-only.');
        }

        if ($category->wallet_id !== $categoryDTO->walletId) {
            throw new ModelNotFoundException;
        }

        $wallet = $this->walletService->find($category->wallet_id);

        if ($wallet === null) {
            throw new ModelNotFoundException;
        }

        $this->membershipAuthorization->authorize($user, $wallet, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);

        if ($categoryDTO->parentId !== null) {
            if ($categoryDTO->parentId === $category->id) {
                throw ValidationException::withMessages(['parent_id' => 'A category cannot be its own parent.']);
            }

            $parent = $this->categoryService->find($categoryDTO->parentId);

            if ($parent === null) {
                throw ValidationException::withMessages(['parent_id' => 'The category parent must belong to the category wallet.']);
            }

            $this->categoryService->validateParentForWallet($parent, $category->wallet_id, $categoryDTO->type);
        }

        return $this->categoryService->update($category, $categoryDTO);
    }
}
