import { Button, Modal, ModalBody, ModalFooter, ModalHeader } from 'flowbite-react';
import { CircleDollarSign } from 'lucide-react';
import { Suspense } from 'react';
import Inputs from '@/Components/Inputs';

export default function GoalContributionModal({ open, onClose, form, goal, onSubmit, submitting, errors }) {
    return <Modal show={open} onClose={onClose} dismissible={!submitting} size="lg" className="goal-contribution-modal wallet-modal">
        <ModalHeader className="wallet-modal__header">
            <span className="wallet-modal__mark"><CircleDollarSign className="h-5 w-5" aria-hidden="true" /></span>
            <span className="wallet-modal__heading">
                <span className="wallet-modal__eyebrow">Planejamento financeiro</span>
                <span className="wallet-modal__title">Registrar aporte</span>
            </span>
        </ModalHeader>
        <form onSubmit={onSubmit}><ModalBody className="wallet-modal__body"><Suspense fallback={<div className="h-36" aria-hidden="true" />}>
            <div className="wallet-modal__intro"><div><p className="wallet-modal__kicker">Construindo sua meta</p><p className="wallet-modal__description">Registre um novo aporte e acompanhe seu progresso.</p></div></div>
            <div className="wallet-modal__fields">
                <div className="goal-contribution-modal__goal wallet-modal__field--wide"><span>Meta selecionada</span><strong>{goal?.name}</strong></div>
                <Inputs.Number name="amount" label="Valor do aporte" value={form.data.amount} setData={form.setData} errors={errors} format="currency" required />
                <Inputs.Flatpickr name="contributed_at" label="Data" value={form.data.contributed_at} setData={form.setData} errors={errors} />
                <div className="wallet-modal__field--wide"><Inputs.Textarea name="note" label="Observação (opcional)" value={form.data.note} setData={form.setData} errors={errors} rows={3} /></div>
                <div className="wallet-modal__field--wide"><Inputs.ErrorSummary errors={errors} /></div>
            </div>
        </Suspense></ModalBody><ModalFooter className="wallet-modal__footer"><Button type="button" color="light" className="wallet-modal__cancel" onClick={onClose} disabled={submitting}>Cancelar</Button><Button type="submit" color="blue" className="wallet-modal__submit" disabled={submitting}>{submitting ? 'Registrando…' : 'Registrar aporte'}</Button></ModalFooter></form>
    </Modal>;
}
