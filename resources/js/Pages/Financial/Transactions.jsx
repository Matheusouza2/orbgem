import { ChevronLeft, ChevronRight, Plus, Search, SlidersHorizontal, X } from 'lucide-react';
import { Suspense, useMemo, useState } from 'react';
import Inputs from '@/Components/Inputs';
import AppLayout from '@/Layouts/AppLayout';
import MerchantModal from './Components/MerchantModal';
import ReversalConfirmModal from './Components/ReversalConfirmModal';
import TransactionModal from './Components/TransactionModal';
import TransactionList from './Components/TransactionList';
import useDashboard from './Hooks/useDashboard';

const statusOptions = [{ value: 'ACTIVE', label: 'Todas' }, { value: 'PROJECTED', label: 'Previstas' }, { value: 'POSTED', label: 'Efetivadas' }];
const money = (cents = 0) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100);
const shiftMonth = (month, offset) => {
    const date = new Date(`${month}-01T12:00:00`);
    date.setMonth(date.getMonth() + offset);

    return date.toISOString().slice(0, 7);
};
const monthLabel = (month) => {
    const label = new Date(`${month}-01T12:00:00`).toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });

    return label.charAt(0).toUpperCase() + label.slice(1);
};

export default function Transactions() {
    const transactions = useDashboard();
    const [transactionModalOpen, setTransactionModalOpen] = useState(false);
    const [filtersOpen, setFiltersOpen] = useState(false);
    const [query, setQuery] = useState('');

    const walletOptions = transactions.wallets.map((wallet) => ({ value: String(wallet.id), label: wallet.name }));
    const accountOptions = transactions.accounts.map((account) => ({ value: String(account.id), label: account.name }));
    const walletName = walletOptions.find((wallet) => wallet.value === String(transactions.selectedWalletId))?.label;
    const accountName = accountOptions.find((account) => account.value === String(transactions.selectedAccountId))?.label;

    const postedIncome = Number(transactions.summary.actual_income || 0);
    const postedExpenses = Number(transactions.summary.actual_expenses || 0);
    const projectedIncome = Number(transactions.summary.forecast_income || 0) - postedIncome;
    const projectedExpenses = Number(transactions.summary.forecast_expenses || 0) - postedExpenses;
    const totals = transactions.transactionStatus === 'POSTED'
        ? { income: postedIncome, expenses: postedExpenses, label: 'efetivadas' }
        : transactions.transactionStatus === 'PROJECTED'
            ? { income: projectedIncome, expenses: projectedExpenses, label: 'previstas' }
            : { income: postedIncome + projectedIncome, expenses: postedExpenses + projectedExpenses, label: 'previstas e efetivadas' };
    const result = totals.income - totals.expenses;
    const flow = totals.income + totals.expenses;
    const incomeShare = flow > 0 ? Math.round((totals.income / flow) * 100) : 50;

    const visibleTransactions = useMemo(() => {
        const term = query.trim().toLowerCase();

        return term ? transactions.transactions.filter((transaction) => String(transaction.description ?? '').toLowerCase().includes(term)) : transactions.transactions;
    }, [transactions.transactions, query]);

    const activeFilters = [accountName && 'conta', transactions.includeThirdParty && 'terceiros'].filter(Boolean).length;
    const hasListFilters = Boolean(query.trim()) || transactions.transactionStatus !== 'ACTIVE' || Boolean(transactions.selectedAccountId);
    const clearFilters = () => { setQuery(''); transactions.setTransactionStatus('ACTIVE'); transactions.selectAccount(''); };

    return <AppLayout>
        <div className="tx-page">
            <header className="tx-header">
                <div>
                    <h1 className="tx-title">Transações</h1>
                    <p className="tx-subtitle">{walletName ? <>Carteira <strong>{walletName}</strong>{accountName && <> · conta <strong>{accountName}</strong></>}</> : 'Seus lançamentos, dia a dia.'}</p>
                </div>
                <div className="tx-header__actions">
                    <div className="tx-month" role="group" aria-label="Competência">
                        <button type="button" className="orbital-btn" onClick={() => transactions.setMonth(shiftMonth(transactions.month, -1))} aria-label="Mês anterior"><ChevronLeft className="h-5 w-5" aria-hidden="true" /></button>
                        <span aria-live="polite">{monthLabel(transactions.month)}</span>
                        <button type="button" className="orbital-btn" onClick={() => transactions.setMonth(shiftMonth(transactions.month, 1))} aria-label="Próximo mês"><ChevronRight className="h-5 w-5" aria-hidden="true" /></button>
                    </div>
                    <button type="button" className="tx-cta orbital-btn" onClick={() => setTransactionModalOpen(true)} disabled={!transactions.selectedWalletId}><Plus className="h-4 w-4" aria-hidden="true" />Nova transação</button>
                </div>
            </header>

            {transactions.error && <div className="ledger-error" role="alert">{transactions.error}</div>}
            {!transactions.selectedWalletId && !transactions.loading && <div className="ledger-panel mb-5" role="status">Você ainda não possui uma carteira disponível.</div>}

            {transactions.selectedWalletId && <>
                <section className="tx-summary" aria-label="Resumo do mês">
                    <div className="tx-summary__balance">
                        <p>Saldo do mês</p>
                        <strong className="tx-summary__figure">{money(transactions.summary.balance)}</strong>
                    </div>
                    <dl className="tx-summary__flows">
                        <div><dt>Entradas</dt><dd className="is-in">{money(totals.income)}</dd></div>
                        <div><dt>Despesas</dt><dd className="is-out">{money(totals.expenses)}</dd></div>
                        <div><dt>Resultado</dt><dd className={result >= 0 ? 'is-in' : 'is-out'}>{result < 0 ? '−' : ''}{money(Math.abs(result))}</dd></div>
                    </dl>
                    <div className="tx-summary__bar" role="img" aria-label={`Entradas ${incomeShare}% e despesas ${100 - incomeShare}% do movimento ${totals.label}`}>
                        <span className="is-in" style={{ width: `${incomeShare}%` }} /><span className="is-out" style={{ width: `${100 - incomeShare}%` }} />
                    </div>
                    <p className="tx-summary__caption">Movimento {totals.label}</p>
                </section>

                <section className="tx-controls" aria-label="Filtros de transações">
                    <div className="tx-controls__row">
                        <div className="tx-segment" role="radiogroup" aria-label="Status">
                            {statusOptions.map((option) => <button key={option.value} type="button" role="radio" aria-checked={transactions.transactionStatus === option.value} data-active={transactions.transactionStatus === option.value ? 'true' : undefined} className="orbital-btn" onClick={() => transactions.setTransactionStatus(option.value)} disabled={transactions.loading}>{option.label}</button>)}
                        </div>
                        <label className="tx-search">
                            <Search className="h-4 w-4" aria-hidden="true" />
                            <span className="sr-only">Buscar por descrição</span>
                            <input type="search" value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Buscar por descrição" autoComplete="off" />
                            {query && <button type="button" onClick={() => setQuery('')} aria-label="Limpar busca"><X className="h-4 w-4" aria-hidden="true" /></button>}
                        </label>
                        <button type="button" className="tx-filter-toggle orbital-btn" aria-expanded={filtersOpen} aria-controls="tx-more-filters" onClick={() => setFiltersOpen((open) => !open)}>
                            <SlidersHorizontal className="h-4 w-4" aria-hidden="true" />Filtros{activeFilters > 0 && <b>{activeFilters}</b>}
                        </button>
                    </div>
                    <div id="tx-more-filters" className="tx-controls__more" data-open={filtersOpen ? 'true' : 'false'}>
                        <div>
                            <Suspense fallback={<div className="h-[74px]" aria-hidden="true" />}>
                                <div className="tx-controls__grid">
                                    <Inputs.Select name="wallet_id" label="Carteira" value={walletOptions.find((wallet) => wallet.value === String(transactions.selectedWalletId)) ?? null} onChange={(option) => transactions.selectWallet(option?.value ?? '')} options={walletOptions} isClearable={false} isDisabled={transactions.loading} />
                                    <Inputs.Select name="account_id" label="Conta" value={accountOptions.find((account) => account.value === String(transactions.selectedAccountId)) ?? null} onChange={(option) => transactions.selectAccount(option?.value ?? '')} options={accountOptions} isClearable isDisabled={!transactions.selectedWalletId || transactions.loading} />
                                    <Inputs.Flatpickr name="month" label="Ir para o mês" value={transactions.month} onChange={(_, dateString) => dateString && transactions.setMonth(dateString)} monthYearOnly />
                                    <Inputs.Checkbox name="include_third_party" label="Incluir despesas de terceiros" value={transactions.includeThirdParty} onChange={transactions.setIncludeThirdParty} />
                                </div>
                            </Suspense>
                        </div>
                    </div>
                </section>

                <p className="tx-count" aria-live="polite">{transactions.loading ? 'Atualizando…' : `${visibleTransactions.length} ${visibleTransactions.length === 1 ? 'lançamento' : 'lançamentos'}${query.trim() ? ` para “${query.trim()}”` : ''}`}</p>

                <TransactionList transactions={visibleTransactions} categories={transactions.categories} loading={transactions.loading} hasFilters={hasListFilters} onClearFilters={clearFilters} onCreate={() => setTransactionModalOpen(true)} onRequestReversal={transactions.requestReversal} onRequestEdit={transactions.requestEdit} onRequestDelete={transactions.requestDelete} onEffectivate={transactions.effectivateTransaction} />
            </>}
        </div>

        <MerchantModal open={transactions.merchantOpen} onClose={() => transactions.setMerchantOpen(false)} form={transactions.merchantForm} onSubmit={transactions.submitMerchant} submitting={transactions.action === 'merchant'} errors={transactions.merchantErrors} />
        <TransactionModal open={transactionModalOpen || Boolean(transactions.editingTransaction)} onClose={() => { setTransactionModalOpen(false); transactions.setEditingTransaction(null); }} form={transactions.form} accounts={transactions.accounts} creditCards={transactions.creditCards} categories={transactions.categories} merchants={transactions.merchants} onSubmit={async (event) => { const requestError = await transactions.submitTransaction(event); if (!requestError) setTransactionModalOpen(false); }} onSubmitAndContinue={(event) => transactions.submitTransaction(event, { keepOpen: true })} onCreateMerchant={() => transactions.setMerchantOpen(true)} submitting={transactions.submitting} apiErrors={transactions.apiErrors} editing={transactions.editingTransaction} />
        <ReversalConfirmModal transaction={transactions.reversalTransaction} open={Boolean(transactions.reversalTransaction)} onClose={() => transactions.setReversalTransaction(null)} form={transactions.reversalForm} onSubmit={transactions.confirmReversal} submitting={transactions.action === 'reversal'} errors={transactions.reversalErrors} />
    </AppLayout>;
}
