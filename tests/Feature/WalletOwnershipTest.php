<?php

namespace Tests\Feature;

use App\Enums\TransactionType;
use App\Enums\WalletMemberRole;
use App\Models\Category;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMember;
use App\Services\CategoryService;
use App\Services\WalletMembershipAuthorization;
use App\Services\WalletService;
use Database\Seeders\DefaultCategorySeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use LogicException;
use Tests\TestCase;

class WalletOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_wallet_creation_and_listing_return_unauthorized(): void
    {
        $this->postJson('/api/v1/wallets', ['name' => 'Personal'])
            ->assertUnauthorized();

        $this->getJson('/api/v1/wallets')
            ->assertUnauthorized();
    }

    public function test_authenticated_user_creating_wallet_gets_exactly_one_owner_membership(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/wallets', ['name' => 'Personal']);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Personal');

        $wallet = Wallet::query()->where('name', 'Personal')->firstOrFail();

        $this->assertDatabaseCount('wallet_members', 1);
        $this->assertDatabaseHas('wallet_members', [
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'role' => WalletMemberRole::OWNER->value,
        ]);
    }

    public function test_new_wallet_gets_the_default_income_and_expense_categories(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/wallets', ['name' => 'Personal'])
            ->assertCreated();

        $wallet = Wallet::query()->where('name', 'Personal')->firstOrFail();

        $this->assertEqualsCanonicalizing([
            'Alimentação', 'Lazer', 'Moradia', 'Saúde', 'Rendimento', 'Salário',
        ], Category::query()
            ->where('wallet_id', $wallet->id)
            ->pluck('name')
            ->all());

        $this->assertSame(4, Category::query()->where('wallet_id', $wallet->id)->where('type', TransactionType::EXPENSE)->count());
        $this->assertSame(2, Category::query()->where('wallet_id', $wallet->id)->where('type', TransactionType::INCOME)->count());

        $categoryVisuals = Category::query()
            ->where('wallet_id', $wallet->id)
            ->get(['name', 'icon', 'icon_color'])
            ->mapWithKeys(fn (Category $category): array => [$category->name => ['icon' => $category->icon, 'icon_color' => $category->icon_color]])
            ->all();
        ksort($categoryVisuals);

        $this->assertSame([
            'Alimentação' => ['icon' => 'Utensils', 'icon_color' => '#279112'],
            'Lazer' => ['icon' => 'Clapperboard', 'icon_color' => '#0050f0'],
            'Moradia' => ['icon' => 'Home', 'icon_color' => null],
            'Rendimento' => ['icon' => 'TrendingUp', 'icon_color' => '#002c85'],
            'Salário' => ['icon' => 'DollarSign', 'icon_color' => '#129138'],
            'Saúde' => ['icon' => 'HeartPulse', 'icon_color' => '#ff0000'],
        ], $categoryVisuals);
    }

    public function test_default_category_initialization_is_idempotent_and_preserves_custom_categories(): void
    {
        $wallet = Wallet::create(['name' => 'Personal']);
        Category::create([
            'wallet_id' => $wallet->id,
            'name' => 'Minha categoria',
            'type' => TransactionType::EXPENSE,
            'active' => true,
        ]);

        $categoryService = app(CategoryService::class);
        $categoryService->ensureDefaultCategories($wallet->id);
        $categoryService->ensureDefaultCategories($wallet->id);

        $this->assertSame(7, Category::query()->where('wallet_id', $wallet->id)->count());
        $this->assertDatabaseHas('categories', ['wallet_id' => $wallet->id, 'name' => 'Minha categoria']);
    }

    public function test_default_category_seeder_applies_the_configured_visuals_to_existing_categories(): void
    {
        $wallet = Wallet::create(['name' => 'Personal']);
        app(CategoryService::class)->ensureDefaultCategories($wallet->id);

        $this->seed(DefaultCategorySeeder::class);

        $this->assertDatabaseHas('categories', [
            'wallet_id' => $wallet->id,
            'name' => 'Alimentação',
            'icon' => 'Utensils',
            'icon_color' => '#279112',
        ]);
        $this->assertDatabaseHas('categories', [
            'wallet_id' => $wallet->id,
            'name' => 'Moradia',
            'icon' => 'Home',
            'icon_color' => null,
        ]);
    }

    public function test_authenticated_user_can_create_a_wallet_with_only_its_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/wallets', [
                'name' => 'Carteira principal',
                'bank_code' => '60701190',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Carteira principal')
            ->assertJsonMissingPath('data.bank_code');

        $this->assertDatabaseHas('wallets', [
            'name' => 'Carteira principal',
        ]);
    }

    public function test_wallet_creation_does_not_accept_account_attributes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/wallets', [
                'name' => 'Carteira principal',
                'bank_code' => '341',
            ])
            ->assertCreated()
            ->assertJsonMissingPath('data.bank_code');
    }

    public function test_editor_can_update_wallet_name(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::EDITOR);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/wallets/{$wallet->id}", [
                'name' => 'Carteira atualizada',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Carteira atualizada');

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'name' => 'Carteira atualizada',
        ]);
    }

    public function test_viewer_cannot_update_wallet_details(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::VIEWER);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/wallets/{$wallet->id}", [
                'name' => 'Alteração indevida',
            ])
            ->assertForbidden();
    }

    public function test_member_cannot_update_another_wallet(): void
    {
        [$user] = $this->walletWithMember(WalletMemberRole::EDITOR);
        $otherWallet = Wallet::create(['name' => 'Outra carteira']);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/wallets/{$otherWallet->id}", [
                'name' => 'Alteração cruzada',
            ])
            ->assertForbidden();
    }

    public function test_listing_returns_only_wallets_where_the_user_is_a_member(): void
    {
        $user = User::factory()->create();
        $memberWallet = Wallet::create(['name' => 'Member wallet']);
        $otherWallet = Wallet::create(['name' => 'Other wallet']);
        WalletMember::create([
            'wallet_id' => $memberWallet->id,
            'user_id' => $user->id,
            'role' => WalletMemberRole::VIEWER,
            'joined_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/wallets');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Member wallet')
            ->assertJsonMissing(['name' => $otherWallet->name]);
    }

    public function test_wallet_member_can_list_members_with_public_user_fields(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::VIEWER);
        $otherUser = User::factory()->create();
        $membership = WalletMember::create([
            'wallet_id' => $wallet->id,
            'user_id' => $otherUser->id,
            'role' => WalletMemberRole::EDITOR,
            'joined_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/wallets/{$wallet->id}/members");

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.1.id', $membership->id)
            ->assertJsonPath('data.1.user.id', $otherUser->id)
            ->assertJsonPath('data.1.user.email', $otherUser->email)
            ->assertJsonPath('data.1.role', WalletMemberRole::EDITOR->value);
    }

    public function test_owner_can_add_member_and_duplicate_membership_is_rejected(): void
    {
        [$owner, $wallet] = $this->walletWithMember(WalletMemberRole::OWNER);
        $newUser = User::factory()->create();

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/wallets/{$wallet->id}/members", [
                'user_id' => $newUser->id,
                'role' => WalletMemberRole::VIEWER->value,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.user.id', $newUser->id)
            ->assertJsonPath('data.role', WalletMemberRole::VIEWER->value);

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/wallets/{$wallet->id}/members", [
                'user_id' => $newUser->id,
                'role' => WalletMemberRole::EDITOR->value,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('user_id');

        $this->assertDatabaseCount('wallet_members', 2);
    }

    public function test_only_owner_can_add_change_and_remove_members(): void
    {
        [$owner, $wallet] = $this->walletWithMember(WalletMemberRole::OWNER);
        $editor = User::factory()->create();
        $viewer = User::factory()->create();
        $editorMembership = WalletMember::create(['wallet_id' => $wallet->id, 'user_id' => $editor->id, 'role' => WalletMemberRole::EDITOR, 'joined_at' => Carbon::now()]);
        $viewerMembership = WalletMember::create(['wallet_id' => $wallet->id, 'user_id' => $viewer->id, 'role' => WalletMemberRole::VIEWER, 'joined_at' => Carbon::now()]);
        $candidate = User::factory()->create();

        foreach ([[$editor, 'post', 'members', ['user_id' => $candidate->id, 'role' => WalletMemberRole::VIEWER->value]], [$editor, 'patch', "members/{$viewerMembership->id}", ['role' => WalletMemberRole::EDITOR->value]], [$editor, 'delete', "members/{$viewerMembership->id}", []]] as [$actor, $method, $suffix, $payload]) {
            $this->actingAs($actor, 'sanctum')->json($method, "/api/v1/wallets/{$wallet->id}/{$suffix}", $payload)->assertForbidden();
        }

        $this->actingAs($owner, 'sanctum')->patchJson("/api/v1/wallets/{$wallet->id}/members/{$editorMembership->id}", ['role' => WalletMemberRole::VIEWER->value])->assertOk()->assertJsonPath('data.role', WalletMemberRole::VIEWER->value);
        $this->actingAs($owner, 'sanctum')->deleteJson("/api/v1/wallets/{$wallet->id}/members/{$viewerMembership->id}")->assertNoContent();
        $this->assertDatabaseMissing('wallet_members', ['id' => $viewerMembership->id]);
    }

    public function test_last_owner_cannot_be_demoted_or_removed_through_the_api(): void
    {
        [$owner, $wallet, $membership] = $this->walletWithMember(WalletMemberRole::OWNER);

        $this->actingAs($owner, 'sanctum')->patchJson("/api/v1/wallets/{$wallet->id}/members/{$membership->id}", ['role' => WalletMemberRole::EDITOR->value])->assertUnprocessable()->assertJsonValidationErrors('role');
        $this->actingAs($owner, 'sanctum')->deleteJson("/api/v1/wallets/{$wallet->id}/members/{$membership->id}")->assertUnprocessable()->assertJsonValidationErrors('member');
        $this->assertDatabaseHas('wallet_members', ['id' => $membership->id, 'role' => WalletMemberRole::OWNER->value]);
    }

    public function test_member_routes_reject_cross_wallet_members_and_guests(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::OWNER);
        $otherWallet = Wallet::create(['name' => 'Other wallet']);
        $otherUser = User::factory()->create();
        $otherMembership = WalletMember::create(['wallet_id' => $otherWallet->id, 'user_id' => $otherUser->id, 'role' => WalletMemberRole::OWNER, 'joined_at' => Carbon::now()]);

        $this->getJson("/api/v1/wallets/{$wallet->id}/members")->assertUnauthorized();
        $this->actingAs($user, 'sanctum')->patchJson("/api/v1/wallets/{$wallet->id}/members/{$otherMembership->id}", ['role' => WalletMemberRole::VIEWER->value])->assertNotFound();
        $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/wallets/{$wallet->id}/members/{$otherMembership->id}")->assertNotFound();
    }

    public function test_duplicate_membership_is_rejected_by_the_database(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::create(['name' => 'Shared wallet']);
        $membership = [
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'role' => WalletMemberRole::OWNER,
            'joined_at' => Carbon::now(),
        ];

        WalletMember::create($membership);

        $this->expectException(QueryException::class);
        WalletMember::create($membership);
    }

    public function test_last_owner_cannot_be_removed(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::create(['name' => 'Protected wallet']);
        $membership = WalletMember::create([
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'role' => WalletMemberRole::OWNER,
            'joined_at' => Carbon::now(),
        ]);

        try {
            app(WalletService::class)->removeMember($membership);
            $this->fail('The last owner should not be removable.');
        } catch (LogicException) {
            $this->assertDatabaseHas('wallet_members', [
                'id' => $membership->id,
                'role' => WalletMemberRole::OWNER->value,
            ]);
        }
    }

    public function test_last_owner_cannot_be_demoted(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::create(['name' => 'Protected wallet']);
        $membership = WalletMember::create([
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'role' => WalletMemberRole::OWNER,
            'joined_at' => Carbon::now(),
        ]);

        try {
            app(WalletService::class)->changeMemberRole($membership, WalletMemberRole::EDITOR);
            $this->fail('The last owner should not be demotable.');
        } catch (LogicException) {
            $this->assertDatabaseHas('wallet_members', [
                'id' => $membership->id,
                'role' => WalletMemberRole::OWNER->value,
            ]);
        }
    }

    public function test_multiple_owners_allow_one_owner_to_be_removed(): void
    {
        $firstOwner = User::factory()->create();
        $secondOwner = User::factory()->create();
        $wallet = Wallet::create(['name' => 'Shared wallet']);
        $membership = WalletMember::create([
            'wallet_id' => $wallet->id,
            'user_id' => $firstOwner->id,
            'role' => WalletMemberRole::OWNER,
            'joined_at' => Carbon::now(),
        ]);
        WalletMember::create([
            'wallet_id' => $wallet->id,
            'user_id' => $secondOwner->id,
            'role' => WalletMemberRole::OWNER,
            'joined_at' => Carbon::now(),
        ]);

        app(WalletService::class)->removeMember($membership);

        $this->assertDatabaseMissing('wallet_members', ['id' => $membership->id]);
        $this->assertSame(1, $wallet->ownerMemberships()->count());
    }

    public function test_membership_authorization_allows_requested_role_and_rejects_insufficient_role(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::create(['name' => 'Authorized wallet']);
        WalletMember::create([
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'role' => WalletMemberRole::EDITOR,
            'joined_at' => Carbon::now(),
        ]);

        $authorization = app(WalletMembershipAuthorization::class);

        $this->assertSame(WalletMemberRole::EDITOR, $authorization
            ->authorize($user, $wallet, WalletMemberRole::EDITOR)
            ->role);

        $this->expectException(AuthorizationException::class);
        $authorization->authorize($user, $wallet, WalletMemberRole::OWNER);
    }

    public function test_membership_authorization_resolves_each_initial_role(): void
    {
        $authorization = app(WalletMembershipAuthorization::class);

        foreach (WalletMemberRole::cases() as $role) {
            $user = User::factory()->create();
            $wallet = Wallet::create(['name' => $role->value.' wallet']);
            WalletMember::create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'role' => $role,
                'joined_at' => Carbon::now(),
            ]);

            $this->assertSame($role, $authorization->authorize($user, $wallet, $role)->role);
        }

        $viewer = User::factory()->create();
        $wallet = Wallet::create(['name' => 'Viewer wallet']);
        WalletMember::create([
            'wallet_id' => $wallet->id,
            'user_id' => $viewer->id,
            'role' => WalletMemberRole::VIEWER,
            'joined_at' => Carbon::now(),
        ]);

        $this->expectException(AuthorizationException::class);
        $authorization->authorize($viewer, $wallet, WalletMemberRole::EDITOR);
    }

    /** @return array{User, Wallet, WalletMember} */
    private function walletWithMember(WalletMemberRole $role): array
    {
        $user = User::factory()->create();
        $wallet = Wallet::create(['name' => uniqid('wallet')]);
        $member = WalletMember::create([
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'role' => $role,
            'joined_at' => Carbon::now(),
        ]);

        return [$user, $wallet, $member];
    }
}
