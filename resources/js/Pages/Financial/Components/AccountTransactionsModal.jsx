import { Badge, Button, Modal, ModalBody, ModalFooter, ModalHeader, Tooltip } from 'flowbite-react';
import { ArrowDownLeft, ArrowUpRight, CalendarClock, CalendarDays, CircleCheck, Pencil, ReceiptText, Trash2 } from 'lucide-react';
import { useState } from 'react';
import AccountIcon from './AccountIcon';
import Inputs from '@/Components/Inputs';
import { formatDateBR } from '@/Utils/date';
import { canEffectivateTransaction } from '../Hooks/transactionActions';

const money = (cents) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(cents || 0) / 100);
const statusOptions = [{ value: 'ACTIVE', label: 'Todas' }, { value: 'PROJECTED', label: 'Previstas' }, { value: 'POSTED', label: 'Efetivadas' }];
const typeOptions = [{ value: '', label: 'Todos os tipos' }, { value: 'EXPENSE', label: 'Despesas' }, { value: 'INCOME', label: 'Receitas' }];

function IconAction({ content, onClick, icon: Icon, danger = false, disabled = false }) {
    return <Tooltip content={content} placement="top"><Button type="button" color={danger ? 'failure' : 'light'} className="!h-10 !w-10 !p-0" onClick={onClick} disabled={disabled} aria-label={content} title={content}><Icon className="h-4 w-4" aria-hidden="true" /></Button></Tooltip>;
}

