<?php

namespace App\Repositories;

use App\DTO\BudgetDTO;
use App\Models\Budget;
use Illuminate\Support\Collection;

interface BudgetRepositoryInterface
{
    public function create(BudgetDTO $dto): Budget;

    public function forWallet(int $walletId, ?string $month = null): Collection;

    public function find(int $id): ?Budget;

    public function update(Budget $budget, BudgetDTO $dto): Budget;

    public function delete(Budget $budget): void;
}
