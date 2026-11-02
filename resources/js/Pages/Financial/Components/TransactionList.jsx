import { Dropdown, DropdownItem } from 'flowbite-react';
import { ArrowDownLeft, ArrowUpRight, CircleCheck, MoreVertical, Pencil, Plus, Repeat2, RotateCcw, SearchX, Trash2 } from 'lucide-react';
import { useMemo } from 'react';
import { FINANCIAL_ICON_OPTIONS } from '@/Components/Inputs/IconPicker';
import { canEffectivateTransaction } from '../Hooks/transactionActions';

const money = (cents = 0) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100);
const typeLabel = { INCOME: 'Entrada', EXPENSE: 'Saída', TRANSFER: 'Transferência' };

const dayKey = (value) => String(value ?? '').split('T')[0];
const toDate = (key) => new Date(`${key}T12:00:00`);

function dayLabel(key) {
    const date = toDate(key);
    if (Number.isNaN(date.getTime())) return key;
    const today = new Date();
    today.setHours(12, 0, 0, 0);
    const diff = Math.round((today - date) / 86400000);
    const long = date.toLocaleDateString('pt-BR', { weekday: 'long', day: 'numeric', month: 'short' }).replace('.', '');
    if (diff === 0) return { main: 'Hoje', sub: long };
    if (diff === 1) return { main: 'Ontem', sub: long };
    const [weekday, ...rest] = long.split(', ');
    return { main: rest.join(', ') || long, sub: rest.length ? weekday : '' };
}

const signedAmount = (transaction) => (transaction.effect === 'CREDIT' ? transaction.amount : transaction.effect === 'DEBIT' ? -transaction.amount : 0);

function SkeletonRows() {
    return <div className="tx-skeleton" aria-hidden="true">{[0, 1, 2, 3, 4].map((item) => <div key={item} className="tx-skeleton__row" style={{ animationDelay: `${item * 80}ms` }}><span /><div><i /><i /></div><b /></div>)}</div>;
}

export default function TransactionList({ transactions, categories = [], loading, hasFilters = false, onClearFilters, onCreate, onRequestReversal, onRequestEdit, onRequestDelete, onEffectivate }) {
    const groups = useMemo(() => {
        const map = new Map();
        transactions.forEach((transaction) => {
            const key = dayKey(transaction.transaction_date);
            if (!map.has(key)) map.set(key, []);
            map.get(key).push(transaction);
        });

        return [...map.entries()].sort(([a], [b]) => (a < b ? 1 : -1)).map(([key, items]) => ({ key, items, net: items.reduce((sum, item) => sum + signedAmount(item), 0), label: dayLabel(key) }));
    }, [transactions]);

    if (loading) return <section className="tx-list" aria-label="Lançamentos" aria-busy="true"><SkeletonRows /></section>;

    if (transactions.length === 0) {
        return <section className="tx-list tx-empty" aria-label="Lançamentos">
            <span className="tx-empty__mark" aria-hidden="true">{hasFilters ? <SearchX className="h-6 w-6" /> : <Plus className="h-6 w-6" />}</span>
            <h2>{hasFilters ? 'Nada encontrado com esses filtros' : 'Nenhuma movimentação neste mês'}</h2>
            <p>{hasFilters ? 'Tente outro status, outra conta ou limpe a busca.' : 'Registre uma entrada ou saída e ela aparece aqui, agrupada por dia.'}</p>
            {hasFilters ? <button type="button" className="tx-link orbital-btn" onClick={onClearFilters}>Limpar filtros</button> : <button type="button" className="tx-cta orbital-btn" onClick={onCreate}><Plus className="h-4 w-4" aria-hidden="true" />Nova transação</button>}
        </section>;
    }

    return <section className="tx-list" aria-label="Lançamentos">
        {groups.map((group) => <div key={group.key} className="tx-day">
            <header className="tx-day__header">
                <h2><span>{group.label.main}</span>{group.label.sub && <small>{group.label.sub}</small>}</h2>
                <span className={`tx-day__net ${group.net > 0 ? 'is-positive' : group.net < 0 ? 'is-negative' : ''}`} title="Resultado do dia">{group.net > 0 ? '+' : group.net < 0 ? '−' : ''}{money(Math.abs(group.net))}</span>
            </header>
            <ul>
                {group.items.map((transaction) => {
                    const credit = transaction.effect === 'CREDIT';
                    const debit = transaction.effect === 'DEBIT';
                    const transfer = transaction.type === 'TRANSFER';
                    const category = categories.find((item) => String(item.id) === String(transaction.category_id));
                    const CategoryIcon = FINANCIAL_ICON_OPTIONS.find((option) => option.id === category?.icon)?.Icon;
                    const Icon = CategoryIcon ?? (transfer ? Repeat2 : credit ? ArrowDownLeft : ArrowUpRight);
                    const manageable = transaction.canManage !== false;
                    const posted = transaction.status === 'POSTED';

                    return <li key={transaction.id} className={`tx-row ${credit ? 'tx-row--credit' : debit ? 'tx-row--debit' : ''} ${posted ? '' : 'tx-row--projected'}`}>
                        <span className="tx-row__icon" style={CategoryIcon && category?.icon_color ? { color: category.icon_color } : undefined}><Icon className="h-[18px] w-[18px]" aria-hidden="true" /></span>
                        <div className="tx-row__main">
                            <p title={transaction.description} className="tx-row__title">{transaction.description}</p>
                            <p className="tx-row__meta">
                                <span className={`tx-status ${posted ? 'is-posted' : 'is-projected'}`}>{posted ? 'Realizado' : 'Previsto'}</span>
                                {category?.name && <span>{category.name}</span>}
                                <span>{typeLabel[transaction.type] ?? transaction.type}</span>
                            </p>
                        </div>
                        <strong className={`tx-row__amount ${credit ? 'is-credit' : debit ? 'is-debit' : ''}`}>{credit ? '+' : debit ? '−' : ''}{money(transaction.amount)}</strong>
                        <div className="tx-row__actions">
                            {canEffectivateTransaction({ ...transaction, canManage: manageable })
                                ? <button type="button" className="tx-effectivate orbital-btn" onClick={() => onEffectivate(transaction)} aria-label={`Efetivar ${transaction.description}`} title="Efetivar transação"><CircleCheck className="h-4 w-4" aria-hidden="true" /><span>Efetivar</span></button>
                                : null}
                            <Dropdown inline arrowIcon={false} label={<MoreVertical className="h-5 w-5" aria-label={`Ações para ${transaction.description}`} />} placement="bottom-end" className="transaction-actions-dropdown">
                                <DropdownItem icon={RotateCcw} onClick={() => onRequestReversal(transaction)} disabled={!transaction.canReverse}>Estornar</DropdownItem>
                                <DropdownItem icon={Pencil} onClick={() => onRequestEdit(transaction)} disabled={!manageable}>Editar</DropdownItem>
                                <DropdownItem icon={Trash2} onClick={() => onRequestDelete(transaction)} disabled={!manageable}>Excluir</DropdownItem>
                            </Dropdown>
                        </div>
                    </li>;
                })}
            </ul>
        </div>)}
    </section>;
}