export default function AccountTransactionsModal({ account, transactions, meta, loading, error, filters, categories = [], onClose, onFilter, onEditTransaction, onEffectivate, onDeleteTransaction, onUpdateRecurringDate }) {
    const [dateTransaction, setDateTransaction] = useState(null);
    const [date, setDate] = useState('');
    const [savingDate, setSavingDate] = useState(false);
    const totalAmount = meta?.total_amount ?? 0;
    const categoryOptions = [{ value: '', label: 'Todas as categorias' }, ...categories.filter((category, index, list) => list.findIndex((item) => item.id === category.id) === index).map((category) => ({ value: String(category.id), label: category.name }))];

    const saveDate = async (event) => {
        event.preventDefault();
        if (!dateTransaction || !date) return;
        setSavingDate(true);
        try {
            await onUpdateRecurringDate(dateTransaction, date);
            setDateTransaction(null);
        } catch {
            // The history modal renders the API error above the movement list.
        } finally {
            setSavingDate(false);
        }
    };

    return <>
        <Modal show={Boolean(account)} onClose={onClose} dismissible={!loading} size="7xl" className="transactions-history-modal wallet-modal">
            <ModalHeader className="wallet-modal__header">
                <span className="wallet-modal__mark"><AccountIcon bankCode={account?.bank_code} /></span>
                <span className="wallet-modal__heading"><span className="wallet-modal__eyebrow">Movimentos da conta</span><span className="wallet-modal__title">{account?.name}</span></span>
            </ModalHeader>
            <ModalBody className="wallet-modal__body transactions-history-modal__body">
                <div className="transactions-history-modal__intro"><div><p className="wallet-modal__kicker">Histórico financeiro</p><p className="wallet-modal__description">Consulte e filtre os movimentos registrados nesta conta.</p><p className="mt-3 text-sm text-orbital-text-secondary">Total filtrado: <strong className="text-base text-orbital-primary-dark">{money(totalAmount)}</strong></p></div></div>
                <div className="transactions-history-modal__filters"><Inputs.Flatpickr name="month" label="Competência" value={filters.month} onChange={(_, dateString) => onFilter('month', dateString)} monthYearOnly /><Inputs.Select name="status" label="Status" value={statusOptions.find((option) => option.value === filters.status) ?? statusOptions[0]} onChange={(option) => onFilter('status', option?.value ?? '')} options={statusOptions} isClearable={false} /><Inputs.Select name="type" label="Tipo" value={typeOptions.find((option) => option.value === filters.type) ?? typeOptions[0]} onChange={(option) => onFilter('type', option?.value ?? '')} options={typeOptions} isClearable={false} /><Inputs.Select name="category_id" label="Categoria" value={categoryOptions.find((option) => option.value === String(filters.category_id)) ?? categoryOptions[0]} onChange={(option) => onFilter('category_id', option?.value ?? '')} options={categoryOptions} isClearable={false} /><Inputs.Checkbox name="include_third_party" label="Incluir despesas de terceiros" value={filters.include_third_party} onChange={(value) => onFilter('include_third_party', value)} /></div>
                {error && <div className="ledger-error mb-4" role="alert">{error}</div>}
                {loading ? <div className="ledger-state" role="status">Carregando movimentos…</div> : transactions.length === 0 ? <div className="ledger-empty rounded-2xl bg-orbital-background"><ReceiptText className="mx-auto h-8 w-8 text-orbital-primary" aria-hidden="true" /><p className="mt-3 font-semibold text-orbital-primary-dark">Nenhum movimento neste período.</p><p className="mt-1 text-sm text-orbital-text-secondary">Tente outra competência ou altere os filtros.</p></div> : <ul className="divide-y divide-orbital-border rounded-2xl border border-orbital-border">{transactions.map((transaction) => {
                    const credit = transaction.effect === 'CREDIT';
                    const movementDate = transaction.is_invoice_summary && transaction.status === 'POSTED' ? transaction.transaction_date : (transaction.due_date ?? transaction.transaction_date);
                    const recurring = transaction.recurring_transaction_id != null;
                    return <li key={transaction.id} className="flex flex-wrap items-center gap-3 p-4 sm:flex-nowrap">
                        <span className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ${credit ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'}`}>{credit ? <ArrowDownLeft className="h-4 w-4" aria-hidden="true" /> : <ArrowUpRight className="h-4 w-4" aria-hidden="true" />}</span>
                        <div className="min-w-0 flex-1"><p className="truncate text-sm font-semibold text-orbital-text-primary">{transaction.description}</p><p className="mt-1 flex flex-wrap items-center gap-1 text-xs text-orbital-text-secondary"><CalendarDays className="h-3 w-3" aria-hidden="true" />{formatDateBR(movementDate)}{recurring && <span className="ml-1 rounded-full bg-orbital-background px-2 py-0.5 font-semibold">Recorrência</span>}</p></div>
                        <div className="ml-auto flex items-center gap-3 sm:ml-0"><div className="text-right"><strong className={`block whitespace-nowrap text-sm ${credit ? 'text-emerald-700' : 'text-rose-700'}`}>{credit ? '+' : '−'} {money(transaction.amount)}</strong><Badge color={transaction.status === 'POSTED' ? 'success' : transaction.status === 'CANCELLED' ? 'gray' : 'warning'}>{transaction.status === 'POSTED' ? 'Realizada' : transaction.status === 'CANCELLED' ? 'Cancelada' : 'Prevista'}</Badge></div>
                            <div className="flex items-center gap-1">{!transaction.is_invoice_summary && canEffectivateTransaction(transaction) && <IconAction content="Efetivar movimento" icon={CircleCheck} onClick={() => onEffectivate(transaction)} />}{!transaction.is_invoice_summary && recurring && <IconAction content="Alterar data" icon={CalendarClock} onClick={() => { setDateTransaction(transaction); setDate(transaction.due_date ?? transaction.transaction_date ?? ''); }} />}{!transaction.is_invoice_summary && !recurring && transaction.canManage !== false && <><IconAction content="Editar movimento" icon={Pencil} onClick={() => onEditTransaction(transaction)} /><IconAction content="Excluir movimento" icon={Trash2} danger onClick={() => onDeleteTransaction(transaction)} /></>}</div>
                        </div>
                    </li>;
                })}</ul>}
            </ModalBody>
            <ModalFooter className="wallet-modal__footer"><Button color="light" className="wallet-modal__cancel" onClick={onClose} disabled={loading}>Fechar</Button></ModalFooter>
        </Modal>
        <Modal show={Boolean(dateTransaction)} onClose={() => !savingDate && setDateTransaction(null)} dismissible={!savingDate} size="md" className="wallet-modal">
            <ModalHeader className="wallet-modal__header"><span className="wallet-modal__mark"><CalendarClock className="h-5 w-5" aria-hidden="true" /></span><span className="wallet-modal__heading"><span className="wallet-modal__eyebrow">Ocorrência recorrente</span><span className="wallet-modal__title">Alterar data</span></span></ModalHeader>
            <form onSubmit={saveDate}><ModalBody className="wallet-modal__body"><p className="wallet-modal__description mb-4">A alteração vale somente para “{dateTransaction?.description}”.</p>{error && <div className="ledger-error mb-4" role="alert">{error}</div>}<label className="block text-sm font-semibold text-orbital-text-primary" htmlFor="recurring-occurrence-date">Data do movimento<input id="recurring-occurrence-date" type="date" required value={date} onChange={(event) => setDate(event.target.value)} className="mt-1 block min-h-11 w-full rounded-lg border border-orbital-border bg-orbital-surface px-3 py-2 text-sm focus:border-orbital-primary focus:ring-2 focus:ring-orbital-primary" /></label></ModalBody><ModalFooter className="wallet-modal__footer"><Button type="button" color="light" onClick={() => setDateTransaction(null)} disabled={savingDate}>Cancelar</Button><Button type="submit" color="blue" disabled={savingDate || !date}>{savingDate ? 'Salvando…' : 'Salvar data'}</Button></ModalFooter></form>
        </Modal>
    </>;
}
