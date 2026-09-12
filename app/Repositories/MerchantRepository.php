<?php

namespace App\Repositories;

use App\DTO\MerchantDTO;
use App\Models\Merchant;
use Illuminate\Support\Collection;

class MerchantRepository implements MerchantRepositoryInterface
{
    public function create(MerchantDTO $merchantDTO): Merchant
    {
        return Merchant::query()->create($merchantDTO->toArray());
    }

    public function existsByNormalizedName(int $walletId, string $normalizedName): bool
    {
        return Merchant::query()
            ->where('wallet_id', $walletId)
            ->where('normalized_name', $normalizedName)
            ->exists();
    }

    public function forWallet(int $walletId): Collection
    {
        return Merchant::query()->where('wallet_id', $walletId)->latest()->get();
    }

    public function find(int $merchantId): ?Merchant
    {
        return Merchant::query()->find($merchantId);
    }
}
