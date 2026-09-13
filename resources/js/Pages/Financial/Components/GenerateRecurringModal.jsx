import { Button, Modal, ModalBody, ModalFooter, ModalHeader } from 'flowbite-react';
import { RefreshCw } from 'lucide-react';
import Inputs from '@/Components/Inputs';

export default function GenerateRecurringModal({ open, onClose, form, recurrence, onSubmit, submitting, errors }) {
    return <Modal show={open} onClose={onClose} dismissible={!submitting} size="lg" className="generate-recurring-modal wallet-modal">
        <ModalHeader className="wallet-modal__header">
            <span className="wallet-modal__mark"><RefreshCw className="h-5 w-5" aria-hidden="true" /></span>
            <span className="wallet-modal__heading">
                <span className="wallet-modal__eyebrow">Planejamento financeiro</span>
                <span className="wallet-modal__title">Gerar lançamentos</span>
            </span>
        </ModalHeader>
        <form onSubmit={onSubmit}><ModalBody className="wallet-modal__body">
            <div className="wallet-modal__intro"><div><p className="wallet-modal__kicker">Automatize sua rotina</p><p className="wallet-modal__description">Escolha até quando os lançamentos desta recorrência devem ser gerados.</p></div></div>
            <div className="generate-recurring-modal__recurrence"><span>Recorrência selecionada</span><strong>{recurrence?.description}</strong><small>Ocorrências já geradas não serão duplicadas.</small></div>
            <div className="mt-4"><Inputs.Flatpickr name="until" label="Gerar até" value={form.data.until} setData={form.setData} errors={errors} required options={{ static: true }} /></div>
        </ModalBody><ModalFooter className="wallet-modal__footer"><Button type="button" color="light" className="wallet-modal__cancel" onClick={onClose} disabled={submitting}>Cancelar</Button><Button type="submit" color="blue" className="wallet-modal__submit" disabled={submitting}>{submitting ? 'Gerando…' : 'Gerar lançamentos'}</Button></ModalFooter></form>
    </Modal>;
}
