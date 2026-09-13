<?php

namespace Tests\Feature;

use App\Enums\InvestmentType;
use App\Enums\WalletMemberRole;
use App\Models\Investment;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InvestmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_external_investment_transactions_uses_a_mariadb_safe_index_name(): void
    {
        $indexNames = collect(Schema::getIndexes('external_investment_transactions'))
            ->pluck('name')
            ->all();

        $this->assertContains('external_investment_transactions_investment_date_idx', $indexNames);
    }

    public function test_cdi_investment_accrues_daily_and_does_not_duplicate_a_date(): void
    {
        [$user, $wallet] = $this->walletWithMember();
        Http::fake(['https://brapi.dev/api/v2/macro*' => Http::response(['results' => [['symbol' => 'cdi', 'observations' => [['date' => '2026-09-10', 'value' => 0.05], ['date' => '2026-09-12', 'value' => 0.05]]]]], 200)]);

        $investment = $this->actingAs($user, 'sanctum')->postJson('/api/v1/investments', [
            'wallet_id' => $wallet->id, 'name' => 'CDB 110% CDI', 'type' => InvestmentType::FIXED_INCOME->value, 'quantity' => '1',
            'average_price' => 100000, 'invested_amount' => 100000, 'current_value' => 100000, 'acquired_at' => '2026-09-10',
            'cdi_linked' => true, 'cdi_percentage' => 110, 'active' => true,
        ])->assertCreated()->json('data.id');

        $this->artisan('investments:accrue-cdi', ['--date' => '2026-09-10'])->assertSuccessful();
        $this->artisan('investments:accrue-cdi', ['--date' => '2026-09-10'])->assertSuccessful();

        $this->assertDatabaseHas('investment_yields', ['investment_id' => $investment, 'reference_date' => '2026-09-10 00:00:00', 'yield_amount' => 55, 'closing_value' => 100055]);
        $this->assertDatabaseCount('investment_yields', 1);
    }

    public function test_owner_can_create_list_update_and_delete_an_investment(): void
    {
        [$user, $wallet] = $this->walletWithMember();
        $payload = ['wallet_id' => $wallet->id, 'name' => 'Tesouro Selic 2029', 'ticker' => 'SELIC29', 'type' => InvestmentType::FIXED_INCOME->value, 'institution' => 'Corretora Orbital', 'quantity' => '2.50000000', 'average_price' => 100000, 'invested_amount' => 250000, 'current_value' => 260000, 'acquired_at' => '2026-09-01', 'active' => true];

        $investment = $this->actingAs($user, 'sanctum')->postJson('/api/v1/investments', $payload)->assertCreated()->assertJsonPath('data.name', $payload['name'])->assertJsonPath('data.profit_amount', 10000)->assertJsonPath('data.profit_percentage', 4)->json('data.id');
        $this->actingAs($user, 'sanctum')->getJson('/api/v1/investments?wallet_id='.$wallet->id)->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($user, 'sanctum')->putJson('/api/v1/investments/'.$investment, [...$payload, 'name' => 'Tesouro atualizado', 'current_value' => 270000])->assertOk()->assertJsonPath('data.name', 'Tesouro atualizado');
        $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/investments/'.$investment)->assertNoContent();
        $this->assertDatabaseCount('investments', 0);
    }

    public function test_owner_can_list_cdi_yield_history_for_an_investment(): void
    {
        [$user, $wallet] = $this->walletWithMember();
        $investment = Investment::query()->create([
            'wallet_id' => $wallet->id,
            'name' => 'CDB CDI',
            'type' => InvestmentType::FIXED_INCOME->value,
            'quantity' => 1,
            'average_price' => 100000,
            'invested_amount' => 100000,
            'current_value' => 100055,
            'cdi_linked' => true,
            'cdi_percentage' => 110,
            'active' => true,
        ]);
        $investment->yields()->create([
            'reference_date' => '2026-09-10',
            'cdi_daily_rate' => 0.05,
            'cdi_percentage' => 110,
            'opening_value' => 100000,
            'yield_amount' => 55,
            'closing_value' => 100055,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/investments/'.$investment->id.'/yields')
            ->assertOk()
            ->assertJsonPath('data.0.reference_date', '2026-09-10')
            ->assertJsonPath('data.0.yield_amount', 55);
    }

    public function test_viewer_cannot_create_an_investment(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::VIEWER);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/investments', ['wallet_id' => $wallet->id, 'name' => 'Investimento bloqueado', 'type' => InvestmentType::STOCK->value, 'quantity' => '1', 'average_price' => 100, 'invested_amount' => 100, 'current_value' => 100, 'active' => true])->assertForbidden();
    }

    /** @return array{User, Wallet} */
    private function walletWithMember(WalletMemberRole $role = WalletMemberRole::OWNER): array
    {
        $user = User::factory()->create();
        $wallet = Wallet::query()->create(['name' => 'Carteira']);
        WalletMember::query()->create(['wallet_id' => $wallet->id, 'user_id' => $user->id, 'role' => $role, 'joined_at' => now()]);

        return [$user, $wallet];
    }
}
