import { ArrowDownLeft, ArrowUpRight, CreditCard, Landmark, MoveRight, WalletCards } from 'lucide-react';

const money = (cents = 0) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(cents || 0) / 100);
const percent = (value = 0) => `${Number(value || 0).toLocaleString('pt-BR', { maximumFractionDigits: 1 })}%`;

const categoryColor = ['#123B8F', '#F4B321', '#4F75C8', '#79A7A3', '#C98A00', '#667085'];
const statusColors = { posted: 'dashboard-status__bar--posted', upcoming: 'dashboard-status__bar--upcoming', overdue: 'dashboard-status__bar--overdue', distant: 'dashboard-status__bar--distant' };

function ValueItem({ icon: Icon, label, value, tone = '' }) {
    return <div className="dashboard-value-item"><span className="dashboard-value-item__label"><Icon aria-hidden="true" />{label}</span><strong className={tone}>{money(value)}</strong></div>;
}

function CardHeading({ eyebrow, title, detail }) {
    return <div className="dashboard-card-heading"><div><p className="dashboard-eyebrow dashboard-eyebrow--dark">{eyebrow}</p><h2>{title}</h2></div>{detail && <span>{detail}</span>}</div>;
}

function classifyTransactions(transactions, type) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const limit = new Date(today);
    limit.setDate(limit.getDate() + 7);
    const groups = { posted: 0, upcoming: 0, overdue: 0, distant: 0 };

    transactions.filter((transaction) => transaction.type === type).forEach((transaction) => {
        const amount = Number(transaction.amount || 0);
        if (transaction.status === 'POSTED') {
            groups.posted += amount;
            return;
        }

        const dueDate = transaction.due_date ? new Date(`${transaction.due_date}T12:00:00`) : null;
        if (dueDate && dueDate < today) groups.overdue += amount;
        else if (dueDate && dueDate <= limit) groups.upcoming += amount;
        else groups.distant += amount;
    });

    return groups;
}

function StatusCard({ type, summary }) {
    const groups = type === 'EXPENSE' ? (summary.expense_status_breakdown || {}) : (summary.income_status_breakdown || {});
    const total = Object.values(groups).reduce((sum, amount) => sum + amount, 0);
    const labels = { posted: 'Efetivadas', upcoming: 'Próximo do vencimento', overdue: 'Vencidas', distant: 'Distante do vencimento' };

    return <article className="dashboard-insight-card dashboard-status-card"><CardHeading eyebrow={type === 'EXPENSE' ? 'Despesas' : 'Receitas'} title={type === 'EXPENSE' ? 'Onde está o compromisso' : 'O que entra no radar'} detail={money(total)} /><div className="dashboard-status-list">{Object.entries(groups).map(([key, amount]) => <div className="dashboard-status-row" key={key}><div className="dashboard-status-row__top"><span><i className={`dashboard-status__dot dashboard-status__dot--${key}`} />{labels[key]}</span><strong>{money(amount)}</strong></div><div className="dashboard-status__track"><span className={statusColors[key]} style={{ width: `${total > 0 ? Math.max(amount > 0 ? 3 : 0, (amount / total) * 100) : 0}%` }} /></div><small>{percent(total > 0 ? (amount / total) * 100 : 0)}</small></div>)}</div></article>;
}

function EconomyChart({ summary, previousSummary }) {
    const periods = [{ label: 'Mês anterior', data: previousSummary }, { label: 'Mês atual', data: summary }];
    const max = Math.max(...periods.flatMap(({ data }) => [Number(data.actual_income || 0), Number(data.actual_expenses || 0)]), 1);

    return <article className="dashboard-insight-card dashboard-economy-card"><CardHeading eyebrow="Economia mensal" title="Receita contra despesa" detail="valores efetivados" /><div className="dashboard-economy-legend"><span><i className="dashboard-legend-dot dashboard-legend-dot--income" />Receitas</span><span><i className="dashboard-legend-dot dashboard-legend-dot--expense" />Despesas</span></div><div className="dashboard-economy-chart">{periods.map(({ label, data }) => { const income = Number(data.actual_income || 0); const expenses = Number(data.actual_expenses || 0); const savings = income > 0 ? ((income - expenses) / income) * 100 : 0; return <div className="dashboard-economy-period" key={label}><div className="dashboard-economy-bars"><span className="dashboard-economy-bar dashboard-economy-bar--income" style={{ height: `${Math.max(income > 0 ? 5 : 0, (income / max) * 100)}%` }} /><span className="dashboard-economy-bar dashboard-economy-bar--expense" style={{ height: `${Math.max(expenses > 0 ? 5 : 0, (expenses / max) * 100)}%` }} /></div><strong className={savings >= 0 ? 'text-emerald-700' : 'text-rose-700'}>{savings >= 0 ? '+' : ''}{percent(savings)}</strong><small>{label}</small></div>; })}</div></article>;
}

