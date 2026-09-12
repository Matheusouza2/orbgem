import { Badge } from 'flowbite-react';

const money = (cents = 0) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100);

export default function BalanceSummary({ summary, month }) {
    const actual = summary.actual_expenses ?? 0;
    const forecast = summary.forecast_expenses ?? 0;
    const ratio = forecast ? Math.min(100, Math.round((actual / forecast) * 100)) : 0;

    return <section className="ledger-panel ledger-hero" aria-labelledby="balance-title">
        <div className="flex items-start justify-between gap-4">
            <div><p className="ledger-eyebrow">Saldo disponível</p><h1 id="balance-title" className="ledger-balance">{money(summary.balance)}</h1></div>
            <Badge color="success" className="ledger-badge">{month}</Badge>
        </div>
        <div className="mt-8"><div className="mb-2 flex justify-between text-xs font-semibold uppercase tracking-[0.16em] text-slate-500"><span>Ritmo do mês</span><span>{ratio}% realizado</span></div><div className="ledger-meter" aria-label={`${ratio}% das despesas previstas realizadas`} role="progressbar" aria-valuenow={ratio} aria-valuemin="0" aria-valuemax="100"><span style={{ width: `${ratio}%` }} /></div></div>
        <div className="mt-6 grid grid-cols-2 gap-3"><div className="ledger-stat"><span>Entradas</span><strong className="text-emerald-700">{money(summary.actual_income)}</strong></div><div className="ledger-stat"><span>Despesas previstas</span><strong className="text-rose-700">{money(forecast)}</strong></div></div>
    </section>;
}
