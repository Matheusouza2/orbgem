<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\FinancialInstrumentType;
use App\Enums\TransactionEffect;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\WalletMemberRole;
use App\Models\Account;
use App\Models\DevicePushToken;
use App\Models\Merchant;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletion_requires_authentication_and_the_current_password(): void
    {
        $this->deleteJson('/api/v1/user', ['password' => 'password'])->assertUnauthorized();

        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/user', ['password' => 'wrong'])
            ->assertUnprocessable()->assertJsonValidationErrors(['password']);
        $this->assertModelExists($user);
    }

    public function test_user_with_only_private_wallets_is_fully_removed_with_their_financial_data(): void
    {
        $user = User::factory()->create();
        [$wallet, $member] = $this->wallet($user, WalletMemberRole::OWNER);
        $this->transaction($wallet, $this->account($wallet), $member);
        DevicePushToken::create(['user_id' => $user->id, 'token' => 'fcm', 'platform' => 'android']);
        $user->createToken('mobile');

        $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/user', ['password' => 'password'])->assertNoContent();

        $this->assertModelMissing($user);
        $this->assertModelMissing($wallet);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('accounts', 0);
        $this->assertDatabaseCount('device_push_tokens', 0);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_owner_of_a_wallet_with_other_members_cannot_delete_the_account(): void
    {
        $owner = User::factory()->create();
        [$wallet] = $this->wallet($owner, WalletMemberRole::OWNER);
        WalletMember::create(['wallet_id' => $wallet->id, 'user_id' => User::factory()->create()->id, 'role' => WalletMemberRole::EDITOR, 'joined_at' => Carbon::now()]);

        $this->actingAs($owner, 'sanctum')->deleteJson('/api/v1/user', ['password' => 'password'])
            ->assertUnprocessable()->assertJsonValidationErrors(['account']);

        $this->assertModelExists($owner);
        $this->assertModelExists($wallet);
    }

    public function test_member_of_someone_elses_wallet_is_anonymized_and_the_shared_ledger_is_kept(): void
    {
        $owner = User::factory()->create();
        [$wallet] = $this->wallet($owner, WalletMemberRole::OWNER);
        $editor = User::factory()->create(['name' => 'Maria', 'email' => 'maria@example.com']);
        $editorMember = WalletMember::create(['wallet_id' => $wallet->id, 'user_id' => $editor->id, 'role' => WalletMemberRole::EDITOR, 'joined_at' => Carbon::now()]);
        $this->transaction($wallet, $this->account($wallet), $editorMember);

        $this->actingAs($editor, 'sanctum')->deleteJson('/api/v1/user', ['password' => 'password'])->assertNoContent();

        $this->assertModelExists($wallet);
        $this->assertDatabaseCount('transactions', 1);
        $editor->refresh();
        $this->assertSame('Usuário removido', $editor->name);
        $this->assertStringEndsWith('@deleted.invalid', $editor->email);
        $this->assertDatabaseMissing('users', ['email' => 'maria@example.com']);
    }

    /** @return array{0: Wallet, 1: WalletMember} */
    private function wallet(User $user, WalletMemberRole $role): array
    {
        $wallet = Wallet::create(['name' => uniqid('wallet')]);
        $member = WalletMember::create(['wallet_id' => $wallet->id, 'user_id' => $user->id, 'role' => $role, 'joined_at' => Carbon::now()]);

        return [$wallet, $member];
    }

    private function account(Wallet $wallet): Account
    {
        return Account::create(['wallet_id' => $wallet->id, 'name' => 'Conta', 'type' => AccountType::CHECKING, 'initial_balance' => 0, 'active' => true]);
    }

    private function transaction(Wallet $wallet, Account $account, WalletMember $member): Transaction
    {
        return Transaction::create([
            'wallet_id' => $wallet->id, 'account_id' => $account->id, 'description' => 'Lançamento', 'type' => TransactionType::EXPENSE->value,
            'effect' => TransactionEffect::DEBIT->value, 'amount' => 1000, 'financial_instrument_type' => FinancialInstrumentType::ACCOUNT->value,
            'transaction_date' => '2026-09-07', 'competence_date' => '2026-09-07', 'status' => TransactionStatus::POSTED->value,
            'created_by_member_id' => $member->id, 'updated_by_member_id' => $member->id,
            'merchant_id' => Merchant::create(['wallet_id' => $wallet->id, 'name' => 'Mercado', 'normalized_name' => 'mercado', 'active' => true])->id,
        ]);
    }
}
