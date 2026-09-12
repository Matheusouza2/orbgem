<?php

namespace App\Repositories;

use App\Models\ExternalInvestment;

class ExternalInvestmentRepository implements ExternalInvestmentRepositoryInterface
{
    public function findBySourceAndExternalId(string $source, string $externalId): ?ExternalInvestment
    {
        return ExternalInvestment::query()->where(['source' => $source, 'external_id' => $externalId])->first();
    }

    public function upsert(string $source, string $externalId, array $attributes): ExternalInvestment
    {
        return ExternalInvestment::query()->updateOrCreate(['source' => $source, 'external_id' => $externalId], $attributes);
    }
}
