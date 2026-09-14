<?php

namespace Tests\Feature;

use App\Enums\WalletMemberRole;
use App\Jobs\SyncPluggyConnectionJob;
use App\Jobs\SyncPluggyInvestmentsJob;
use App\Jobs\SyncPluggyInvestmentTransactionsJob;
use App\Jobs\SyncPluggyTransactionsJob;
use App\Models\Account;
use App\Models\CreditCard;
use App\Models\CreditCardInvoice;
use App\Models\ExternalAccount;
use App\Models\ExternalInvestmentTransaction;
use App\Models\ExternalTransaction;
use App\Models\FinancialConnection;
use App\Models\Investment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PluggySynchronizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_links_imported_credit_card_transactions_to_the_correct_invoice(): void
    {
        config(['services.pluggy.api_key' => 'test-api-key', 'services.pluggy.client_id' => null, 'services.pluggy.client_secret' => null]);
        $wallet = Wallet::query()->create(['name' => 'Carteira']);
        $user = User::factory()->create();
        WalletMember::query()->create(['wallet_id' => $wallet->id, 'user_id' => $user->id, 'role' => WalletMemberRole::OWNER, 'joined_at' => now()]);
        $connection = $wallet->financialConnections()->create(['provider' => 'pluggy', 'external_id' => 'item-card', 'institution_name' => 'Banco Teste', 'status' => 'UPDATED']);
        Http::fake([
            'https://api.pluggy.ai/items/item-card' => Http::response(['id' => 'item-card', 'status' => 'UPDATED', 'connector' => ['name' => 'Banco Teste']], 200),
            'https://api.pluggy.ai/accounts*' => Http::response(['results' => [['id' => 'card-1', 'type' => 'CREDIT', 'subtype' => 'CREDIT_CARD', 'name' => 'Cartão principal', 'creditLimit' => 1000]]], 200),
            'https://api.pluggy.ai/v2/transactions*' => Http::response(['results' => [['id' => 'card-transaction-1', 'description' => 'Compra no cartão', 'amount' => 125.50, 'type' => 'DEBIT', 'date' => '2026-09-12', 'status' => 'POSTED']]], 200),
            'https://api.pluggy.ai/investments*' => Http::response(['results' => []], 200),
        ]);

        SyncPluggyConnectionJob::dispatchSync($connection->id);

        $externalAccount = ExternalAccount::query()->firstOrFail();
        SyncPluggyTransactionsJob::dispatchSync($externalAccount->id);

        $card = CreditCard::query()->firstOrFail();
        $invoice = CreditCardInvoice::query()->where('credit_card_id', $card->id)->firstOrFail();
        $transaction = $wallet->transactions()->where('description', 'Compra no cartão')->firstOrFail();

        self::assertSame('2026-10', $invoice->reference_month);
        self::assertSame($invoice->id, $transaction->credit_card_invoice_id);
        self::assertSame(0, $invoice->installments()->sum('amount'));
        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/credit-cards?wallet_id='.$wallet->id)
            ->assertOk()
            ->assertJsonPath('data.0.current_invoice_amount', 12550)
            ->assertJsonPath('data.0.limit_usage_percentage', 12.55);
    }

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

        $transaction = $wallet->transactions()->where('description', 'Salário')->firstOrFail();
        $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/transactions/'.$transaction->id)->assertNoContent();
        self::assertNull(ExternalTransaction::query()->where('external_id', 'transaction-1')->value('transaction_id'));

        SyncPluggyTransactionsJob::dispatchSync($externalAccount->id);

        self::assertSame(0, $wallet->transactions()->where('description', 'Salário')->count());
    }

    public function test_it_uses_pluggy_bill_forecast_month_for_future_card_installments(): void
    {
        config(['services.pluggy.api_key' => 'test-api-key', 'services.pluggy.client_id' => null, 'services.pluggy.client_secret' => null]);
        $wallet = Wallet::query()->create(['name' => 'Carteira']);
        $user = User::factory()->create();
        $member = WalletMember::query()->create(['wallet_id' => $wallet->id, 'user_id' => $user->id, 'role' => WalletMemberRole::OWNER, 'joined_at' => now()]);
        $card = CreditCard::query()->create(['wallet_id' => $wallet->id, 'owner_wallet_member_id' => $member->id, 'name' => 'Cartão', 'credit_limit' => 100000, 'closing_day' => 5, 'due_day' => 12, 'active' => true]);
        $connection = FinancialConnection::query()->create(['wallet_id' => $wallet->id, 'provider' => 'pluggy', 'external_id' => 'item-1', 'institution_name' => 'Banco Teste', 'status' => 'UPDATED']);
        $externalAccount = ExternalAccount::query()->create(['financial_connection_id' => $connection->id, 'external_id' => 'card-1', 'type' => 'CREDIT', 'subtype' => 'CREDIT_CARD', 'accountable_type' => CreditCard::class, 'accountable_id' => $card->id]);
        Http::fake([
            'https://api.pluggy.ai/v2/transactions*' => Http::response(['results' => [[
                'id' => 'installment-3',
                'description' => 'Compra parcelada',
                'amount' => 100,
                'type' => 'DEBIT',
                'date' => '2026-09-03',
                'status' => 'PENDING',
                'creditCardMetadata' => [
                    'installmentNumber' => 3,
                    'totalInstallments' => 4,
                    'totalAmount' => 400,
                    'billForecastDate' => '2026-10',
                ],
            ]], 'next' => null], 200),
        ]);

        SyncPluggyTransactionsJob::dispatchSync($externalAccount->id);

        $transaction = Transaction::query()->where('description', 'Compra parcelada')->firstOrFail();
        self::assertSame('2026-10-01', $transaction->competence_date->toDateString());
        self::assertSame('2026-10-12', $transaction->due_date->toDateString());
        self::assertSame('2026-10', $transaction->creditCardInvoice->reference_month);
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

    public function test_it_stores_pluggy_investment_values_as_integer_cents(): void
    {
        config(['services.pluggy.api_key' => 'test-api-key', 'services.pluggy.client_id' => null, 'services.pluggy.client_secret' => null]);
        $wallet = Wallet::query()->create(['name' => 'Carteira']);
        $connection = FinancialConnection::query()->create([
            'wallet_id' => $wallet->id,
            'provider' => 'pluggy',
            'external_id' => 'item-1',
            'institution_name' => 'Banco Teste',
            'status' => 'UPDATED',
        ]);

        Http::fake([
            'https://api.pluggy.ai/investments*' => Http::response(['results' => [[
                'id' => 'investment-1',
                'name' => 'MXRF11',
                'code' => 'MXRF11',
                'type' => 'EQUITY',
                'balance' => 916,
                'amountOriginal' => 916,
                'value' => 91.60,
                'quantity' => 10,
            ]]], 200),
            'https://api.pluggy.ai/investments/investment-1/transactions*' => Http::response(['results' => []], 200),
        ]);

        SyncPluggyInvestmentsJob::dispatchSync($connection->id);

        $investment = Investment::query()->where('ticker', 'MXRF11')->firstOrFail();
        self::assertSame(91600, $investment->invested_amount);
        self::assertSame(91600, $investment->current_value);
        self::assertSame(9160, $investment->average_price);
    }
}
