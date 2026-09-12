<?php

namespace App\Repositories;

use App\DTO\BudgetDTO;
use App\Models\Budget;
use Illuminate\Support\Collection;

class BudgetRepository implements BudgetRepositoryInterface
{
    public function create(BudgetDTO $dto): Budget
    {
        return Budget::query()->updateOrCreate(['wallet_id' => $dto->walletId, 'category_id' => $dto->categoryId, 'reference_month' => $dto->referenceMonth], $dto->toArray());
    }

    public function forWallet(int $walletId, ?string $month = null): Collection
    {
        $q = Budget::query()->with('category')->where('wallet_id', $walletId);
        if ($month !== null) {
            $q->where('reference_month', $month);
        }

        return $q->orderBy('reference_month')->get();
    }

    public function find(int $id): ?Budget
    {
        return Budget::query()->find($id);
    }

    public function update(Budget $budget, BudgetDTO $dto): Budget
    {
        $budget->update($dto->toArray());

        return $budget->refresh();
    }

    public function delete(Budget $budget): void
    {
        $budget->delete();
    }
}
