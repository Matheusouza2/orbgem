<?php

namespace App\Repositories;

use App\DTO\MerchantDTO;
use App\Models\Merchant;
use Illuminate\Support\Collection;

interface MerchantRepositoryInterface
{
    public function create(MerchantDTO $merchantDTO): Merchant;

    public function existsByNormalizedName(int $walletId, string $normalizedName): bool;

    public function forWallet(int $walletId): Collection;

    public function find(int $merchantId): ?Merchant;
}
