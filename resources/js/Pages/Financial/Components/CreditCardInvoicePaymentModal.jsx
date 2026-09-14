import { Button, Modal, ModalBody, ModalFooter, ModalHeader } from 'flowbite-react';
import { CalendarDays, Check, CreditCard, Landmark } from 'lucide-react';
import Inputs from '@/Components/Inputs';

const today = () => new Date().toISOString().slice(0, 10);

export default function CreditCardInvoicePaymentModal({ open, onClose, invoice, accounts, form, errors, submitting, onSubmit }) {
    const accountOptions = accounts.map((account) => ({ value: String(account.id), label: account.name }));

    return <Modal show={open} onClose={onClose} dismissible={!submitting} size="lg" className="wallet-modal">
        <ModalHeader className="wallet-modal__header"><span className="wallet-modal__mark"><CreditCard className="h-5 w-5" aria-hidden="true" /></span><span className="wallet-modal__heading"><span className="wallet-modal__eyebrow">Fatura do cartão</span><span className="wallet-modal__title">Pagar fatura</span></span></ModalHeader>
        <form onSubmit={onSubmit}><ModalBody className="wallet-modal__body"><div className="space-y-4"><div className="rounded-xl bg-orbital-background p-4"><p className="text-xs font-bold uppercase tracking-wider text-orbital-text-secondary">Fatura {invoice?.reference_month}</p><p className="mt-1 text-lg font-bold text-orbital-primary-dark">Informe como esta fatura foi paga.</p></div><Inputs.ErrorSummary errors={errors} /><Inputs.Select name="account_id" label="Conta de pagamento" value={accountOptions.find((option) => option.value === String(form.data.account_id)) ?? null} onChange={(option) => form.setData('account_id', option?.value ?? '')} errors={errors} options={accountOptions} isClearable={false} required /><Inputs.Number id="invoice-payment-amount" label="Valor pago (R$)" name="amount" value={form.data.amount} setData={form.setData} errors={errors} format="currency" required /><Inputs.Validation id="invoice-payment-date" label="Data do pagamento" name="payment_date" value={form.data.payment_date} setData={form.setData} errors={errors} type="date" max={today()} required /><p className="flex items-center gap-2 text-xs text-orbital-text-secondary"><Landmark className="h-4 w-4" aria-hidden="true" />O valor será debitado da conta selecionada.</p></div></ModalBody><ModalFooter className="wallet-modal__footer"><Button type="button" color="light" onClick={onClose} disabled={submitting}>Cancelar</Button><Button type="submit" color="blue" disabled={submitting || accounts.length === 0 || Number(form.data.amount || 0) <= 0}><Check className="mr-2 h-4 w-4" aria-hidden="true" />{submitting ? 'Registrando…' : 'Confirmar pagamento'}</Button></ModalFooter></form>
    </Modal>;
}
