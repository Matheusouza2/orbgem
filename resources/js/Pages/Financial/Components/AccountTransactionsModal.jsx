import { Badge, Button, Modal, ModalBody, ModalFooter, ModalHeader } from 'flowbite-react';
import { ArrowDownLeft, ArrowUpRight, CalendarDays, ChevronLeft, ChevronRight, Pencil, ReceiptText } from 'lucide-react';
import AccountIcon from './AccountIcon';
import { formatDateBR } from '@/Utils/date';
import Inputs from '@/Components/Inputs';
import { Suspense } from 'react';

const money = (cents) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(cents || 0) / 100);
const statusOptions = [{ value: '', label: 'Todos os status' }, { value: 'POSTED', label: 'Realizadas' }, { value: 'PROJECTED', label: 'Previstas' }, { value: 'CANCELLED', label: 'Canceladas' }];

export default function AccountTransactionsModal({ account, transactions, meta, loading, error, filters, onClose, onFilter, onPage, onEditTransaction }) {
    return <Modal show={Boolean(account)} onClose={onClose} dismissible={!loading} size="4xl" className="transactions-history-modal wallet-modal">
        <ModalHeader className="wallet-modal__header">
            <span className="wallet-modal__mark"><ReceiptText className="h-5 w-5" aria-hidden="true" /></span>
            <span className="wallet-modal__heading">
                <span className="wallet-modal__eyebrow">Movimentos da conta</span>
                <span className="wallet-modal__title">{account?.name}</span>
            </span>
        </ModalHeader>
        <ModalBody className="wallet-modal__body transactions-history-modal__body">
            <Suspense fallback={<div className="h-96" aria-hidden="true" />}>
            <div className="transactions-history-modal__intro">
                <div>
                    <p className="wallet-modal__kicker">Histórico financeiro</p>
                    <p className="wallet-modal__description">Consulte e filtre os movimentos registrados nesta conta.</p>
                </div>
            </div>
            <div className="transactions-history-modal__filters"><Inputs.Flatpickr name="month" label="Competência" value={filters.month} onChange={(_, dateString) => onFilter('month', dateString)} monthYearOnly /><Inputs.Select name="status" label="Status" value={statusOptions.find((option) => option.value === filters.status) ?? statusOptions[0]} onChange={(option) => onFilter('status', option?.value ?? '')} options={statusOptions} isClearable={false} /><Inputs.Checkbox name="include_third_party" label="Incluir despesas de terceiros" value={filters.include_third_party} onChange={(value) => onFilter('include_third_party', value)} /></div>
            {error && <div className="ledger-error mb-4" role="alert">{error}</div>}
            {loading ? <div className="ledger-state" role="status">Carregando transações…</div> : transactions.length === 0 ? <div className="ledger-empty rounded-2xl bg-orbital-background"><ReceiptText className="mx-auto h-8 w-8 text-orbital-primary" aria-hidden="true" /><p className="mt-3 font-semibold text-orbital-primary-dark">Nenhuma transação neste período.</p><p className="mt-1 text-sm text-orbital-text-secondary">Tente outra competência ou altere os filtros.</p></div> : <ul className="divide-y divide-orbital-border rounded-2xl border border-orbital-border">{transactions.map((transaction) => { const credit = transaction.effect === 'CREDIT'; return <li key={transaction.id} className="flex items-center gap-3 p-4"><span className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-xl ${credit ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'}`}>{credit ? <ArrowDownLeft className="h-4 w-4" aria-hidden="true" /> : <ArrowUpRight className="h-4 w-4" aria-hidden="true" />}</span><div className="min-w-0 flex-1"><p className="truncate text-sm font-semibold text-orbital-text-primary">{transaction.description}</p><p className="mt-1 flex flex-wrap items-center gap-1 text-xs text-orbital-text-secondary"><CalendarDays className="h-3 w-3" aria-hidden="true" />{formatDateBR(transaction.transaction_date)}</p></div><div className="text-right"><strong className={`block whitespace-nowrap text-sm ${credit ? 'text-emerald-700' : 'text-rose-700'}`}>{credit ? '+' : '-'} {money(transaction.amount)}</strong><Badge color={transaction.status === 'POSTED' ? 'success' : transaction.status === 'CANCELLED' ? 'gray' : 'warning'}>{transaction.status === 'POSTED' ? 'Realizada' : transaction.status === 'CANCELLED' ? 'Cancelada' : 'Prevista'}</Badge>{transaction.canManage !== false && <Button color="light" size="xs" className="mt-2" onClick={() => onEditTransaction(transaction)}><Pencil className="mr-1 h-3 w-3" aria-hidden="true" />Editar</Button>}</div></li>; })}</ul>}
            {meta && meta.last_page > 1 && <div className="mt-4 flex items-center justify-between text-sm text-orbital-text-secondary"><span>{meta.from}–{meta.to} de {meta.total}</span><div className="flex gap-2"><Button color="light" size="xs" disabled={meta.current_page <= 1 || loading} onClick={() => onPage(meta.current_page - 1)}><ChevronLeft className="h-4 w-4" aria-hidden="true" /></Button><span className="flex items-center px-2 font-semibold">{meta.current_page}/{meta.last_page}</span><Button color="light" size="xs" disabled={meta.current_page >= meta.last_page || loading} onClick={() => onPage(meta.current_page + 1)}><ChevronRight className="h-4 w-4" aria-hidden="true" /></Button></div></div>}
        </Suspense></ModalBody>
        <ModalFooter className="wallet-modal__footer"><Button color="light" className="wallet-modal__cancel" onClick={onClose} disabled={loading}>Fechar</Button></ModalFooter>
    </Modal>;
}
