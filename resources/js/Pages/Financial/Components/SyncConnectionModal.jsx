import { Suspense } from 'react';
import { Button, Modal, ModalBody, ModalFooter, ModalHeader } from 'flowbite-react';
import Inputs from '@/Components/Inputs';

const periodOptions = [
    { value: '30d', label: 'Últimos 30 dias' },
    { value: '6m', label: 'Últimos 6 meses' },
    { value: '1y', label: 'Último ano' },
    { value: 'custom', label: 'Período personalizado' },
];

export default function SyncConnectionModal({ open, onClose, form, onSubmit, submitting, errors }) {
    return <Modal show={open} onClose={onClose} dismissible={!submitting} size="md">
        <ModalHeader>Sincronizar dados</ModalHeader>
        <form onSubmit={onSubmit}>
            <ModalBody>
                <p className="mb-5 text-sm leading-6 text-orbital-text-secondary">Escolha o período que deseja atualizar. A sincronização será processada em segundo plano.</p>
                <Suspense fallback={<div className="h-24" aria-hidden="true" />}>
                    <Inputs.Select name="period" label="Período" value={periodOptions.find((option) => option.value === form.period)} onChange={(option) => form.setPeriod(option?.value ?? '1y')} options={periodOptions} isClearable={false} required />
                    {form.period === 'custom' && <div className="mt-4 grid gap-4 sm:grid-cols-2"><Inputs.Flatpickr name="from" label="Data inicial" value={form.from} onChange={(_, value) => form.setFrom(value)} errors={errors} required /><Inputs.Flatpickr name="to" label="Data final" value={form.to} onChange={(_, value) => form.setTo(value)} errors={errors} required /></div>}
                </Suspense>
                {Object.keys(errors).length > 0 && <div className="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700" role="alert">{Object.values(errors).flat()[0]}</div>}
            </ModalBody>
            <ModalFooter><Button type="button" color="light" onClick={onClose} disabled={submitting}>Cancelar</Button><Button type="submit" color="blue" disabled={submitting}>{submitting ? 'Solicitando…' : 'Sincronizar agora'}</Button></ModalFooter>
        </form>
    </Modal>;
}
