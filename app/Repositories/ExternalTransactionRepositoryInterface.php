<?php

namespace App\Repositories;

use App\Models\ExternalTransaction;

interface ExternalTransactionRepositoryInterface
{
    public function findBySourceAndExternalId(string $source, string $externalId): ?ExternalTransaction;

    public function upsert(string $source, string $externalId, array $attributes): ExternalTransaction;
}
