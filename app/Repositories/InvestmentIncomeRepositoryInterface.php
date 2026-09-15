<?php

namespace App\Repositories;

use App\Models\InvestmentIncome;
use Illuminate\Support\Collection;

interface InvestmentIncomeRepositoryInterface
{
    public function create(array $attributes): InvestmentIncome;

    public function forWallet(int $walletId, ?int $investmentId = null, ?string $from = null, ?string $to = null): Collection;
}
