<?php

namespace App\UseCases\Wallet;

use App\DTO\WalletDTO;
use App\Models\User;
use App\Models\Wallet;
use App\Services\CategoryService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;

class CreateWalletUseCase
{
    public function __construct(
        private WalletService $walletService,
        private CategoryService $categoryService,
    ) {}

    public function execute(WalletDTO $walletDTO, User $owner): Wallet
    {
        return DB::transaction(function () use ($walletDTO, $owner): Wallet {
            $wallet = $this->walletService->createWallet($walletDTO, $owner);
            $this->categoryService->ensureDefaultCategories($wallet->id);

            return $wallet;
        });
    }
}
