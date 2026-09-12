<?php

namespace App\Repositories;

use App\Models\FinancialConnection;

interface FinancialConnectionRepositoryInterface
{
    public function upsert(int $walletId, string $provider, string $externalId, array $attributes): FinancialConnection;

    public function find(int $id): ?FinancialConnection;

    public function findByProviderExternalId(string $provider, string $externalId): ?FinancialConnection;
}
