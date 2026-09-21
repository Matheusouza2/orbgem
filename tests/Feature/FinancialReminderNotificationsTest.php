<?php

namespace Tests\Feature;

use App\Enums\FinancialInstrumentType;
use App\Enums\TransactionEffect;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\WalletMemberRole;
use App\Models\Account;
use App\Models\CreditCard;
use App\Models\CreditCardInvoice;
use App\Models\CreditCardPurchase;
use App\Models\InAppNotification;
use App\Models\Installment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FinancialReminderNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_command_notifies_due_and_overdue_unpaid_transactions_once_and_ignores_third_party(): void
    {
        Carbon::setTestNow('2026-09-21 09:00:00');
        [$user, $wallet, $member, $account] = $this->walletWithMember();

        $this->transaction($wallet, $member, $account, 'Vence hoje', '2026-09-21');
        $this->transaction($wallet, $member, $account, 'Vencida', '2026-09-20');
        $this->transaction($wallet, $member, $account, 'Terceiro', '2026-09-21', true);
        $this->transaction($wallet, $member, $account, 'Futura', '2026-09-22');

        $this->artisan('notifications:send-financial-reminders', ['--date' => '2026-09-21'])->assertSuccessful();
        $this->artisan('notifications:send-financial-reminders', ['--date' => '2026-09-21'])->assertSuccessful();

        $notifications = InAppNotification::query()->where('user_id', $user->id)->get();

        self::assertCount(2, $notifications);
        self::assertSame(['TRANSACTION_DUE_TODAY', 'TRANSACTION_OVERDUE'], $notifications->pluck('type')->sort()->values()->all());
        self::assertSame(['Vence hoje', 'Vencida'], $notifications->pluck('data.description')->sort()->values()->all());
    }

    public function test_effectivating_a_transaction_notifies_that_it_was_paid(): void
    {
        [$user, $wallet, $member, $account] = $this->walletWithMember();
        $transaction = $this->transaction($wallet, $member, $account, 'Conta paga', '2026-09-21');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/transactions/'.$transaction->id.'/effectivate')
            ->assertOk();

        $this->assertDatabaseHas('in_app_notifications', ['user_id' => $user->id, 'type' => 'TRANSACTION_PAID']);
    }

    public function test_fully_paying_a_credit_card_invoice_notifies_that_the_invoice_was_paid(): void
    {
        [$user, $wallet, $member, $account] = $this->walletWithMember();
        $card = CreditCard::query()->create(['wallet_id' => $wallet->id, 'owner_wallet_member_id' => $member->id, 'name' => 'Cartão', 'credit_limit' => 100000, 'closing_day' => 15, 'due_day' => 5, 'active' => true]);
        $invoice = CreditCardInvoice::query()->create(['wallet_id' => $wallet->id, 'credit_card_id' => $card->id, 'reference_month' => '2026-09', 'closing_date' => '2026-09-15', 'due_date' => '2026-10-05', 'status' => 'CLOSED']);
        $purchase = CreditCardPurchase::query()->create(['wallet_id' => $wallet->id, 'credit_card_id' => $card->id, 'description' => 'Compra', 'purchase_date' => '2026-09-10', 'total_amount' => 1000, 'installment_count' => 1]);
        Installment::query()->create(['credit_card_purchase_id' => $purchase->id, 'credit_card_invoice_id' => $invoice->id, 'number' => 1, 'amount' => 1000, 'competence_date' => '2026-09-01', 'due_date' => '2026-10-05', 'status' => 'PENDING']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/credit-card-invoices/'.$invoice->id.'/payments', ['account_id' => $account->id, 'amount' => 1000, 'payment_date' => '2026-09-21'])
            ->assertCreated();

        $this->assertDatabaseHas('in_app_notifications', ['user_id' => $user->id, 'type' => 'CREDIT_CARD_INVOICE_PAID']);
    }

    public function test_automatic_payment_on_due_date_notifies_that_the_transaction_was_paid(): void
    {
        [$user, $wallet, $member, $account] = $this->walletWithMember();
        $transaction = $this->transaction($wallet, $member, $account, 'Pagamento automático', '2026-09-21');
        $transaction->update(['auto_post_on_due_date' => true]);

        $this->artisan('transactions:post-due', ['--date' => '2026-09-21'])->assertSuccessful();

        $this->assertDatabaseHas('in_app_notifications', ['user_id' => $user->id, 'type' => 'TRANSACTION_PAID']);
    }

    public function test_creating_an_already_paid_expense_notifies_that_it_was_paid(): void
    {
        [$user, $wallet, , $account] = $this->walletWithMember();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/transactions', [
            'wallet_id' => $wallet->id,
            'account_id' => $account->id,
            'description' => 'Compra paga',
            'type' => TransactionType::EXPENSE->value,
            'effect' => TransactionEffect::DEBIT->value,
            'amount' => 1000,
            'financial_instrument_type' => FinancialInstrumentType::ACCOUNT->value,
            'transaction_date' => '2026-09-21',
            'competence_date' => '2026-09-21',
            'status' => TransactionStatus::POSTED->value,
            'paid_at' => '2026-09-21 09:00:00',
        ])->assertCreated();

        $this->assertDatabaseHas('in_app_notifications', ['user_id' => $user->id, 'type' => 'TRANSACTION_PAID']);
    }

    /** @return array{User, Wallet, WalletMember, Account} */
    private function walletWithMember(): array
    {
        $user = User::factory()->create();
        $wallet = Wallet::query()->create(['name' => 'Carteira']);
        $member = WalletMember::query()->create(['wallet_id' => $wallet->id, 'user_id' => $user->id, 'role' => WalletMemberRole::OWNER, 'joined_at' => now()]);
        $account = Account::query()->create(['wallet_id' => $wallet->id, 'name' => 'Conta', 'type' => 'CHECKING', 'initial_balance' => 0, 'active' => true]);

        return [$user, $wallet, $member, $account];
    }

    private function transaction(Wallet $wallet, WalletMember $member, Account $account, string $description, string $dueDate, bool $thirdParty = false): Transaction
    {
        return Transaction::query()->create([
            'wallet_id' => $wallet->id,
            'account_id' => $account->id,
            'description' => $description,
            'type' => TransactionType::EXPENSE,
            'effect' => TransactionEffect::DEBIT,
            'amount' => 1000,
            'financial_instrument_type' => FinancialInstrumentType::ACCOUNT,
            'transaction_date' => '2026-09-01',
            'competence_date' => '2026-09-01',
            'due_date' => $dueDate,
            'status' => TransactionStatus::PROJECTED,
            'is_third_party' => $thirdParty,
            'created_by_member_id' => $member->id,
            'updated_by_member_id' => $member->id,
        ]);
    }
}
