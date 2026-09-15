<?php

namespace App\Services;

use App\Models\InvestmentPosition;
use App\Repositories\InvestmentPositionRepositoryInterface;
use Illuminate\Support\Collection;

class InvestmentPositionService
{
    public function __construct(private InvestmentPositionRepositoryInterface $repository) {}

    public function forInvestment(int $investmentId): Collection
    {
        return $this->repository->forInvestment($investmentId);
    }

    public function upsert(int $investmentId, array $attributes): InvestmentPosition
    {
        return $this->repository->upsert($investmentId, $attributes);
    }

    public function delete(InvestmentPosition $position): void
    {
        $this->repository->delete($position);
    }

    public function historyForWallet(int $walletId): Collection
    {
        return $this->repository->historyForWallet($walletId);
    }
}
