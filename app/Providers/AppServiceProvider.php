<?php

namespace App\Providers;

use App\Repositories\AccountRepository;
use App\Repositories\AccountRepositoryInterface;
use App\Repositories\BudgetRepository;
use App\Repositories\BudgetRepositoryInterface;
use App\Repositories\CategoryRepository;
use App\Repositories\CategoryRepositoryInterface;
use App\Repositories\CreditCardInvoiceRepository;
use App\Repositories\CreditCardInvoiceRepositoryInterface;
use App\Repositories\CreditCardPurchaseRepository;
use App\Repositories\CreditCardPurchaseRepositoryInterface;
use App\Repositories\CreditCardRepository;
use App\Repositories\CreditCardRepositoryInterface;
use App\Repositories\ExternalAccountRepository;
use App\Repositories\ExternalAccountRepositoryInterface;
use App\Repositories\ExternalInvestmentRepository;
use App\Repositories\ExternalInvestmentRepositoryInterface;
use App\Repositories\ExternalInvestmentTransactionRepository;
use App\Repositories\ExternalInvestmentTransactionRepositoryInterface;
use App\Repositories\ExternalTransactionRepository;
use App\Repositories\ExternalTransactionRepositoryInterface;
use App\Repositories\FinancialCommitmentRepository;
use App\Repositories\FinancialCommitmentRepositoryInterface;
use App\Repositories\FinancialConnectionRepository;
use App\Repositories\FinancialConnectionRepositoryInterface;
use App\Repositories\InstallmentRepository;
use App\Repositories\InstallmentRepositoryInterface;
use App\Repositories\InvestmentRepository;
use App\Repositories\InvestmentRepositoryInterface;
use App\Repositories\InvoicePaymentRepository;
use App\Repositories\InvoicePaymentRepositoryInterface;
use App\Repositories\MerchantRepository;
use App\Repositories\MerchantRepositoryInterface;
use App\Repositories\PlanningReportRepository;
use App\Repositories\PlanningReportRepositoryInterface;
use App\Repositories\PluggyItemRepository;
use App\Repositories\PluggyItemRepositoryInterface;
use App\Repositories\RecurringTransactionRepository;
use App\Repositories\RecurringTransactionRepositoryInterface;
use App\Repositories\Slice5Repository;
use App\Repositories\Slice5RepositoryInterface;
use App\Repositories\TransactionRepository;
use App\Repositories\TransactionRepositoryInterface;
use App\Repositories\UserRepository;
use App\Repositories\UserRepositoryInterface;
use App\Repositories\WalletRepository;
use App\Repositories\WalletRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(WalletRepositoryInterface::class, WalletRepository::class);
        $this->app->bind(AccountRepositoryInterface::class, AccountRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);
        $this->app->bind(TransactionRepositoryInterface::class, TransactionRepository::class);
        $this->app->bind(MerchantRepositoryInterface::class, MerchantRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(CreditCardRepositoryInterface::class, CreditCardRepository::class);
        $this->app->bind(CreditCardPurchaseRepositoryInterface::class, CreditCardPurchaseRepository::class);
        $this->app->bind(CreditCardInvoiceRepositoryInterface::class, CreditCardInvoiceRepository::class);
        $this->app->bind(InstallmentRepositoryInterface::class, InstallmentRepository::class);
        $this->app->bind(InvoicePaymentRepositoryInterface::class, InvoicePaymentRepository::class);
        $this->app->bind(RecurringTransactionRepositoryInterface::class, RecurringTransactionRepository::class);
        $this->app->bind(FinancialCommitmentRepositoryInterface::class, FinancialCommitmentRepository::class);
        $this->app->bind(BudgetRepositoryInterface::class, BudgetRepository::class);
        $this->app->bind(PlanningReportRepositoryInterface::class, PlanningReportRepository::class);
        $this->app->bind(Slice5RepositoryInterface::class, Slice5Repository::class);
        $this->app->bind(InvestmentRepositoryInterface::class, InvestmentRepository::class);
        $this->app->bind(PluggyItemRepositoryInterface::class, PluggyItemRepository::class);
        $this->app->bind(FinancialConnectionRepositoryInterface::class, FinancialConnectionRepository::class);
        $this->app->bind(ExternalAccountRepositoryInterface::class, ExternalAccountRepository::class);
        $this->app->bind(ExternalTransactionRepositoryInterface::class, ExternalTransactionRepository::class);
        $this->app->bind(ExternalInvestmentRepositoryInterface::class, ExternalInvestmentRepository::class);
        $this->app->bind(ExternalInvestmentTransactionRepositoryInterface::class, ExternalInvestmentTransactionRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {}
}
