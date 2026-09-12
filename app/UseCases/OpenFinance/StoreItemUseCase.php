<?php

namespace App\UseCases\OpenFinance;

use App\Enums\WalletMemberRole;
use App\Models\PluggyItem;
use App\Models\User;
use App\Services\PluggyItemService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class StoreItemUseCase
{
    public function __construct(private PluggyItemService $items, private WalletService $wallets, private WalletMembershipAuthorization $auth) {}

    public function execute(User $user, int $walletId, string $itemId): PluggyItem
    {
        $wallet = $this->wallets->find($walletId);
        if ($wallet === null) {
            throw new ModelNotFoundException;
        }
        $this->auth->authorize($user, $wallet, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);
        $item = $this->items->upsert($user->id, $walletId, $itemId, ['status' => 'CREATING']);

        return $item->load('accounts');
    }
}
