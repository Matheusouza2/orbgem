import { useId } from 'react';
import { CircleDollarSign, TrendingUp } from 'lucide-react';

const monthLabel = (date) => new Intl.DateTimeFormat('pt-BR', { month: 'short' }).format(date).replace('.', '');
const money = (cents) => (Number(cents || 0) / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
const percentage = (value) => `${Number(value || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}%`;

function monthsForYear(date) {
    return Array.from({ length: 6 }, (_, index) => new Date(date.getFullYear(), date.getMonth() - 5 + index, 1));
}

function getTickerRows(investments, income, months, currentYear) {
    const groups = new Map();
    const investmentGroups = new Map();

    investments.forEach((investment) => {
        const ticker = String(investment.ticker || '').trim();
        const key = ticker ? `ticker:${ticker.toUpperCase()}` : `investment:${investment.id}`;
        if (!groups.has(key)) {
            groups.set(key, { key, ticker: ticker || investment.name || 'Sem ticker', name: ticker ? investment.name : '', capital: 0, yearIncome: 0, monthIncome: new Map() });
        }
        const group = groups.get(key);
        group.capital += Number(investment.invested_amount || 0);
        investmentGroups.set(String(investment.id), group);
    });

    const visibleMonthKeys = new Set(months.map(({ year, month }) => `${year}-${String(month + 1).padStart(2, '0')}`));
    income.forEach((entry) => {
        const group = investmentGroups.get(String(entry.investment_id));
        if (!group || !entry.transaction_date) return;
        const date = new Date(`${String(entry.transaction_date).slice(0, 10)}T12:00:00`);
        if (Number.isNaN(date.getTime())) return;
        const amount = Number(entry.amount || 0);
        if (date.getFullYear() === currentYear) group.yearIncome += amount;
        const key = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
        if (visibleMonthKeys.has(key)) group.monthIncome.set(key, (group.monthIncome.get(key) || 0) + amount);
    });

    return [...groups.values()].map((group) => ({
        ...group,
        yearRate: group.capital > 0 ? (group.yearIncome / group.capital) * 100 : 0,
        monthRate: (amount) => group.capital > 0 ? (amount / group.capital) * 100 : 0,
        months: months.map(({ year, month }) => {
            const key = `${year}-${String(month + 1).padStart(2, '0')}`;
            const amount = group.monthIncome.get(key) || 0;
            return { key, label: monthLabel(new Date(year, month, 1)), amount, rate: group.capital > 0 ? (amount / group.capital) * 100 : 0 };
        }),
    })).sort((a, b) => b.yearRate - a.yearRate || b.yearIncome - a.yearIncome || a.ticker.localeCompare(b.ticker, 'pt-BR'));
}

export default function InvestmentTickerYieldCard({ investments, income, onOpenTickerIncome }) {
    const titleId = useId();
    const now = new Date();
    const months = monthsForYear(now);
    const rows = getTickerRows(investments, income, months, now.getFullYear());
    const maxMonthlyRate = Math.max(0, ...rows.flatMap((row) => row.months.map((month) => Math.abs(month.rate))));
    const yearIncome = rows.reduce((sum, row) => sum + row.yearIncome, 0);
    const capital = rows.reduce((sum, row) => sum + row.capital, 0);
    const totalYearRate = capital > 0 ? (yearIncome / capital) * 100 : 0;
    const currentMonth = months[months.length - 1];
    const currentMonthKey = `${currentMonth.getFullYear()}-${String(currentMonth.getMonth() + 1).padStart(2, '0')}`;
    const currentMonthIncome = rows.reduce((sum, row) => sum + (row.monthIncome.get(currentMonthKey) || 0), 0);
    const currentMonthRate = capital > 0 ? (currentMonthIncome / capital) * 100 : 0;

    return <article className="investment-ticker-yield" aria-labelledby={titleId}>
        <header className="investment-ticker-yield__header">
            <div className="investment-dashboard__card-title">
                <span className="investment-dashboard__icon"><CircleDollarSign aria-hidden="true" /></span>
                <div><p className="ledger-eyebrow">Renda da carteira</p><h2 id={titleId}>Rendimento por ticker</h2><p className="investment-chart-card__subtitle">Proventos recebidos em relação ao capital investido.</p></div>
            </div>
            <button type="button" className="investment-ticker-yield__action" onClick={onOpenTickerIncome} disabled={!investments.length}><TrendingUp aria-hidden="true" />Ver lançamentos</button>
        </header>
        <div className="investment-ticker-yield__summary" aria-label="Resumo dos rendimentos">
            <div><span>{currentMonth.label} · carteira</span><strong>{money(currentMonthIncome)} <small>{percentage(currentMonthRate)}</small></strong></div>
            <div><span>Acumulado no ano</span><strong>{money(yearIncome)} <small>{percentage(totalYearRate)}</small></strong></div>
        </div>
        {rows.length ? <div className="investment-ticker-yield__table-wrap"><table className="investment-ticker-yield__table">
            <caption className="sr-only">Rendimento mensal recebido por ticker nos últimos seis meses, rendimento do mês atual e acumulado do ano.</caption>
            <thead><tr><th scope="col">Ticker</th>{months.map(({ year, month }) => <th scope="col" key={`${year}-${month}`}>{monthLabel(new Date(year, month, 1))}</th>)}<th scope="col">Este mês</th><th scope="col">No ano</th></tr></thead>
            <tbody>{rows.map((row) => {
                const currentMonthAmount = row.monthIncome.get(currentMonthKey) || 0;
                return <tr key={row.key}>
                    <th scope="row"><strong>{row.ticker}</strong>{row.name ? <small title={row.name}>{row.name}</small> : null}<small>Capital {money(row.capital)}</small></th>
                    {row.months.map((month) => {
                        const height = maxMonthlyRate > 0 ? Math.max(month.rate ? 7 : 0, (Math.abs(month.rate) / maxMonthlyRate) * 100) : 0;
                        return <td key={month.key}><div className="investment-ticker-yield__month" aria-label={`${row.ticker}, ${month.label}: ${money(month.amount)}, ${percentage(month.rate)}`}><span>{percentage(month.rate)}</span><div className="investment-ticker-yield__track" aria-hidden="true"><i style={{ height: `${height}%` }} /></div><small>{money(month.amount)}</small></div></td>;
                    })}
                    <td className="investment-ticker-yield__result"><strong>{percentage(row.monthRate(currentMonthAmount))}</strong><small>{money(currentMonthAmount)}</small></td>
                    <td className="investment-ticker-yield__result investment-ticker-yield__result--year"><strong>{percentage(row.yearRate)}</strong><small>{money(row.yearIncome)}</small></td>
                </tr>;
            })}</tbody>
        </table></div> : <p className="investment-dashboard__empty investment-ticker-yield__empty">Cadastre investimentos para comparar os rendimentos recebidos por ticker.</p>}
        <p className="investment-ticker-yield__note">Percentuais calculados sobre o capital investido atual. O histórico de proventos não prevê rendimentos futuros.</p>
    </article>;
}
