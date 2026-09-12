import { Button, Modal, ModalBody, ModalFooter, ModalHeader } from 'flowbite-react';
import Inputs from '@/Components/Inputs';

export default function GenerateRecurringModal({ open, onClose, form, recurrence, onSubmit, submitting, errors }) {
    return <Modal show={open} onClose={onClose} dismissible={!submitting}><ModalHeader>Gerar lançamentos</ModalHeader><form onSubmit={onSubmit}><ModalBody><p className="mb-4 text-sm leading-6 text-orbital-text-secondary">Gere os lançamentos de “{recurrence?.description}” até a data escolhida. Ocorrências já geradas não serão duplicadas.</p><Inputs.Flatpickr name="until" label="Gerar até" value={form.data.until} setData={form.setData} errors={errors} required /></ModalBody><ModalFooter><Button type="button" color="light" onClick={onClose} disabled={submitting}>Cancelar</Button><Button type="submit" color="blue" disabled={submitting}>{submitting ? 'Gerando…' : 'Gerar lançamentos'}</Button></ModalFooter></form></Modal>;
}
