import { Button } from 'flowbite-react';
import { BarChart3, RefreshCw } from 'lucide-react';

const money = (value) => (Number(value || 0) / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
const monthLabel = (month) => new Date(`${month}-01T12:00:00`).toLocaleDateString('pt-BR', { month: 'short', year: '2-digit' }).replace('.', '');
const typeLabels = { STOCK: 'Ação', FUND: 'Fundo', FII: 'FII', FIXED_INCOME: 'Renda fixa', CRYPTO: 'Cripto', OTHER: 'Outro' };

const insightSections = [
    ['spending_insights', 'Gastos'],
    ['savings_opportunities', 'Oportunidades de economia'],
    ['portfolio_observations', 'Carteira de investimentos'],
    ['risks', 'Riscos'],
    ['alerts', 'Alertas'],
];

const hasData = (facts) => facts?.months?.some((month) => month.transaction_count > 0) || Number(facts?.portfolio?.current_value_cents) > 0;

function Insights({ analysis }) {
    const sections = insightSections.filter(([key]) => analysis?.[key]?.length > 0);
    if (!analysis?.overview && sections.length === 0) return null;

    return <div className="grid gap-4">
        {analysis.overview && <p className="text-sm leading-6 text-orbital-text-primary">{analysis.overview}</p>}
        {sections.map(([key, title]) => <section key={key} aria-labelledby={`insight-${key}`}>
            <h4 id={`insight-${key}`} className="text-xs font-bold uppercase tracking-wider text-orbital-primary">{title}</h4>
            <ul className="mt-1.5 list-disc space-y-1 pl-5 text-sm leading-6 text-orbital-text-secondary">{analysis[key].map((item, index) => <li key={index}>{item}</li>)}</ul>
        </section>)}
    </div>;
}

function MonthsTable({ months }) {
    return <div className="overflow-x-auto" tabIndex={0} role="region" aria-label="Receitas e despesas por mês">
        <table className="w-full min-w-[22rem] text-left text-sm">
            <caption className="sr-only">Receitas, despesas e saldo dos últimos meses</caption>
            <thead><tr className="text-xs uppercase tracking-wider text-orbital-text-secondary"><th scope="col" className="py-2 pr-3 font-semibold">Mês</th><th scope="col" className="px-3 py-2 text-right font-semibold">Receitas</th><th scope="col" className="px-3 py-2 text-right font-semibold">Despesas</th><th scope="col" className="py-2 pl-3 text-right font-semibold">Saldo</th></tr></thead>
            <tbody>{months.map((month) => <tr key={month.month} className="border-t border-orbital-border"><th scope="row" className="py-2 pr-3 font-medium capitalize text-orbital-primary-dark">{monthLabel(month.month)}</th><td className="px-3 py-2 text-right tabular-nums">{money(month.income_cents)}</td><td className="px-3 py-2 text-right tabular-nums">{money(month.expense_cents)}</td><td className={`py-2 pl-3 text-right font-semibold tabular-nums ${month.net_cents < 0 ? 'text-red-600' : 'text-orbital-primary-dark'}`}>{money(month.net_cents)}</td></tr>)}</tbody>
        </table>
    </div>;
}

function Breakdown({ title, items, labelFor = (item) => item.name }) {
    if (!items?.length) return null;

    return <section aria-label={title}>
        <h4 className="text-xs font-bold uppercase tracking-wider text-orbital-primary">{title}</h4>
        <ul className="mt-2 grid gap-2.5">{items.slice(0, 5).map((item) => <li key={labelFor(item)}>
            <div className="flex items-baseline justify-between gap-3 text-sm"><span className="min-w-0 truncate text-orbital-text-primary">{labelFor(item)}</span><span className="shrink-0 tabular-nums text-orbital-text-secondary">{money(item.total_cents ?? item.current_value_cents)} · {item.share_percent}%</span></div>
            <div className="mt-1 h-1.5 overflow-hidden rounded-full bg-orbital-primary-light" aria-hidden="true"><div className="h-full rounded-full bg-orbital-primary" style={{ width: `${Math.min(100, item.share_percent)}%` }} /></div>
        </li>)}</ul>
    </section>;
}

export default function AssistantSummary({ summary, status, error, canLoad, notice, onRefresh }) {
    const generatedAt = summary?.generated_at ? new Date(summary.generated_at).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' }) : null;

    return <aside className="ledger-panel grid min-w-0 content-start gap-5" aria-labelledby="assistant-summary-title">
        <div className="flex items-start justify-between gap-3">
            <div><h2 id="assistant-summary-title" className="ledger-title flex items-center gap-2"><BarChart3 className="h-5 w-5 text-orbital-primary" aria-hidden="true" />Análise da carteira</h2>{generatedAt && <p className="mt-1 text-xs text-orbital-text-secondary">Atualizada em {generatedAt}</p>}</div>
            <Button color="light" size="xs" onClick={onRefresh} disabled={!canLoad || status === 'loading'} aria-label="Atualizar análise"><RefreshCw className="h-4 w-4" aria-hidden="true" /></Button>
        </div>
        <p className="sr-only" role="status">{notice}</p>
        {notice && <p className="rounded-lg bg-orbital-primary-light px-3 py-2 text-xs text-orbital-primary-dark" aria-hidden="true">{notice}</p>}

        {status === 'loading' && <p className="ledger-state" role="status">Analisando seus dados…</p>}
        {status === 'error' && <div className="ledger-error !mb-0" role="alert">{error}<Button color="light" size="xs" className="mt-3" onClick={onRefresh}>Tentar de novo</Button></div>}
        {status === 'idle' && <p className="ledger-state">Selecione uma carteira para ver a análise.</p>}
        {status === 'ready' && !hasData(summary.facts) && <div className="ledger-empty text-center" role="status"><p className="font-semibold text-orbital-primary-dark">Ainda não há dados para analisar.</p><p className="mt-1 text-sm text-orbital-text-secondary">Registre lançamentos ou investimentos nesta carteira para o assistente ter o que avaliar.</p></div>}
        {status === 'ready' && hasData(summary.facts) && <>
            <Insights analysis={summary.analysis} />
            <MonthsTable months={summary.facts.months} />
            <Breakdown title="Maiores gastos por categoria" items={summary.facts.top_expense_categories} />
            {Number(summary.facts.portfolio?.current_value_cents) > 0 && <>
                <p className="text-sm text-orbital-text-secondary">Carteira de investimentos: <strong className="text-orbital-primary-dark">{money(summary.facts.portfolio.current_value_cents)}</strong> ({summary.facts.portfolio.profit_percent >= 0 ? '+' : ''}{summary.facts.portfolio.profit_percent}%)</p>
                <Breakdown title="Alocação por tipo" items={summary.facts.portfolio.allocation_by_type} labelFor={(item) => typeLabels[item.type] ?? item.type} />
            </>}
        </>}
    </aside>;
}
