<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\CreditCardInvoiceStatus;
use App\Enums\FinancialInstrumentType;
use App\Enums\InstallmentStatus;
use App\Enums\TransactionEffect;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\WalletMemberRole;
use App\Models\Account;
use App\Models\CreditCard;
use App\Models\CreditCardInvoice;
use App\Models\ExternalAccount;
use App\Models\ExternalTransaction;
use App\Models\FinancialConnection;
use App\Models\Installment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CreditCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_can_create_and_list_wallet_credit_cards(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::EDITOR);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/credit-cards', [
            'wallet_id' => $wallet->id,
            'name' => 'Cartão principal',
            'institution' => 'Banco',
            'limit' => 500000,
            'closing_day' => 15,
            'due_day' => 5,
        ]);

        $response->assertCreated()->assertJsonPath('data.name', 'Cartão principal')->assertJsonPath('data.limit', 500000);
        $this->actingAs($user, 'sanctum')->getJson('/api/v1/credit-cards?wallet_id='.$wallet->id)->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_viewer_cannot_create_credit_card_and_wallets_are_isolated(): void
    {
        [$viewer, $wallet] = $this->walletWithMember(WalletMemberRole::VIEWER);
        $otherWallet = Wallet::create(['name' => 'Outra']);

        $this->actingAs($viewer, 'sanctum')->postJson('/api/v1/credit-cards', [
            'wallet_id' => $wallet->id,
            'name' => 'Negado',
            'limit' => 1000,
            'closing_day' => 15,
            'due_day' => 5,
        ])->assertForbidden();

        CreditCard::create(['wallet_id' => $otherWallet->id, 'name' => 'Oculto', 'credit_limit' => 1000, 'closing_day' => 15, 'due_day' => 5, 'active' => true]);
        $this->actingAs($viewer, 'sanctum')->getJson('/api/v1/credit-cards?wallet_id='.$wallet->id)->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_purchase_creates_exact_installments_invoices_and_projected_card_transactions(): void
    {
        [$user, $wallet, $member] = $this->walletWithMember(WalletMemberRole::EDITOR);
        $card = $this->creditCard($wallet);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/credit-card-purchases', [
            'wallet_id' => $wallet->id,
            'credit_card_id' => $card->id,
            'description' => 'Compra parcelada',
            'purchase_date' => '2026-09-10',
            'total_amount' => 1000,
            'installment_count' => 3,
        ]);

        $response->assertCreated()->assertJsonPath('data.total_amount', 1000)->assertJsonPath('data.installment_count', 3);
        $this->assertDatabaseCount('installments', 3);
        $this->assertSame(1000, (int) Installment::query()->sum('amount'));
        $this->assertDatabaseCount('credit_card_invoices', 3);
        $this->assertDatabaseCount('transactions', 3);
        $this->assertSame(0, Account::query()->count());
        $this->assertSame([TransactionType::EXPENSE, TransactionType::EXPENSE, TransactionType::EXPENSE], Transaction::query()->orderBy('id')->pluck('type')->all());
        $this->assertSame([TransactionEffect::NONE, TransactionEffect::NONE, TransactionEffect::NONE], Transaction::query()->orderBy('id')->pluck('effect')->all());
        $this->assertSame([FinancialInstrumentType::CREDIT_CARD, FinancialInstrumentType::CREDIT_CARD, FinancialInstrumentType::CREDIT_CARD], Transaction::query()->orderBy('id')->pluck('financial_instrument_type')->all());
        $this->assertSame([TransactionStatus::PROJECTED, TransactionStatus::PROJECTED, TransactionStatus::PROJECTED], Transaction::query()->orderBy('id')->pluck('status')->all());
        $this->assertSame([$member->id, $member->id, $member->id], Transaction::query()->orderBy('id')->pluck('created_by_member_id')->all());
    }

    public function test_card_list_includes_current_invoice_and_limit_usage(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::OWNER);
        $card = $this->creditCard($wallet);
        $this->createPurchase($user, $wallet, $card);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/credit-cards?wallet_id='.$wallet->id)
            ->assertOk()
            ->assertJsonPath('data.0.current_invoice_amount', 1000)
            ->assertJsonPath('data.0.limit_usage_percentage', 1);
    }

    public function test_card_transactions_include_purchases_and_imported_pluggy_transactions(): void
    {
        [$user, $wallet, $member] = $this->walletWithMember(WalletMemberRole::EDITOR);
        $card = $this->creditCard($wallet);
        $this->createPurchase($user, $wallet, $card);
        $connection = FinancialConnection::query()->create(['wallet_id' => $wallet->id, 'provider' => 'pluggy', 'external_id' => 'item-1', 'institution_name' => 'Banco Teste', 'status' => 'UPDATED']);
        $externalAccount = ExternalAccount::query()->create(['financial_connection_id' => $connection->id, 'external_id' => 'account-1', 'type' => 'CREDIT', 'subtype' => 'CREDIT_CARD', 'accountable_type' => CreditCard::class, 'accountable_id' => $card->id]);
        $importedTransaction = Transaction::query()->create([
            'wallet_id' => $wallet->id,
            'description' => 'Compra importada',
            'type' => TransactionType::EXPENSE,
            'effect' => TransactionEffect::NONE,
            'amount' => 2500,
            'financial_instrument_type' => FinancialInstrumentType::CREDIT_CARD,
            'transaction_date' => '2026-09-12',
            'competence_date' => '2026-09-12',
            'due_date' => '2026-10-05',
            'status' => TransactionStatus::POSTED,
            'created_by_member_id' => $member->id,
            'updated_by_member_id' => $member->id,
        ]);
        ExternalTransaction::query()->create(['external_account_id' => $externalAccount->id, 'transaction_id' => $importedTransaction->id, 'source' => 'pluggy', 'external_id' => 'transaction-1']);

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/credit-cards/'.$card->id.'/transactions?wallet_id='.$wallet->id.'&month=2026-09&per_page=10')
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonFragment(['description' => 'Compra importada'])
            ->assertJsonFragment(['description' => 'Compra (1/1)']);
    }

    public function test_invoice_can_close_and_mark_its_pending_installments_as_invoiced(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::OWNER);
        $card = $this->creditCard($wallet);
        $this->createPurchase($user, $wallet, $card);
        $invoice = CreditCardInvoice::query()->where('reference_month', '2026-09')->firstOrFail();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/credit-card-invoices/'.$invoice->id.'/close')->assertOk()->assertJsonPath('data.status', CreditCardInvoiceStatus::CLOSED->value);
        $this->assertDatabaseHas('installments', ['credit_card_invoice_id' => $invoice->id, 'status' => InstallmentStatus::INVOICED->value]);
    }

    public function test_invoice_payment_debits_account_and_supports_partial_payment_without_creating_expense(): void
    {
        [$user, $wallet, $member] = $this->walletWithMember(WalletMemberRole::OWNER);
        $card = $this->creditCard($wallet);
        $account = Account::create(['wallet_id' => $wallet->id, 'name' => 'Conta', 'type' => AccountType::CHECKING, 'initial_balance' => 5000, 'active' => true]);
        $this->createPurchase($user, $wallet, $card);
        $invoice = CreditCardInvoice::query()->where('reference_month', '2026-09')->firstOrFail();
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/credit-card-invoices/'.$invoice->id.'/close')->assertOk();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/credit-card-invoices/'.$invoice->id.'/payments', ['account_id' => $account->id, 'amount' => 400])->assertCreated();
        $this->assertDatabaseHas('invoice_payments', ['credit_card_invoice_id' => $invoice->id, 'amount' => 400]);
        $this->assertSame(CreditCardInvoiceStatus::CLOSED, $invoice->fresh()->status);
        $this->assertSame(4600, $this->accountBalance($account));
        $this->assertSame(1, Transaction::query()->where('type', TransactionType::EXPENSE)->count());

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/credit-card-invoices/'.$invoice->id.'/payments', ['account_id' => $account->id, 'amount' => 600])->assertCreated()->assertJsonPath('data.status', CreditCardInvoiceStatus::PAID->value);
        $this->assertSame(4000, $this->accountBalance($account));
        $this->assertSame(2, Transaction::query()->where('type', TransactionType::TRANSFER)->count());
        $this->assertSame($member->id, Transaction::query()->where('type', TransactionType::TRANSFER)->latest('id')->value('created_by_member_id'));
    }

    public function test_payment_cannot_exceed_invoice_total_or_use_another_wallet_account(): void
    {
        [$user, $wallet] = $this->walletWithMember(WalletMemberRole::OWNER);
        $card = $this->creditCard($wallet);
        $this->createPurchase($user, $wallet, $card);
        $invoice = CreditCardInvoice::query()->where('reference_month', '2026-09')->firstOrFail();
        $account = Account::create(['wallet_id' => $wallet->id, 'name' => 'Conta', 'type' => AccountType::CHECKING, 'initial_balance' => 5000, 'active' => true]);
        $otherWallet = Wallet::create(['name' => 'Outra']);
        $otherAccount = Account::create(['wallet_id' => $otherWallet->id, 'name' => 'Outra conta', 'type' => AccountType::CHECKING, 'initial_balance' => 5000, 'active' => true]);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/credit-card-invoices/'.$invoice->id.'/payments', ['account_id' => $otherAccount->id, 'amount' => 100])->assertUnprocessable()->assertJsonValidationErrors('account_id');
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/credit-card-invoices/'.$invoice->id.'/payments', ['account_id' => $account->id, 'amount' => 1001])->assertUnprocessable()->assertJsonValidationErrors('amount');
        $this->assertDatabaseCount('invoice_payments', 0);
    }

    /** @return array{User, Wallet, WalletMember} */
    private function walletWithMember(WalletMemberRole $role): array
    {
        $user = User::factory()->create();
        $wallet = Wallet::create(['name' => uniqid('wallet')]);
        $member = WalletMember::create(['wallet_id' => $wallet->id, 'user_id' => $user->id, 'role' => $role, 'joined_at' => Carbon::now()]);

        return [$user, $wallet, $member];
    }

    private function creditCard(Wallet $wallet): CreditCard
    {
        return CreditCard::create(['wallet_id' => $wallet->id, 'name' => 'Cartão', 'credit_limit' => 100000, 'closing_day' => 15, 'due_day' => 5, 'active' => true]);
    }

    private function createPurchase(User $user, Wallet $wallet, CreditCard $card): void
    {
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/credit-card-purchases', ['wallet_id' => $wallet->id, 'credit_card_id' => $card->id, 'description' => 'Compra', 'purchase_date' => '2026-09-10', 'total_amount' => 1000, 'installment_count' => 1])->assertCreated();
    }

    private function accountBalance(Account $account): int
    {
        return $account->initial_balance + Transaction::query()->where('account_id', $account->id)->where('status', TransactionStatus::POSTED)->get()->sum(fn (Transaction $transaction): int => match ($transaction->effect) {
            TransactionEffect::CREDIT => $transaction->amount,
            TransactionEffect::DEBIT => -$transaction->amount,
            default => 0,
        });
    }
}
