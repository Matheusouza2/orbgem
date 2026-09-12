<?php

namespace App\Services;

use App\DTO\InvestmentDTO;
use App\Models\Investment;
use App\Repositories\InvestmentRepositoryInterface;
use Illuminate\Support\Collection;

class InvestmentService
{
    public function __construct(private InvestmentRepositoryInterface $investmentRepository) {}

    public function create(InvestmentDTO $investmentDTO): Investment
    {
        return $this->investmentRepository->create($investmentDTO);
    }

    public function update(Investment $investment, InvestmentDTO $investmentDTO): Investment
    {
        return $this->investmentRepository->update($investment, $investmentDTO);
    }

    public function forWallet(int $walletId): Collection
    {
        return $this->investmentRepository->forWallet($walletId);
    }

    public function delete(Investment $investment): void
    {
        $this->investmentRepository->delete($investment);
    }

    public function activeCdi(): Collection
    {
        return $this->investmentRepository->activeCdi();
    }

    public function accrueCdi(Investment $investment, string $date, float $dailyRate, int $yieldAmount, int $closingValue): bool
    {
        return $this->investmentRepository->accrueCdi($investment, $date, $dailyRate, $yieldAmount, $closingValue);
    }
}
