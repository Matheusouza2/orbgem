<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\TransactionEffect;
use App\Enums\TransactionType;
use App\Enums\WalletMemberRole;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMember;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AccountAndCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_account_and_category_routes_return_unauthorized(): void
    {
        $this->postJson('/api/v1/accounts', [])->assertUnauthorized();
        $this->getJson('/api/v1/accounts?wallet_id=1')->assertUnauthorized();
        $this->postJson('/api/v1/categories', [])->assertUnauthorized();
        $this->getJson('/api/v1/categories?wallet_id=1')->assertUnauthorized();
    }

    public function test_editor_can_create_an_account_with_owner_member_and_resource_contract(): void
    {
        [$user, $wallet, $ownerMember] = $this->walletWithMember(WalletMemberRole::EDITOR);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/accounts', [
            'wallet_id' => $wallet->id,
            'owner_wallet_member_id' => $ownerMember->id,
            'name' => 'Conta corrente',
            'institution' => 'Banco Central',
            'type' => AccountType::CHECKING->value,
            'initial_balance' => 125050,
            'active' => true,
        ]);

        $response->assertCreated()->assertJsonPath('data.name', 'Conta corrente')
            ->assertJsonPath('data.wallet_id', $wallet->id)
            ->assertJsonPath('data.owner_wallet_member_id', $ownerMember->id)
            ->assertJsonPath('data.type', AccountType::CHECKING->value)
            ->assertJsonPath('data.initial_balance', 125050)
            ->assertJsonPath('data.active', true);

        $this->assertDatabaseHas('accounts', [
            'wallet_id' => $wallet->id,
            'owner_wallet_member_id' => $ownerMember->id,
            'initial_balance' => 125050,
        ]);
    }

    public function test_viewer_cannot_create_an_account(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::VIEWER);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/accounts', [
            'wallet_id' => $wallet->id,
            'name' => 'Conta bloqueada',
            'type' => AccountType::CASH->value,
            'initial_balance' => 0,
            'active' => true,
        ])->assertForbidden();

        $this->assertDatabaseCount('accounts', 0);
    }

    public function test_account_creation_rejects_a_wallet_where_user_is_not_a_member(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::create(['name' => 'Outro wallet']);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/accounts', [
            'wallet_id' => $wallet->id,
            'name' => 'Conta isolada',
            'type' => AccountType::SAVINGS->value,
            'initial_balance' => 0,
            'active' => true,
        ])->assertForbidden();
    }

    public function test_owner_member_must_belong_to_the_account_wallet(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::OWNER);
        $otherWallet = Wallet::create(['name' => 'Wallet externo']);
        $otherMember = WalletMember::create([
            'wallet_id' => $otherWallet->id,
            'user_id' => User::factory()->create()->id,
            'role' => WalletMemberRole::OWNER,
            'joined_at' => Carbon::now(),
        ]);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/accounts', [
            'wallet_id' => $wallet->id,
            'owner_wallet_member_id' => $otherMember->id,
            'name' => 'Conta inválida',
            'type' => AccountType::CASH->value,
            'initial_balance' => 0,
            'active' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('owner_wallet_member_id');
    }

    public function test_editor_can_create_a_wallet_category_and_child_must_match_parent_type(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::EDITOR);

        $parentResponse = $this->actingAs($user, 'sanctum')->postJson('/api/v1/categories', [
            'wallet_id' => $wallet->id,
            'name' => 'Moradia',
            'type' => TransactionType::EXPENSE->value,
            'icon' => 'home',
            'active' => true,
        ]);

        $parentResponse->assertCreated()->assertJsonPath('data.type', TransactionType::EXPENSE->value);
        $parent = Category::query()->where('name', 'Moradia')->firstOrFail();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/categories', [
            'wallet_id' => $wallet->id,
            'parent_id' => $parent->id,
            'name' => 'Salário indevido',
            'type' => TransactionType::INCOME->value,
            'active' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('type');
    }

    public function test_category_creation_persists_and_returns_the_complete_resource_contract(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::OWNER);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/categories', [
            'wallet_id' => $wallet->id,
            'name' => 'Alimentação',
            'type' => TransactionType::EXPENSE->value,
            'icon' => 'utensils',
            'icon_color' => '#C98A00',
            'active' => false,
        ]);

        $category = Category::query()->where('name', 'Alimentação')->firstOrFail();

        $response->assertCreated()->assertJsonPath('data.id', $category->id)
            ->assertJsonPath('data.wallet_id', $wallet->id)
            ->assertJsonPath('data.parent_id', null)
            ->assertJsonPath('data.name', 'Alimentação')
            ->assertJsonPath('data.type', TransactionType::EXPENSE->value)
            ->assertJsonPath('data.icon', 'utensils')
            ->assertJsonPath('data.icon_color', '#C98A00')
            ->assertJsonPath('data.active', false);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'wallet_id' => $wallet->id,
            'parent_id' => null,
            'type' => TransactionType::EXPENSE->value,
            'icon' => 'utensils',
            'icon_color' => '#C98A00',
            'active' => false,
        ]);
    }

    public function test_editor_can_update_a_wallet_category(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::EDITOR);
        $category = Category::create([
            'wallet_id' => $wallet->id,
            'name' => 'Nome antigo',
            'type' => TransactionType::EXPENSE,
            'icon' => 'tag',
            'icon_color' => '#123B8F',
            'active' => true,
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/categories/{$category->id}", [
                'wallet_id' => $wallet->id,
                'name' => 'Nome atualizado',
                'type' => TransactionType::EXPENSE->value,
                'icon' => 'home',
                'icon_color' => '#C98A00',
                'active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nome atualizado')
            ->assertJsonPath('data.icon', 'home');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Nome atualizado',
            'icon_color' => '#C98A00',
            'active' => false,
        ]);
    }

    public function test_global_category_cannot_be_updated(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::OWNER);
        $category = Category::create([
            'wallet_id' => null,
            'name' => 'Categoria global',
            'type' => TransactionType::EXPENSE,
            'active' => true,
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/categories/{$category->id}", [
                'wallet_id' => $wallet->id,
                'name' => 'Alteração global',
                'type' => TransactionType::EXPENSE->value,
                'active' => true,
            ])
            ->assertForbidden();
    }

    public function test_category_cannot_be_its_own_parent(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::OWNER);
        $category = Category::create([
            'wallet_id' => $wallet->id,
            'name' => 'Categoria',
            'type' => TransactionType::EXPENSE,
            'active' => true,
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/categories/{$category->id}", [
                'wallet_id' => $wallet->id,
                'name' => 'Categoria',
                'type' => TransactionType::EXPENSE->value,
                'parent_id' => $category->id,
                'active' => true,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('parent_id');
    }

    public function test_viewer_cannot_update_a_wallet_category(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::VIEWER);
        $category = Category::create([
            'wallet_id' => $wallet->id,
            'name' => 'Categoria protegida',
            'type' => TransactionType::EXPENSE,
            'active' => true,
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/categories/{$category->id}", [
                'wallet_id' => $wallet->id,
                'name' => 'Alteração indevida',
                'type' => TransactionType::EXPENSE->value,
                'active' => true,
            ])
            ->assertForbidden();
    }

    public function test_category_from_another_wallet_cannot_be_updated(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::OWNER);
        $otherWallet = Wallet::create(['name' => 'Outra carteira']);
        $category = Category::create([
            'wallet_id' => $otherWallet->id,
            'name' => 'Categoria externa',
            'type' => TransactionType::EXPENSE,
            'active' => true,
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/categories/{$category->id}", [
                'wallet_id' => $wallet->id,
                'name' => 'Alteração cruzada',
                'type' => TransactionType::EXPENSE->value,
                'active' => true,
            ])
            ->assertNotFound();
    }

    public function test_global_category_can_be_used_as_a_parent_for_a_wallet_category(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::EDITOR);
        $globalParent = Category::create([
            'wallet_id' => null,
            'name' => 'Despesas globais',
            'type' => TransactionType::EXPENSE,
            'active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/categories', [
            'wallet_id' => $wallet->id,
            'parent_id' => $globalParent->id,
            'name' => 'Casa',
            'type' => TransactionType::EXPENSE->value,
            'active' => true,
        ]);

        $response->assertCreated()->assertJsonPath('data.parent_id', $globalParent->id);
        $this->assertDatabaseHas('categories', ['parent_id' => $globalParent->id, 'wallet_id' => $wallet->id]);
    }

    public function test_account_and_category_lists_are_scoped_to_member_wallets_and_include_global_categories(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::VIEWER);
        $otherWallet = Wallet::create(['name' => 'Wallet oculto']);
        $account = $this->createAccount($wallet, 'Conta visível');
        $this->createAccount($otherWallet, 'Conta oculta');
        $walletCategory = Category::create([
            'wallet_id' => $wallet->id,
            'name' => 'Categoria visível',
            'type' => TransactionType::EXPENSE,
            'active' => true,
        ]);
        Category::create([
            'wallet_id' => $otherWallet->id,
            'name' => 'Categoria oculta',
            'type' => TransactionType::EXPENSE,
            'active' => true,
        ]);
        $globalCategory = Category::create([
            'wallet_id' => null,
            'name' => 'Categoria global',
            'type' => TransactionType::INCOME,
            'active' => true,
        ]);

        $accountResponse = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/accounts?wallet_id='.$wallet->id);
        $categoryResponse = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/categories?wallet_id='.$wallet->id);

        $accountResponse->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $account->id)
            ->assertJsonMissing(['name' => 'Conta oculta']);
        $categoryResponse->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $walletCategory->id, 'wallet_id' => $wallet->id])
            ->assertJsonFragment(['id' => $globalCategory->id, 'wallet_id' => null])
            ->assertJsonMissing(['name' => 'Categoria oculta']);
    }

    public function test_database_rejects_an_account_owner_from_another_wallet(): void
    {
        [, $wallet] = $this->walletWithMember(WalletMemberRole::OWNER);
        $otherWallet = Wallet::create(['name' => 'Wallet do owner']);
        $otherMember = WalletMember::create([
            'wallet_id' => $otherWallet->id,
            'user_id' => User::factory()->create()->id,
            'role' => WalletMemberRole::OWNER,
            'joined_at' => Carbon::now(),
        ]);

        $this->expectException(QueryException::class);
        $this->createAccount($wallet, 'Conta inválida', $otherMember->id);
    }

    public function test_viewer_cannot_create_a_wallet_category(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::VIEWER);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/categories', [
            'wallet_id' => $wallet->id,
            'name' => 'Categoria bloqueada',
            'type' => TransactionType::EXPENSE->value,
            'active' => true,
        ])->assertForbidden();
    }

    public function test_global_category_creation_is_read_only_to_users(): void
    {
        [$user] = $this->walletWithMember(WalletMemberRole::OWNER);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/categories', [
            'name' => 'Categoria do sistema',
            'type' => TransactionType::INCOME->value,
            'active' => true,
        ])->assertForbidden();
    }

    public function test_category_parent_from_another_wallet_is_rejected(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::OWNER);
        $otherWallet = Wallet::create(['name' => 'Wallet do pai']);
        $parent = Category::create([
            'wallet_id' => $otherWallet->id,
            'name' => 'Pai externo',
            'type' => TransactionType::EXPENSE,
            'active' => true,
        ]);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/categories', [
            'wallet_id' => $wallet->id,
            'parent_id' => $parent->id,
            'name' => 'Filho inválido',
            'type' => TransactionType::EXPENSE->value,
            'active' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('parent_id');
    }

    public function test_transaction_enums_expose_the_exact_ledger_values(): void
    {
        $this->assertSame(
            ['CHECKING', 'SAVINGS', 'CASH', 'INVESTMENT', 'DIGITAL_WALLET'],
            array_column(AccountType::cases(), 'value'),
        );
        $this->assertSame(['INCOME', 'EXPENSE', 'TRANSFER'], array_column(TransactionType::cases(), 'value'));
        $this->assertSame(['DEBIT', 'CREDIT', 'NONE'], array_column(TransactionEffect::cases(), 'value'));
    }

    /** @return array{User, Wallet, WalletMember|null} */
    private function walletWithMember(WalletMemberRole $role): array
    {
        $user = User::factory()->create();
        $wallet = Wallet::create(['name' => 'Wallet '.uniqid()]);
        $member = WalletMember::create([
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'role' => $role,
            'joined_at' => Carbon::now(),
        ]);

        return [$user, $wallet, $member];
    }

    private function createAccount(Wallet $wallet, string $name, ?int $ownerMemberId = null): Account
    {
        return Account::create([
            'wallet_id' => $wallet->id,
            'owner_wallet_member_id' => $ownerMemberId,
            'name' => $name,
            'type' => AccountType::CHECKING,
            'initial_balance' => 0,
            'active' => true,
        ]);
    }
}
