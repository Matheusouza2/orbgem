import { Button } from 'flowbite-react';
import { CreditCard as CreditCardIcon, Pencil, Plus, ReceiptText } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import CreditCardLogo from './Components/CreditCardIcon';
import CreditCardModal from './Components/CreditCardModal';
import CreditCardTransactionsModal from './Components/CreditCardTransactionsModal';
import CreditCardInvoicePaymentModal from './Components/CreditCardInvoicePaymentModal';
import TransactionModal from './Components/TransactionModal';
import useCreditCards from './Hooks/useCreditCards';
import { shouldShowCreditCardInvoicePaymentAction } from './Hooks/invoiceActions';

const money = (value) => (Number(value || 0) / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

function CreditCardSummary({ card, onPay }) {
    const percentage = Number(card.limit_usage_percentage || 0);
    const barWidth = Math.min(percentage, 100);
    const available = Math.max(0, Number(card.limit || 0) - Number(card.committed_amount ?? card.current_invoice_amount ?? 0));
    const barColor = percentage >= 90 ? 'bg-red-500' : percentage >= 70 ? 'bg-orbital-accent' : 'bg-orbital-primary';

    const invoice = {
        id: card.current_invoice_id,
        status: card.current_invoice_status,
        amount: card.current_invoice_amount,
    };
    const payable = shouldShowCreditCardInvoicePaymentAction(invoice);

    return <div className="mt-4 rounded-xl bg-orbital-background p-3"><div className="mb-3 flex items-center justify-between gap-3 text-xs text-orbital-text-secondary"><span>Fatura anterior</span><strong className="font-semibold text-orbital-text-primary">{money(card.previous_invoice_amount)}</strong></div><div className="flex items-center justify-between gap-3 text-sm"><span className="text-orbital-text-secondary">Fatura atual</span><strong className="text-orbital-primary-dark">{money(card.current_invoice_amount)}</strong></div>{payable && <Button color="blue" size="xs" className="mt-3 w-full" onClick={() => onPay(card)}>Pagar fatura</Button>}<div className="mt-3 flex items-center justify-between gap-3 text-xs text-orbital-text-secondary"><span>Limite comprometido</span><span className="font-semibold text-orbital-text-primary">{percentage.toLocaleString('pt-BR', { maximumFractionDigits: 2 })}%</span></div><div className="mt-2 h-2.5 overflow-hidden rounded-full bg-orbital-border" role="progressbar" aria-label={'Limite comprometido do cartão ' + card.name} aria-valuenow={percentage} aria-valuemin="0" aria-valuemax="100"><div className={'h-full rounded-full transition-all ' + barColor} style={{ width: barWidth + '%' }} /></div><p className="mt-2 text-xs text-orbital-text-secondary">{money(available)} disponíveis</p></div>;
}

function CreditCardEntry({ card, wallet, onEdit, onLaunch, onTransactions, onPay }) {
    return <article className="ledger-panel account-card"><div className="flex items-start justify-between gap-3"><CreditCardLogo institution={card.institution} /><Button color="light" size="xs" onClick={() => onEdit(card)} aria-label={'Editar cartão ' + card.name}><Pencil className="h-4 w-4" /></Button></div><h2 className="mt-5 text-lg font-bold text-orbital-primary-dark">{card.name}</h2><p className="mt-1 text-sm text-orbital-text-secondary">{wallet?.name ?? 'Carteira'}</p>{card.institution && <p className="mt-1 text-xs text-orbital-text-secondary">{card.institution}</p>}<CreditCardSummary card={card} onPay={onPay} /><p className="mt-3 text-xs text-orbital-text-secondary">Fecha dia {card.closing_day} · vence dia {card.due_day}</p><span className={'mt-4 inline-flex rounded-full px-2 py-1 text-[10px] font-bold uppercase tracking-wider ' + (card.active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500')}>{card.active ? 'Ativo' : 'Inativo'}</span><div className="account-card__actions"><span>Registrar compra</span><div className="flex gap-2"><Button color="blue" size="sm" onClick={() => onLaunch(card)} disabled={!card.active}><Plus className="mr-2 h-4 w-4" aria-hidden="true" />Lançar</Button><Button color="light" size="sm" onClick={() => onTransactions(card)}><ReceiptText className="mr-2 h-4 w-4" aria-hidden="true" />Ver</Button></div></div></article>;
}

export default function CreditCards() {
    const cards = useCreditCards();

    return <AppLayout><header className="ledger-header"><div><p className="ledger-eyebrow">Meios de pagamento</p><h1 className="ledger-heading">Cartões de crédito.</h1><p className="mt-3 max-w-xl text-base leading-7 text-orbital-text-secondary">Organize limites, vencimentos e compras parceladas por carteira.</p></div><Button color="blue" onClick={() => cards.openModal()} disabled={cards.wallets.length === 0}><Plus className="mr-2 h-4 w-4" aria-hidden="true" />Novo cartão</Button></header>{Object.keys(cards.errors).length > 0 && <div className="ledger-error" role="alert">{Object.values(cards.errors).flat()[0]}</div>}{cards.loading ? <div className="ledger-panel ledger-state" role="status">Carregando cartões…</div> : cards.cards.length === 0 ? <div className="ledger-panel ledger-empty" role="status"><CreditCardIcon className="mx-auto h-8 w-8 text-orbital-primary" aria-hidden="true" /><p className="mt-3 font-semibold text-orbital-primary-dark">Nenhum cartão cadastrado.</p><p className="mt-1 text-sm text-orbital-text-secondary">Use o botão acima para criar seu primeiro cartão.</p></div> : <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">{cards.cards.map((card) => <CreditCardEntry key={card.id} card={card} wallet={cards.wallets.find((wallet) => wallet.id === card.wallet_id)} onEdit={cards.openModal} onLaunch={cards.openTransactionModal} onTransactions={cards.openTransactions} onPay={cards.openPaymentModal} />)}</div>}<CreditCardModal open={cards.modalOpen} onClose={cards.closeModal} form={cards.form} wallets={cards.wallets} accounts={cards.accounts} onWalletChange={cards.changeWallet} onSubmit={cards.submit} submitting={cards.submitting} errors={cards.errors} editing={cards.editingCard} /><TransactionModal open={cards.transactionModalOpen} onClose={cards.closeTransactionModal} form={cards.transactionForm} accounts={[]} creditCards={cards.selectedCardForTransaction ? [cards.selectedCardForTransaction] : []} categories={cards.transactionCategories} merchants={cards.transactionMerchants} onCreateMerchant={() => {}} onSubmit={cards.submitTransaction} submitting={cards.transactionSubmitting} apiErrors={cards.transactionErrors} /><CreditCardTransactionsModal card={cards.transactionsCard} transactions={cards.cardTransactions} meta={cards.transactionsMeta} categories={cards.transactionCategories} loading={cards.transactionsLoading} error={cards.transactionsError} filters={cards.transactionsFilters} onClose={cards.closeTransactions} onFilter={cards.updateTransactionsFilters} onPage={cards.changeTransactionsPage} onEditInstallment={cards.requestEditInstallment} onEditPurchase={cards.requestEditPurchase} onDeleteInstallment={cards.requestDeleteInstallment} onDeletePurchase={cards.requestDeletePurchase} onEffectivate={cards.requestEffectivateTransaction} /><CreditCardInvoicePaymentModal open={Boolean(cards.paymentInvoice)} onClose={cards.closePaymentModal} invoice={cards.paymentInvoice} accounts={cards.accounts.filter((account) => account.wallet_id === cards.paymentInvoice?.wallet_id)} form={cards.paymentForm} errors={cards.paymentErrors} submitting={cards.paymentSubmitting} onSubmit={cards.submitPayment} /></AppLayout>;
}
