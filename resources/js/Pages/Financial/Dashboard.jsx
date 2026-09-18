import { Suspense } from 'react';
import { useState } from 'react';
import Inputs from '@/Components/Inputs';
import AppLayout from '@/Layouts/AppLayout';
import DashboardOverview from './Components/DashboardOverview';
import MerchantModal from './Components/MerchantModal';
import ReversalConfirmModal from './Components/ReversalConfirmModal';
import TransactionModal from './Components/TransactionModal';
import TransferModal from './Components/TransferModal';
import useDashboard from './Hooks/useDashboard';

export default function Dashboard() {
    const dashboard = useDashboard();
    const [transactionModalOpen, setTransactionModalOpen] = useState(false);

    return <AppLayout>
        <header className="dashboard-header">
            <div>
                <p className="ledger-eyebrow">Visão geral</p>
                <h1 className="ledger-heading">Seu centro de decisão.</h1>
                <p className="mt-3 max-w-xl text-base leading-7 text-orbital-text-secondary">O essencial da sua carteira para acompanhar o presente e escolher o próximo passo.</p>
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
            <DashboardOverview summary={dashboard.summary} previousSummary={dashboard.previousSummary} nextSummary={dashboard.nextSummary} month={dashboard.month} transactions={dashboard.transactions} categories={dashboard.categories} accounts={dashboard.accounts} creditCards={dashboard.creditCards} onNewTransaction={() => setTransactionModalOpen(true)} onTransfer={() => dashboard.setTransferOpen(true)} />
        </>}

        <TransactionModal open={transactionModalOpen} onClose={() => setTransactionModalOpen(false)} form={dashboard.form} accounts={dashboard.accounts} creditCards={dashboard.creditCards} categories={dashboard.categories} merchants={dashboard.merchants} onSubmit={async (event) => { const requestError = await dashboard.submitTransaction(event); if (!requestError) setTransactionModalOpen(false); }} onSubmitAndContinue={(event) => dashboard.submitTransaction(event, { keepOpen: true })} onCreateMerchant={() => dashboard.setMerchantOpen(true)} submitting={dashboard.submitting} apiErrors={dashboard.apiErrors} />
        <TransferModal open={dashboard.transferOpen} onClose={() => dashboard.setTransferOpen(false)} form={dashboard.transferForm} accounts={dashboard.accounts} onSubmit={dashboard.submitTransfer} submitting={dashboard.action === 'transfer'} errors={dashboard.transferErrors} />
        <MerchantModal open={dashboard.merchantOpen} onClose={() => dashboard.setMerchantOpen(false)} form={dashboard.merchantForm} onSubmit={dashboard.submitMerchant} submitting={dashboard.action === 'merchant'} errors={dashboard.merchantErrors} />
        <ReversalConfirmModal transaction={dashboard.reversalTransaction} open={Boolean(dashboard.reversalTransaction)} onClose={() => dashboard.setReversalTransaction(null)} form={dashboard.reversalForm} onSubmit={dashboard.confirmReversal} submitting={dashboard.action === 'reversal'} errors={dashboard.reversalErrors} />
    </AppLayout>;
}
