<?php

namespace App\Repositories;

use App\Models\FinancialConnection;
use Illuminate\Support\Collection;

class FinancialConnectionRepository implements FinancialConnectionRepositoryInterface
{
    public function upsert(int $walletId, string $provider, string $externalId, array $attributes): FinancialConnection
    {
        return FinancialConnection::query()->updateOrCreate(
            ['provider' => $provider, 'external_id' => $externalId],
            ['wallet_id' => $walletId, ...$attributes],
        );
    }

    public function find(int $id): ?FinancialConnection
    {
        return FinancialConnection::query()->find($id);
    }

    public function findByProviderExternalId(string $provider, string $externalId): ?FinancialConnection
    {
        return FinancialConnection::query()->where(['provider' => $provider, 'external_id' => $externalId])->first();
    }

    public function forWallets(array $walletIds): Collection
    {
        return FinancialConnection::query()->whereIn('wallet_id', $walletIds)->with('externalAccounts.accountable')->latest()->get();
    }
}
