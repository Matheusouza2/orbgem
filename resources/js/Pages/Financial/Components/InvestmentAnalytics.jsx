import { useId } from 'react';
import { Activity, BarChart3, CircleDollarSign, PieChart } from 'lucide-react';

const colors = ['#123B8F', '#F4B321', '#4F75C8', '#79A7A3', '#C98A00', '#667085'];
const money = (value) => (Number(value || 0) / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
const compactMoney = (value) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL', notation: 'compact', maximumFractionDigits: 1 }).format(Number(value || 0) / 100);
const monthKey = (value) => { const date = new Date(`${String(value).slice(0, 10)}T12:00:00`); return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`; };
const monthLabel = (key) => { const [year, month] = key.split('-').map(Number); return new Intl.DateTimeFormat('pt-BR', { month: 'short' }).format(new Date(year, month - 1, 15)).replace('.', ''); };
const lastMonths = (count = 6) => { const now = new Date(); return Array.from({ length: count }, (_, index) => { const date = new Date(now.getFullYear(), now.getMonth() - count + index + 1, 1); return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`; }); };

function Card({ title, subtitle, icon: Icon, children }) {
    return <article className="investment-dashboard__card investment-chart-card"><div className="investment-dashboard__card-title"><span className="investment-dashboard__icon"><Icon aria-hidden="true" /></span><div><p className="ledger-eyebrow">Carteira</p><h2>{title}</h2><p className="investment-chart-card__subtitle">{subtitle}</p></div></div>{children}</article>;
}

function Empty({ children }) { return <p className="investment-dashboard__empty investment-chart-empty">{children}</p>; }

function MonthlyBars({ records, dateKey, valueKey, title }) {
    const months = lastMonths();
    const totals = records.reduce((result, row) => { const key = monthKey(row[dateKey]); if (months.includes(key)) result[key] = (result[key] || 0) + Number(row[valueKey] || 0); return result; }, {});
    const max = Math.max(...months.map((key) => totals[key] || 0));
    if (!max) return <Empty>Os dados aparecerão aqui após registrar {title}.</Empty>;
    return <div className="investment-bars" role="list" aria-label={title}>
        {months.map((key) => { const amount = totals[key] || 0; const height = max ? Math.max(amount ? 7 : 0, (amount / max) * 100) : 0; return <div className="investment-bars__item" role="listitem" key={key} aria-label={`${monthLabel(key)}: ${money(amount)}`}><div className="investment-bars__value">{amount ? compactMoney(amount) : ''}</div><div className="investment-bars__track"><span style={{ height: `${height}%` }} title={`${monthLabel(key)}: ${money(amount)}`} /></div><small>{monthLabel(key)}</small></div>; })}
    </div>;
}

function PortfolioLine({ history }) {
    const chartId = useId();
    const points = history.slice(-12).map((row) => ({ ...row, value: Number(row.total_value || 0) }));
    if (!points.length) return <Empty>Registre posições para acompanhar a evolução da carteira.</Empty>;
    const width = 560; const height = 188; const pad = { top: 16, right: 16, bottom: 28, left: 54 };
    const min = Math.min(0, ...points.map((point) => point.value)); const max = Math.max(1, ...points.map((point) => point.value));
    const x = (index) => pad.left + (points.length === 1 ? (width - pad.left - pad.right) / 2 : index * (width - pad.left - pad.right) / (points.length - 1));
    const y = (value) => pad.top + ((max - value) / Math.max(1, max - min)) * (height - pad.top - pad.bottom);
    const path = points.map((point, index) => `${index ? 'L' : 'M'} ${x(index)} ${y(point.value)}`).join(' ');
    const area = `${path} L ${x(points.length - 1)} ${height - pad.bottom} L ${x(0)} ${height - pad.bottom} Z`;
    const labels = [...new Set([0, Math.floor((points.length - 1) / 2), points.length - 1])];
    return <div className="investment-line"><svg viewBox={`0 0 ${width} ${height}`} role="img" aria-labelledby={`${chartId}-title ${chartId}-desc`}>
        <title id={`${chartId}-title`}>Evolução patrimonial da carteira</title><desc id={`${chartId}-desc`}>Linha com o valor da carteira ao longo das datas registradas. Pontos dourados indicam aportes.</desc>
        {[0, .5, 1].map((fraction) => { const value = max - (max - min) * fraction; const yy = pad.top + (height - pad.top - pad.bottom) * fraction; return <g key={fraction}><line x1={pad.left} x2={width - pad.right} y1={yy} y2={yy} className="investment-line__grid" /><text x={pad.left - 7} y={yy + 4} textAnchor="end" className="investment-line__axis">{compactMoney(value)}</text></g>; })}
        <path d={area} className="investment-line__area" /><path d={path} className="investment-line__path" />
        {points.map((point, index) => <circle key={`${point.position_date}-${index}`} cx={x(index)} cy={y(point.value)} r={Number(point.contribution_amount || 0) > 0 ? 4.5 : 3} className={Number(point.contribution_amount || 0) > 0 ? 'investment-line__point investment-line__point--contribution' : 'investment-line__point'} tabIndex="0" aria-label={`${point.position_date}: ${money(point.value)}${Number(point.contribution_amount || 0) > 0 ? `, aporte de ${money(point.contribution_amount)}` : ''}`}><title>{`${point.position_date}: ${money(point.value)}${Number(point.contribution_amount || 0) > 0 ? ` · aporte ${money(point.contribution_amount)}` : ''}`}</title></circle>)}
        {labels.map((index) => <text key={index} x={x(index)} y={height - 7} textAnchor={index === 0 ? 'start' : index === points.length - 1 ? 'end' : 'middle'} className="investment-line__axis">{new Date(`${points[index].position_date}T12:00:00`).toLocaleDateString('pt-BR', { month: 'short', year: '2-digit' })}</text>)}
    </svg><div className="investment-line__legend"><span><i />Valor da carteira</span><span><i className="investment-line__legend-contribution" />Dia com aporte</span></div><p className="sr-only">{points.map((point) => `${point.position_date}: ${money(point.value)}`).join('; ')}</p></div>;
}

