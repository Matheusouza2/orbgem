<?php

namespace Tests\Feature;

use App\Enums\WalletMemberRole;
use App\Jobs\SyncPluggyConnectionJob;
use App\Jobs\SyncPluggyInvestmentTransactionsJob;
use App\Jobs\SyncPluggyTransactionsJob;
use App\Models\Account;
use App\Models\ExternalAccount;
use App\Models\ExternalInvestmentTransaction;
use App\Models\ExternalTransaction;
use App\Models\FinancialConnection;
use App\Models\Investment;
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
            'https://api.pluggy.ai/v2/transactions*' => Http::response(['results' => [['id' => 'transaction-1', 'description' => 'Salário', 'amount' => 1000, 'type' => 'CREDIT', 'date' => '2026-09-12', 'status' => 'POSTED']]], 200),
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

    public function test_it_imports_pluggy_investment_income_with_paginated_results_idempotently(): void
    {
        config(['services.pluggy.api_key' => 'test-api-key', 'services.pluggy.client_id' => null, 'services.pluggy.client_secret' => null]);
        $wallet = Wallet::query()->create(['name' => 'Carteira']);
        $user = User::factory()->create();
        WalletMember::query()->create(['wallet_id' => $wallet->id, 'user_id' => $user->id, 'role' => WalletMemberRole::OWNER, 'joined_at' => now()]);
        $connection = FinancialConnection::query()->create(['wallet_id' => $wallet->id, 'provider' => 'pluggy', 'external_id' => 'item-1', 'institution_name' => 'Banco Teste', 'status' => 'UPDATED']);
        $investment = Investment::query()->create(['wallet_id' => $wallet->id, 'name' => 'MXRF11', 'ticker' => 'MXRF11', 'type' => 'FII', 'quantity' => 10, 'average_price' => 1000, 'invested_amount' => 10000, 'current_value' => 11000, 'active' => true]);
        $externalInvestment = $connection->externalInvestments()->create(['investment_id' => $investment->id, 'source' => 'pluggy', 'external_id' => 'investment-1', 'raw_data' => ['id' => 'investment-1']]);
        Http::fake([
            'https://api.pluggy.ai/investments/investment-1/transactions*' => Http::sequence()
                ->push(['results' => [['id' => 'income-1', 'description' => 'Proceeds interests and dividends', 'amount' => 12.50, 'date' => '2026-09-10', 'type' => 'DIVIDEND']], 'next' => null], 200)
                ->push(['results' => [['id' => 'income-2', 'description' => 'Proceeds interests and dividends', 'amount' => 10.00, 'date' => '2026-08-10', 'type' => 'DIVIDEND']], 'next' => null], 200),
        ]);

        SyncPluggyInvestmentTransactionsJob::dispatchSync($externalInvestment->id);
        SyncPluggyInvestmentTransactionsJob::dispatchSync($externalInvestment->id);

        self::assertSame(2, ExternalInvestmentTransaction::query()->where('source', 'pluggy')->count());
        self::assertSame(1, ExternalInvestmentTransaction::query()->where('external_id', 'income-1')->count());

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/investment-income?wallet_id='.$wallet->id);
        $response->assertOk()->assertJsonCount(2, 'data');
    }
}
