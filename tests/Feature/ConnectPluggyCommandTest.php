<?php

namespace Tests\Feature;

use App\Jobs\SyncPluggyConnectionJob;
use App\Models\FinancialConnection;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ConnectPluggyCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_registers_an_item_and_dispatches_initial_synchronization(): void
    {
        config(['services.pluggy.api_key' => 'test-api-key', 'services.pluggy.client_id' => null, 'services.pluggy.client_secret' => null]);
        $wallet = Wallet::query()->create(['name' => 'Carteira principal']);
        Queue::fake();
        Http::fake(['https://api.pluggy.ai/items/item-1' => Http::response(['id' => 'item-1', 'status' => 'UPDATED', 'connector' => ['name' => 'Banco Teste']], 200)]);

        $this->artisan('pluggy:connect', ['--item' => 'item-1', '--wallet' => $wallet->id])
            ->expectsOutputToContain('Connection ID')
            ->expectsOutputToContain('Banco Teste')
            ->assertSuccessful();

        self::assertDatabaseHas('financial_connections', ['provider' => 'pluggy', 'external_id' => 'item-1', 'wallet_id' => $wallet->id]);
        Queue::assertPushed(SyncPluggyConnectionJob::class);
    }

    public function test_registering_the_same_item_and_wallet_reuses_the_connection(): void
    {
        config(['services.pluggy.api_key' => 'test-api-key', 'services.pluggy.client_id' => null, 'services.pluggy.client_secret' => null]);
        $wallet = Wallet::query()->create(['name' => 'Carteira principal']);
        Queue::fake();
        Http::fake(['https://api.pluggy.ai/items/item-1' => Http::response(['id' => 'item-1', 'status' => 'UPDATED'], 200)]);

        $this->artisan('pluggy:connect', ['--item' => 'item-1', '--wallet' => $wallet->id])->assertSuccessful();
        $this->artisan('pluggy:connect', ['--item' => 'item-1', '--wallet' => $wallet->id])->assertSuccessful();

        self::assertSame(1, FinancialConnection::query()->where('provider', 'pluggy')->where('external_id', 'item-1')->count());
    }
}
