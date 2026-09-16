<?php

namespace App\Services;

use App\Models\FinancialConnection;
use App\Repositories\FinancialConnectionRepositoryInterface;
use Illuminate\Support\Collection;

class FinancialConnectionService
{
    public function __construct(private FinancialConnectionRepositoryInterface $repository) {}

    public function upsertPluggy(int $walletId, string $itemId, array $item): FinancialConnection
    {
        return $this->repository->upsert($walletId, 'pluggy', $itemId, [
            'institution_name' => $item['connector']['name'] ?? $item['institution']['name'] ?? null,
            'status' => $item['status'] ?? null,
            'metadata' => [
                'connector_id' => $item['connector']['id'] ?? null,
                'connector_name' => $item['connector']['name'] ?? null,
                'connector_image_url' => $item['connector']['imageUrl'] ?? null,
                'last_updated_at' => $item['lastUpdatedAt'] ?? null,
            ],
        ]);
    }

    public function find(int $id): ?FinancialConnection
    {
        return $this->repository->find($id);
    }

    public function delete(FinancialConnection $connection): void
    {
        $this->repository->delete($connection);
    }

    public function findByProviderExternalId(string $provider, string $externalId): ?FinancialConnection
    {
        return $this->repository->findByProviderExternalId($provider, $externalId);
    }

    public function forWallets(array $walletIds): Collection
    {
        return $this->repository->forWallets($walletIds);
    }

    public function markSynced(FinancialConnection $connection): void
    {
        $connection->update(['last_synced_at' => now()]);
    }
}
