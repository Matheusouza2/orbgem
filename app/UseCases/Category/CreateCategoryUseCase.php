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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateCategoryUseCase
{
    public function __construct(
        private CategoryService $categoryService,
        private WalletService $walletService,
        private WalletMembershipAuthorization $membershipAuthorization,
    ) {}

    public function execute(CategoryDTO $categoryDTO, User $user): Category
    {
        return DB::transaction(function () use ($categoryDTO, $user): Category {
            if ($categoryDTO->walletId === null) {
                throw new AuthorizationException('Global categories are system-managed and read-only.');
            }

            $wallet = $this->walletService->find($categoryDTO->walletId);

            if ($wallet === null) {
                throw new ModelNotFoundException;
            }

            $this->membershipAuthorization->authorize($user, $wallet, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);

            if ($categoryDTO->parentId !== null) {
                $parent = $this->categoryService->find($categoryDTO->parentId);

                if ($parent === null) {
                    throw ValidationException::withMessages([
                        'parent_id' => 'The category parent must belong to the category wallet.',
                    ]);
                }

                $this->categoryService->validateParentForWallet($parent, $categoryDTO->walletId, $categoryDTO->type);
            }

            return $this->categoryService->create($categoryDTO);
        });
    }
}
