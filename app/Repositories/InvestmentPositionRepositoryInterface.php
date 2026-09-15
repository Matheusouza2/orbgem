<?php

namespace App\Repositories;

use App\Models\InvestmentPosition;
use Illuminate\Support\Collection;

interface InvestmentPositionRepositoryInterface
{
    public function forInvestment(int $investmentId): Collection;

    public function upsert(int $investmentId, array $attributes): InvestmentPosition;

    public function delete(InvestmentPosition $position): void;

    public function historyForWallet(int $walletId): Collection;
}
