<?php

namespace App\Repositories;

use App\Models\ExternalAccount;
use Illuminate\Support\Collection;

interface ExternalAccountRepositoryInterface
{
    public function upsert(int $connectionId, string $externalId, array $attributes): ExternalAccount;

    public function find(int $id): ?ExternalAccount;

    public function forConnection(int $connectionId): Collection;
}
