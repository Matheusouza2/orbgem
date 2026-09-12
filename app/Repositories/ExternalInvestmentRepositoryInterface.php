<?php

namespace App\Repositories;

use App\Models\ExternalInvestment;

interface ExternalInvestmentRepositoryInterface
{
    public function findBySourceAndExternalId(string $source, string $externalId): ?ExternalInvestment;

    public function upsert(string $source, string $externalId, array $attributes): ExternalInvestment;
}
