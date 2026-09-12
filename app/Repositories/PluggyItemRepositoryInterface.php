<?php

namespace App\Repositories;

use App\Models\PluggyItem;
use Illuminate\Support\Collection;

interface PluggyItemRepositoryInterface
{
    public function upsert(int $userId, int $walletId, string $pluggyItemId, array $attributes): PluggyItem;

    public function forUser(int $userId): Collection;

    public function findForUser(int $id, int $userId): ?PluggyItem;

    public function findByPluggyId(string $itemId): ?PluggyItem;

    public function find(int $id): ?PluggyItem;

    public function delete(PluggyItem $item): void;
}
