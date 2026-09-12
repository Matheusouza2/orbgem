<?php

namespace Tests\Feature;

use App\Enums\FinancialCommitmentType;
use App\Enums\TransactionType;
use App\Enums\WalletMemberRole;
use App\Models\Account;
use App\Models\Category;
use App\Models\FinancialCommitment;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PlanningSliceTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_open_the_recurring_transactions_page(): void
    {
        [$user] = $this->walletWithMember();

        $this->actingAs($user)->get('/recorrencias')->assertOk();
    }

    public function test_editor_can_create_and_generate_recurring_transactions_without_duplicates(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::EDITOR);
        $account = Account::query()->create(['wallet_id' => $wallet->id, 'name' => 'Conta', 'type' => 'CHECKING', 'initial_balance' => 0, 'active' => true]);
        $member = WalletMember::query()->where('wallet_id', $wallet->id)->where('user_id', $user->id)->firstOrFail();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/recurring-transactions', [
            'wallet_id' => $wallet->id, 'account_id' => $account->id, 'description' => 'Aluguel', 'type' => TransactionType::EXPENSE->value,
            'amount' => 1200, 'frequency' => 'MONTHLY', 'start_date' => '2026-01-05', 'due_day' => 5, 'auto_create' => false, 'active' => true,
        ]);
        $response->assertCreated();
        $id = $response->json('data.id');

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/recurring-transactions/'.$id.'/generate', ['until' => '2026-03-31'])->assertOk()->assertJsonCount(3, 'data');
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/recurring-transactions/'.$id.'/generate', ['until' => '2026-03-31'])->assertOk()->assertJsonCount(0, 'data');
        $this->assertSame(3, Transaction::query()->where('recurring_transaction_id', $id)->count());
    }

    public function test_recurring_auto_create_and_commitment_generation_are_idempotent(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::EDITOR);
        $account = Account::query()->create(['wallet_id' => $wallet->id, 'name' => 'Conta', 'type' => 'CHECKING', 'initial_balance' => 0, 'active' => true]);
        $recurring = $this->actingAs($user, 'sanctum')->postJson('/api/v1/recurring-transactions', ['wallet_id' => $wallet->id, 'account_id' => $account->id, 'description' => 'Salário', 'type' => TransactionType::INCOME->value, 'amount' => 5000, 'frequency' => 'MONTHLY', 'start_date' => '2026-01-05', 'auto_create' => true])->assertCreated()->json('data.id');
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/recurring-transactions/'.$recurring.'/generate', ['until' => '2026-01-31'])->assertOk();
        $this->assertDatabaseHas('transactions', ['recurring_transaction_id' => $recurring, 'status' => 'POSTED']);
        $commitment = $this->actingAs($user, 'sanctum')->postJson('/api/v1/financial-commitments', ['wallet_id' => $wallet->id, 'description' => 'Financiamento', 'type' => 'FINANCING', 'original_amount' => 3000, 'installment_amount' => 1000, 'installment_count' => 3, 'start_date' => '2026-01-10', 'end_date' => '2026-03-10'])->assertCreated()->json('data.id');
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/financial-commitments/'.$commitment.'/generate', ['until' => '2026-03-31'])->assertOk()->assertJsonCount(3, 'data');
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/financial-commitments/'.$commitment.'/generate', ['until' => '2026-03-31'])->assertOk()->assertJsonCount(0, 'data');
        $this->assertSame(3, Transaction::query()->where('financial_commitment_id', $commitment)->count());
        $this->assertDatabaseHas('financial_commitments', ['id' => $commitment, 'current_installment' => 3, 'active' => false]);
    }

    public function test_commitment_generation_continues_from_the_current_installment(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::EDITOR);
        $start = Carbon::today();
        $commitment = $this->actingAs($user, 'sanctum')->postJson('/api/v1/financial-commitments', [
            'wallet_id' => $wallet->id,
            'description' => 'Financiamento longo',
            'type' => FinancialCommitmentType::FINANCING->value,
            'original_amount' => 6000,
            'installment_amount' => 1000,
            'installment_count' => 6,
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addMonths(5)->toDateString(),
        ])->assertCreated()->json('data.id');

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/financial-commitments/'.$commitment.'/generate', [
            'until' => $start->copy()->addMonths(2)->toDateString(),
        ])->assertOk()->assertJsonCount(3, 'data');

        $this->assertDatabaseHas('financial_commitments', ['id' => $commitment, 'current_installment' => 3, 'active' => true]);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/financial-commitments/'.$commitment.'/generate', [
            'until' => $start->copy()->addMonths(5)->toDateString(),
        ])->assertOk()->assertJsonCount(3, 'data');

        $this->assertDatabaseHas('financial_commitments', ['id' => $commitment, 'current_installment' => 6, 'active' => false]);
        $this->assertDatabaseCount('transactions', 6);
    }

    public function test_editor_can_update_and_delete_recurring_transactions_and_commitments(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::EDITOR);
        $recurring = $this->actingAs($user, 'sanctum')->postJson('/api/v1/recurring-transactions', [
            'wallet_id' => $wallet->id, 'description' => 'Aluguel', 'type' => 'EXPENSE', 'amount' => 1000, 'frequency' => 'MONTHLY', 'start_date' => '2026-09-01', 'active' => true,
        ])->assertCreated()->json('data.id');
        $this->actingAs($user, 'sanctum')->putJson('/api/v1/recurring-transactions/'.$recurring, [
            'wallet_id' => $wallet->id, 'description' => 'Aluguel reajustado', 'type' => 'EXPENSE', 'amount' => 1100, 'frequency' => 'MONTHLY', 'start_date' => '2026-09-01', 'active' => true,
        ])->assertOk()->assertJsonPath('data.amount', 1100);
        $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/recurring-transactions/'.$recurring)->assertNoContent();

        $commitment = $this->actingAs($user, 'sanctum')->postJson('/api/v1/financial-commitments', [
            'wallet_id' => $wallet->id, 'description' => 'Financiamento', 'type' => 'FINANCING', 'original_amount' => 3000, 'installment_amount' => 1000, 'installment_count' => 3, 'start_date' => '2026-09-01', 'end_date' => '2026-11-01',
        ])->assertCreated()->json('data.id');
        $this->actingAs($user, 'sanctum')->putJson('/api/v1/financial-commitments/'.$commitment, [
            'wallet_id' => $wallet->id, 'description' => 'Financiamento atualizado', 'type' => 'FINANCING', 'original_amount' => 3000, 'installment_amount' => 1200, 'installment_count' => 3, 'start_date' => '2026-09-01', 'end_date' => '2026-11-01',
        ])->assertOk()->assertJsonPath('data.installment_amount', 1200);
        $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/financial-commitments/'.$commitment)->assertNoContent();
    }

    public function test_financial_planning_entities_are_isolated_between_users(): void
    {
        [$owner, $wallet] = $this->walletWithMember(WalletMemberRole::OWNER);
        $otherUser = User::factory()->create();
        $recurring = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/recurring-transactions', [
            'wallet_id' => $wallet->id, 'description' => 'Privado', 'type' => 'EXPENSE', 'amount' => 100, 'frequency' => 'MONTHLY', 'start_date' => '2026-09-01',
        ])->assertCreated()->json('data.id');

        $this->actingAs($otherUser, 'sanctum')->putJson('/api/v1/recurring-transactions/'.$recurring, [
            'wallet_id' => $wallet->id, 'description' => 'Ataque', 'type' => 'EXPENSE', 'amount' => 999, 'frequency' => 'MONTHLY', 'start_date' => '2026-09-01',
        ])->assertForbidden();
        $this->actingAs($otherUser, 'sanctum')->deleteJson('/api/v1/recurring-transactions/'.$recurring)->assertForbidden();
        $this->actingAs($otherUser, 'sanctum')->getJson('/api/v1/financial-commitments?wallet_id='.$wallet->id)->assertForbidden();
    }

    public function test_scheduler_command_generates_commitments_operationally_and_is_idempotent(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::OWNER);
        $start = Carbon::today();
        $commitment = FinancialCommitment::query()->create([
            'wallet_id' => $wallet->id,
            'description' => 'Compromisso agendado',
            'type' => FinancialCommitmentType::FINANCING,
            'original_amount' => 2000,
            'installment_amount' => 1000,
            'installment_count' => 2,
            'start_date' => $start,
            'end_date' => $start->copy()->addMonth(),
            'active' => true,
        ]);

        $this->assertSame(0, Artisan::call('planning:generate-commitments'));
        $this->assertDatabaseCount('transactions', 2);
        $this->assertDatabaseHas('financial_commitments', ['id' => $commitment->id, 'current_installment' => 2, 'active' => false]);

        $this->assertSame(0, Artisan::call('planning:generate-commitments'));
        $this->assertDatabaseCount('transactions', 2);
    }

    public function test_scheduler_command_generates_recurring_transactions_operationally_and_is_idempotent(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::OWNER);
        $account = Account::query()->create(['wallet_id' => $wallet->id, 'name' => 'Conta', 'type' => 'CHECKING', 'initial_balance' => 0, 'active' => true]);
        $start = Carbon::today();
        $recurring = RecurringTransaction::query()->create([
            'wallet_id' => $wallet->id,
            'account_id' => $account->id,
            'description' => 'Receita agendada',
            'type' => TransactionType::INCOME,
            'amount' => 2500,
            'frequency' => 'MONTHLY',
            'start_date' => $start,
            'due_day' => $start->day,
            'auto_create' => true,
            'active' => true,
        ]);

        $this->assertSame(0, Artisan::call('planning:generate-recurring'));
        $this->assertDatabaseCount('transactions', 2);
        $this->assertDatabaseHas('transactions', ['recurring_transaction_id' => $recurring->id, 'status' => 'POSTED']);

        $this->assertSame(0, Artisan::call('planning:generate-recurring'));
        $this->assertDatabaseCount('transactions', 2);
    }

    public function test_planning_summary_exposes_consolidated_monthly_planning(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::OWNER);
        $category = Category::query()->create(['wallet_id' => $wallet->id, 'name' => 'Moradia', 'type' => TransactionType::EXPENSE, 'active' => true]);
        $account = Account::query()->create(['wallet_id' => $wallet->id, 'name' => 'Conta', 'type' => 'CHECKING', 'initial_balance' => 0, 'active' => true]);
        $member = WalletMember::query()->where('wallet_id', $wallet->id)->where('user_id', $user->id)->firstOrFail();
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/budgets', ['wallet_id' => $wallet->id, 'category_id' => $category->id, 'reference_month' => '2026-09', 'amount' => 5000])->assertCreated();

        Transaction::query()->create([
            'wallet_id' => $wallet->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'description' => 'Despesa realizada',
            'type' => TransactionType::EXPENSE,
            'effect' => 'DEBIT',
            'amount' => 1200,
            'financial_instrument_type' => 'ACCOUNT',
            'competence_date' => '2026-09-10',
            'transaction_date' => '2026-09-10',
            'status' => 'POSTED',
            'created_by_member_id' => $member->id,
            'updated_by_member_id' => $member->id,
        ]);
        Transaction::query()->create([
            'wallet_id' => $wallet->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'description' => 'Despesa prevista',
            'type' => TransactionType::EXPENSE,
            'effect' => 'DEBIT',
            'amount' => 800,
            'financial_instrument_type' => 'ACCOUNT',
            'competence_date' => '2026-09-20',
            'transaction_date' => '2026-09-20',
            'status' => 'PROJECTED',
            'created_by_member_id' => $member->id,
            'updated_by_member_id' => $member->id,
        ]);

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/monthly-summary?wallet_id='.$wallet->id.'&month=2026-09')
            ->assertOk()
            ->assertJsonPath('data.planning_consolidated.budget.total', 5000)
            ->assertJsonPath('data.planning_consolidated.budget.consumed', 2000)
            ->assertJsonPath('data.planning_consolidated.expenses.realized', 1200)
            ->assertJsonPath('data.planning_consolidated.expenses.projected', 800)
            ->assertJsonPath('data.planning_consolidated.expenses.total', 2000);
    }

    public function test_budget_can_be_updated_deleted_and_future_commitments_are_grouped(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::OWNER);
        $category = Category::query()->create(['wallet_id' => $wallet->id, 'name' => 'Moradia', 'type' => TransactionType::EXPENSE, 'active' => true]);
        $budget = $this->actingAs($user, 'sanctum')->postJson('/api/v1/budgets', ['wallet_id' => $wallet->id, 'category_id' => $category->id, 'reference_month' => '2026-09', 'amount' => 3000])->assertCreated()->json('data.id');
        $this->actingAs($user, 'sanctum')->putJson('/api/v1/budgets/'.$budget, ['wallet_id' => $wallet->id, 'category_id' => $category->id, 'reference_month' => '2026-09', 'amount' => 4000])->assertOk()->assertJsonPath('data.amount', 4000);
        $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/budgets/'.$budget)->assertNoContent();
        $this->assertDatabaseMissing('budgets', ['id' => $budget]);
    }

    public function test_editor_can_create_commitment_and_budget_with_wallet_isolation(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::EDITOR);
        $category = Category::query()->create(['wallet_id' => $wallet->id, 'name' => 'Moradia', 'type' => TransactionType::EXPENSE, 'active' => true]);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/financial-commitments', [
            'wallet_id' => $wallet->id, 'description' => 'Financiamento', 'type' => FinancialCommitmentType::FINANCING->value,
            'original_amount' => 12000, 'installment_amount' => 1000, 'installment_count' => 12, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31',
        ])->assertCreated()->assertJsonPath('data.installment_amount', 1000);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/budgets', [
            'wallet_id' => $wallet->id, 'category_id' => $category->id, 'reference_month' => '2026-09', 'amount' => 3000,
        ])->assertCreated()->assertJsonPath('data.amount', 3000);

        $this->assertDatabaseCount('financial_commitments', 1);
        $this->assertDatabaseCount('budgets', 1);
    }

    /** @return array{User, Wallet} */
    private function walletWithMember(WalletMemberRole $role): array
    {
        $user = User::factory()->create();
        $wallet = Wallet::query()->create(['name' => 'Carteira']);
        WalletMember::query()->create(['wallet_id' => $wallet->id, 'user_id' => $user->id, 'role' => $role, 'joined_at' => now()]);

        return [$user, $wallet];
    }
}
