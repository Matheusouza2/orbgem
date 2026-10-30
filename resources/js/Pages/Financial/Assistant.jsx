import { Sparkles } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import AssistantChat from './Components/AssistantChat';
import AssistantSummary from './Components/AssistantSummary';
import useAssistant from './Hooks/useAssistant';

export default function Assistant() {
    const assistant = useAssistant();
    const hasWallet = assistant.selectedWalletId !== '';

    return <AppLayout>
        <header className="ledger-header"><div><p className="ledger-eyebrow">Inteligência sobre o seu dinheiro</p><h1 className="ledger-heading">Assistente financeiro.</h1><p className="mt-3 max-w-xl text-base leading-7 text-orbital-text-secondary">Tire dúvidas sobre gastos e investimentos com base na análise da sua carteira.</p></div></header>
        <div className="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-orbital-border bg-orbital-surface px-4 py-3 shadow-sm"><label className="text-sm font-semibold text-orbital-primary-dark" htmlFor="assistant-wallet">Carteira</label><select id="assistant-wallet" value={assistant.selectedWalletId} onChange={(event) => assistant.selectWallet(event.target.value)} disabled={assistant.walletsLoading} className="min-w-48 rounded-lg border-orbital-border bg-orbital-surface px-3 py-2 text-sm text-orbital-text-primary focus:border-orbital-primary focus:ring-orbital-primary"><option value="">Selecione uma carteira</option>{assistant.wallets.map((wallet) => <option key={wallet.id} value={wallet.id}>{wallet.name}</option>)}</select></div>

        {assistant.walletsLoading
            ? <div className="ledger-panel ledger-state" role="status">Carregando carteiras…</div>
            : !hasWallet && assistant.wallets.length === 0
                ? <div className="ledger-panel ledger-empty text-center" role="status"><Sparkles className="mx-auto h-8 w-8 text-orbital-primary" aria-hidden="true" /><p className="mt-3 font-semibold text-orbital-primary-dark">Crie uma carteira para começar.</p></div>
                : <div className="grid items-start gap-4 lg:grid-cols-[minmax(0,1.4fr)_minmax(18rem,.8fr)]">
                    <AssistantChat messages={assistant.messages} draft={assistant.draft} onDraftChange={assistant.setDraft} onSend={assistant.send} sending={assistant.sending} disabled={!hasWallet} />
                    <AssistantSummary summary={assistant.summary} status={assistant.summaryStatus} error={assistant.summaryError} canLoad={hasWallet} notice={assistant.refreshNotice} onRefresh={assistant.requestRefresh} />
                </div>}
    </AppLayout>;
}
