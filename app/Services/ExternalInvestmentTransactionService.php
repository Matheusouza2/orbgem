<?php

namespace App\Services;

use App\DTO\PluggyInvestmentTransactionDTO;
use App\Models\ExternalInvestment;
use App\Models\ExternalInvestmentTransaction;
use App\Repositories\ExternalInvestmentTransactionRepositoryInterface;
use Illuminate\Support\Collection;

class ExternalInvestmentTransactionService
{
    public function __construct(private ExternalInvestmentTransactionRepositoryInterface $repository) {}

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
        return $this->repository->forWallet($walletId, $investmentId, $from, $to);
    }
}
