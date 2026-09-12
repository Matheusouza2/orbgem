<?php

namespace App\Services;

use App\DTO\CategoryDTO;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Repositories\CategoryRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    public function __construct(private CategoryRepositoryInterface $categoryRepository) {}

    public function create(CategoryDTO $categoryDTO): Category
    {
        return $this->categoryRepository->create($categoryDTO);
    }

    public function update(Category $category, CategoryDTO $categoryDTO): Category
    {
        return $this->categoryRepository->update($category, $categoryDTO);
    }

    public function find(int $categoryId): ?Category
    {
        return $this->categoryRepository->find($categoryId);
    }

    public function ensureDefaultCategories(int $walletId): void
    {
        foreach ($this->defaultCategories() as $defaultCategory) {
            $name = $defaultCategory['name'];
            $type = $defaultCategory['type'];
            $transactionType = TransactionType::from($type);

            if ($this->categoryRepository->findForWalletByNameAndType($walletId, $name, $transactionType) !== null) {
                continue;
            }

            $this->create(CategoryDTO::fromArray([
                'wallet_id' => $walletId,
                'parent_id' => null,
                'name' => $name,
                'type' => $type,
                'icon' => $defaultCategory['icon'],
                'icon_color' => $defaultCategory['icon_color'],
                'active' => true,
            ]));
        }
    }

    /**
     * A global category may be a parent, while a wallet category may only use
     * a parent from its own wallet; all child categories must share its type.
     */
    public function validateParentForWallet(Category $parent, int $walletId, TransactionType $type): void
    {
        if ($parent->wallet_id !== null && $parent->wallet_id !== $walletId) {
            throw ValidationException::withMessages([
                'parent_id' => 'The category parent must belong to the category wallet.',
            ]);
        }

        if ($parent->type !== $type) {
            throw ValidationException::withMessages([
                'type' => 'A child category must have the same type as its parent.',
            ]);
        }
    }

    /**
     * Wallet categories must match the transaction wallet; global categories
     * are the deliberate nullable-wallet exception to the composite invariant.
     */
    public function validateForTransaction(Category $category, int $walletId, TransactionType $type): void
    {
        if ($category->wallet_id !== null && $category->wallet_id !== $walletId) {
            throw ValidationException::withMessages(['category_id' => 'The category must belong to the transaction wallet.']);
        }

        if ($category->type !== $type) {
            throw ValidationException::withMessages(['category_id' => 'The category is incompatible with the transaction type.']);
        }
    }

    public function listForWalletIncludingGlobal(int $walletId): Collection
    {
        return $this->categoryRepository->forWalletIncludingGlobal($walletId);
    }

    /** @return list<array{name: string, type: string, icon: string, icon_color: string|null}> */
    private function defaultCategories(): array
    {
        return [
            ['name' => 'Alimentação', 'type' => TransactionType::EXPENSE->value, 'icon' => 'Utensils', 'icon_color' => '#279112'],
            ['name' => 'Moradia', 'type' => TransactionType::EXPENSE->value, 'icon' => 'Home', 'icon_color' => null],
            ['name' => 'Lazer', 'type' => TransactionType::EXPENSE->value, 'icon' => 'Clapperboard', 'icon_color' => '#0050f0'],
            ['name' => 'Saúde', 'type' => TransactionType::EXPENSE->value, 'icon' => 'HeartPulse', 'icon_color' => '#ff0000'],
            ['name' => 'Salário', 'type' => TransactionType::INCOME->value, 'icon' => 'DollarSign', 'icon_color' => '#129138'],
            ['name' => 'Rendimento', 'type' => TransactionType::INCOME->value, 'icon' => 'TrendingUp', 'icon_color' => '#002c85'],
        ];
    }
}
