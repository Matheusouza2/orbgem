<?php

namespace App\Repositories;

use App\Models\ExternalAccount;
use Illuminate\Support\Collection;

class ExternalAccountRepository implements ExternalAccountRepositoryInterface
{
    public function upsert(int $connectionId, string $externalId, array $attributes): ExternalAccount
    {
        return ExternalAccount::query()->updateOrCreate(
            ['financial_connection_id' => $connectionId, 'external_id' => $externalId],
            $attributes,
        );
    }

    public function find(int $id): ?ExternalAccount
    {
        return ExternalAccount::query()->with('financialConnection.wallet')->find($id);
    }

    public function forConnection(int $connectionId): Collection
    {
        return ExternalAccount::query()->where('financial_connection_id', $connectionId)->get();
    }

    public function findByConnectionAndExternalId(int $connectionId, string $externalId): ?ExternalAccount
    {
        return ExternalAccount::query()->where(['financial_connection_id' => $connectionId, 'external_id' => $externalId])->first();
    }
}
