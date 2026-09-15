<?php

namespace App\Services;

use App\DTO\PluggyInvestmentTransactionDTO;
use App\Models\ExternalInvestment;
use App\Models\ExternalInvestmentTransaction;
use App\Models\Investment;
use App\Models\InvestmentIncome;
use App\Repositories\ExternalInvestmentTransactionRepositoryInterface;
use App\Repositories\InvestmentIncomeRepositoryInterface;
use Illuminate\Support\Collection;

class ExternalInvestmentTransactionService
{
    public function __construct(private ExternalInvestmentTransactionRepositoryInterface $repository, private InvestmentIncomeRepositoryInterface $manualIncome) {}

    public function sync(ExternalInvestment $externalInvestment, PluggyInvestmentTransactionDTO $dto): ExternalInvestmentTransaction
    {
        return $this->repository->upsert('pluggy', $dto->externalId, [
            'external_investment_id' => $externalInvestment->id,
            'investment_id' => $externalInvestment->investment_id,
            'event_type' => $dto->eventType,
            'description' => $dto->description,
            'amount' => $dto->amount,
            'transaction_date' => $dto->date,
            'imported_at' => now(),
            'raw_data' => $dto->rawData,
        ]);
    }

    public function forWallet(int $walletId, ?int $investmentId = null, ?string $from = null, ?string $to = null): Collection
    {
        return $this->repository->forWallet($walletId, $investmentId, $from, $to)
            ->concat($this->manualIncome->forWallet($walletId, $investmentId, $from, $to))
            ->sortByDesc(fn (ExternalInvestmentTransaction|InvestmentIncome $income) => $income->transaction_date)
            ->values();
    }

    public function createManual(Investment $investment, int $amount, string $date): InvestmentIncome
    {
        return $this->manualIncome->create([
            'investment_id' => $investment->id,
            'description' => 'Rendimento lançado manualmente',
            'event_type' => 'MANUAL',
            'amount' => $amount,
            'transaction_date' => $date,
            'source' => 'MANUAL',
        ]);
    }
}
