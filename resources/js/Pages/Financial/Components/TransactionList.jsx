import { Badge, Button } from 'flowbite-react';
import { ArrowDownLeft, ArrowUpRight, Repeat2 } from 'lucide-react';
import { formatDateBR } from '@/Utils/date';

const money = (cents) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100);
const transactionType = { INCOME: 'Entrada', EXPENSE: 'Saída', TRANSFER: 'Transferência' };

export default function TransactionList({ transactions, loading, onRequestReversal, title = 'Todos os lançamentos' }) {
    return <section className="ledger-panel transactions-list" aria-labelledby="transactions-title">
        <div className="mb-6 flex items-end justify-between gap-4"><div><p className="ledger-eyebrow">Linha do tempo</p><h2 id="transactions-title" className="ledger-title">{title}</h2></div><span className="transactions-count">{transactions.length.toString().padStart(2, '0')} registros</span></div>
        {loading ? <p className="ledger-state" aria-live="polite">Atualizando lançamentos…</p> : transactions.length === 0 ? <p className="ledger-state">Nenhuma movimentação neste período. Registre uma entrada ou saída para começar.</p> : <ul className="transactions-list__items">{transactions.map((transaction) => { const credit = transaction.effect === 'CREDIT'; const transfer = transaction.type === 'TRANSFER'; const Icon = transfer ? Repeat2 : credit ? ArrowDownLeft : ArrowUpRight; return <li key={transaction.id} className={`transaction-row ${credit ? 'transaction-row--credit' : 'transaction-row--debit'}`}><span className="transaction-row__icon"><Icon className="h-5 w-5" aria-hidden="true" /></span><div className="min-w-0 flex-1"><div className="flex flex-wrap items-center gap-2"><p className="truncate font-semibold text-orbital-text-primary">{transaction.description}</p><Badge color={transaction.status === 'POSTED' ? 'success' : 'warning'}>{transaction.status === 'POSTED' ? 'Realizado' : 'Previsto'}</Badge></div><p className="mt-1 text-xs text-orbital-text-secondary">{transactionType[transaction.type] ?? transaction.type} · {formatDateBR(transaction.transaction_date)}</p></div><div className="flex items-center gap-3"><strong className={`whitespace-nowrap ${credit ? 'text-emerald-700' : transaction.effect === 'DEBIT' ? 'text-rose-700' : 'text-slate-600'}`}>{credit ? '+' : transaction.effect === 'DEBIT' ? '-' : ''} {money(transaction.amount)}</strong>{transaction.canReverse && <Button size="xs" color="light" onClick={() => onRequestReversal(transaction)} aria-label={`Estornar ${transaction.description}`}>Estornar</Button>}</div></li>; })}</ul>}
    </section>;
}
