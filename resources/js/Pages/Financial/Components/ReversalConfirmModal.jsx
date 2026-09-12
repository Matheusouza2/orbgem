import { Button, Modal, ModalBody, ModalFooter, ModalHeader } from 'flowbite-react';
import Inputs from '@/Components/Inputs';

const money = (cents) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100);

export default function ReversalConfirmModal({ transaction, open, onClose, form, onSubmit, submitting, errors }) {
    return <Modal show={open} onClose={onClose} dismissible={!submitting}><ModalHeader>Confirmar estorno</ModalHeader><form onSubmit={onSubmit}><ModalBody><div className="space-y-4"><p className="text-slate-700">Será criado um lançamento inverso para <strong>{transaction?.description}</strong>, no valor de <strong>{transaction ? money(transaction.amount) : ''}</strong>. O original será preservado.</p><Inputs.ErrorSummary errors={errors} /><Inputs.Textarea id="reversal-notes" label="Motivo (opcional)" name="notes" value={form.data.notes} setData={form.setData} errors={errors} rows={3} /></div></ModalBody><ModalFooter><Button type="submit" color="red" disabled={submitting}>{submitting ? 'Estornando…' : 'Confirmar estorno'}</Button><Button type="button" color="light" onClick={onClose} disabled={submitting}>Voltar</Button></ModalFooter></form></Modal>;
}
