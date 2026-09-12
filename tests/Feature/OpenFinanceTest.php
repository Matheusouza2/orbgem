<?php

namespace Tests\Feature;

use App\Enums\WalletMemberRole;
use App\Jobs\SyncPluggyItem;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OpenFinanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_request_a_connect_token_without_exposing_credentials(): void
    {
        [$user, $wallet] = $this->walletWithMember();
        config(['services.pluggy.api_key' => 'test-api-key', 'services.pluggy.client_id' => null, 'services.pluggy.client_secret' => null]);
        Http::fake(['https://api.pluggy.ai/connect_token' => Http::response(['accessToken' => 'connect-token'], 200)]);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/open-finance/connect-token', ['wallet_id' => $wallet->id])->assertOk()->assertJson(['accessToken' => 'connect-token']);

        Http::assertSent(fn ($request): bool => $request->hasHeader('X-API-KEY', 'test-api-key') && $request->data()['options']['clientUserId'] === (string) $user->id);
    }

    public function test_it_refreshes_the_api_key_after_an_unauthorized_response(): void
    {
        [$user, $wallet] = $this->walletWithMember();
        config(['services.pluggy.api_key' => 'expired-api-key', 'services.pluggy.client_id' => 'client-id', 'services.pluggy.client_secret' => 'client-secret']);
        $connectTokenAttempts = 0;

        Http::fake(function ($request) use (&$connectTokenAttempts) {
            if (str_ends_with($request->url(), '/auth')) {
                return Http::response(['apiKey' => 'refreshed-api-key'], 200);
            }

            if (str_ends_with($request->url(), '/connect_token')) {
                $connectTokenAttempts++;

                return $connectTokenAttempts === 1
                    ? Http::response(['message' => 'Missing or invalid authorization token'], 403)
                    : Http::response(['accessToken' => 'connect-token'], 200);
            }

            return Http::response([], 404);
        });

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/open-finance/connect-token', ['wallet_id' => $wallet->id])
            ->assertOk()
            ->assertJson(['accessToken' => 'connect-token']);

        Http::assertSent(fn ($request): bool => str_ends_with($request->url(), '/connect_token') && $request->hasHeader('X-API-KEY', 'refreshed-api-key'));
    }

    public function test_webhook_queues_entity_synchronization(): void
    {
        Queue::fake();
        [$user, $wallet] = $this->walletWithMember();
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/open-finance/items', ['wallet_id' => $wallet->id, 'item_id' => '123e4567-e89b-12d3-a456-426614174000'])->assertCreated();

        $this->postJson('/api/v1/open-finance/webhook', ['event' => 'item/updated', 'itemId' => '123e4567-e89b-12d3-a456-426614174000'])->assertOk();
        Queue::assertPushed(SyncPluggyItem::class);
    }

    /** @return array{User, Wallet} */
    private function walletWithMember(): array
    {
        $user = User::factory()->create();
        $wallet = Wallet::query()->create(['name' => 'Carteira']);
        WalletMember::query()->create(['wallet_id' => $wallet->id, 'user_id' => $user->id, 'role' => WalletMemberRole::OWNER, 'joined_at' => now()]);

        return [$user, $wallet];
    }
}
