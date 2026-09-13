import { Button, Modal, ModalBody, ModalFooter, ModalHeader } from 'flowbite-react';
import { Target } from 'lucide-react';
import { Suspense } from 'react';
import Inputs from '@/Components/Inputs';

export default function GoalModal({ open, onClose, form, onSubmit, submitting, errors, editing, wallets }) {
    const walletOptions = wallets.map((wallet) => ({ value: wallet.id, label: wallet.name }));

    return <Modal show={open} onClose={onClose} dismissible={!submitting} size="lg" className="wallet-modal">
        <ModalHeader className="wallet-modal__header">
            <span className="wallet-modal__mark"><Target className="h-5 w-5" aria-hidden="true" /></span>
            <span className="wallet-modal__heading">
                <span className="wallet-modal__eyebrow">{editing ? 'Atualize seu planejamento' : 'Planejamento financeiro'}</span>
                <span className="wallet-modal__title">{editing ? 'Editar meta' : 'Nova meta'}</span>
            </span>
        </ModalHeader>
        <form onSubmit={onSubmit}><ModalBody className="wallet-modal__body"><div className="wallet-modal__intro"><div><p className="wallet-modal__kicker">Dê direção ao seu dinheiro</p><p className="wallet-modal__description">Defina um objetivo e acompanhe sua evolução até ele.</p></div></div><Suspense fallback={<div className="h-48" aria-hidden="true" />}>
            <Inputs.ErrorSummary errors={errors} />
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="sm:col-span-2"><Inputs.Validation name="name" label="Nome da meta" value={form.data.name} setData={form.setData} errors={errors} placeholder="Ex.: Reserva de emergência" required autoFocus /></div>
                <Inputs.Number name="target_amount" label="Quanto você quer alcançar" value={form.data.target_amount} setData={form.setData} errors={errors} format="currency" required />
                <Inputs.Flatpickr name="deadline" label="Prazo (opcional)" value={form.data.deadline} setData={form.setData} errors={errors} clearable />
                <div className="sm:col-span-2"><Inputs.Select name="wallet_id" label="Carteira" value={walletOptions.find((option) => String(option.value) === String(form.data.wallet_id)) ?? null} onChange={(option) => form.setData('wallet_id', option?.value ?? '')} errors={errors} options={walletOptions} isClearable={false} required /></div>
            </div>
        </Suspense></ModalBody><ModalFooter className="wallet-modal__footer"><Button type="button" color="light" onClick={onClose} disabled={submitting}>Cancelar</Button><Button type="submit" color="blue" disabled={submitting}>{submitting ? 'Salvando…' : 'Salvar meta'}</Button></ModalFooter></form>
    </Modal>;
}
