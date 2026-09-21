<?php

namespace Tests\Feature;

use App\Models\DevicePushToken;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\Channels\FirebasePushChannel;
use App\Notifications\WalletActivityNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Mockery;
use Tests\TestCase;

class PushTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_push_token_routes_require_authentication_and_validate_mobile_platforms(): void
    {
        $this->postJson('/api/v1/mobile/push-tokens', ['token' => 'token', 'platform' => 'android'])->assertUnauthorized();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson('/api/v1/mobile/push-tokens', ['token' => 'token', 'platform' => 'windows'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['platform']);
    }

    public function test_authenticated_mobile_user_can_register_a_push_token_idempotently(): void
    {
        $user = User::factory()->create();
        $payload = ['token' => str_repeat('fcm-token-', 30), 'platform' => 'android', 'device_id' => 'pixel-9'];

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/mobile/push-tokens', $payload)
            ->assertCreated()
            ->assertJsonPath('data.platform', 'android')
            ->assertJsonPath('data.device_id', 'pixel-9')
            ->assertJsonMissingPath('data.token');

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/mobile/push-tokens', [...$payload, 'platform' => 'ios'])
            ->assertOk()
            ->assertJsonPath('data.platform', 'ios');

        $this->assertDatabaseCount('device_push_tokens', 1);
        $this->assertDatabaseHas('device_push_tokens', ['user_id' => $user->id, 'platform' => 'ios', 'device_id' => 'pixel-9']);
    }

    public function test_user_can_remove_only_their_own_push_token(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $token = DevicePushToken::query()->create(['user_id' => $user->id, 'token' => 'token-owner', 'platform' => 'android', 'last_seen_at' => now()]);
        DevicePushToken::query()->create(['user_id' => $other->id, 'token' => 'token-other', 'platform' => 'ios', 'last_seen_at' => now()]);

        $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/mobile/push-tokens', ['token' => 'token-other'])->assertNoContent();
        $this->assertDatabaseHas('device_push_tokens', ['id' => $token->id]);

        $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/mobile/push-tokens', ['token' => 'token-owner'])->assertNoContent();
        $this->assertDatabaseMissing('device_push_tokens', ['id' => $token->id]);
    }

    public function test_push_channel_sends_to_registered_tokens_without_exposing_them(): void
    {
        $user = User::factory()->create();
        DevicePushToken::query()->create(['user_id' => $user->id, 'token' => 'token-to-send', 'platform' => 'android', 'last_seen_at' => now()]);
        $messaging = Mockery::mock(Messaging::class);
        $messaging->shouldReceive('sendMulticast')->once()->withArgs(function ($message, array $tokens): bool {
            return $tokens === ['token-to-send'];
        })->andReturn(MulticastSendReport::withItems([]));
        $this->app->instance(Messaging::class, $messaging);

        (new FirebasePushChannel($messaging))->send($user, new WalletActivityNotification(7, 'transaction.created', 'Novo lançamento', 'Uma nova movimentação foi registrada.', ['transaction_id' => 9]));

        $this->assertDatabaseHas('device_push_tokens', ['token' => 'token-to-send']);
    }

    public function test_wallet_activity_notification_adds_firebase_channel_when_enabled(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::query()->create(['name' => 'Carteira de teste']);
        DevicePushToken::query()->create(['user_id' => $user->id, 'token' => 'token-enabled', 'platform' => 'ios', 'last_seen_at' => now()]);
        $messaging = Mockery::mock(Messaging::class);
        $messaging->shouldReceive('sendMulticast')->once()->andReturn(MulticastSendReport::withItems([]));
        $this->app->instance(Messaging::class, $messaging);
        config(['notifications.firebase_push_enabled' => true]);

        $user->notify(new WalletActivityNotification($wallet->id, 'transaction.created', 'Novo lançamento', null));

        $this->assertDatabaseCount('in_app_notifications', 1);
    }
}
