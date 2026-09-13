import { Button } from 'flowbite-react';
import { ArrowDownLeft, ArrowUpRight, MoveRight, Plus, Sparkles, TrendingDown, TrendingUp } from 'lucide-react';
import DashboardInsights from './DashboardInsights';

const money = (cents = 0) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100);
const monthLabel = (month) => month ? new Intl.DateTimeFormat('pt-BR', { month: 'short' }).format(new Date(`${month}-01T12:00:00`)).replace('.', '') : 'Sem dados';
const shiftMonth = (month, offset) => {
    const date = new Date(`${month}-01T12:00:00`);
    date.setMonth(date.getMonth() + offset);

    return date.toISOString().slice(0, 7);
};

function ExpenseBar({ label, amount, max, tone }) {
    const width = max > 0 ? Math.max(amount > 0 ? 4 : 0, Math.min(100, (amount / max) * 100)) : 0;

    return <div className="dashboard-chart__row"><div className="flex items-center justify-between gap-3 text-xs"><span className="font-semibold text-orbital-text-primary">{label}</span><strong className="text-orbital-text-secondary">{money(amount)}</strong></div><div className="dashboard-chart__track"><span className={`dashboard-chart__bar dashboard-chart__bar--${tone}`} style={{ width: `${width}%` }} /></div></div>;
}

export default function DashboardOverview({ summary, previousSummary, nextSummary, month, transactions, categories, accounts, creditCards, onNewTransaction, onTransfer }) {
    const income = summary.actual_income ?? 0;
    const expenses = summary.actual_expenses ?? 0;
    const forecastExpenses = summary.forecast_expenses ?? 0;
    const previousExpenses = previousSummary.actual_expenses ?? 0;
    const nextExpenses = nextSummary.forecast_expenses ?? 0;
    const net = income - expenses;
    const savings = previousExpenses - expenses;
    const comparisonMax = Math.max(previousExpenses, forecastExpenses, nextExpenses);
    const expenseProgress = forecastExpenses ? Math.min(100, Math.round((expenses / forecastExpenses) * 100)) : 0;
    const nextChange = nextExpenses - forecastExpenses;
    const previousMonth = shiftMonth(month, -1);
    const nextMonth = shiftMonth(month, 1);
    const hasNextForecast = nextExpenses > 0;

    return <>
        <section className="dashboard-overview" aria-labelledby="dashboard-overview-title">
        <div className="dashboard-overview__intro">
            <div><div className="mb-3 flex items-center gap-2 text-orbital-accent"><Sparkles className="h-4 w-4" aria-hidden="true" /><p className="dashboard-eyebrow">Pulso da carteira · {monthLabel(month)}</p></div><h1 id="dashboard-overview-title">O que merece sua atenção hoje?</h1><p>Compare o que aconteceu, o que está acontecendo e o que já está previsto para o próximo mês.</p></div>
            <div className="dashboard-overview__balance"><span>Saldo disponível</span><strong>{money(summary.balance)}</strong><small>atualizado agora</small></div>
        </div>

        <div className="dashboard-kpis"><div className="dashboard-kpi"><span><ArrowDownLeft aria-hidden="true" /> Entradas realizadas</span><strong className="dashboard-kpi--positive">{money(income)}</strong><small>neste mês</small></div><div className="dashboard-kpi"><span><ArrowUpRight aria-hidden="true" /> Despesas realizadas</span><strong className="dashboard-kpi--negative">{money(expenses)}</strong><small>neste mês</small></div><div className="dashboard-kpi"><span><MoveRight aria-hidden="true" /> Resultado líquido</span><strong className={net >= 0 ? 'dashboard-kpi--positive' : 'dashboard-kpi--negative'}>{net >= 0 ? '+' : ''}{money(net)}</strong><small>entradas menos despesas</small></div></div>

        <div className="dashboard-charts"><div className="dashboard-chart"><div className="dashboard-chart__heading"><div><p className="dashboard-eyebrow dashboard-eyebrow--dark">Visão de despesas</p><h2>Antes, agora e depois</h2></div><span>3 meses</span></div><div className="mt-5 space-y-4"><ExpenseBar label={monthLabel(previousMonth)} amount={previousExpenses} max={comparisonMax} tone="muted" /><ExpenseBar label={`${monthLabel(month)} · previsto`} amount={forecastExpenses} max={comparisonMax} tone="current" /><ExpenseBar label={`${monthLabel(nextMonth)} · previsão`} amount={nextExpenses} max={comparisonMax} tone="next" /></div><div className={`dashboard-chart__callout ${!hasNextForecast || nextChange <= 0 ? 'dashboard-chart__callout--positive' : 'dashboard-chart__callout--warning'}`}>{hasNextForecast && nextChange > 0 ? <TrendingUp aria-hidden="true" /> : <TrendingDown aria-hidden="true" />}<span>{!hasNextForecast ? 'Nenhuma despesa foi prevista para o próximo mês.' : nextChange > 0 ? `A previsão sobe ${money(nextChange)} no próximo mês.` : nextChange < 0 ? `A previsão cai ${money(Math.abs(nextChange))} no próximo mês.` : 'A previsão de despesas permanece estável.'}</span></div></div><div className="dashboard-chart dashboard-chart--decision"><div className="dashboard-chart__heading"><div><p className="dashboard-eyebrow dashboard-eyebrow--dark">Economia</p><h2>Seu ritmo mudou?</h2></div><span>mês a mês</span></div><div className="dashboard-saving"><strong>{savings >= 0 ? '+' : '-'}{money(Math.abs(savings))}</strong><span>{savings >= 0 ? 'economizados em relação ao mês anterior' : 'a mais que no mês anterior'}</span></div><div className="dashboard-progress" role="progressbar" aria-label={`${expenseProgress}% das despesas previstas realizadas`} aria-valuenow={expenseProgress} aria-valuemin="0" aria-valuemax="100"><span style={{ width: `${expenseProgress}%` }} /></div><div className="mt-3 flex justify-between gap-3 text-xs text-orbital-text-secondary"><span>Realizado: {money(expenses)}</span><span>Previsto: {money(forecastExpenses)}</span></div><div className="dashboard-actions"><Button color="blue" onClick={onNewTransaction}><Plus className="mr-2 h-4 w-4" aria-hidden="true" />Novo lançamento</Button><Button color="light" onClick={onTransfer}>Transferir entre contas</Button></div></div></div>
        </section>
        <DashboardInsights summary={summary} previousSummary={previousSummary} transactions={transactions} categories={categories} accounts={accounts} creditCards={creditCards} />
    </>;
}
