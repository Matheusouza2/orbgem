<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\FinancialInstrumentType;
use App\Enums\TransactionEffect;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\WalletMemberRole;
use App\Models\Account;
use App\Models\Merchant;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MerchantTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_rejected_from_merchant_endpoints(): void
    {
        $this->postJson('/api/v1/merchants', [])->assertUnauthorized();
        $this->getJson('/api/v1/merchants?wallet_id=1')->assertUnauthorized();
    }

    public function test_editor_can_create_merchant_with_normalized_name_and_resource_fields(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::EDITOR);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/merchants', [
            'wallet_id' => $wallet->id,
            'name' => '  Café   Central  ',
        ]);

        $merchant = Merchant::query()->firstOrFail();
        $response->assertCreated()
            ->assertJsonPath('data.id', $merchant->id)
            ->assertJsonPath('data.wallet_id', $wallet->id)
            ->assertJsonPath('data.name', 'Café   Central')
            ->assertJsonPath('data.normalized_name', 'café central')
            ->assertJsonPath('data.active', true);
        $this->assertSame('café central', $merchant->normalized_name);
    }

    public function test_viewer_cannot_create_merchant_but_can_list_wallet_merchants(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::VIEWER);
        $merchant = Merchant::create(['wallet_id' => $wallet->id, 'name' => 'Mercado', 'normalized_name' => 'mercado', 'active' => true]);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/merchants', ['wallet_id' => $wallet->id, 'name' => 'Farmácia'])->assertForbidden();
        $this->actingAs($user, 'sanctum')->getJson('/api/v1/merchants?wallet_id='.$wallet->id)
            ->assertOk()->assertJsonPath('data.0.id', $merchant->id);
    }

    public function test_normalized_duplicate_is_rejected_within_the_same_wallet(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::OWNER);
        Merchant::create(['wallet_id' => $wallet->id, 'name' => 'Supermercado', 'normalized_name' => 'supermercado', 'active' => true]);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/merchants', ['wallet_id' => $wallet->id, 'name' => '  SUPERMERCADO  '])
            ->assertUnprocessable()->assertJsonValidationErrors('name');
    }

    public function test_merchant_listing_is_isolated_between_wallets(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::VIEWER);
        $otherWallet = Wallet::create(['name' => 'Outra carteira']);
        $visible = Merchant::create(['wallet_id' => $wallet->id, 'name' => 'Visível', 'normalized_name' => 'visível', 'active' => true]);
        $hidden = Merchant::create(['wallet_id' => $otherWallet->id, 'name' => 'Oculto', 'normalized_name' => 'oculto', 'active' => true]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/merchants?wallet_id='.$wallet->id);

        $response->assertOk()->assertJsonPath('data.0.id', $visible->id)->assertJsonMissing(['id' => $hidden->id]);
    }

    public function test_transaction_accepts_same_wallet_merchant_and_rejects_cross_wallet_merchant(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::EDITOR);
        $account = $this->account($wallet);
        $merchant = Merchant::create(['wallet_id' => $wallet->id, 'name' => 'Loja', 'normalized_name' => 'loja', 'active' => true]);
        $otherWallet = Wallet::create(['name' => 'Outra']);
        $otherMerchant = Merchant::create(['wallet_id' => $otherWallet->id, 'name' => 'Outra loja', 'normalized_name' => 'outra loja', 'active' => true]);

        $valid = $this->actingAs($user, 'sanctum')->postJson('/api/v1/transactions', $this->transactionPayload($wallet, $account, $merchant->id));
        $valid->assertCreated()->assertJsonPath('data.merchant_id', $merchant->id);
        $this->assertSame($merchant->id, Transaction::query()->firstOrFail()->merchant_id);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/transactions', $this->transactionPayload($wallet, $account, $otherMerchant->id))
            ->assertUnprocessable()->assertJsonValidationErrors('merchant_id');
    }

    public function test_transaction_list_can_filter_by_merchant(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::VIEWER);
        $account = $this->account($wallet);
        $first = Merchant::create(['wallet_id' => $wallet->id, 'name' => 'Primeira', 'normalized_name' => 'primeira', 'active' => true]);
        $second = Merchant::create(['wallet_id' => $wallet->id, 'name' => 'Segunda', 'normalized_name' => 'segunda', 'active' => true]);
        $member = WalletMember::query()->where('wallet_id', $wallet->id)->where('user_id', $user->id)->firstOrFail();
        Transaction::create([...$this->transactionPayload($wallet, $account, $first->id), 'created_by_member_id' => $member->id, 'updated_by_member_id' => $member->id]);
        Transaction::create([...$this->transactionPayload($wallet, $account, $second->id), 'created_by_member_id' => $member->id, 'updated_by_member_id' => $member->id]);

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/transactions?wallet_id='.$wallet->id.'&merchant_id='.$first->id)
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.merchant_id', $first->id);
    }

    /** @return array{User, Wallet} */
    private function walletWithMember(WalletMemberRole $role): array
    {
        $user = User::factory()->create();
        $wallet = Wallet::create(['name' => uniqid('wallet')]);
        WalletMember::create(['wallet_id' => $wallet->id, 'user_id' => $user->id, 'role' => $role, 'joined_at' => Carbon::now()]);

        return [$user, $wallet];
    }

    private function account(Wallet $wallet): Account
    {
        return Account::create(['wallet_id' => $wallet->id, 'name' => uniqid('account'), 'type' => AccountType::CHECKING, 'initial_balance' => 0, 'active' => true]);
    }

    /** @return array<string, mixed> */
    private function transactionPayload(Wallet $wallet, Account $account, int $merchantId): array
    {
        return ['wallet_id' => $wallet->id, 'account_id' => $account->id, 'merchant_id' => $merchantId, 'description' => 'Compra', 'type' => TransactionType::EXPENSE->value, 'effect' => TransactionEffect::DEBIT->value, 'amount' => 100, 'financial_instrument_type' => FinancialInstrumentType::ACCOUNT->value, 'transaction_date' => '2026-09-07', 'competence_date' => '2026-09-07', 'status' => TransactionStatus::POSTED->value];
    }
}
