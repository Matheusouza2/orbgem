<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Models\PluggyItem;
use Illuminate\Support\Facades\DB;

class PluggySyncService
{
    public function __construct(private PluggyClient $client, private PluggyItemService $items, private AccountService $accounts) {}

    public function sync(PluggyItem $item): PluggyItem
    {
        $remoteItem = $this->client->item($item->pluggy_item_id);
        $remoteAccounts = $this->client->accounts($item->pluggy_item_id);

        return DB::transaction(function () use ($item, $remoteItem, $remoteAccounts): PluggyItem {
            $item = $this->items->upsert($item->user_id, $item->wallet_id, $item->pluggy_item_id, ['connector_id' => $remoteItem['connector']['id'] ?? null, 'connector_name' => $remoteItem['connector']['name'] ?? null, 'connector_logo' => $remoteItem['connector']['imageUrl'] ?? null, 'status' => $remoteItem['status'] ?? null, 'last_updated_at' => $remoteItem['lastUpdatedAt'] ?? null, 'error_code' => null, 'error_message' => null]);
            foreach ($remoteAccounts as $remoteAccount) {
                $balance = (int) round(((float) ($remoteAccount['balance'] ?? 0)) * 100);
                $this->accounts->syncPluggyAccount($item->wallet_id, $item->id, ['pluggy_account_id' => (string) ($remoteAccount['id'] ?? ''), 'name' => $remoteAccount['name'] ?? 'Conta Open Finance', 'institution' => $item->connector_name, 'account_number' => $remoteAccount['number'] ?? null, 'type' => $this->accountType($remoteAccount), 'initial_balance' => $balance, 'pluggy_balance' => $balance, 'active' => true]);
            }

            return $item->load('accounts');
        });
    }

    private function accountType(array $account): AccountType
    {
        return match (strtoupper((string) ($account['subtype'] ?? ''))) {
            'SAVINGS_ACCOUNT' => AccountType::SAVINGS,
            default => AccountType::CHECKING,
        };
    }
}
