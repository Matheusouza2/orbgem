<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class HealthAndAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_health_endpoint_returns_success(): void
    {
        $this->get('/up')->assertSuccessful();
    }

    public function test_unauthenticated_api_request_returns_json_unauthorized_response(): void
    {
        $this->getJson('/api/v1/user')
            ->assertUnauthorized()
            ->assertJsonStructure(['message']);
    }

    public function test_user_can_login_and_use_the_issued_sanctum_token(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'password',
            'device_name' => 'test-device',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'token_type',
                    'user' => ['id', 'name', 'email'],
                ],
            ])
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.email', 'user@example.com');

        $this->withHeader('Authorization', 'Bearer '.$response->json('data.token'))
            ->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonStructure(['data' => ['id', 'name', 'email']])
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_api_login_establishes_the_web_session_for_the_dashboard(): void
    {
        User::factory()->create(['email' => 'web@example.com']);

        $this->withHeader('Referer', url('/login'))->postJson('/api/v1/auth/login', [
            'email' => 'web@example.com',
            'password' => 'password',
            'device_name' => 'web-browser',
        ])->assertOk();

        $this->get('/dashboard-financeiro')->assertSuccessful();
    }

    public function test_root_page_is_rendered_as_the_login_page_by_inertia(): void
    {
        $this->withoutVite()->get('/')
            ->assertSuccessful()
            ->assertSee('Auth\\/Login');
    }

    public function test_register_page_is_rendered_by_inertia(): void
    {
        $this->withoutVite()->get('/register')
            ->assertSuccessful()
            ->assertSee('Auth\\/Register');
    }

    public function test_forgot_password_page_is_rendered_by_inertia(): void
    {
        $this->withoutVite()->get('/forgot-password')
            ->assertSuccessful()
            ->assertSee('Auth\\/ForgotPassword');
    }

    public function test_login_rejects_invalid_credentials_without_revealing_the_failure_reason(): void
    {
        User::factory()->create(['email' => 'user@example.com']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ])->assertUnauthorized()->assertJson(['message' => 'Invalid credentials.']);
    }

    public function test_login_validates_credentials_and_device_name(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'not-an-email',
            'password' => '',
            'device_name' => str_repeat('x', 256),
        ])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password', 'device_name']);
    }

    public function test_user_can_register_and_receive_a_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'device_name' => 'registration-device',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['data' => ['token', 'token_type', 'user']])
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', 'new@example.com');

        $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
        $user = User::query()->where('email', 'new@example.com')->firstOrFail();
        $wallet = Wallet::query()->whereHas('members', fn ($query) => $query->where('user_id', $user->id))->firstOrFail();

        $this->assertSame('Carteira principal', $wallet->name);
        $this->assertDatabaseHas('wallet_members', ['wallet_id' => $wallet->id, 'user_id' => $user->id, 'role' => 'OWNER']);
        $this->assertDatabaseHas('accounts', ['wallet_id' => $wallet->id, 'name' => 'Conta principal', 'is_default' => true]);
        $this->assertSame(6, Category::query()->where('wallet_id', $wallet->id)->count());
    }

    public function test_registration_establishes_the_web_session_for_the_dashboard(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Web User',
            'email' => 'web-registration@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'device_name' => 'web-browser',
        ])->assertCreated();

        $this->get('/dashboard-financeiro')->assertSuccessful();
    }

    public function test_user_can_list_revoke_and_logout_tokens(): void
    {
        $user = User::factory()->create();
        $firstToken = $user->createToken('first-device');
        $secondToken = $user->createToken('second-device');

        $this->withToken($secondToken->plainTextToken)
            ->getJson('/api/v1/auth/tokens')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['name' => 'first-device']);

        $this->withToken($secondToken->plainTextToken)
            ->deleteJson('/api/v1/auth/tokens/'.$firstToken->accessToken->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $firstToken->accessToken->id]);

        $this->withToken($secondToken->plainTextToken)
            ->postJson('/api/v1/auth/logout')
            ->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $secondToken->accessToken->id]);

        $this->app['auth']->forgetGuards();

        $this->withToken($secondToken->plainTextToken)
            ->getJson('/api/v1/user')
            ->assertUnauthorized();
    }

    public function test_user_can_logout_from_all_devices(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('first-device');
        $user->createToken('second-device');

        $this->withToken($token->plainTextToken)
            ->postJson('/api/v1/auth/logout-all')
            ->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_user_can_request_and_complete_a_password_reset(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'reset@example.com']);

        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])
            ->assertAccepted();

        Notification::assertSentTo($user, ResetPassword::class);

        $token = Password::broker()->createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk();

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertUnauthorized();

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'new-password',
        ])->assertOk();
    }
}
