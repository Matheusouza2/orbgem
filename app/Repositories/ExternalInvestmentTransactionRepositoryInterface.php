<?php

namespace App\Repositories;

use App\Models\ExternalInvestmentTransaction;
use Illuminate\Support\Collection;

interface ExternalInvestmentTransactionRepositoryInterface
{
    public function upsert(string $source, string $externalId, array $attributes): ExternalInvestmentTransaction;

    public function forWallet(int $walletId, ?int $investmentId = null, ?string $from = null, ?string $to = null): Collection;
}
