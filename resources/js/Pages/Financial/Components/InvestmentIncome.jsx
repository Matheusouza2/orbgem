import { Button } from 'flowbite-react';
import { CalendarDays, CircleDollarSign, Filter, Plus } from 'lucide-react';
import { Suspense } from 'react';
import Inputs from '@/Components/Inputs';
import { formatDateBR } from '@/Utils/date';

const money = (value) => (Number(value || 0) / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

export default function InvestmentIncome({ income, investments, filters, loading, onFilterChange, onApply, onCreateIncome }) {
    const total = income.reduce((sum, item) => sum + Number(item.amount || 0), 0);

    return <section className="ledger-panel mt-6 overflow-hidden">
        <div className="border-b border-orbital-border bg-gradient-to-r from-orbital-primary-light/60 to-white px-5 py-5">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div><p className="ledger-eyebrow">Fluxo recebido</p><h2 className="mt-1 flex items-center gap-2 text-xl font-bold text-orbital-primary-dark"><CircleDollarSign className="h-5 w-5 text-orbital-accent-dark" aria-hidden="true" />Rendimentos</h2><p className="mt-1 text-sm text-orbital-text-secondary">Proventos, juros e dividendos identificados nos investimentos.</p></div>
                <div className="flex flex-wrap items-start gap-4 text-left sm:text-right"><div><p className="text-xs font-semibold uppercase tracking-wide text-orbital-text-secondary">Total no período</p><p className="mt-1 text-2xl font-bold text-orbital-primary-dark">{money(total)}</p></div><Button color="blue" size="sm" onClick={onCreateIncome} disabled={investments.length === 0}><Plus className="mr-2 h-4 w-4" aria-hidden="true" />Lançar rendimento</Button></div>
            </div>
            <Suspense fallback={<div className="mt-4 h-10" aria-hidden="true" />}>
                <div className="mt-5 grid gap-3 sm:grid-cols-[1.4fr_1fr_1fr_auto] sm:items-end">
                    <Inputs.Select name="income_investment_id" label="Investimento" value={investments.map((item) => ({ value: item.id, label: item.ticker || item.name })).find((option) => String(option.value) === String(filters.investment_id)) ?? null} onChange={(option) => onFilterChange('investment_id', option?.value ?? '')} options={investments.map((item) => ({ value: item.id, label: item.ticker || item.name }))} />
                    <Inputs.Flatpickr name="income_from" label="De" value={filters.from} onChange={(_, value) => onFilterChange('from', value)} clearable />
                    <Inputs.Flatpickr name="income_to" label="Até" value={filters.to} onChange={(_, value) => onFilterChange('to', value)} clearable />
                    <Button color="blue" onClick={onApply} disabled={loading}><Filter className="mr-2 h-4 w-4" aria-hidden="true" />Filtrar</Button>
                </div>
            </Suspense>
        </div>
        {loading ? <div className="ledger-state p-6" role="status">Carregando rendimentos…</div> : income.length === 0 ? <div className="ledger-empty p-8"><CalendarDays className="mx-auto h-8 w-8 text-orbital-primary" aria-hidden="true" /><p className="mt-3 font-semibold text-orbital-primary-dark">Nenhum rendimento encontrado.</p><p className="mt-1 text-sm text-orbital-text-secondary">Sincronize seus investimentos no Open Finance para importar proventos.</p></div> : <div className="divide-y divide-orbital-border">{income.map((item) => <div key={item.id} className="flex flex-wrap items-center justify-between gap-3 px-5 py-4"><div className="flex min-w-0 items-center gap-3"><span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700"><CircleDollarSign className="h-4 w-4" aria-hidden="true" /></span><div className="min-w-0"><p className="truncate font-semibold text-orbital-text-primary">{item.ticker || item.investment_name}</p><p className="truncate text-xs text-orbital-text-secondary">{item.description}</p></div></div><div className="text-right"><p className="font-bold text-emerald-700">+ {money(item.amount)}</p><p className="text-xs text-orbital-text-secondary">{formatDateBR(item.transaction_date)}</p></div></div>)}</div>}
    </section>;
}
