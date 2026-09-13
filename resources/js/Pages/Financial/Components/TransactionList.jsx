import { Badge, Dropdown, DropdownItem } from 'flowbite-react';
import { ArrowDownLeft, ArrowUpRight, MoreVertical, Pencil, Repeat2, RotateCcw, Trash2 } from 'lucide-react';
import { FINANCIAL_ICON_OPTIONS } from '@/Components/Inputs/IconPicker';
import { formatDateBR } from '@/Utils/date';

const money = (cents) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100);
const transactionType = { INCOME: 'Entrada', EXPENSE: 'Saída', TRANSFER: 'Transferência' };
const MAX_DESCRIPTION_LENGTH = 60;
const truncateDescription = (description) => description.length > MAX_DESCRIPTION_LENGTH ? `${description.slice(0, MAX_DESCRIPTION_LENGTH - 3)}...` : description;

export default function TransactionList({ transactions, categories = [], loading, onRequestReversal, onRequestEdit, onRequestDelete, title = 'Todos os lançamentos' }) {
    return <section className="ledger-panel transactions-list" aria-labelledby="transactions-title">
        <div className="mb-6 flex items-end justify-between gap-4"><div><p className="ledger-eyebrow">Linha do tempo</p><h2 id="transactions-title" className="ledger-title">{title}</h2></div><span className="transactions-count">{transactions.length.toString().padStart(2, '0')} registros</span></div>
        {loading ? <p className="ledger-state" aria-live="polite">Atualizando lançamentos…</p> : transactions.length === 0 ? <p className="ledger-state">Nenhuma movimentação neste período. Registre uma entrada ou saída para começar.</p> : <ul className="transactions-list__items">{transactions.map((transaction) => {
            const credit = transaction.effect === 'CREDIT';
            const transfer = transaction.type === 'TRANSFER';
            const category = categories.find((item) => String(item.id) === String(transaction.category_id));
            const CategoryIcon = FINANCIAL_ICON_OPTIONS.find((option) => option.id === category?.icon)?.Icon;
            const Icon = CategoryIcon ?? (transfer ? Repeat2 : credit ? ArrowDownLeft : ArrowUpRight);
            const manageable = transaction.canManage !== false;

            return <li key={transaction.id} className={`transaction-row ${credit ? 'transaction-row--credit' : 'transaction-row--debit'}`}>
                <span className="transaction-row__icon" style={CategoryIcon && category?.icon_color ? { color: category.icon_color } : undefined}><Icon className="h-5 w-5" aria-hidden="true" /></span>
                <div className="transaction-row__content min-w-0 flex-1"><div className="flex min-w-0 flex-wrap items-center gap-2"><p title={transaction.description} className="min-w-0 flex-1 truncate font-semibold text-orbital-text-primary">{truncateDescription(transaction.description)}</p><Badge className="shrink-0" color={transaction.status === 'POSTED' ? 'success' : 'warning'}>{transaction.status === 'POSTED' ? 'Realizado' : 'Previsto'}</Badge></div><p className="mt-1 text-xs text-orbital-text-secondary">{transactionType[transaction.type] ?? transaction.type} · {formatDateBR(transaction.transaction_date)}</p></div>
                <div className="transaction-row__actions flex items-center gap-3"><strong className={`whitespace-nowrap ${credit ? 'text-emerald-700' : transaction.effect === 'DEBIT' ? 'text-rose-700' : 'text-slate-600'}`}>{credit ? '+' : transaction.effect === 'DEBIT' ? '-' : ''} {money(transaction.amount)}</strong><Dropdown inline arrowIcon={false} trigger="hover" label={<MoreVertical className="h-5 w-5" aria-hidden="true" />} placement="bottom-end" className="transaction-actions-dropdown" aria-label={`Ações para ${transaction.description}`}><DropdownItem icon={RotateCcw} onClick={() => onRequestReversal(transaction)} disabled={!transaction.canReverse}>Estornar</DropdownItem><DropdownItem icon={Pencil} onClick={() => onRequestEdit(transaction)} disabled={!manageable}>Editar</DropdownItem><DropdownItem icon={Trash2} onClick={() => onRequestDelete(transaction)} disabled={!manageable}>Excluir</DropdownItem></Dropdown></div>
            </li>;
        })}</ul>}
    </section>;
}
