<?php

namespace App\Repositories;

use App\Models\PluggyItem;
use Illuminate\Support\Collection;

class PluggyItemRepository implements PluggyItemRepositoryInterface
{
    public function upsert(int $userId, int $walletId, string $pluggyItemId, array $attributes): PluggyItem
    {
        return PluggyItem::query()->updateOrCreate(['pluggy_item_id' => $pluggyItemId], ['user_id' => $userId, 'wallet_id' => $walletId, ...$attributes]);
    }

    public function forUser(int $userId): Collection
    {
        return PluggyItem::query()->where('user_id', $userId)->with('accounts')->latest()->get();
    }

    public function findForUser(int $id, int $userId): ?PluggyItem
    {
        return PluggyItem::query()->where('id', $id)->where('user_id', $userId)->first();
    }

    public function findByPluggyId(string $itemId): ?PluggyItem
    {
        return PluggyItem::query()->where('pluggy_item_id', $itemId)->first();
    }

    public function find(int $id): ?PluggyItem
    {
        return PluggyItem::query()->find($id);
    }

    public function delete(PluggyItem $item): void
    {
        $item->delete();
    }
}
