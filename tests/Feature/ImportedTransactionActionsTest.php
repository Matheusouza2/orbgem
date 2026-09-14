<?php

namespace Tests\Feature;

use App\Enums\CreditCardInvoiceStatus;
use App\Enums\FinancialInstrumentType;
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
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ImportedTransactionActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_can_edit_and_delete_an_account_transaction_with_none_effect(): void
    {
        [$user, $wallet, $externalAccount] = $this->externalAccount();
        $account = Account::create(['wallet_id' => $wallet->id, 'name' => 'Conta', 'type' => 'CHECKING', 'initial_balance' => 0, 'active' => true]);
        $transaction = Transaction::create([
            'wallet_id' => $wallet->id, 'account_id' => $account->id, 'description' => 'Importada',
            'type' => TransactionType::EXPENSE, 'effect' => TransactionEffect::NONE, 'amount' => 1000,
            'financial_instrument_type' => FinancialInstrumentType::ACCOUNT, 'transaction_date' => '2026-09-10',
            'competence_date' => '2026-09-10', 'status' => TransactionStatus::POSTED,
            'created_by_member_id' => $wallet->members()->first()->id, 'updated_by_member_id' => $wallet->members()->first()->id,
        ]);
        $this->actingAs($user, 'sanctum')->putJson('/api/v1/transactions/'.$transaction->id, [
            'wallet_id' => $wallet->id, 'account_id' => $account->id, 'description' => 'Corrigida', 'type' => 'EXPENSE',
            'amount' => 1200, 'financial_instrument_type' => 'ACCOUNT', 'transaction_date' => '2026-09-11',
            'competence_date' => '2026-09-11', 'status' => 'POSTED', 'recurrence_type' => 'NONE',
        ])->assertOk();
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'description' => 'Corrigida', 'effect' => 'DEBIT']);

        $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/transactions/'.$transaction->id)->assertNoContent();
        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
    }

    public function test_editor_can_edit_and_delete_an_imported_card_transaction_from_an_open_invoice(): void
    {
        [$user, $wallet, $externalAccount] = $this->externalCardAccount();
        $invoice = CreditCardInvoice::create([
            'wallet_id' => $wallet->id, 'credit_card_id' => $externalAccount->accountable_id, 'reference_month' => '2026-09',
            'closing_date' => '2026-09-15', 'due_date' => '2026-10-05', 'status' => CreditCardInvoiceStatus::OPEN,
        ]);
        $transaction = Transaction::create([
            'wallet_id' => $wallet->id, 'description' => 'Compra Pluggy', 'type' => TransactionType::EXPENSE,
            'effect' => TransactionEffect::NONE, 'amount' => 2500, 'financial_instrument_type' => FinancialInstrumentType::CREDIT_CARD,
            'credit_card_invoice_id' => $invoice->id, 'transaction_date' => '2026-09-10', 'competence_date' => '2026-09-01',
            'due_date' => '2026-10-05', 'status' => TransactionStatus::POSTED,
            'created_by_member_id' => $wallet->members()->first()->id, 'updated_by_member_id' => $wallet->members()->first()->id,
        ]);
        $this->actingAs($user, 'sanctum')->getJson('/api/v1/credit-cards/'.$externalAccount->accountable_id.'/transactions?wallet_id='.$wallet->id.'&month=2026-09')
            ->assertOk()->assertJsonPath('data.0.can_edit', true)->assertJsonPath('data.0.can_delete', true);
        ExternalTransaction::create(['external_account_id' => $externalAccount->id, 'transaction_id' => $transaction->id, 'source' => 'pluggy', 'external_id' => 'card-transaction']);
        $this->actingAs($user, 'sanctum')->getJson('/api/v1/credit-cards/'.$externalAccount->accountable_id.'/transactions?wallet_id='.$wallet->id.'&month=2026-09')
            ->assertOk()->assertJsonPath('data.0.can_edit', true)->assertJsonPath('data.0.can_delete', true);
        $this->actingAs($user, 'sanctum')->putJson('/api/v1/credit-card-transactions/'.$transaction->id, [
            'description' => 'Compra corrigida', 'amount' => 2700, 'transaction_date' => '2026-09-12',
        ])->assertOk();
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'description' => 'Compra corrigida', 'amount' => 2700]);

        $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/credit-card-transactions/'.$transaction->id)->assertNoContent();
        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
    }

    /** @return array{User, Wallet, ExternalAccount} */
    private function externalAccount(): array
    {
        $user = User::factory()->create();
        $wallet = Wallet::create(['name' => 'Carteira']);
        WalletMember::create(['wallet_id' => $wallet->id, 'user_id' => $user->id, 'role' => WalletMemberRole::EDITOR, 'joined_at' => Carbon::now()]);
        $account = Account::create(['wallet_id' => $wallet->id, 'name' => 'Conta Pluggy', 'type' => 'CHECKING', 'initial_balance' => 0, 'active' => true]);
        $connection = FinancialConnection::create(['wallet_id' => $wallet->id, 'provider' => 'pluggy', 'external_id' => 'item', 'status' => 'UPDATED']);
        $external = ExternalAccount::create(['financial_connection_id' => $connection->id, 'external_id' => 'account', 'type' => 'BANK', 'subtype' => 'CHECKING_ACCOUNT', 'accountable_type' => Account::class, 'accountable_id' => $account->id]);

        return [$user, $wallet, $external];
    }

    /** @return array{User, Wallet, ExternalAccount} */
    private function externalCardAccount(): array
    {
        $result = $this->externalAccount();
        $card = CreditCard::create(['wallet_id' => $result[1]->id, 'name' => 'Cartão Pluggy', 'credit_limit' => 100000, 'closing_day' => 15, 'due_day' => 5, 'active' => true]);
        $result[2]->update(['external_id' => 'card', 'type' => 'CREDIT', 'subtype' => 'CREDIT_CARD', 'accountable_type' => CreditCard::class, 'accountable_id' => $card->id]);

        return $result;
    }
}
