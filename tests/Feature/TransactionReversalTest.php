<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\FinancialInstrumentType;
use App\Enums\TransactionEffect;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\WalletMemberRole;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMember;
use App\Services\AccountBalanceCalculator;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class TransactionReversalTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_reverses_posted_expense_with_exact_resource_and_immutable_original(): void
    {
        [$user, $wallet, $member] = $this->walletWithMember(WalletMemberRole::EDITOR);
        $account = $this->account($wallet, 10000);
        $original = $this->transaction($wallet, $member, $account, TransactionType::EXPENSE, TransactionEffect::DEBIT, TransactionStatus::POSTED, 2500);
        $original->refresh();
        $originalAttributes = $original->getAttributes();

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/transactions/{$original->id}/reversal", [
            'transaction_date' => '2026-09-08',
            'competence_date' => '2026-10-01',
            'notes' => 'Compra devolvida',
            'amount' => 1,
            'account_id' => 999999,
            'effect' => TransactionEffect::DEBIT->value,
            'status' => TransactionStatus::CANCELLED->value,
        ]);

        $response->assertCreated();
        $reversal = Transaction::query()->where('reversal_of_transaction_id', $original->id)->firstOrFail();
        $this->assertSame(['data' => $this->resourceContract($reversal)], $response->json());
        $this->assertSame(TransactionType::INCOME, $reversal->type);
        $this->assertSame(TransactionEffect::CREDIT, $reversal->effect);
        $this->assertSame(TransactionStatus::POSTED, $reversal->status);
        $this->assertSame(FinancialInstrumentType::ACCOUNT, $reversal->financial_instrument_type);
        $this->assertSame(2500, $reversal->amount);
        $this->assertSame($account->id, $reversal->account_id);
        $this->assertSame($member->id, $reversal->created_by_member_id);
        $this->assertSame($member->id, $reversal->updated_by_member_id);
        $this->assertSame('2026-09-08', $reversal->transaction_date->toDateString());
        $this->assertSame('2026-10-01', $reversal->competence_date->toDateString());
        $this->assertSame('Compra devolvida', $reversal->notes);
        $this->assertSame($originalAttributes, $original->fresh()->getAttributes());
        $this->assertSame(10000, $this->balance($account));
    }

    public function test_owner_reverses_posted_income_and_monthly_summary_reflects_both_entries(): void
    {
        [$user, $wallet, $member] = $this->walletWithMember(WalletMemberRole::OWNER);
        $account = $this->account($wallet, 1000);
        $original = $this->transaction($wallet, $member, $account, TransactionType::INCOME, TransactionEffect::CREDIT, TransactionStatus::POSTED, 4000);

        $this->actingAs($user, 'sanctum')->postJson("/api/v1/transactions/{$original->id}/reversal", [])->assertCreated()
            ->assertJsonPath('data.type', TransactionType::EXPENSE->value)
            ->assertJsonPath('data.effect', TransactionEffect::DEBIT->value)
            ->assertJsonPath('data.amount', 4000);

        $this->assertSame(1000, $this->balance($account));
        $this->actingAs($user, 'sanctum')->getJson("/api/v1/monthly-summary?wallet_id={$wallet->id}&month=2026-09")
            ->assertOk()
            ->assertJsonPath('data.actual_income', 4000)
            ->assertJsonPath('data.actual_expenses', 4000)
            ->assertJsonPath('data.balance', 1000);
    }

    public function test_projected_transaction_reversal_stays_projected_and_does_not_change_account_balance(): void
    {
        [$user, $wallet, $member] = $this->walletWithMember(WalletMemberRole::EDITOR);
        $account = $this->account($wallet, 9000);
        $original = $this->transaction($wallet, $member, $account, TransactionType::EXPENSE, TransactionEffect::DEBIT, TransactionStatus::PROJECTED, 1200);

        $this->actingAs($user, 'sanctum')->postJson("/api/v1/transactions/{$original->id}/reversal", [])->assertCreated()
            ->assertJsonPath('data.status', TransactionStatus::PROJECTED->value)
            ->assertJsonPath('data.type', TransactionType::INCOME->value)
            ->assertJsonPath('data.effect', TransactionEffect::CREDIT->value)
            ->assertJsonPath('data.transaction_date', '2026-09-07')
            ->assertJsonPath('data.competence_date', '2026-09-07');

        $this->assertSame(9000, $this->balance($account));
    }

    public function test_reversing_one_transfer_leg_atomically_reverses_the_complete_balanced_group(): void
    {
        [$user, $wallet, $member] = $this->walletWithMember(WalletMemberRole::OWNER);
        $source = $this->account($wallet, 3000);
        $destination = $this->account($wallet, 1000);
        $transfer = $this->actingAs($user, 'sanctum')->postJson('/api/v1/account-transfers', [
            'wallet_id' => $wallet->id,
            'from_account_id' => $source->id,
            'to_account_id' => $destination->id,
            'amount' => 900,
            'transaction_date' => '2026-09-07',
            'competence_date' => '2026-09-07',
        ])->assertCreated();
        $originals = Transaction::query()->where('transfer_group_id', $transfer->json('data.transfer_group_id'))->orderBy('id')->get();
        $originalAttributes = $originals->mapWithKeys(fn (Transaction $transaction): array => [$transaction->id => $transaction->getAttributes()]);

        $this->actingAs($user, 'sanctum')->postJson("/api/v1/transactions/{$originals[0]->id}/reversal", [])->assertCreated()
            ->assertJsonPath('data.type', TransactionType::TRANSFER->value)
            ->assertJsonPath('data.effect', TransactionEffect::CREDIT->value)
            ->assertJsonPath('data.amount', 900)
            ->assertJsonPath('data.reversal_of_transaction_id', $originals[0]->id);

        $reversals = Transaction::query()->whereNotNull('reversal_of_transaction_id')->orderBy('id')->get();
        $this->assertCount(2, $reversals);
        $this->assertSame($originals->pluck('id')->all(), $reversals->pluck('reversal_of_transaction_id')->all());
        $this->assertSame([TransactionEffect::CREDIT, TransactionEffect::DEBIT], $reversals->pluck('effect')->all());
        $this->assertSame($originals->pluck('transfer_group_id')->all(), $reversals->pluck('transfer_group_id')->all());
        $this->assertSame($originalAttributes->all(), $originals->mapWithKeys(fn (Transaction $transaction): array => [$transaction->id => $transaction->fresh()->getAttributes()])->all());
        $this->assertSame(3000, $this->balance($source));
        $this->assertSame(1000, $this->balance($destination));
        $this->assertSame(4000, $this->balance($source) + $this->balance($destination));
    }

    public function test_partial_or_unbalanced_transfer_group_is_rejected_without_any_reversal(): void
    {
        [$user, $wallet, $member] = $this->walletWithMember(WalletMemberRole::OWNER);
        $groupId = (string) Str::uuid();
        $partial = $this->transaction($wallet, $member, $this->account($wallet), TransactionType::TRANSFER, TransactionEffect::DEBIT, TransactionStatus::POSTED, 900);
        $partial->update(['transfer_group_id' => $groupId]);

        $this->actingAs($user, 'sanctum')->postJson("/api/v1/transactions/{$partial->id}/reversal", [])->assertUnprocessable();
        $this->assertDatabaseCount('transactions', 1);

        $credit = $this->transaction($wallet, $member, $this->account($wallet), TransactionType::TRANSFER, TransactionEffect::CREDIT, TransactionStatus::POSTED, 800);
        $credit->update(['transfer_group_id' => $groupId]);

        $this->actingAs($user, 'sanctum')->postJson("/api/v1/transactions/{$partial->id}/reversal", [])->assertUnprocessable();
        $this->assertDatabaseCount('transactions', 2);
    }

    public function test_transfer_group_with_a_null_account_is_rejected_without_any_reversal(): void
    {
        [$user, $wallet, $member] = $this->walletWithMember(WalletMemberRole::OWNER);
        $groupId = (string) Str::uuid();
        $debit = $this->transaction($wallet, $member, $this->account($wallet), TransactionType::TRANSFER, TransactionEffect::DEBIT, TransactionStatus::POSTED, 900);
        $debit->update(['transfer_group_id' => $groupId]);
        $credit = $this->transaction($wallet, $member, null, TransactionType::TRANSFER, TransactionEffect::CREDIT, TransactionStatus::POSTED, 900);
        $credit->update(['transfer_group_id' => $groupId]);

        $this->actingAs($user, 'sanctum')->postJson("/api/v1/transactions/{$debit->id}/reversal", [])->assertUnprocessable();

        $this->assertDatabaseCount('transactions', 2);
        $this->assertSame(0, Transaction::query()->whereNotNull('reversal_of_transaction_id')->count());
    }

    public function test_repeated_transfer_reversal_from_either_original_leg_is_rejected_without_duplicates(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::OWNER);
        $source = $this->account($wallet);
        $destination = $this->account($wallet);
        $transfer = $this->actingAs($user, 'sanctum')->postJson('/api/v1/account-transfers', [
            'wallet_id' => $wallet->id,
            'from_account_id' => $source->id,
            'to_account_id' => $destination->id,
            'amount' => 900,
            'transaction_date' => '2026-09-07',
            'competence_date' => '2026-09-07',
        ])->assertCreated();
        $originals = Transaction::query()->where('transfer_group_id', $transfer->json('data.transfer_group_id'))->orderBy('id')->get();

        $this->actingAs($user, 'sanctum')->postJson("/api/v1/transactions/{$originals[0]->id}/reversal", [])->assertCreated();
        $this->actingAs($user, 'sanctum')->postJson("/api/v1/transactions/{$originals[0]->id}/reversal", [])->assertUnprocessable();
        $this->actingAs($user, 'sanctum')->postJson("/api/v1/transactions/{$originals[1]->id}/reversal", [])->assertUnprocessable();

        $this->assertDatabaseCount('transactions', 4);
        $this->assertSame(2, Transaction::query()->whereNotNull('reversal_of_transaction_id')->count());
    }

    public function test_transfer_group_reversal_rolls_back_when_second_reversal_creation_fails(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::OWNER);
        $source = $this->account($wallet);
        $destination = $this->account($wallet);
        $transfer = $this->actingAs($user, 'sanctum')->postJson('/api/v1/account-transfers', [
            'wallet_id' => $wallet->id,
            'from_account_id' => $source->id,
            'to_account_id' => $destination->id,
            'amount' => 900,
            'transaction_date' => '2026-09-07',
            'competence_date' => '2026-09-07',
        ])->assertCreated();
        $original = Transaction::query()->where('transfer_group_id', $transfer->json('data.transfer_group_id'))->firstOrFail();
        $calls = 0;
        $eventDispatcher = clone Transaction::getEventDispatcher();

        Transaction::creating(function (Transaction $transaction) use (&$calls): void {
            if ($transaction->reversal_of_transaction_id !== null) {
                $calls++;
                if ($calls === 2) {
                    throw new \RuntimeException('second reversal failed');
                }
            }
        });
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($user, 'sanctum')->postJson("/api/v1/transactions/{$original->id}/reversal", []);
            $this->fail('The second reversal failure should be propagated.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('second reversal failed', $exception->getMessage());
        } finally {
            Transaction::setEventDispatcher($eventDispatcher);
        }

        $this->assertSame(2, $calls);
        $this->assertDatabaseCount('transactions', 2);
        $this->assertSame(0, Transaction::query()->whereNotNull('reversal_of_transaction_id')->count());
    }

    public function test_reversal_description_is_deterministically_limited_to_255_multibyte_characters(): void
    {
        [$user, $wallet, $member] = $this->walletWithMember(WalletMemberRole::EDITOR);
        $original = $this->transaction($wallet, $member, $this->account($wallet), TransactionType::EXPENSE, TransactionEffect::DEBIT, TransactionStatus::POSTED, 500);
        $original->update(['description' => str_repeat('Á', 255)]);

        $this->actingAs($user, 'sanctum')->postJson("/api/v1/transactions/{$original->id}/reversal", [])->assertCreated();

        $reversal = Transaction::query()->where('reversal_of_transaction_id', $original->id)->firstOrFail();
        $this->assertSame(255, mb_strlen($reversal->description));
        $this->assertSame('Estorno: '.str_repeat('Á', 246), $reversal->description);
        $this->assertSame(str_repeat('Á', 255), $original->fresh()->description);
    }

    public function test_guest_viewer_and_cross_wallet_member_cannot_reverse(): void
    {
        [, $wallet, $member] = $this->walletWithMember(WalletMemberRole::OWNER);
        $original = $this->transaction($wallet, $member, $this->account($wallet), TransactionType::EXPENSE, TransactionEffect::DEBIT, TransactionStatus::POSTED, 500);

        $this->postJson("/api/v1/transactions/{$original->id}/reversal", [])->assertUnauthorized();

        [$viewer] = $this->walletWithMember(WalletMemberRole::VIEWER);
        WalletMember::create(['wallet_id' => $wallet->id, 'user_id' => $viewer->id, 'role' => WalletMemberRole::VIEWER, 'joined_at' => Carbon::now()]);
        $this->actingAs($viewer, 'sanctum')->postJson("/api/v1/transactions/{$original->id}/reversal", [])->assertForbidden();

        [$outsider] = $this->walletWithMember(WalletMemberRole::EDITOR);
        $this->actingAs($outsider, 'sanctum')->postJson("/api/v1/transactions/{$original->id}/reversal", [])->assertForbidden();
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_cancelled_transaction_reversal_and_repeated_reversal_are_rejected_without_writes(): void
    {
        [$user, $wallet, $member] = $this->walletWithMember(WalletMemberRole::EDITOR);
        $account = $this->account($wallet);
        $cancelled = $this->transaction($wallet, $member, $account, TransactionType::EXPENSE, TransactionEffect::DEBIT, TransactionStatus::CANCELLED, 500);

        $this->actingAs($user, 'sanctum')->postJson("/api/v1/transactions/{$cancelled->id}/reversal", [])->assertUnprocessable();
        $this->assertDatabaseCount('transactions', 1);

        $original = $this->transaction($wallet, $member, $account, TransactionType::EXPENSE, TransactionEffect::DEBIT, TransactionStatus::POSTED, 700);
        $first = $this->actingAs($user, 'sanctum')->postJson("/api/v1/transactions/{$original->id}/reversal", [])->assertCreated();

        $this->actingAs($user, 'sanctum')->postJson("/api/v1/transactions/{$original->id}/reversal", [])->assertUnprocessable();
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/transactions/'.$first->json('data.id').'/reversal', [])->assertUnprocessable();
        $this->assertDatabaseCount('transactions', 3);
    }

    public function test_transaction_with_none_effect_and_missing_transaction_are_rejected(): void
    {
        [$user, $wallet, $member] = $this->walletWithMember(WalletMemberRole::EDITOR);
        $original = $this->transaction($wallet, $member, $this->account($wallet), TransactionType::EXPENSE, TransactionEffect::NONE, TransactionStatus::PROJECTED, 800);

        $this->actingAs($user, 'sanctum')->postJson("/api/v1/transactions/{$original->id}/reversal", [])->assertUnprocessable();
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/transactions/999999/reversal', [])->assertNotFound();
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_optional_dates_must_be_valid(): void
    {
        [$user, $wallet, $member] = $this->walletWithMember(WalletMemberRole::EDITOR);
        $original = $this->transaction($wallet, $member, $this->account($wallet), TransactionType::EXPENSE, TransactionEffect::DEBIT, TransactionStatus::POSTED, 500);

        $this->actingAs($user, 'sanctum')->postJson("/api/v1/transactions/{$original->id}/reversal", [
            'transaction_date' => 'invalid',
            'competence_date' => 'invalid',
        ])->assertUnprocessable()->assertJsonValidationErrors(['transaction_date', 'competence_date']);
        $this->assertDatabaseCount('transactions', 1);
    }

    /** @return array{User, Wallet, WalletMember} */
    private function walletWithMember(WalletMemberRole $role): array
    {
        $user = User::factory()->create();
        $wallet = Wallet::create(['name' => uniqid('wallet')]);
        $member = WalletMember::create(['wallet_id' => $wallet->id, 'user_id' => $user->id, 'role' => $role, 'joined_at' => Carbon::now()]);

        return [$user, $wallet, $member];
    }

    private function account(Wallet $wallet, int $initialBalance = 0): Account
    {
        return Account::create(['wallet_id' => $wallet->id, 'name' => uniqid('account'), 'type' => AccountType::CHECKING, 'initial_balance' => $initialBalance, 'active' => true]);
    }

    private function transaction(Wallet $wallet, WalletMember $member, ?Account $account, TransactionType $type, TransactionEffect $effect, TransactionStatus $status, int $amount): Transaction
    {
        return Transaction::create([
            'wallet_id' => $wallet->id,
            'account_id' => $account?->id,
            'description' => 'Lançamento original',
            'type' => $type,
            'effect' => $effect,
            'amount' => $amount,
            'financial_instrument_type' => FinancialInstrumentType::ACCOUNT,
            'transaction_date' => '2026-09-07',
            'competence_date' => '2026-09-07',
            'status' => $status,
            'notes' => 'Nota original',
            'created_by_member_id' => $member->id,
            'updated_by_member_id' => $member->id,
        ]);
    }

    private function balance(Account $account): int
    {
        return app(AccountBalanceCalculator::class)->calculate($account->initial_balance, app(TransactionService::class)->postedAmountsForAccount($account->id));
    }

    /** @return array<string, mixed> */
    private function resourceContract(Transaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'wallet_id' => $transaction->wallet_id,
            'account_id' => $transaction->account_id,
            'category_id' => null,
            'merchant_id' => null,
            'description' => $transaction->description,
            'type' => $transaction->type->value,
            'effect' => $transaction->effect->value,
            'amount' => $transaction->amount,
            'financial_instrument_type' => $transaction->financial_instrument_type->value,
            'transaction_date' => $transaction->transaction_date->toDateString(),
            'competence_date' => $transaction->competence_date->toDateString(),
            'due_date' => null,
            'paid_at' => null,
            'status' => $transaction->status->value,
            'payment_channel' => null,
            'notes' => $transaction->notes,
            'recurring_transaction_id' => null,
            'credit_card_invoice_id' => null,
            'installment_id' => null,
            'transfer_group_id' => null,
            'reversal_of_transaction_id' => $transaction->reversal_of_transaction_id,
            'has_reversal' => $transaction->reversals()->exists(),
            'created_by_member_id' => $transaction->created_by_member_id,
            'updated_by_member_id' => $transaction->updated_by_member_id,
            'created_at' => $transaction->created_at->toJSON(),
        ];
    }
}
