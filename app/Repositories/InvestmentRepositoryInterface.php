<?php

namespace App\Repositories;

use App\DTO\InvestmentDTO;
use App\Models\Investment;
use Illuminate\Support\Collection;

interface InvestmentRepositoryInterface
{
    public function create(InvestmentDTO $investmentDTO): Investment;

    public function update(Investment $investment, InvestmentDTO $investmentDTO): Investment;

    public function forWallet(int $walletId): Collection;

    public function delete(Investment $investment): void;

    public function activeCdi(): Collection;

    public function accrueCdi(Investment $investment, string $date, float $dailyRate, int $yieldAmount, int $closingValue): bool;

    public function find(int $investmentId): ?Investment;

    public function yields(Investment $investment, ?string $from = null, ?string $to = null): Collection;
}
