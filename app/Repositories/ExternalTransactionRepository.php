<?php

namespace App\Repositories;

use App\Models\ExternalTransaction;

class ExternalTransactionRepository implements ExternalTransactionRepositoryInterface
{
    public function findBySourceAndExternalId(string $source, string $externalId): ?ExternalTransaction
    {
        return ExternalTransaction::query()->where(['source' => $source, 'external_id' => $externalId])->first();
    }

    public function upsert(string $source, string $externalId, array $attributes): ExternalTransaction
    {
        return ExternalTransaction::query()->updateOrCreate(
            ['source' => $source, 'external_id' => $externalId],
            $attributes,
        );
    }
}
