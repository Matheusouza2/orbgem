import { Button } from 'flowbite-react';
import { Plus, Sparkles } from 'lucide-react';
import { Suspense } from 'react';
import { useState } from 'react';
import Inputs from '@/Components/Inputs';
import AppLayout from '@/Layouts/AppLayout';
import MerchantModal from './Components/MerchantModal';
import ReversalConfirmModal from './Components/ReversalConfirmModal';
import TransactionModal from './Components/TransactionModal';
import TransactionList from './Components/TransactionList';
import useDashboard from './Hooks/useDashboard';

export default function Transactions() {
    const transactions = useDashboard();
    const [transactionModalOpen, setTransactionModalOpen] = useState(false);
    const walletOptions = transactions.wallets.map((wallet) => ({ value: String(wallet.id), label: wallet.name }));
    const accountOptions = transactions.accounts.map((account) => ({ value: String(account.id), label: account.name }));
    const money = (cents = 0) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100);

    return <AppLayout>
        <div className="transactions-page">
            <header className="transactions-hero">
                <div className="relative z-10"><div className="mb-5 flex items-center gap-2 text-orbital-accent"><Sparkles className="h-4 w-4" aria-hidden="true" /><p className="text-[10px] font-bold uppercase tracking-[.24em]">Pulso financeiro</p></div><h1 className="transactions-hero__title">O que aconteceu<br /><span>com seu dinheiro?</span></h1><p className="mt-5 max-w-lg text-sm leading-6 text-white/65">Uma leitura limpa dos seus movimentos, organizada por carteira e competência.</p></div>
                <div className="transactions-hero__balance"><span>Saldo na competência</span><strong>{money(transactions.summary.balance)}</strong><small>{transactions.month}</small></div>
            </header>

            <section className="transactions-toolbar" aria-label="Filtros de transações">
                <div className="flex items-center gap-2"><span className="h-2 w-2 rounded-full bg-orbital-accent" aria-hidden="true" /><span className="text-sm font-semibold text-orbital-primary-dark">Explorar lançamentos</span></div>
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
                <Suspense fallback={<div className="h-[74px] min-w-52" aria-hidden="true" />}>
                    <Inputs.Select name="wallet_id" label="Carteira" value={walletOptions.find((wallet) => wallet.value === String(transactions.selectedWalletId)) ?? null} onChange={(option) => transactions.selectWallet(option?.value ?? '')} options={walletOptions} isClearable={false} isDisabled={transactions.loading} className="min-w-52" />
                    <Inputs.Select name="account_id" label="Conta" value={accountOptions.find((account) => account.value === String(transactions.selectedAccountId)) ?? null} onChange={(option) => transactions.selectAccount(option?.value ?? '')} options={accountOptions} isClearable className="min-w-52" isDisabled={!transactions.selectedWalletId || transactions.loading} />
                    <Inputs.Flatpickr name="month" label="Competência" value={transactions.month} onChange={(_, dateString) => dateString && transactions.setMonth(dateString)} monthYearOnly className="min-w-40" />
                </Suspense>
                    <Button color="blue" onClick={() => setTransactionModalOpen(true)} disabled={!transactions.selectedWalletId}><Plus className="mr-2 h-4 w-4" aria-hidden="true" />Nova transação</Button>
                </div>
            </section>

            {transactions.error && <div className="ledger-error" role="alert">{transactions.error}</div>}
            {!transactions.selectedWalletId && !transactions.loading && <div className="ledger-panel mb-5" role="status">Você ainda não possui uma carteira disponível.</div>}
            {transactions.selectedWalletId && <>
                <div className="transactions-stats"><div><span>Entradas realizadas</span><strong className="text-emerald-700">{money(transactions.summary.actual_income)}</strong><small>neste mês</small></div><div><span>Despesas realizadas</span><strong className="text-rose-700">{money(transactions.summary.actual_expenses)}</strong><small>neste mês</small></div><div><span>Despesas previstas</span><strong className="text-orbital-primary">{money(transactions.summary.forecast_expenses)}</strong><small>planejado</small></div></div>
                <div className="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(20rem,26rem)]">
                    <TransactionList transactions={transactions.transactions} loading={transactions.loading} onRequestReversal={transactions.requestReversal} />
                    <div className="transactions-quick-add"><div className="transactions-quick-add__mark"><Plus className="h-5 w-5" aria-hidden="true" /></div><div><p className="ledger-eyebrow">Movimento novo?</p><h2 className="text-lg font-bold text-orbital-primary-dark">Adicione ao seu livro.</h2><p className="mt-1 text-sm leading-6 text-orbital-text-secondary">Entradas e saídas ficam organizadas no mesmo lugar.</p></div><Button color="blue" className="mt-4 w-full" onClick={() => setTransactionModalOpen(true)}>Abrir formulário</Button></div>
                </div>
            </>}
        </div>

        <MerchantModal open={transactions.merchantOpen} onClose={() => transactions.setMerchantOpen(false)} form={transactions.merchantForm} onSubmit={transactions.submitMerchant} submitting={transactions.action === 'merchant'} errors={transactions.merchantErrors} />
        <TransactionModal open={transactionModalOpen} onClose={() => setTransactionModalOpen(false)} form={transactions.form} accounts={transactions.accounts} categories={transactions.categories} merchants={transactions.merchants} onSubmit={async (event) => { const requestError = await transactions.submitTransaction(event); if (!requestError) setTransactionModalOpen(false); }} onCreateMerchant={() => transactions.setMerchantOpen(true)} submitting={transactions.submitting} apiErrors={transactions.apiErrors} />
        <ReversalConfirmModal transaction={transactions.reversalTransaction} open={Boolean(transactions.reversalTransaction)} onClose={() => transactions.setReversalTransaction(null)} form={transactions.reversalForm} onSubmit={transactions.confirmReversal} submitting={transactions.action === 'reversal'} errors={transactions.reversalErrors} />
    </AppLayout>;
}
