<?php

namespace Tests\Feature;

use App\DTO\TransactionDTO;
use App\Enums\AccountType;
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
use Mockery\MockInterface;
use Tests\TestCase;

class AccountTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_creates_two_transfer_legs_with_balances_and_resource_contract(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::EDITOR);
        $source = $this->account($wallet, 10000, 'Source');
        $destination = $this->account($wallet, 2000, 'Destination');

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/account-transfers', $this->payload($wallet, $source, $destination, 2500));

        $response->assertCreated();
        $transactions = Transaction::query()->orderBy('id')->get();
        $responseTransactions = $response->json('data.transactions');
        $groupId = $response->json('data.transfer_group_id');
        $memberId = WalletMember::query()->where('wallet_id', $wallet->id)->where('user_id', $user->id)->value('id');

        $this->assertCount(2, $transactions);
        $this->assertCount(2, $responseTransactions);
        $this->assertSame([
            'data' => [
                'transfer_group_id' => $groupId,
                'transactions' => $transactions
                    ->map(fn (Transaction $transaction): array => $this->resourceContract($transaction))
                    ->all(),
            ],
        ], $response->json());
        $this->assertSame($groupId, $transactions[0]->transfer_group_id);
        $this->assertSame($groupId, $transactions[1]->transfer_group_id);
        $this->assertSame([
            ['account_id' => $source->id, 'effect' => TransactionEffect::DEBIT->value],
            ['account_id' => $destination->id, 'effect' => TransactionEffect::CREDIT->value],
        ], $transactions->map(fn (Transaction $transaction): array => ['account_id' => $transaction->account_id, 'effect' => $transaction->effect->value])->values()->all());
        $this->assertSame([$wallet->id, $wallet->id], $transactions->pluck('wallet_id')->all());
        $this->assertSame([2500, 2500], $transactions->pluck('amount')->all());
        $this->assertSame(['2026-09-07', '2026-09-07'], $transactions->pluck('transaction_date')->map(fn (Carbon $date): string => $date->toDateString())->all());
        $this->assertSame(['2026-09-07', '2026-09-07'], $transactions->pluck('competence_date')->map(fn (Carbon $date): string => $date->toDateString())->all());
        $this->assertSame([$memberId, $memberId], $transactions->pluck('created_by_member_id')->all());
        $this->assertSame([$memberId, $memberId], $transactions->pluck('updated_by_member_id')->all());
        $this->assertSame([null, null], $transactions->pluck('category_id')->all());
        $this->assertSame([null, null], $transactions->pluck('merchant_id')->all());
        $this->assertSame(7500, $response->json('data.transactions.0.account_id') === $source->id ? $this->balance($source) : $this->balance($destination));
        $this->assertSame(4500, $this->balance($destination));
        $this->assertSame(7500, $this->balance($source));
    }

    public function test_owner_can_transfer_but_viewer_and_guest_cannot(): void
    {
        [$owner, $wallet] = $this->walletWithMember(WalletMemberRole::OWNER);
        $source = $this->account($wallet);
        $destination = $this->account($wallet);

        $this->actingAs($owner, 'sanctum')->postJson('/api/v1/account-transfers', $this->payload($wallet, $source, $destination))->assertCreated();

        [$viewer, $viewerWallet] = $this->walletWithMember(WalletMemberRole::VIEWER);
        $viewerSource = $this->account($viewerWallet);
        $viewerDestination = $this->account($viewerWallet);
        $this->actingAs($viewer, 'sanctum')->postJson('/api/v1/account-transfers', $this->payload($viewerWallet, $viewerSource, $viewerDestination))->assertForbidden();
    }

    public function test_guest_cannot_create_transfer(): void
    {
        $this->postJson('/api/v1/account-transfers', [])->assertUnauthorized();
    }

    public function test_transfer_rejects_cross_wallet_same_account_and_non_positive_amount_without_rows(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::EDITOR);
        $source = $this->account($wallet);
        $otherWallet = Wallet::create(['name' => 'Other']);
        $other = $this->account($otherWallet);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/account-transfers', $this->payload($wallet, $source, $other))->assertUnprocessable()->assertJsonValidationErrors('to_account_id');
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/account-transfers', $this->payload($wallet, $source, $source))->assertUnprocessable()->assertJsonValidationErrors('to_account_id');
        $invalid = $this->payload($wallet, $source, $this->account($wallet), 0);
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/account-transfers', $invalid)->assertUnprocessable()->assertJsonValidationErrors('amount');
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_transfer_rejects_nonexistent_account_with_validation_error_and_no_rows(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::EDITOR);
        $source = $this->account($wallet);
        $destination = $this->account($wallet);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/account-transfers', [...$this->payload($wallet, $source, $destination), 'to_account_id' => 999999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('to_account_id');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_transfer_ignores_forbidden_caller_fields_and_persists_only_derived_values(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::EDITOR);
        $source = $this->account($wallet);
        $destination = $this->account($wallet);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/account-transfers', [
            ...$this->payload($wallet, $source, $destination, 2500),
            'type' => TransactionType::INCOME->value,
            'effect' => TransactionEffect::CREDIT->value,
            'financial_instrument_type' => 'CASH',
            'status' => TransactionStatus::PROJECTED->value,
            'merchant_id' => 999999,
            'category_id' => 999999,
            'created_by_member_id' => 999999,
            'updated_by_member_id' => 999999,
            'transfer_group_id' => 'caller-controlled',
        ]);

        $response->assertCreated();
        $transactions = Transaction::query()->orderBy('id')->get();
        $memberId = WalletMember::query()->where('wallet_id', $wallet->id)->where('user_id', $user->id)->value('id');

        $this->assertCount(2, $transactions);
        $this->assertSame([TransactionType::TRANSFER->value, TransactionType::TRANSFER->value], $transactions->pluck('type')->map(fn (TransactionType $type): string => $type->value)->all());
        $this->assertSame([TransactionEffect::DEBIT->value, TransactionEffect::CREDIT->value], $transactions->pluck('effect')->map(fn (TransactionEffect $effect): string => $effect->value)->all());
        $this->assertSame(['ACCOUNT', 'ACCOUNT'], $transactions->pluck('financial_instrument_type')->map(fn ($type): string => $type->value)->all());
        $this->assertSame([TransactionStatus::POSTED->value, TransactionStatus::POSTED->value], $transactions->pluck('status')->map(fn (TransactionStatus $status): string => $status->value)->all());
        $this->assertSame([null, null], $transactions->pluck('category_id')->all());
        $this->assertSame([null, null], $transactions->pluck('merchant_id')->all());
        $this->assertSame([$memberId, $memberId], $transactions->pluck('created_by_member_id')->all());
        $this->assertSame([$memberId, $memberId], $transactions->pluck('updated_by_member_id')->all());
        $this->assertNotSame('caller-controlled', $response->json('data.transfer_group_id'));
    }

    public function test_transfer_rolls_back_first_leg_when_second_leg_insertion_fails(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::EDITOR);
        $source = $this->account($wallet);
        $destination = $this->account($wallet);
        $realTransactionService = app(TransactionService::class);
        $calls = 0;

        $this->mock(TransactionService::class, function (MockInterface $mock) use ($realTransactionService, &$calls): void {
            $mock->shouldReceive('create')->twice()->andReturnUsing(function (TransactionDTO $dto) use ($realTransactionService, &$calls): Transaction {
                $calls++;
                if ($calls === 2) {
                    throw new \RuntimeException('second leg failed');
                }

                return $realTransactionService->create($dto);
            });
        });
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($user, 'sanctum')->postJson('/api/v1/account-transfers', $this->payload($wallet, $source, $destination));
            $this->fail('The second leg failure should be propagated.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('second leg failed', $exception->getMessage());
        }

        $this->assertSame(2, $calls);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_transfer_does_not_change_income_expense_summary(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::EDITOR);
        $source = $this->account($wallet);
        $destination = $this->account($wallet);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/account-transfers', $this->payload($wallet, $source, $destination, 1000))->assertCreated();

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/monthly-summary?wallet_id='.$wallet->id.'&month=2026-09')->assertOk()->assertJsonPath('data.actual_expenses', 0)->assertJsonPath('data.actual_income', 0)->assertJsonPath('data.balance', 0);
    }

    /** @return array{wallet_id: int, from_account_id: int, to_account_id: int, amount: int, transaction_date: string, competence_date: string, notes: string, payment_channel: string} */
    private function payload(Wallet $wallet, Account $source, Account $destination, int $amount = 1000): array
    {
        return ['wallet_id' => $wallet->id, 'from_account_id' => $source->id, 'to_account_id' => $destination->id, 'amount' => $amount, 'transaction_date' => '2026-09-07', 'competence_date' => '2026-09-07', 'notes' => 'Internal transfer', 'payment_channel' => 'BANK_TRANSFER'];
    }

    private function account(Wallet $wallet, int $initialBalance = 0, string $name = 'Account'): Account
    {
        return Account::create(['wallet_id' => $wallet->id, 'name' => $name.uniqid(), 'type' => AccountType::CHECKING, 'initial_balance' => $initialBalance, 'active' => true]);
    }

    /** @return array{User, Wallet} */
    private function walletWithMember(WalletMemberRole $role): array
    {
        $user = User::factory()->create();
        $wallet = Wallet::create(['name' => uniqid('wallet')]);
        WalletMember::create(['wallet_id' => $wallet->id, 'user_id' => $user->id, 'role' => $role, 'joined_at' => Carbon::now()]);

        return [$user, $wallet];
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
            'category_id' => $transaction->category_id,
            'merchant_id' => $transaction->merchant_id,
            'description' => $transaction->description,
            'type' => $transaction->type->value,
            'effect' => $transaction->effect->value,
            'amount' => $transaction->amount,
            'financial_instrument_type' => $transaction->financial_instrument_type->value,
            'transaction_date' => $transaction->transaction_date?->toDateString(),
            'competence_date' => $transaction->competence_date?->toDateString(),
            'due_date' => null,
            'recurrence_type' => $transaction->recurrence_type->value,
            'installment_initial' => null,
            'installment_count' => null,
            'installment_periodicity' => null,
            'auto_post_on_due_date' => false,
            'paid_at' => null,
            'status' => $transaction->status->value,
            'payment_channel' => $transaction->payment_channel?->value,
            'notes' => $transaction->notes,
            'is_third_party' => false,
            'recurring_transaction_id' => null,
            'credit_card_invoice_id' => null,
            'installment_id' => null,
            'transfer_group_id' => $transaction->transfer_group_id,
            'reversal_of_transaction_id' => null,
            'has_reversal' => $transaction->reversals()->exists(),
            'created_by_member_id' => $transaction->created_by_member_id,
            'updated_by_member_id' => $transaction->updated_by_member_id,
            'created_at' => $transaction->created_at?->toJSON(),
        ];
    }
}
