import { Button, Modal, ModalBody, ModalFooter, ModalHeader } from 'flowbite-react';
import { CircleDollarSign } from 'lucide-react';
import { Suspense } from 'react';
import Inputs from '@/Components/Inputs';

export default function InvestmentIncomeModal({ open, onClose, investments, form, errors, submitting, onSubmit }) {
    const options = investments.map((item) => ({ value: item.id, label: item.ticker ? `${item.ticker} · ${item.name}` : item.name }));

    return <Modal show={open} onClose={onClose} dismissible={!submitting} size="lg" className="wallet-modal">
        <ModalHeader className="wallet-modal__header"><span className="wallet-modal__mark"><CircleDollarSign className="h-5 w-5" aria-hidden="true" /></span><span className="wallet-modal__heading"><span className="wallet-modal__eyebrow">Fluxo recebido</span><span className="wallet-modal__title">Lançar rendimento</span></span></ModalHeader>
        <form onSubmit={onSubmit}><ModalBody className="wallet-modal__body"><div className="wallet-modal__intro"><div><p className="wallet-modal__kicker">Registre um recebimento</p><p className="wallet-modal__description">Informe o investimento, a data e o valor recebido para acompanhar seus proventos.</p></div></div><Suspense fallback={<div className="h-56" aria-hidden="true" />}><Inputs.ErrorSummary errors={errors} /><div className="space-y-4"><Inputs.Select name="investment_id" label="Investimento" value={options.find((option) => String(option.value) === String(form.data.investment_id)) ?? null} onChange={(option) => form.setData('investment_id', option?.value ?? '')} errors={errors} options={options} isClearable={false} required /><Inputs.Number name="amount" label="Valor recebido (R$)" value={form.data.amount} setData={form.setData} errors={errors} format="currency" required /><Inputs.Flatpickr name="transaction_date" label="Data do recebimento" value={form.data.transaction_date} setData={form.setData} errors={errors} required /></div></Suspense></ModalBody><ModalFooter className="wallet-modal__footer"><Button type="button" color="light" onClick={onClose} disabled={submitting}>Cancelar</Button><Button type="submit" color="blue" disabled={submitting || !form.data.investment_id}>{submitting ? 'Salvando…' : 'Salvar rendimento'}</Button></ModalFooter></form>
    </Modal>;
}
