<?php

namespace App\Services;

use App\DTO\InvestmentDTO;
use App\Enums\InvestmentType;
use App\Models\ExternalInvestment;
use App\Models\FinancialConnection;
use App\Repositories\ExternalInvestmentRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ExternalInvestmentService
{
    public function __construct(private ExternalInvestmentRepositoryInterface $repository, private InvestmentService $investments) {}

    /** @param array<string, mixed> $remote */
    public function sync(FinancialConnection $connection, array $remote): ExternalInvestment
    {
        return DB::transaction(function () use ($connection, $remote): ExternalInvestment {
            $externalId = (string) ($remote['id'] ?? $remote['investmentId'] ?? '');
            $existing = $this->repository->findBySourceAndExternalId('pluggy', $externalId);
            $quantity = (string) ($remote['quantity'] ?? $remote['balance']['quantity'] ?? 0);
            $currentValue = $this->cents($remote['currentValue'] ?? $remote['balance'] ?? $remote['marketValue'] ?? 0);
            $investedAmount = $this->cents($remote['investedAmount'] ?? $remote['costBasis'] ?? $currentValue);
            $averagePrice = $this->cents($remote['averagePrice'] ?? $remote['price'] ?? 0);
            $investment = $existing?->investment;
            $attributes = InvestmentDTO::fromArray([
                'wallet_id' => $connection->wallet_id,
                'name' => $remote['name'] ?? $remote['description'] ?? 'Investimento Pluggy',
                'ticker' => $remote['ticker'] ?? $remote['code'] ?? null,
                'type' => $this->type($remote)->value,
                'institution' => $connection->institution_name,
                'quantity' => $quantity,
                'average_price' => $averagePrice,
                'invested_amount' => $investedAmount,
                'current_value' => $currentValue,
                'acquired_at' => $remote['purchaseDate'] ?? null,
                'active' => true,
            ]);
            $investment = $investment === null ? $this->investments->create($attributes) : $this->investments->update($investment, $attributes);

            return $this->repository->upsert('pluggy', $externalId, [
                'financial_connection_id' => $connection->id,
                'investment_id' => $investment->id,
                'raw_data' => $remote,
            ]);
        });
    }

    /** @param array<string, mixed> $remote */
    private function type(array $remote): InvestmentType
    {
        $type = strtoupper((string) ($remote['type'] ?? $remote['subtype'] ?? 'OTHER'));

        return str_contains($type, 'FII') ? InvestmentType::FII : match ($type) {
            'EQUITY' => InvestmentType::STOCK,
            'MUTUAL_FUND' => InvestmentType::FUND,
            'ETF' => InvestmentType::FUND,
            'FIXED_INCOME' => InvestmentType::FIXED_INCOME,
            default => InvestmentType::OTHER,
        };
    }

    private function cents(mixed $value): int
    {
        return (int) round((float) $value * 100);
    }
}
