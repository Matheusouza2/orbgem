<?php

namespace App\Services;

use App\Models\PluggyItem;
use App\Repositories\PluggyItemRepositoryInterface;
use Illuminate\Support\Collection;

class PluggyItemService
{
    public function __construct(private PluggyItemRepositoryInterface $items) {}

    public function upsert(int $userId, int $walletId, string $itemId, array $attributes): PluggyItem
    {
        return $this->items->upsert($userId, $walletId, $itemId, $attributes);
    }

    public function forUser(int $userId): Collection
    {
        return $this->items->forUser($userId);
    }

    public function findForUser(int $id, int $userId): ?PluggyItem
    {
        return $this->items->findForUser($id, $userId);
    }

    public function findByPluggyId(string $itemId): ?PluggyItem
    {
        return $this->items->findByPluggyId($itemId);
    }

    public function find(int $id): ?PluggyItem
    {
        return $this->items->find($id);
    }

    public function delete(PluggyItem $item): void
    {
        $this->items->delete($item);
    }
}
