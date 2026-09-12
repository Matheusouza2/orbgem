<?php

namespace App\Repositories;

use App\DTO\CategoryDTO;
use App\Enums\TransactionType;
use App\Models\Category;
use Illuminate\Support\Collection;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function create(CategoryDTO $categoryDTO): Category
    {
        return Category::query()->create($categoryDTO->toArray());
    }

    public function update(Category $category, CategoryDTO $categoryDTO): Category
    {
        $category->update($categoryDTO->toArray());

        return $category->refresh();
    }

    public function find(int $categoryId): ?Category
    {
        return Category::query()->find($categoryId);
    }

    public function findForWalletByNameAndType(int $walletId, string $name, TransactionType $type): ?Category
    {
        return Category::query()
            ->where('wallet_id', $walletId)
            ->where('name', $name)
            ->where('type', $type)
            ->first();
    }

    public function forWalletIncludingGlobal(int $walletId): Collection
    {
        return Category::query()
            ->where(function ($query) use ($walletId): void {
                $query->where('wallet_id', $walletId)->orWhereNull('wallet_id');
            })
            ->latest()
            ->get();
    }
}
