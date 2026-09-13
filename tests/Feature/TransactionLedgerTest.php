<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\FinancialInstrumentType;
use App\Enums\InstallmentPeriodicity;
use App\Enums\TransactionEffect;
use App\Enums\TransactionRecurrence;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\WalletMemberRole;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TransactionLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_transaction_routes_are_rejected(): void
    {
        $this->postJson('/api/v1/transactions', [])->assertUnauthorized();
        $this->getJson('/api/v1/transactions?wallet_id=1')->assertUnauthorized();
        $this->getJson('/api/v1/monthly-summary?wallet_id=1&month=2026-09')->assertUnauthorized();
    }

    public function test_editor_can_create_income_and_expense_and_summary_uses_posted_and_projected_values(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::EDITOR);
        $account = $this->account($wallet, 10000);
        $incomeCategory = $this->category($wallet, TransactionType::INCOME);
        $expenseCategory = $this->category($wallet, TransactionType::EXPENSE);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/transactions', $this->payload($wallet, $account, $incomeCategory, TransactionType::INCOME, TransactionEffect::CREDIT, 5000))->assertCreated();
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/transactions', $this->payload($wallet, $account, $expenseCategory, TransactionType::EXPENSE, TransactionEffect::DEBIT, 2000))->assertCreated();
        $projected = $this->payload($wallet, $account, $expenseCategory, TransactionType::EXPENSE, TransactionEffect::DEBIT, 3000);
        $projected['status'] = TransactionStatus::PROJECTED->value;
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/transactions', $projected)->assertCreated();
        $forecastIncome = $this->payload($wallet, $account, $incomeCategory, TransactionType::INCOME, TransactionEffect::CREDIT, 700);
        $forecastIncome['status'] = TransactionStatus::PROJECTED->value;
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/transactions', $forecastIncome)->assertCreated();

        $summary = $this->actingAs($user, 'sanctum')->getJson('/api/v1/monthly-summary?wallet_id='.$wallet->id.'&month=2026-09');
        $summary->assertOk()->assertJsonPath('data.balance', 13000)->assertJsonPath('data.actual_expenses', 2000)->assertJsonPath('data.forecast_expenses', 5000)->assertJsonPath('data.actual_income', 5000)->assertJsonPath('data.forecast_income', 5700);
    }

    public function test_third_party_expenses_can_be_excluded_from_monthly_summary(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::OWNER);
        $account = $this->account($wallet);
        $memberId = WalletMember::query()->where('wallet_id', $wallet->id)->where('user_id', $user->id)->value('id');

        Transaction::query()->create([
            ...$this->payload($wallet, $account, null, TransactionType::EXPENSE, TransactionEffect::DEBIT, 1000),
            'is_third_party' => true,
            'created_by_member_id' => $memberId,
            'updated_by_member_id' => $memberId,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/monthly-summary?wallet_id='.$wallet->id.'&month=2026-09&include_third_party=0')
            ->assertOk()
            ->assertJsonPath('data.actual_expenses', 0)
            ->assertJsonPath('data.forecast_expenses', 0);
    }

    public function test_account_transactions_can_hide_third_party_expenses(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::OWNER);
        $account = $this->account($wallet);
        $memberId = WalletMember::query()->where('wallet_id', $wallet->id)->where('user_id', $user->id)->value('id');

        foreach ([false, true] as $isThirdParty) {
            Transaction::query()->create([
                ...$this->payload($wallet, $account, null, TransactionType::EXPENSE, TransactionEffect::DEBIT, $isThirdParty ? 200 : 100),
                'is_third_party' => $isThirdParty,
                'created_by_member_id' => $memberId,
                'updated_by_member_id' => $memberId,
            ]);
        }

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/transactions?wallet_id='.$wallet->id.'&account_id='.$account->id.'&include_third_party=0');

        $response->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.amount', 100);
    }

    public function test_ignored_accounts_are_excluded_from_summary_totals_and_balance(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::OWNER);
        $includedAccount = $this->account($wallet, 1000);
        $ignoredAccount = Account::query()->create([
            'wallet_id' => $wallet->id,
            'name' => 'Conta fora dos totais',
            'type' => AccountType::CHECKING,
            'initial_balance' => 5000,
            'ignore_in_totals' => true,
            'active' => true,
        ]);
        $memberId = WalletMember::query()->where('wallet_id', $wallet->id)->where('user_id', $user->id)->value('id');

        foreach ([[$includedAccount, TransactionType::INCOME, TransactionEffect::CREDIT, 200], [$includedAccount, TransactionType::EXPENSE, TransactionEffect::DEBIT, 100], [$ignoredAccount, TransactionType::INCOME, TransactionEffect::CREDIT, 900], [$ignoredAccount, TransactionType::EXPENSE, TransactionEffect::DEBIT, 300]] as [$account, $type, $effect, $amount]) {
            Transaction::query()->create([
                ...$this->payload($wallet, $account, null, $type, $effect, $amount),
                'created_by_member_id' => $memberId,
                'updated_by_member_id' => $memberId,
            ]);
        }

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/monthly-summary?wallet_id='.$wallet->id.'&month=2026-09')
            ->assertOk()
            ->assertJsonPath('data.balance', 1100)
            ->assertJsonPath('data.actual_income', 200)
            ->assertJsonPath('data.forecast_income', 200)
            ->assertJsonPath('data.actual_expenses', 100)
            ->assertJsonPath('data.forecast_expenses', 100)
            ->assertJsonPath('data.planning_consolidated.income.realized', 200)
            ->assertJsonPath('data.planning_consolidated.expenses.realized', 100);
    }

    public function test_transaction_persists_due_date_recurrence_installment_and_automatic_posting_options(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::EDITOR);
        $account = $this->account($wallet);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/transactions', [
            ...$this->payload($wallet, $account, null, TransactionType::EXPENSE, TransactionEffect::DEBIT, 12500),
            'status' => TransactionStatus::PROJECTED->value,
            'due_date' => '2026-09-20',
            'recurrence_type' => TransactionRecurrence::INSTALLMENT->value,
            'installment_initial' => 2,
            'installment_count' => 12,
            'installment_periodicity' => InstallmentPeriodicity::MONTHLY->value,
            'auto_post_on_due_date' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.due_date', '2026-09-20')
            ->assertJsonPath('data.recurrence_type', TransactionRecurrence::INSTALLMENT->value)
            ->assertJsonPath('data.installment_initial', 2)
            ->assertJsonPath('data.installment_count', 12)
            ->assertJsonPath('data.installment_periodicity', InstallmentPeriodicity::MONTHLY->value)
            ->assertJsonPath('data.auto_post_on_due_date', true);
    }

    public function test_due_projected_transactions_are_posted_by_the_automatic_command(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::OWNER);
        $account = $this->account($wallet);
        $memberId = WalletMember::query()->where('wallet_id', $wallet->id)->value('id');
        $transaction = Transaction::create([...$this->payload($wallet, $account, null, TransactionType::EXPENSE, TransactionEffect::DEBIT, 100), 'status' => TransactionStatus::PROJECTED, 'due_date' => '2026-09-01', 'auto_post_on_due_date' => true, 'created_by_member_id' => $memberId, 'updated_by_member_id' => $memberId]);

        $this->artisan('transactions:post-due', ['--date' => '2026-09-11'])->assertSuccessful();

        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'status' => TransactionStatus::POSTED->value]);
    }

    public function test_viewer_cannot_create_transaction(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::VIEWER);
        $account = $this->account($wallet);
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/transactions', $this->payload($wallet, $account, null, TransactionType::EXPENSE, TransactionEffect::DEBIT, 100))->assertForbidden();
    }

    public function test_transaction_rejects_cross_wallet_dependencies_and_invalid_contract(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::OWNER);
        $other = Wallet::create(['name' => 'Outro']);
        $account = $this->account($other);
        $category = $this->category($other, TransactionType::EXPENSE);
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/transactions', $this->payload($wallet, $account, $category, TransactionType::EXPENSE, TransactionEffect::DEBIT, 100));
        $response->assertUnprocessable()->assertJsonValidationErrors(['account_id']);
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/transactions', $this->payload($wallet, $this->account($wallet), $category, TransactionType::EXPENSE, TransactionEffect::DEBIT, 100));
        $response->assertUnprocessable()->assertJsonValidationErrors(['category_id']);
        $valid = $this->payload($wallet, $this->account($wallet), null, TransactionType::EXPENSE, TransactionEffect::CREDIT, 100);
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/transactions', $valid)->assertUnprocessable()->assertJsonValidationErrors('effect');
        $valid['effect'] = TransactionEffect::DEBIT->value;
        $valid['financial_instrument_type'] = FinancialInstrumentType::CREDIT_CARD->value;
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/transactions', $valid)->assertUnprocessable()->assertJsonValidationErrors('financial_instrument_type');
    }

    public function test_cancelled_transaction_is_excluded_and_list_isolated_and_filterable(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::OWNER);
        $account = $this->account($wallet);
        $payload = $this->payload($wallet, $account, null, TransactionType::EXPENSE, TransactionEffect::DEBIT, 1000);
        $payload['status'] = TransactionStatus::CANCELLED->value;
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/transactions', $payload)->assertCreated();
        $other = Wallet::create(['name' => 'Oculta']);
        $this->account($other);

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/transactions?wallet_id='.$wallet->id.'&status=CANCELLED')->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($user, 'sanctum')->getJson('/api/v1/transactions?wallet_id='.$wallet->id.'&status=POSTED')->assertOk()->assertJsonCount(0, 'data');

        $secondWallet = Wallet::create(['name' => 'Segunda']);
        $secondMember = WalletMember::create(['wallet_id' => $secondWallet->id, 'user_id' => $user->id, 'role' => WalletMemberRole::VIEWER, 'joined_at' => Carbon::now()]);
        $secondAccount = $this->account($secondWallet);
        Transaction::create([...$this->payload($secondWallet, $secondAccount, null, TransactionType::EXPENSE, TransactionEffect::DEBIT, 800), 'created_by_member_id' => $secondMember->id, 'updated_by_member_id' => $secondMember->id]);
        $this->actingAs($user, 'sanctum')->getJson('/api/v1/transactions?wallet_id='.$wallet->id)->assertOk()->assertJsonCount(1, 'data')->assertJsonMissing(['amount' => 800]);
        $this->actingAs($user, 'sanctum')->getJson('/api/v1/transactions?wallet_id='.$wallet->id.'&account_id='.$account->id.'&type=EXPENSE&month=2026-09')->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($user, 'sanctum')->getJson('/api/v1/monthly-summary?wallet_id='.$wallet->id.'&month=2026-09')->assertJsonPath('data.balance', 0)->assertJsonPath('data.actual_expenses', 0)->assertJsonPath('data.forecast_expenses', 0);
    }

    public function test_transaction_list_returns_paginated_data_with_stable_order_and_date_filters(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::VIEWER);
        $account = $this->account($wallet);
        $first = $this->payload($wallet, $account, null, TransactionType::EXPENSE, TransactionEffect::DEBIT, 100);
        $second = [...$first, 'amount' => 200];
        $third = [...$first, 'amount' => 300, 'transaction_date' => '2026-10-01', 'competence_date' => '2026-10-01'];
        foreach ([$first, $second, $third] as $payload) {
            $transaction = Transaction::create([...$payload, 'created_by_member_id' => WalletMember::query()->where('wallet_id', $wallet->id)->value('id'), 'updated_by_member_id' => WalletMember::query()->where('wallet_id', $wallet->id)->value('id')]);
            $this->assertNotNull($transaction->id);
        }

        $pageOne = $this->actingAs($user, 'sanctum')->getJson("/api/v1/transactions?wallet_id={$wallet->id}&per_page=2&sort_by=transaction_date&sort_direction=desc");
        $pageOne->assertOk()
            ->assertJsonStructure(['data', 'links' => ['first', 'last', 'prev', 'next'], 'meta' => ['current_page', 'from', 'last_page', 'per_page', 'to', 'total']])
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('data.0.amount', 300)
            ->assertJsonPath('data.1.amount', 200);

        $this->actingAs($user, 'sanctum')->getJson("/api/v1/transactions?wallet_id={$wallet->id}&per_page=2&sort_by=transaction_date&sort_direction=desc&page=2")
            ->assertOk()
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('data.0.amount', 100);

        $this->actingAs($user, 'sanctum')->getJson("/api/v1/transactions?wallet_id={$wallet->id}&transaction_date_from=2026-09-01&transaction_date_to=2026-09-30")
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonCount(2, 'data');
    }

    public function test_transaction_list_rejects_invalid_pagination_sort_and_date_ranges(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::VIEWER);

        $this->actingAs($user, 'sanctum')->getJson("/api/v1/transactions?wallet_id={$wallet->id}&page=0&per_page=101&sort_by=secret&sort_direction=sideways&transaction_date_from=2026-10-01&transaction_date_to=2026-09-01")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['page', 'per_page', 'sort_by', 'sort_direction', 'transaction_date_to']);
    }

    public function test_request_rejects_positive_amount_and_malformed_enums_and_accepts_global_category_casts(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::OWNER);
        $account = $this->account($wallet);
        $category = Category::create(['wallet_id' => null, 'name' => 'Global', 'type' => TransactionType::EXPENSE, 'active' => true]);
        $payload = $this->payload($wallet, $account, $category, TransactionType::EXPENSE, TransactionEffect::DEBIT, 0);
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/transactions', $payload)->assertUnprocessable()->assertJsonValidationErrors('amount');
        foreach (['type' => 'BROKEN', 'effect' => 'BROKEN', 'financial_instrument_type' => 'BROKEN', 'status' => 'BROKEN'] as $field => $value) {
            $invalid = $this->payload($wallet, $account, null, TransactionType::EXPENSE, TransactionEffect::DEBIT, 100);
            $invalid[$field] = $value;
            $this->actingAs($user, 'sanctum')->postJson('/api/v1/transactions', $invalid)->assertUnprocessable()->assertJsonValidationErrors($field);
        }
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/transactions', $this->payload($wallet, $account, $category, TransactionType::EXPENSE, TransactionEffect::DEBIT, 100));
        $response->assertCreated()->assertJsonPath('data.type', 'EXPENSE')->assertJsonPath('data.effect', 'DEBIT')->assertJsonPath('data.financial_instrument_type', 'ACCOUNT');
        $transaction = Transaction::query()->latest('id')->firstOrFail();
        $this->assertInstanceOf(TransactionType::class, $transaction->type);
        $this->assertInstanceOf(TransactionEffect::class, $transaction->effect);
        $this->assertSame(100, $transaction->amount);
    }

    public function test_transaction_creation_rejects_fields_owned_by_other_use_cases_without_persisting(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::EDITOR);
        $account = $this->account($wallet);
        $payload = [
            ...$this->payload($wallet, $account, null, TransactionType::EXPENSE, TransactionEffect::DEBIT, 100),
            'recurring_transaction_id' => 1,
            'credit_card_invoice_id' => 1,
            'installment_id' => 1,
            'transfer_group_id' => 'caller-controlled',
            'reversal_of_transaction_id' => 1,
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/transactions', $payload);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'recurring_transaction_id',
            'credit_card_invoice_id',
            'installment_id',
            'transfer_group_id',
            'reversal_of_transaction_id',
        ]);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_same_wallet_category_with_wrong_type_is_rejected(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::EDITOR);
        $account = $this->account($wallet);
        $category = $this->category($wallet, TransactionType::INCOME);
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/transactions', $this->payload($wallet, $account, $category, TransactionType::EXPENSE, TransactionEffect::DEBIT, 100))->assertUnprocessable()->assertJsonValidationErrors('category_id');
    }

    /** @return array{User, Wallet} */
    private function walletWithMember(WalletMemberRole $role): array
    {
        $user = User::factory()->create();
        $wallet = Wallet::create(['name' => uniqid('wallet')]);
        WalletMember::create(['wallet_id' => $wallet->id, 'user_id' => $user->id, 'role' => $role, 'joined_at' => Carbon::now()]);

        return [$user, $wallet];
    }

    private function account(Wallet $wallet, int $initialBalance = 0): Account
    {
        return Account::create(['wallet_id' => $wallet->id, 'name' => uniqid('account'), 'type' => AccountType::CHECKING, 'initial_balance' => $initialBalance, 'active' => true]);
    }

    private function category(Wallet $wallet, TransactionType $type): Category
    {
        return Category::create(['wallet_id' => $wallet->id, 'name' => uniqid('category'), 'type' => $type, 'active' => true]);
    }

    /** @return array<string, mixed> */
    private function payload(Wallet $wallet, Account $account, ?Category $category, TransactionType $type, TransactionEffect $effect, int $amount): array
    {
        return ['wallet_id' => $wallet->id, 'account_id' => $account->id, 'category_id' => $category?->id, 'description' => 'Lançamento', 'type' => $type->value, 'effect' => $effect->value, 'amount' => $amount, 'financial_instrument_type' => FinancialInstrumentType::ACCOUNT->value, 'transaction_date' => '2026-09-07', 'competence_date' => '2026-09-07', 'status' => TransactionStatus::POSTED->value];
    }
}
