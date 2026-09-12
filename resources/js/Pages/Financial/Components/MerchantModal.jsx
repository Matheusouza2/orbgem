import { Button, Modal, ModalBody, ModalFooter, ModalHeader } from 'flowbite-react';
import Inputs from '@/Components/Inputs';

export default function MerchantModal({ open, onClose, form, onSubmit, submitting, errors }) {
    return <Modal show={open} onClose={onClose} dismissible={!submitting}><ModalHeader>Novo estabelecimento</ModalHeader><form onSubmit={onSubmit}><ModalBody><div className="space-y-4"><p className="text-sm text-slate-600">Salve uma vez para identificar rapidamente onde você gastou.</p><Inputs.ErrorSummary errors={errors} /><Inputs.Validation id="merchant-name" label="Nome" name="name" value={form.data.name} setData={form.setData} errors={errors} placeholder="Ex.: Mercado Boa Hora" required autoFocus /></div></ModalBody><ModalFooter><Button type="submit" color="blue" disabled={submitting}>{submitting ? 'Salvando…' : 'Salvar estabelecimento'}</Button><Button type="button" color="light" onClick={onClose} disabled={submitting}>Cancelar</Button></ModalFooter></form></Modal>;
}