function CategoryChart({ summary }) {
    const rows = (summary.expense_by_category || []).map((row) => [row.category_name, Number(row.amount || 0)]).slice(0, 5);
    const total = rows.reduce((sum, [, amount]) => sum + amount, 0);
    const max = rows[0]?.[1] || 0;

    return <article className="dashboard-insight-card"><CardHeading eyebrow="Despesas reais" title="Por categoria" detail={money(total)} />{rows.length === 0 ? <p className="dashboard-insight-empty">Nenhuma despesa efetivada neste período.</p> : <div className="dashboard-category-list">{rows.map(([name, amount], index) => <div className="dashboard-category-row" key={name}><div className="dashboard-category-row__top"><span><i style={{ background: categoryColor[index % categoryColor.length] }} />{name}</span><strong>{money(amount)}</strong></div><div className="dashboard-category__track"><span style={{ background: categoryColor[index % categoryColor.length], width: `${(amount / max) * 100}%` }} /></div><small>{percent(total > 0 ? (amount / total) * 100 : 0)}</small></div>)}</div>}</article>;
}

function BalanceCard({ title, eyebrow, icon: Icon, items, valueKey, emptyLabel }) {
    const total = items.reduce((sum, item) => sum + Number(item[valueKey] || 0), 0);

    return <article className="dashboard-insight-card dashboard-balance-card"><CardHeading eyebrow={eyebrow} title={title} detail={money(total)} /><div className="dashboard-balance-list">{items.length === 0 ? <p className="dashboard-insight-empty">{emptyLabel}</p> : items.map((item) => <div className="dashboard-balance-row" key={item.id}><span className="dashboard-balance-row__icon"><Icon aria-hidden="true" /></span><span className="min-w-0 flex-1"><strong className="block truncate">{item.name}</strong><small>{item.institution || 'Sem instituição'}</small></span><strong>{money(item[valueKey])}</strong></div>)}</div></article>;
}

export default function DashboardInsights({ summary, previousSummary, transactions, categories, accounts, creditCards }) {
    const transfers = transactions.filter((transaction) => transaction.type === 'TRANSFER' && transaction.effect === 'DEBIT').reduce((sum, transaction) => sum + Number(transaction.amount || 0), 0);
    const cardTotal = creditCards.reduce((sum, card) => sum + Number(card.dashboard_balance || 0), 0);
    const accountItems = accounts.filter((account) => account.show_in_dashboard !== false && account.ignore_in_totals !== true).map((account) => ({ ...account, dashboard_balance: account.dashboard_balance ?? 0 }));
    const cardItems = creditCards.filter((card) => card.active !== false).map((card) => ({ ...card, dashboard_balance: card.dashboard_balance ?? 0 }));

    return <div className="dashboard-insights"><section className="dashboard-insight-card dashboard-values-card"><CardHeading eyebrow="Visão geral" title="Os números que movem o mês" detail="competência selecionada" /><div className="dashboard-values-grid"><ValueItem icon={ArrowDownLeft} label="Receitas" value={summary.actual_income} tone="dashboard-value--income" /><ValueItem icon={ArrowUpRight} label="Despesas" value={summary.actual_expenses} tone="dashboard-value--expense" /><ValueItem icon={MoveRight} label="Transferências" value={transfers} /><ValueItem icon={WalletCards} label="Balanço" value={summary.balance} tone={summary.balance >= 0 ? 'dashboard-value--income' : 'dashboard-value--expense'} /><ValueItem icon={CreditCard} label="Cartões" value={cardTotal} tone="dashboard-value--expense" /></div></section><div className="dashboard-insight-grid dashboard-insight-grid--balances"><BalanceCard eyebrow="Contas" title="Saldo por conta" icon={Landmark} items={accountItems} valueKey="dashboard_balance" emptyLabel="Nenhuma conta disponível." /><BalanceCard eyebrow="Cartões de crédito" title="Saldo por cartão" icon={CreditCard} items={cardItems} valueKey="dashboard_balance" emptyLabel="Nenhum cartão disponível." /></div><div className="dashboard-insight-grid"><EconomyChart summary={summary} previousSummary={previousSummary} /><CategoryChart summary={summary} /></div><div className="dashboard-insight-grid"><StatusCard type="EXPENSE" summary={summary} /><StatusCard type="INCOME" summary={summary} /></div></div>;
}
