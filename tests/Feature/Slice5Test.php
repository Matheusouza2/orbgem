<?php

namespace Tests\Feature;

use App\Enums\FinancialInstrumentType;
use App\Enums\TransactionEffect;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\WalletMemberRole;
use App\Jobs\ProcessImportBatch;
use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\FinancialCommitment;
use App\Models\ImportBatch;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMember;
use App\Services\Slice5Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class Slice5Test extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_persist_a_virtual_consolidation_only_with_authorized_wallets(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::OWNER);
        $other = Wallet::query()->create(['name' => 'Outra']);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/consolidations', ['name' => 'Visão familiar', 'wallet_ids' => [$wallet->id]])
            ->assertCreated()->assertJsonPath('data.wallet_ids.0', $wallet->id);
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/consolidations', ['name' => 'Inválida', 'wallet_ids' => [$other->id]])->assertForbidden();
    }

    public function test_user_can_create_tags_goals_attach_files_and_import_csv(): void
    {
        Queue::fake();
        [$user, $wallet, $member] = $this->walletWithMember(WalletMemberRole::OWNER);
        $account = Account::query()->create(['wallet_id' => $wallet->id, 'name' => 'Conta', 'type' => 'CHECKING', 'initial_balance' => 0, 'active' => true]);
        $transaction = Transaction::query()->create(['wallet_id' => $wallet->id, 'account_id' => $account->id, 'description' => 'Compra', 'type' => TransactionType::EXPENSE, 'effect' => TransactionEffect::DEBIT, 'amount' => 100, 'financial_instrument_type' => FinancialInstrumentType::ACCOUNT, 'transaction_date' => '2026-09-01', 'competence_date' => '2026-09-01', 'status' => TransactionStatus::POSTED, 'created_by_member_id' => $member->id, 'updated_by_member_id' => $member->id]);

        $tag = $this->actingAs($user, 'sanctum')->postJson('/api/v1/tags', ['wallet_id' => $wallet->id, 'name' => 'Casa'])->assertCreated()->json('data.id');
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/transactions/'.$transaction->id.'/tags', ['tag_ids' => [$tag]])->assertOk();
        $goal = $this->actingAs($user, 'sanctum')->postJson('/api/v1/financial-goals', ['wallet_id' => $wallet->id, 'name' => 'Reserva', 'target_amount' => 10000])->assertCreated()->assertJsonPath('data.remaining_amount', 10000)->json('data.id');
        $this->actingAs($user, 'sanctum')->post('/api/v1/attachments', ['wallet_id' => $wallet->id, 'attachable_type' => 'financial_goal', 'attachable_id' => $goal, 'file' => UploadedFile::fake()->create('meta.txt', 1, 'text/plain')])->assertCreated();
        $this->actingAs($user, 'sanctum')->getJson('/api/v1/attachments?attachable_type=financial_goal&attachable_id='.$goal)->assertOk()->assertJsonCount(1, 'data');
        $attachment = $this->actingAs($user, 'sanctum')->postJson('/api/v1/transactions/'.$transaction->id.'/attachments', ['wallet_id' => $wallet->id, 'file' => UploadedFile::fake()->create('nota.txt', 1, 'text/plain')])->assertCreated()->json('data.id');
        $this->actingAs($user, 'sanctum')->getJson('/api/v1/transactions/'.$transaction->id.'/attachments')->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/attachments/'.$attachment)->assertNoContent();
        $csv = UploadedFile::fake()->createWithContent('transactions.csv', "description,type,amount,transaction_date,competence_date,account_id,category_id\nSalário,INCOME,5000,2026-09-02,2026-09-02,{$account->id},\n");
        $batch = $this->actingAs($user, 'sanctum')->post('/api/v1/imports', ['wallet_id' => $wallet->id, 'file' => $csv])->assertAccepted()->assertJsonPath('data.status', 'QUEUED')->json('data.id');
        Queue::assertPushed(ProcessImportBatch::class, 1);
        (new ProcessImportBatch($batch))->handle(app(Slice5Service::class));
        $this->actingAs($user, 'sanctum')->post('/api/v1/imports', ['wallet_id' => $wallet->id, 'file' => UploadedFile::fake()->createWithContent('transactions.csv', "description,type,amount,transaction_date,competence_date,account_id,category_id\nSalário,INCOME,5000,2026-09-02,2026-09-02,{$account->id},\n")])->assertAccepted()->assertJsonPath('data.status', 'QUEUED');
        $secondBatch = (int) ImportBatch::query()->latest('id')->value('id');
        (new ProcessImportBatch($secondBatch))->handle(app(Slice5Service::class));
        $this->actingAs($user, 'sanctum')->getJson('/api/v1/imports/'.$batch)->assertOk()->assertJsonPath('data.status', 'COMPLETED');
        $this->actingAs($user, 'sanctum')->getJson('/api/v1/notifications')->assertOk()->assertJsonCount(4, 'data');
        $this->assertDatabaseHas('transactions', ['description' => 'Salário', 'amount' => 5000]);
    }

    public function test_consolidation_summary_and_goal_contribution_are_available(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::OWNER);
        $consolidation = $this->actingAs($user, 'sanctum')->postJson('/api/v1/consolidations', ['name' => 'Família', 'wallet_ids' => [$wallet->id]])->assertCreated()->json('data.id');
        $category = Category::query()->create(['wallet_id' => $wallet->id, 'name' => 'Moradia', 'type' => TransactionType::EXPENSE, 'active' => true]);
        Budget::query()->create(['wallet_id' => $wallet->id, 'category_id' => $category->id, 'reference_month' => '2026-09', 'amount' => 5000, 'active' => true]);
        FinancialCommitment::query()->create(['wallet_id' => $wallet->id, 'description' => 'Financiamento', 'type' => 'FINANCING', 'original_amount' => 3000, 'installment_amount' => 1000, 'installment_count' => 3, 'start_date' => '2026-09-01', 'end_date' => '2026-11-01', 'active' => true]);
        $this->actingAs($user, 'sanctum')->putJson('/api/v1/consolidations/'.$consolidation, ['name' => 'Família atualizada', 'wallet_ids' => [$wallet->id]])->assertOk()->assertJsonPath('data.name', 'Família atualizada');
        $this->actingAs($user, 'sanctum')->getJson('/api/v1/consolidations/'.$consolidation.'/summary?month=2026-09')->assertOk()->assertJsonPath('data.consolidation_id', $consolidation)->assertJsonPath('data.budget_total', 5000)->assertJsonPath('data.financial_commitments', 1000)->assertJsonPath('data.planning_consolidated.budget.total', 5000);
        $goal = $this->actingAs($user, 'sanctum')->postJson('/api/v1/financial-goals', ['wallet_id' => $wallet->id, 'name' => 'Reserva', 'target_amount' => 10000])->assertCreated()->json('data.id');
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/financial-goals/'.$goal.'/contributions', ['amount' => 2500, 'note' => 'Aporte inicial'])->assertOk()->assertJsonPath('data.current_amount', 2500)->assertJsonCount(1, 'data.contributions');
        $this->assertDatabaseCount('in_app_notifications', 2);
    }

    public function test_invalid_import_is_recorded_without_creating_transactions(): void
    {
        Queue::fake();
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::OWNER);
        $batch = $this->actingAs($user, 'sanctum')->post('/api/v1/imports', ['wallet_id' => $wallet->id, 'file' => UploadedFile::fake()->createWithContent('invalid.csv', "wrong,header\nvalue\n")])->assertAccepted()->json('data.id');

        (new ProcessImportBatch($batch))->handle(app(Slice5Service::class));

        $this->assertDatabaseHas('import_batches', ['id' => $batch, 'status' => 'FAILED', 'failed_rows' => 1]);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_consolidated_report_aggregates_authorized_wallets(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::OWNER);
        $otherWallet = Wallet::query()->create(['name' => 'Outra']);
        WalletMember::query()->create(['wallet_id' => $otherWallet->id, 'user_id' => $user->id, 'role' => WalletMemberRole::OWNER, 'joined_at' => now()]);
        $category = Category::query()->create(['wallet_id' => $wallet->id, 'name' => 'Casa', 'type' => TransactionType::EXPENSE, 'active' => true]);
        $otherCategory = Category::query()->create(['wallet_id' => $otherWallet->id, 'name' => 'Casa', 'type' => TransactionType::EXPENSE, 'active' => true]);
        Budget::query()->create(['wallet_id' => $wallet->id, 'category_id' => $category->id, 'reference_month' => '2026-09', 'amount' => 3000, 'active' => true]);
        Budget::query()->create(['wallet_id' => $otherWallet->id, 'category_id' => $otherCategory->id, 'reference_month' => '2026-09', 'amount' => 4000, 'active' => true]);
        $consolidation = $this->actingAs($user, 'sanctum')->postJson('/api/v1/consolidations', ['name' => 'Duas carteiras', 'wallet_ids' => [$wallet->id, $otherWallet->id]])->assertCreated()->json('data.id');

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/consolidations/'.$consolidation.'/summary?month=2026-09')
            ->assertOk()->assertJsonPath('data.budget_total', 7000)->assertJsonPath('data.planning_consolidated.budget.total', 7000);
    }

    /** @return array{User, Wallet, WalletMember} */
    private function walletWithMember(WalletMemberRole $role): array
    {
        $user = User::factory()->create();
        $wallet = Wallet::query()->create(['name' => 'Carteira']);
        $member = WalletMember::query()->create(['wallet_id' => $wallet->id, 'user_id' => $user->id, 'role' => $role, 'joined_at' => now()]);

        return [$user, $wallet, $member];
    }
}
