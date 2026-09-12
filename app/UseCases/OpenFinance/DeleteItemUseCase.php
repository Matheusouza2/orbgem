<?php

namespace App\UseCases\OpenFinance;

use App\Enums\WalletMemberRole;
use App\Models\User;
use App\Services\PluggyClient;
use App\Services\PluggyItemService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DeleteItemUseCase
{
    public function __construct(private PluggyClient $client, private PluggyItemService $items, private WalletService $wallets, private WalletMembershipAuthorization $auth) {}

    public function execute(User $user, int $id): void
    {
        $item = $this->items->findForUser($id, $user->id);
        if ($item === null || ($wallet = $this->wallets->find($item->wallet_id)) === null) {
            throw new ModelNotFoundException;
        }
        $this->auth->authorize($user, $wallet, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);
        $this->client->deleteItem($item->pluggy_item_id);
        $this->items->delete($item);
    }
}
