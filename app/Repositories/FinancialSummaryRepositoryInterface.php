<?php

namespace App\Repositories;

use App\Models\FinancialSummary;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

interface FinancialSummaryRepositoryInterface
{
    public function find(int $walletId): ?FinancialSummary;

    /** @param array<string, mixed> $facts @param array<string, mixed>|null $analysis */
    public function save(int $walletId, array $facts, ?array $analysis, string $hash): FinancialSummary;

    /** Lançamentos efetivados (receitas e despesas) que entram nos totais, desde a data informada. */
    public function postedTransactionsSince(int $walletId, Carbon $from): Collection;

    public function activeInvestments(int $walletId): Collection;

    /** @return Collection<int, int> */
    public function walletIdsWithFinancialData(?int $onlyWalletId = null): Collection;
}
