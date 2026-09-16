<?php

namespace App\UseCases\OpenFinance;

use App\Enums\WalletMemberRole;
use App\Models\User;
use App\Services\FinancialConnectionService;
use App\Services\PluggyClient;
use App\Services\PluggyItemService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DeleteItemUseCase
{
    public function __construct(private PluggyClient $client, private PluggyItemService $items, private FinancialConnectionService $connections, private WalletService $wallets, private WalletMembershipAuthorization $auth) {}

    public function execute(User $user, int $id): void
    {
        $connection = $this->connections->find($id);
        if ($connection === null || ($wallet = $this->wallets->find($connection->wallet_id)) === null) {
            throw new ModelNotFoundException;
        }
        $this->auth->authorize($user, $wallet, WalletMemberRole::EDITOR, WalletMemberRole::OWNER);
        $this->client->deleteItem($connection->external_id);
        $legacyItem = $this->items->findByPluggyId($connection->external_id);
        if ($legacyItem !== null) {
            $this->items->delete($legacyItem);
        }
        $this->connections->delete($connection);
    }
}
