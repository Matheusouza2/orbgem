import { Badge, Button, Modal, ModalBody, ModalFooter, ModalHeader } from 'flowbite-react';
import { ArrowDownLeft, ArrowUpRight, Check, CircleAlert, FileSearch, FileUp, Landmark, RefreshCw, WalletCards } from 'lucide-react';
import Inputs from '@/Components/Inputs';
import { formatDateBR } from '@/Utils/date';

const money = (cents) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(cents || 0) / 100);
const statuses = {
    MATCHED: { label: 'Conciliado', color: 'success' },
    DATE_DIVERGENCE: { label: 'Data diferente', color: 'warning' },
    AMOUNT_DIVERGENCE: { label: 'Valor diferente', color: 'warning' },
    ONLY_IN_STATEMENT: { label: 'Só no extrato', color: 'purple' },
    ONLY_IN_SYSTEM: { label: 'Só no OrbGem', color: 'gray' },
    PROJECTED_IN_SYSTEM: { label: 'Previsto no OrbGem', color: 'warning' },
    NOT_POSTED_IN_SYSTEM: { label: 'Ainda não efetivado', color: 'warning' },
    DUPLICATE_IN_STATEMENT: { label: 'Duplicado no extrato', color: 'failure' },
    DUPLICATE_IN_SYSTEM: { label: 'Possível duplicado', color: 'failure' },
};
const warningCopy = {
    ACCOUNT_NUMBER_MISMATCH: 'O número de conta do arquivo difere da conta selecionada.',
    PERIOD_MISMATCH: 'O período do arquivo difere do mês selecionado.',
    CLOSING_BALANCE_MISMATCH: 'O saldo final informado não coincide com o último saldo do extrato.',
    CREDITS_TOTAL_MISMATCH: 'A soma das entradas do extrato difere do total informado no cabeçalho.',
    DEBITS_TOTAL_MISMATCH: 'A soma das saídas do extrato difere do total informado no cabeçalho.',
};
const flagCopy = { RUNNING_BALANCE_MISMATCH: 'Saldo após lançamento não confere com o movimento anterior.' };

