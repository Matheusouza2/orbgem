<?php

namespace App\UseCases\OpenFinance;

use App\Models\User;
use App\Services\FinancialConnectionService;
use App\Services\WalletService;
use Illuminate\Support\Collection;

class ListItemsUseCase
{
    public function __construct(private FinancialConnectionService $connections, private WalletService $wallets) {}

    public function execute(User $user): Collection
    {
        $walletIds = $this->wallets->listForMember($user)->pluck('id')->all();

        return $this->connections->forWallets($walletIds);
    }
}