function arcPath(cx, cy, radius, start, end) {
    const startPoint = [cx + radius * Math.cos(start), cy + radius * Math.sin(start)]; const endPoint = [cx + radius * Math.cos(end), cy + radius * Math.sin(end)];
    return `M ${cx} ${cy} L ${startPoint[0]} ${startPoint[1]} A ${radius} ${radius} 0 ${end - start > Math.PI ? 1 : 0} 1 ${endPoint[0]} ${endPoint[1]} Z`;
}

function TickerDonut({ investments }) {
    const byTicker = Object.values(investments.reduce((groups, item) => { const label = item.ticker || item.name || 'Sem código'; groups[label] = groups[label] || { label, value: 0 }; groups[label].value += Number(item.current_value || 0); return groups; }, {})).sort((a, b) => b.value - a.value);
    const rows = byTicker.length > 7 ? [...byTicker.slice(0, 6), { label: 'Outros', value: byTicker.slice(6).reduce((sum, row) => sum + row.value, 0) }] : byTicker;
    const total = rows.reduce((sum, row) => sum + row.value, 0);
    if (!total) return <Empty>Cadastre investimentos com ticker para ver a composição.</Empty>;
    let angle = -Math.PI / 2;
    return <div className="investment-donut"><div className="investment-donut__visual"><svg viewBox="0 0 180 180" role="img" aria-label={`Composição por ticker. Total ${money(total)}.`}>{rows.map((row, index) => { const fraction = row.value / total; const next = angle + fraction * Math.PI * 2; const path = arcPath(90, 90, 76, angle, next); angle = next; return fraction >= .999999 ? <circle key={row.label} cx="90" cy="90" r="76" fill={colors[index % colors.length]}><title>{`${row.label}: ${money(row.value)} (100%)`}</title></circle> : <path key={row.label} d={path} fill={colors[index % colors.length]} stroke="white" strokeWidth="2"><title>{`${row.label}: ${money(row.value)} (${(fraction * 100).toFixed(1).replace('.', ',')}%)`}</title></path>; })}<circle cx="90" cy="90" r="48" fill="white" /><text x="90" y="84" textAnchor="middle" className="investment-donut__center-label">VALOR ATUAL</text><text x="90" y="104" textAnchor="middle" className="investment-donut__center-value">{compactMoney(total)}</text></svg></div><ul className="investment-donut__legend">{rows.map((row, index) => <li key={row.label}><i style={{ background: colors[index % colors.length] }} /><span title={row.label}>{row.label}</span><strong>{((row.value / total) * 100).toFixed(0)}%</strong></li>)}</ul></div>;
}

export default function InvestmentAnalytics({ investments, history, income }) {
    return <div className="investment-dashboard__charts investment-analytics"><Card title="Evolução patrimonial" subtitle="Valor da carteira ao longo do tempo" icon={Activity}><PortfolioLine history={history} /></Card><Card title="Por ticker / código" subtitle="Distribuição do valor atual" icon={PieChart}><TickerDonut investments={investments} /></Card><Card title="Aportes por mês" subtitle="Capital adicionado à carteira" icon={BarChart3}><MonthlyBars records={history} dateKey="position_date" valueKey="contribution_amount" title="aportes" /></Card><Card title="Rendimentos por mês" subtitle="Proventos e dividendos recebidos" icon={CircleDollarSign}><MonthlyBars records={income} dateKey="transaction_date" valueKey="amount" title="rendimentos" /></Card></div>;
}
