<?php

namespace App\Services;

use App\DTO\MerchantDTO;
use App\Models\Merchant;
use App\Repositories\MerchantRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class MerchantService
{
    public function __construct(private MerchantRepositoryInterface $merchantRepository) {}

    public function create(MerchantDTO $merchantDTO): Merchant
    {
        $normalizedName = $this->normalizeName($merchantDTO->name);
        if ($this->merchantRepository->existsByNormalizedName($merchantDTO->walletId, $normalizedName)) {
            throw ValidationException::withMessages(['name' => 'A merchant with this name already exists in the wallet.']);
        }

        return $this->merchantRepository->create($merchantDTO->withNormalizedName($normalizedName));
    }

    public function listForWallet(int $walletId): Collection
    {
        return $this->merchantRepository->forWallet($walletId);
    }

    public function find(int $merchantId): ?Merchant
    {
        return $this->merchantRepository->find($merchantId);
    }

    public function belongsToWallet(Merchant $merchant, int $walletId): bool
    {
        return $merchant->wallet_id === $walletId;
    }

    private function normalizeName(string $name): string
    {
        $name = preg_replace('/\s+/u', ' ', trim($name)) ?? trim($name);

        return mb_strtolower($name);
    }
}
