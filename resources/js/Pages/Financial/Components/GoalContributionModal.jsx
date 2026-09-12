import { Button, Modal, ModalBody, ModalFooter, ModalHeader } from 'flowbite-react';
import { CircleDollarSign } from 'lucide-react';
import { Suspense } from 'react';
import Inputs from '@/Components/Inputs';

export default function GoalContributionModal({ open, onClose, form, goal, onSubmit, submitting, errors }) {
    return <Modal show={open} onClose={onClose} dismissible={!submitting} size="lg">
        <ModalHeader><span className="flex items-center gap-3"><CircleDollarSign className="h-5 w-5 text-orbital-accent-dark" aria-hidden="true" />Registrar aporte</span></ModalHeader>
        <form onSubmit={onSubmit}><ModalBody><Suspense fallback={<div className="h-36" aria-hidden="true" />}>
            <p className="mb-5 rounded-xl bg-orbital-primary-light px-4 py-3 text-sm text-orbital-primary-dark">Aporte para <strong>{goal?.name}</strong></p>
            <div className="grid gap-4 sm:grid-cols-2"><Inputs.Number name="amount" label="Valor do aporte" value={form.data.amount} setData={form.setData} errors={errors} format="currency" required /><Inputs.Flatpickr name="contributed_at" label="Data" value={form.data.contributed_at} setData={form.setData} errors={errors} /><div className="sm:col-span-2"><Inputs.Textarea name="note" label="Observação (opcional)" value={form.data.note} setData={form.setData} errors={errors} rows={3} /></div></div>
            <Inputs.ErrorSummary errors={errors} />
        </Suspense></ModalBody><ModalFooter><Button type="button" color="light" onClick={onClose} disabled={submitting}>Cancelar</Button><Button type="submit" color="blue" disabled={submitting}>{submitting ? 'Registrando…' : 'Registrar aporte'}</Button></ModalFooter></form>
    </Modal>;
}
