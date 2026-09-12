<?php

namespace Tests\Feature;

use App\Enums\WalletMemberRole;
use App\Jobs\SyncPluggyConnectionJob;
use App\Jobs\SyncPluggyTransactionsJob;
use App\Models\Account;
use App\Models\ExternalAccount;
use App\Models\ExternalTransaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PluggySynchronizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_maps_bank_accounts_and_imports_transactions_idempotently(): void
    {
        config(['services.pluggy.api_key' => 'test-api-key', 'services.pluggy.client_id' => null, 'services.pluggy.client_secret' => null]);
        $wallet = Wallet::query()->create(['name' => 'Carteira']);
        $user = User::factory()->create();
        WalletMember::query()->create(['wallet_id' => $wallet->id, 'user_id' => $user->id, 'role' => WalletMemberRole::OWNER, 'joined_at' => now()]);
        $connection = $wallet->financialConnections()->create(['provider' => 'pluggy', 'external_id' => 'item-1', 'institution_name' => 'Banco Teste', 'status' => 'UPDATED']);
        Http::fake([
            'https://api.pluggy.ai/items/item-1' => Http::response(['id' => 'item-1', 'status' => 'UPDATED', 'connector' => ['name' => 'Banco Teste']], 200),
            'https://api.pluggy.ai/accounts*' => Http::response(['results' => [['id' => 'account-1', 'type' => 'BANK', 'subtype' => 'CHECKING_ACCOUNT', 'name' => 'Conta principal', 'balance' => 100.00]]], 200),
            'https://api.pluggy.ai/transactions*' => Http::response(['results' => [['id' => 'transaction-1', 'description' => 'Salário', 'amount' => 1000, 'type' => 'CREDIT', 'date' => '2026-09-12', 'status' => 'POSTED']]], 200),
            'https://api.pluggy.ai/investments*' => Http::response(['results' => []], 200),
        ]);

        SyncPluggyConnectionJob::dispatchSync($connection->id);

        $externalAccount = ExternalAccount::query()->firstOrFail();
        self::assertInstanceOf(Account::class, $externalAccount->accountable);
        SyncPluggyTransactionsJob::dispatchSync($externalAccount->id);
        SyncPluggyTransactionsJob::dispatchSync($externalAccount->id);

        self::assertSame(1, ExternalTransaction::query()->where('source', 'pluggy')->where('external_id', 'transaction-1')->count());
        self::assertSame(1, $wallet->transactions()->where('description', 'Salário')->count());
    }
}