export default function BankReconciliationModal({ open, onClose, accounts, state }) {
    const accountOptions = accounts.map((account) => ({
        value: String(account.id),
        label: `${account.name} · ${account.wallet_name ?? 'Carteira'}`,
    }));
    const counts = state.preview?.counts ?? {};
    const entries = state.preview?.entries ?? [];
    const visibleEntries = state.filter === 'ALL' ? entries : entries.filter((entry) => entry.match_status === state.filter);
    const eligibleCount = entries.filter((entry) => entry.can_import).length;
    const hasAccountOrPeriodWarning = (state.preview?.warnings ?? []).some((warning) => ['ACCOUNT_NUMBER_MISMATCH', 'PERIOD_MISMATCH'].includes(warning));
    const summaryItems = [
        ['Conciliados', counts.MATCHED ?? 0, 'text-emerald-700'],
        ['Divergências', (counts.DATE_DIVERGENCE ?? 0) + (counts.AMOUNT_DIVERGENCE ?? 0), 'text-amber-700'],
        ['Só no extrato', counts.ONLY_IN_STATEMENT ?? 0, 'text-orbital-primary'],
        ['Só no OrbGem', counts.ONLY_IN_SYSTEM ?? 0, 'text-orbital-text-secondary'],
    ];

    return <Modal show={open} onClose={onClose} dismissible={!state.loading && !state.importing} size="7xl" className="bank-reconciliation-modal wallet-modal">
        <ModalHeader className="wallet-modal__header">
            <span className="wallet-modal__mark"><Landmark className="h-5 w-5" aria-hidden="true" /></span>
            <span className="wallet-modal__heading"><span className="wallet-modal__eyebrow">Contas · Conciliação bancária</span><span className="wallet-modal__title">{state.reviewing ? 'Revise o extrato' : 'Compare com seu extrato'}</span></span>
        </ModalHeader>
        {!state.reviewing ? <form onSubmit={state.createPreview}>
            <ModalBody className="wallet-modal__body">
                <div className="wallet-modal__intro"><div><p className="wallet-modal__kicker">Confira antes de importar</p><p className="wallet-modal__description">Vamos comparar cada lançamento do arquivo com os movimentos efetivados na conta.</p></div></div>
                <div className="bank-reconciliation-upload">
                    <label className="block text-sm font-semibold text-orbital-text-primary" htmlFor="reconciliation-month">Mês do extrato<input id="reconciliation-month" type="month" required value={state.month} onChange={(event) => state.setMonth(event.target.value)} className="mt-1 block min-h-11 w-full rounded-lg border border-orbital-border bg-orbital-surface px-3 py-2 text-sm focus:border-orbital-primary focus:ring-2 focus:ring-orbital-primary" /></label>
                    <Inputs.Select name="account_id" label="Conta" value={accountOptions.find((option) => option.value === String(state.accountId)) ?? null} onChange={(option) => state.setAccountId(option?.value ?? '')} options={accountOptions} isClearable={false} placeholder="Selecione a conta" required />
                    <label className="bank-reconciliation-file" htmlFor="reconciliation-file"><span className="bank-reconciliation-file__icon"><FileUp className="h-5 w-5" aria-hidden="true" /></span><span><strong>Arquivo do extrato</strong><small>{state.file ? state.file.name : 'PDF com texto selecionável ou arquivo OFX · até 20 MB'}</small></span><input id="reconciliation-file" type="file" required accept=".pdf,.ofx,application/pdf,application/x-ofx" onChange={(event) => state.setFile(event.target.files?.[0] ?? null)} /></label>
                </div>
                {state.errors.general && <div className="ledger-error mt-4" role="alert">{state.errors.general}</div>}
                {state.errors.account_id && <div className="ledger-error mt-4" role="alert">{state.errors.account_id[0]}</div>}
                {state.errors.month && <div className="ledger-error mt-4" role="alert">{state.errors.month[0]}</div>}
                {state.errors.file && <div className="ledger-error mt-4" role="alert">{state.errors.file[0]}</div>}
                <p className="mt-4 flex items-start gap-2 text-xs leading-5 text-orbital-text-secondary"><CircleAlert className="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />O arquivo é analisado para esta conferência e não fica armazenado. A prévia guarda as linhas necessárias para revisar e importar.</p>
            </ModalBody>
            <ModalFooter className="wallet-modal__footer"><Button type="button" color="light" onClick={onClose} disabled={state.loading}>Cancelar</Button><Button type="submit" color="blue" disabled={state.loading || !state.file || !state.accountId}>{state.loading ? <><RefreshCw className="mr-2 h-4 w-4 animate-spin" aria-hidden="true" />Lendo extrato…</> : <><FileSearch className="mr-2 h-4 w-4" aria-hidden="true" />Analisar extrato</>}</Button></ModalFooter>
        </form> : <>
            <ModalBody className="wallet-modal__body bank-reconciliation-modal__body">
                <div className="bank-reconciliation-summary">
                    <div className="bank-reconciliation-balance"><span>Saldo inicial</span><strong>{state.preview?.opening_balance == null ? '—' : money(state.preview.opening_balance)}</strong><ArrowDownLeft className="h-4 w-4" aria-hidden="true" /><span>Saldo final</span><strong>{state.preview?.closing_balance == null ? '—' : money(state.preview.closing_balance)}</strong></div>
                    {summaryItems.map(([label, count, color]) => <div key={label} className="bank-reconciliation-summary__item"><span>{label}</span><strong className={color}>{count}</strong></div>)}
                </div>
                <div className="bank-reconciliation-meta"><div><p className="wallet-modal__kicker">{state.preview?.account?.name} · {state.preview?.month}</p><p className="wallet-modal__description">{state.preview?.original_name} · {state.preview?.statement_row_count} itens no extrato · {state.preview?.imported_count} importados</p></div><Badge color="info">{state.preview?.file_type?.toUpperCase()}</Badge></div>
                {(state.preview?.warnings ?? []).length > 0 && <div className="bank-reconciliation-warnings" role="status"><strong><CircleAlert className="h-4 w-4" aria-hidden="true" />Confira os dados do extrato</strong>{state.preview.warnings.map((warning) => <p key={warning}>{warningCopy[warning] ?? warning}</p>)}</div>}
                {hasAccountOrPeriodWarning && <label className="bank-reconciliation-ack"><input type="checkbox" checked={state.acknowledgeWarnings} onChange={(event) => state.setAcknowledgeWarnings(event.target.checked)} /><span>Confirmei a conta e o mês. Quero importar mesmo assim.</span></label>}
                {state.errors.general && <div className="ledger-error" role="alert">{state.errors.general}</div>}
                {state.errors.entry_ids && <div className="ledger-error" role="alert">{state.errors.entry_ids[0]}</div>}
                {state.errors.acknowledge_warnings && <div className="ledger-error" role="alert">{state.errors.acknowledge_warnings[0]}</div>}
                {state.message && <div className="bank-reconciliation-success" role="status"><Check className="h-4 w-4" aria-hidden="true" />{state.message}</div>}
                <div className="bank-reconciliation-controls"><label className="block text-sm font-semibold text-orbital-text-primary" htmlFor="reconciliation-filter">Mostrar<select id="reconciliation-filter" value={state.filter} onChange={(event) => state.setFilter(event.target.value)} className="mt-1 block min-h-10 w-full rounded-lg border border-orbital-border bg-orbital-surface px-3 py-2 text-sm focus:border-orbital-primary focus:ring-2 focus:ring-orbital-primary"><option value="ALL">Todos os resultados</option>{Object.entries(statuses).map(([value, status]) => <option key={value} value={value}>{status.label} ({counts[value] ?? 0})</option>)}</select></label><p>{state.selectedEntryIds.length} de {eligibleCount} lançamentos selecionados para importar</p></div>
                <ul className="bank-reconciliation-entries" aria-label="Resultado da conciliação">
                    {visibleEntries.map((entry) => {
                        const status = statuses[entry.match_status] ?? { label: entry.match_status, color: 'gray' };
                        const amountIsCredit = entry.signed_amount > 0;
                        return <li key={entry.id} className="bank-reconciliation-entry">
                            <div className="bank-reconciliation-entry__select">{entry.can_import && <input type="checkbox" aria-label={`Importar ${entry.description}`} checked={state.selectedEntryIds.includes(entry.id)} onChange={() => state.toggleEntry(entry.id)} />}</div>
                            <span className={`bank-reconciliation-entry__direction ${amountIsCredit ? 'is-credit' : 'is-debit'}`}>{amountIsCredit ? <ArrowDownLeft className="h-4 w-4" aria-hidden="true" /> : <ArrowUpRight className="h-4 w-4" aria-hidden="true" />}</span>
                            <div className="bank-reconciliation-entry__content"><div className="bank-reconciliation-entry__heading"><strong>{entry.description}</strong><Badge color={status.color}>{status.label}</Badge></div><p>{formatDateBR(entry.transaction_date)}{entry.external_id && <span> · Operação {entry.external_id}</span>}</p>
                                {entry.suggested_transaction && <small>Candidato: {entry.suggested_transaction.description} · {formatDateBR(entry.suggested_transaction.transaction_date)} · {money(entry.suggested_transaction.signed_amount)}</small>}
                                {(entry.flags ?? []).map((flag) => <small className="bank-reconciliation-entry__flag" key={flag}>{flagCopy[flag] ?? flag}</small>)}
                            </div><strong className={`bank-reconciliation-entry__amount ${amountIsCredit ? 'is-credit' : 'is-debit'}`}>{money(entry.signed_amount)}</strong>
                        </li>;
                    })}
                    {visibleEntries.length === 0 && <li className="bank-reconciliation-empty"><WalletCards className="h-7 w-7" aria-hidden="true" /><span>Nenhum resultado nesta classificação.</span></li>}
                </ul>
            </ModalBody>
            <ModalFooter className="wallet-modal__footer"><Button type="button" color="light" onClick={state.returnToUpload} disabled={state.importing}>Trocar arquivo</Button><Button type="button" color="blue" onClick={state.importSelected} disabled={state.importing || state.selectedEntryIds.length === 0 || (hasAccountOrPeriodWarning && !state.acknowledgeWarnings)}>{state.importing ? 'Importando…' : `Importar ${state.selectedEntryIds.length} lançamento${state.selectedEntryIds.length === 1 ? '' : 's'}`}</Button></ModalFooter>
        </>}
    </Modal>;
}
