import { Suspense } from 'react';
import Inputs from '@/Components/Inputs';
import BalanceSummary from './Components/BalanceSummary';
import AppLayout from '@/Layouts/AppLayout';
import MerchantModal from './Components/MerchantModal';
import MovementActions from './Components/MovementActions';
import ReversalConfirmModal from './Components/ReversalConfirmModal';
import TransactionForm from './Components/TransactionForm';
import TransactionList from './Components/TransactionList';
import TransferModal from './Components/TransferModal';
import useDashboard from './Hooks/useDashboard';

export default function Dashboard() {
    const dashboard = useDashboard();

    return <AppLayout>
        <header className="ledger-header">
            <div>
                <p className="ledger-eyebrow">Visão geral</p>
                <h1 className="ledger-heading">Seu dinheiro em foco.</h1>
                <p className="mt-3 max-w-xl text-base leading-7 text-orbital-text-secondary">Veja o movimento da sua carteira e registre o que mudou hoje.</p>
            </div>
            <div className="ledger-filters" aria-label="Filtros financeiros">
                <Suspense fallback={<div className="h-[74px] flex-1" aria-hidden="true" />}>
                    <Inputs.Select name="wallet_id" label="Carteira" value={dashboard.wallets.map((wallet) => ({ value: String(wallet.id), label: wallet.name })).find((wallet) => wallet.value === String(dashboard.selectedWalletId)) ?? null} onChange={(option) => dashboard.selectWallet(option?.value ?? '')} options={dashboard.wallets.map((wallet) => ({ value: String(wallet.id), label: wallet.name }))} isClearable={false} isDisabled={dashboard.loading} className="flex-1" />
                    <Inputs.Flatpickr name="month" label="Competência" value={dashboard.month} onChange={(_, dateString) => dateString && dashboard.setMonth(dateString)} monthYearOnly className="flex-1" />
                </Suspense>
            </div>
        </header>

        {dashboard.error && <div className="ledger-error" role="alert">{dashboard.error}</div>}
        {!dashboard.selectedWalletId && !dashboard.loading && <div className="ledger-panel mb-5" role="status">Você ainda não possui uma carteira disponível.</div>}

        {dashboard.selectedWalletId && <>
            <div className="ledger-grid">
                <BalanceSummary summary={dashboard.summary} month={dashboard.month} />
                <TransactionForm form={dashboard.form} accounts={dashboard.accounts} categories={dashboard.categories} merchants={dashboard.merchants} onSubmit={dashboard.submitTransaction} onCreateMerchant={() => dashboard.setMerchantOpen(true)} submitting={dashboard.submitting} apiErrors={dashboard.apiErrors} />
            </div>
            <MovementActions onTransfer={() => dashboard.setTransferOpen(true)} />
            <div className="mt-5"><TransactionList transactions={dashboard.transactions} loading={dashboard.loading} onRequestReversal={dashboard.requestReversal} /></div>
        </>}

        <TransferModal open={dashboard.transferOpen} onClose={() => dashboard.setTransferOpen(false)} form={dashboard.transferForm} accounts={dashboard.accounts} onSubmit={dashboard.submitTransfer} submitting={dashboard.action === 'transfer'} errors={dashboard.transferErrors} />
        <MerchantModal open={dashboard.merchantOpen} onClose={() => dashboard.setMerchantOpen(false)} form={dashboard.merchantForm} onSubmit={dashboard.submitMerchant} submitting={dashboard.action === 'merchant'} errors={dashboard.merchantErrors} />
        <ReversalConfirmModal transaction={dashboard.reversalTransaction} open={Boolean(dashboard.reversalTransaction)} onClose={() => dashboard.setReversalTransaction(null)} form={dashboard.reversalForm} onSubmit={dashboard.confirmReversal} submitting={dashboard.action === 'reversal'} errors={dashboard.reversalErrors} />
    </AppLayout>;
}
