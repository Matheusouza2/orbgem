<?php

namespace Tests\Feature;

use App\Enums\FinancialInstrumentType;
use App\Enums\TransactionEffect;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\WalletMemberRole;
use App\Models\Account;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_the_financial_dashboard(): void
    {
        $user = User::factory()->create();

        $this->withoutVite()->actingAs($user)
            ->get('/dashboard-financeiro')
            ->assertSuccessful()
            ->assertSee('Financial\\/Dashboard')
            ->assertSee((string) $user->id);
    }

    public function test_guest_is_redirected_from_the_financial_dashboard(): void
    {
        $this->get('/dashboard-financeiro')
            ->assertRedirect('/login');
    }

    public function test_authenticated_user_can_open_the_transactions_screen(): void
    {
        $user = User::factory()->create();

        $this->withoutVite()->actingAs($user)
            ->get('/transacoes')
            ->assertSuccessful()
            ->assertSee('Financial\/Transactions')
            ->assertSee((string) $user->id);
    }

    public function test_wallet_endpoint_returns_only_wallets_available_to_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $ownedWallet = Wallet::query()->create(['name' => 'Carteira pessoal']);
        $otherWallet = Wallet::query()->create(['name' => 'Carteira privada']);

        WalletMember::query()->create([
            'wallet_id' => $ownedWallet->id,
            'user_id' => $user->id,
            'role' => WalletMemberRole::OWNER,
            'joined_at' => now(),
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/wallets')
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownedWallet->id)
            ->assertJsonMissing(['id' => $otherWallet->id]);
    }

    public function test_web_session_can_read_real_summary_data_from_the_financial_api(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::query()->create(['name' => 'Carteira principal']);
        $member = WalletMember::query()->create(['wallet_id' => $wallet->id, 'user_id' => $user->id, 'role' => WalletMemberRole::EDITOR, 'joined_at' => now()]);
        $account = Account::query()->create(['wallet_id' => $wallet->id, 'name' => 'Conta corrente', 'type' => 'CHECKING', 'initial_balance' => 10000, 'active' => true]);

        $this->actingAs($user)->postJson('/api/v1/transactions', [
            'wallet_id' => $wallet->id, 'account_id' => $account->id, 'description' => 'Salário',
            'type' => TransactionType::INCOME->value, 'effect' => TransactionEffect::CREDIT->value,
            'amount' => 2500, 'financial_instrument_type' => FinancialInstrumentType::ACCOUNT->value,
            'transaction_date' => '2026-09-07', 'competence_date' => '2026-09-07',
            'status' => TransactionStatus::POSTED->value,
        ])->assertCreated();

        $this->actingAs($user)->withHeader('Referer', url('/dashboard-financeiro'))
            ->getJson('/api/v1/monthly-summary?wallet_id='.$wallet->id.'&month=2026-09')
            ->assertOk()
            ->assertJsonPath('data.balance', 12500)
            ->assertJsonPath('data.actual_income', 2500)
            ->assertJsonPath('data.actual_expenses', 0)
            ->assertJsonPath('data.forecast_expenses', 0);
    }

    public function test_summary_and_account_list_use_the_selected_competence_as_cutoff(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::query()->create(['name' => 'Carteira principal']);
        WalletMember::query()->create(['wallet_id' => $wallet->id, 'user_id' => $user->id, 'role' => WalletMemberRole::EDITOR, 'joined_at' => now()]);
        $account = Account::query()->create(['wallet_id' => $wallet->id, 'name' => 'Conta corrente', 'type' => 'CHECKING', 'initial_balance' => 10000, 'active' => true]);

        foreach ([
            ['amount' => 2500, 'effect' => TransactionEffect::CREDIT, 'date' => '2026-09-07'],
            ['amount' => 500, 'effect' => TransactionEffect::DEBIT, 'date' => '2026-09-20'],
            ['amount' => 900, 'effect' => TransactionEffect::CREDIT, 'date' => '2026-10-02'],
        ] as $movement) {
            $this->actingAs($user)->postJson('/api/v1/transactions', [
                'wallet_id' => $wallet->id, 'account_id' => $account->id, 'description' => 'Movimento',
                'type' => $movement['effect'] === TransactionEffect::CREDIT ? TransactionType::INCOME->value : TransactionType::EXPENSE->value,
                'effect' => $movement['effect']->value, 'amount' => $movement['amount'],
                'financial_instrument_type' => FinancialInstrumentType::ACCOUNT->value,
                'transaction_date' => $movement['date'], 'competence_date' => $movement['date'],
                'status' => TransactionStatus::POSTED->value,
            ])->assertCreated();
        }

        $this->actingAs($user)->getJson('/api/v1/monthly-summary?wallet_id='.$wallet->id.'&month=2026-09')
            ->assertOk()->assertJsonPath('data.balance', 12000);

        $this->actingAs($user)->getJson('/api/v1/accounts?wallet_id='.$wallet->id.'&month=2026-09')
            ->assertOk()->assertJsonPath('data.0.dashboard_balance', 12000);
    }

    public function test_dashboard_frontend_requests_and_renders_competence_scoped_values(): void
    {
        $hook = file_get_contents(resource_path('js/Pages/Financial/Hooks/useDashboard.js'));
        $service = file_get_contents(resource_path('js/Services/FinancialService.js'));
        $insights = file_get_contents(resource_path('js/Pages/Financial/Components/DashboardInsights.jsx'));

        $this->assertStringContainsString('listAccounts(selectedWalletId, { signal: controller.signal, month })', $hook);
        $this->assertStringContainsString('listCreditCards(selectedWalletId, { signal: controller.signal, month })', $hook);
        $this->assertStringContainsString("params.set('month', month)", $service);
        $this->assertStringContainsString('account.dashboard_balance', $insights);
        $this->assertStringContainsString('card.dashboard_balance', $insights);
        $this->assertStringContainsString('summary.expense_by_category', $insights);
        $this->assertStringContainsString('summary.expense_status_breakdown', $insights);
        $this->assertStringContainsString('summary.income_status_breakdown', $insights);
    }

    public function test_dashboard_frontend_exposes_daily_movement_flows_through_its_layers(): void
    {
        $dashboard = file_get_contents(resource_path('js/Pages/Financial/Dashboard.jsx'));
        $hook = file_get_contents(resource_path('js/Pages/Financial/Hooks/useDashboard.js'));
        $service = file_get_contents(resource_path('js/Services/FinancialService.js'));
        $transactionForm = file_get_contents(resource_path('js/Pages/Financial/Components/TransactionForm.jsx'));
        $transactionList = file_get_contents(resource_path('js/Pages/Financial/Components/TransactionList.jsx'));

        $this->assertStringContainsString('TransferModal', $dashboard);
        $this->assertStringContainsString('MerchantModal', $dashboard);
        $this->assertStringContainsString('ReversalConfirmModal', $dashboard);
        $this->assertStringContainsString('submitTransfer', $hook);
        $this->assertStringContainsString('submitMerchant', $hook);
        $this->assertStringContainsString('confirmReversal', $hook);
        $this->assertStringContainsString('listMerchants', $service);
        $this->assertStringContainsString('createMerchant', $service);
        $this->assertStringContainsString('createTransfer', $service);
        $this->assertStringContainsString('reverseTransaction', $service);
        $this->assertStringContainsString('@/Components/Inputs', $transactionForm);
        $this->assertStringContainsString('merchant_id', $transactionForm);
        $this->assertStringContainsString('category_id', $transactionForm);
        $this->assertStringContainsString('onRequestReversal', $transactionList);
    }

    public function test_transactions_navigation_entry_is_enabled_and_uses_the_existing_financial_flow(): void
    {
        $navigation = file_get_contents(resource_path('js/Components/Navigation/AppNavigation.jsx'));
        $transactions = file_get_contents(resource_path('js/Pages/Financial/Transactions.jsx'));
        $transactionList = file_get_contents(resource_path('js/Pages/Financial/Components/TransactionList.jsx'));
        $service = file_get_contents(resource_path('js/Services/FinancialService.js'));

        $this->assertStringContainsString("href: '/transacoes', icon: ReceiptText }", $navigation);
        $this->assertStringNotContainsString("href: '/transacoes', icon: ReceiptText, comingSoon: true", $navigation);
        $this->assertStringContainsString('useDashboard', $transactions);
        $this->assertStringContainsString('TransactionModal', $transactions);
        $this->assertStringContainsString('TransactionList', $transactions);
        $this->assertStringContainsString('transaction-row__content', $transactionList);
        $this->assertStringContainsString('transaction-row__actions', $transactionList);
        $this->assertStringContainsString('MoreVertical', $transactionList);
        $this->assertStringContainsString('DropdownItem', $transactionList);
        $this->assertStringContainsString('onRequestEdit', $transactionList);
        $this->assertStringContainsString('onRequestDelete', $transactionList);
        $this->assertStringContainsString('updateTransaction', $service);
        $this->assertStringContainsString('deleteTransaction', $service);
        $this->assertStringNotContainsString('Adicione ao seu livro.', $transactions);
        $this->assertStringNotContainsString('transactions-quick-add', $transactions);
        $this->assertStringContainsString('grid-cols-1', $transactions);
        $this->assertStringContainsString('MAX_DESCRIPTION_LENGTH = 60', $transactionList);
        $this->assertStringContainsString('description.slice(0, MAX_DESCRIPTION_LENGTH - 3)', $transactionList);
        $this->assertStringContainsString('title={transaction.description}', $transactionList);
    }

    public function test_daily_movement_forms_use_inertia_form_models_and_presentational_components_have_no_http(): void
    {
        $hook = file_get_contents(resource_path('js/Pages/Financial/Hooks/useDashboard.js'));
        $transferModal = file_get_contents(resource_path('js/Pages/Financial/Components/TransferModal.jsx'));
        $merchantModal = file_get_contents(resource_path('js/Pages/Financial/Components/MerchantModal.jsx'));
        $reversalModal = file_get_contents(resource_path('js/Pages/Financial/Components/ReversalConfirmModal.jsx'));

        $this->assertStringContainsString('useForm({ ...AccountTransfer })', $hook);
        $this->assertStringContainsString('useForm({ ...Merchant })', $hook);
        $this->assertStringContainsString('useForm({ ...TransactionReversal })', $hook);

        foreach ([$transferModal, $merchantModal, $reversalModal] as $component) {
            $this->assertStringNotContainsString('fetch(', $component);
            $this->assertStringNotContainsString('FinancialService', $component);
        }
    }

    public function test_card_movements_expose_scoped_edit_and_delete_actions(): void
    {
        $modal = file_get_contents(resource_path('js/Pages/Financial/Components/CreditCardTransactionsModal.jsx'));
        $page = file_get_contents(resource_path('js/Pages/Financial/CreditCards.jsx'));
        $hook = file_get_contents(resource_path('js/Pages/Financial/Hooks/useCreditCards.js'));
        $service = file_get_contents(resource_path('js/Services/FinancialService.js'));

        foreach (['Editar parcela', 'Editar todas', 'Excluir parcela', 'Excluir todas'] as $label) {
            $this->assertStringContainsString($label, $modal);
        }
        $this->assertStringContainsString('Total da competência', $modal);
        $this->assertStringContainsString('current_invoice_amount', $modal);
        foreach (['requestEditInstallment', 'requestEditPurchase', 'requestDeleteInstallment', 'requestDeletePurchase', 'loadCardTransactions(transactionsCard, transactionsFilters)'] as $contract) {
            $this->assertStringContainsString($contract, $hook);
        }
        foreach (['onEditInstallment={cards.requestEditInstallment}', 'onEditPurchase={cards.requestEditPurchase}', 'onDeleteInstallment={cards.requestDeleteInstallment}', 'onDeletePurchase={cards.requestDeletePurchase}'] as $prop) {
            $this->assertStringContainsString($prop, $page);
        }
        $this->assertStringContainsString('updateCreditCardInstallment', $service);
        $this->assertStringContainsString('updateCreditCardPurchase', $service);
        $this->assertStringContainsString('deleteCreditCardInstallment', $service);
        $this->assertStringContainsString('deleteCreditCardPurchase', $service);
    }

    public function test_account_movements_expose_transaction_edit_action(): void
    {
        $modal = file_get_contents(resource_path('js/Pages/Financial/Components/AccountTransactionsModal.jsx'));
        $page = file_get_contents(resource_path('js/Pages/Financial/Accounts.jsx'));
        $hook = file_get_contents(resource_path('js/Pages/Financial/Hooks/useWallets.js'));

        $this->assertStringContainsString('Editar', $modal);
        $this->assertStringContainsString('onEditTransaction={accounts.requestEditTransaction}', $page);
        $this->assertStringContainsString('requestEditTransaction', $hook);
        $this->assertStringContainsString('updateTransaction', $hook);
    }

    public function test_credit_card_page_exposes_previous_invoice_summary(): void
    {
        $page = file_get_contents(resource_path('js/Pages/Financial/CreditCards.jsx'));
        $resource = file_get_contents(app_path('Http/Resources/CreditCardResource.php'));

        $this->assertStringContainsString('Fatura anterior', $page);
        $this->assertStringContainsString('previous_invoice_amount', $page);
        $this->assertStringContainsString('previous_invoice_usage_percentage', $resource);
    }

    public function test_credit_card_page_exposes_invoice_payment_action(): void
    {
        $page = file_get_contents(resource_path('js/Pages/Financial/CreditCards.jsx'));

        $this->assertStringContainsString('shouldShowCreditCardInvoicePaymentAction', $page);
        $this->assertStringContainsString('Pagar fatura', $page);
        $this->assertStringContainsString('onClick={() => onPay(card)}', $page);
    }

    public function test_transaction_list_prefers_category_icon_and_falls_back_to_direction(): void
    {
        $list = file_get_contents(resource_path('js/Pages/Financial/Components/TransactionList.jsx'));
        $page = file_get_contents(resource_path('js/Pages/Financial/Transactions.jsx'));

        $this->assertStringContainsString('FINANCIAL_ICON_OPTIONS', $list);
        $this->assertStringContainsString('categories = []', $list);
        $this->assertStringContainsString('categories={transactions.categories}', $page);
        $this->assertStringContainsString('category?.icon', $list);
    }
}
