import { useEffect, useMemo, useState } from 'react';
import { Button, Modal, ModalBody, ModalFooter, ModalHeader } from 'flowbite-react';
import { CalendarDays, CircleDollarSign, TrendingUp } from 'lucide-react';
import FinancialService from '@/Services/FinancialService';

const money = (value) => (Number(value || 0) / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
const monthKey = (date) => String(date || '').slice(0, 7);
const monthLabel = (key) => {
    const [year, month] = key.split('-').map(Number);
    return new Intl.DateTimeFormat('pt-BR', { month: 'short', year: '2-digit' }).format(new Date(year, month - 1, 15)).replace('.', '');
};

export default function InvestmentTickerIncomeModal({ open, onClose, walletId, investments }) {
    const [investmentId, setInvestmentId] = useState('');
    const [income, setIncome] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const selectedInvestment = investments.find((item) => String(item.id) === String(investmentId));

    useEffect(() => {
        if (open && investments.length > 0 && !investments.some((investment) => String(investment.id) === String(investmentId))) {
            setInvestmentId(String(investments[0].id));
        }
    }, [open, investmentId, investments]);

    useEffect(() => {
        if (!open || !walletId || !investmentId) return undefined;

        const controller = new AbortController();
        setLoading(true);
        setError('');
        setIncome([]);
        FinancialService.listInvestmentIncome(walletId, { investment_id: investmentId }, { signal: controller.signal })
            .then(setIncome)
            .catch((requestError) => {
                if (requestError?.name !== 'AbortError') {
                    setIncome([]);
                    setError(requestError?.message || 'Não foi possível carregar os rendimentos deste ticker.');
                }
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoading(false);
            });

        return () => controller.abort();
    }, [open, walletId, investmentId]);

    const monthlyIncome = useMemo(() => {
        const totals = income.reduce((result, item) => {
            const key = monthKey(item.transaction_date);
            if (key) result[key] = (result[key] || 0) + Number(item.amount || 0);
            return result;
        }, {});

        return Object.entries(totals).sort(([first], [second]) => first.localeCompare(second));
    }, [income]);
    const total = income.reduce((sum, item) => sum + Number(item.amount || 0), 0);
    const max = Math.max(0, ...monthlyIncome.map(([, amount]) => amount));

    return <Modal show={open} onClose={onClose} dismissible={!loading} size="4xl" className="wallet-modal">
        <ModalHeader className="wallet-modal__header"><span className="wallet-modal__mark"><TrendingUp className="h-5 w-5" aria-hidden="true" /></span><span className="wallet-modal__heading"><span className="wallet-modal__eyebrow">Carteira</span><span className="wallet-modal__title">Rendimentos por ticker</span></span></ModalHeader>
        <ModalBody className="wallet-modal__body">
            <div className="mb-5 grid gap-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
                <label htmlFor="ticker-income-investment" className="text-sm font-semibold text-orbital-text-primary">Ticker<select id="ticker-income-investment" value={investmentId} onChange={(event) => setInvestmentId(event.target.value)} className="mt-1 block w-full rounded-lg border-orbital-border bg-orbital-surface px-3 py-2 text-sm focus:border-orbital-primary focus:ring-orbital-primary">
                    {investments.map((investment) => <option key={investment.id} value={investment.id}>{investment.ticker || investment.name}{investment.ticker && investment.name ? ` · ${investment.name}` : ''}</option>)}
                </select></label>
                <div className="rounded-xl bg-orbital-primary-light px-4 py-3 sm:min-w-48 sm:text-right"><p className="text-xs font-semibold uppercase tracking-wide text-orbital-text-secondary">Total recebido</p><p className="mt-1 text-xl font-bold text-orbital-primary-dark">{money(total)}</p></div>
            </div>
            <p className="mb-4 text-sm text-orbital-text-secondary">{selectedInvestment?.ticker || selectedInvestment?.name || 'Ticker'} · rendimentos agrupados por mês</p>
            {loading ? <div className="ledger-state py-10" role="status">Carregando histórico de rendimentos…</div> : error ? <div className="ledger-error" role="alert">{error}</div> : monthlyIncome.length === 0 ? <div className="ledger-empty py-10"><CalendarDays className="mx-auto h-8 w-8 text-orbital-primary" aria-hidden="true" /><p className="mt-3 font-semibold text-orbital-primary-dark">Nenhum rendimento registrado para este ticker.</p><p className="mt-1 text-sm text-orbital-text-secondary">Os valores aparecerão aqui quando houver proventos ou dividendos.</p></div> : <>
                <div className="mb-5 flex items-center gap-2 rounded-xl border border-orbital-border px-4 py-3"><CircleDollarSign className="h-5 w-5 text-emerald-700" aria-hidden="true" /><span className="text-sm text-orbital-text-secondary">{income.length} {income.length === 1 ? 'rendimento recebido' : 'rendimentos recebidos'}</span></div>
                <div className="overflow-x-auto rounded-xl border border-orbital-border p-4">
                    <div className="flex min-h-64 min-w-max items-end gap-3" role="list" aria-label={`Rendimentos mensais de ${selectedInvestment?.ticker || selectedInvestment?.name}`}>
                        {monthlyIncome.map(([month, amount]) => <div key={month} className="flex h-64 w-20 flex-col items-center justify-end gap-2" role="listitem" aria-label={`${monthLabel(month)}: ${money(amount)}`}>
                            <span className="text-center text-xs font-semibold text-orbital-primary-dark">{money(amount)}</span>
                            <div className="flex h-40 w-full items-end rounded-lg bg-orbital-background"><span className="w-full rounded-lg bg-orbital-primary transition-[height]" style={{ height: `${Math.max(5, (amount / max) * 100)}%` }} title={`${monthLabel(month)}: ${money(amount)}`} /></div>
                            <span className="text-xs text-orbital-text-secondary">{monthLabel(month)}</span>
                        </div>)}
                    </div>
                </div>
            </>}
        </ModalBody>
        <ModalFooter className="wallet-modal__footer"><Button color="light" className="wallet-modal__cancel" onClick={onClose}>Fechar</Button></ModalFooter>
    </Modal>;
}
