<?php

namespace App\Services;

use App\DTO\BudgetDTO;
use App\Models\Budget;
use App\Repositories\BudgetRepositoryInterface;
use Illuminate\Support\Collection;

class BudgetService
{
    public function __construct(private BudgetRepositoryInterface $repository) {}

    public function create(BudgetDTO $dto): Budget
    {
        return $this->repository->create($dto);
    }

    public function forWallet(int $id, ?string $month = null): Collection
    {
        return $this->repository->forWallet($id, $month);
    }

    public function find(int $id): ?Budget
    {
        return $this->repository->find($id);
    }

    public function update(Budget $budget, BudgetDTO $dto): Budget
    {
        return $this->repository->update($budget, $dto);
    }

    public function delete(Budget $budget): void
    {
        $this->repository->delete($budget);
    }
}
