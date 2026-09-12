<?php

namespace App\Repositories;

use App\DTO\CategoryDTO;
use App\Enums\TransactionType;
use App\Models\Category;
use Illuminate\Support\Collection;

interface CategoryRepositoryInterface
{
    public function create(CategoryDTO $categoryDTO): Category;

    public function update(Category $category, CategoryDTO $categoryDTO): Category;

    public function find(int $categoryId): ?Category;

    public function findForWalletByNameAndType(int $walletId, string $name, TransactionType $type): ?Category;

    public function forWalletIncludingGlobal(int $walletId): Collection;
}
