<?php

namespace App\Repositories;

use App\Models\FinancialConnection;
use Illuminate\Support\Collection;

interface FinancialConnectionRepositoryInterface
{
    public function upsert(int $walletId, string $provider, string $externalId, array $attributes): FinancialConnection;

    public function find(int $id): ?FinancialConnection;

    public function delete(FinancialConnection $connection): void;

    public function findByProviderExternalId(string $provider, string $externalId): ?FinancialConnection;

    public function forWallets(array $walletIds): Collection;
}
