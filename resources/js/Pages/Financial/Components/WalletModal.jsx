import { Button, Modal, ModalBody, ModalFooter, ModalHeader } from 'flowbite-react';
import { Folder } from 'lucide-react';
import { Suspense } from 'react';
import Inputs from '@/Components/Inputs';

export default function WalletModal({ open, onClose, form, onSubmit, submitting, errors, editing }) {
    return <Modal show={open} onClose={onClose} dismissible={!submitting} size="lg" className="wallet-modal">
        <ModalHeader className="wallet-modal__header">
            <span className="wallet-modal__mark"><Folder className="h-5 w-5" aria-hidden="true" /></span>
            <span className="wallet-modal__heading">
                <span className="wallet-modal__eyebrow">{editing ? 'Atualize seu espaço financeiro' : 'Novo espaço financeiro'}</span>
                <span className="wallet-modal__title">{editing ? 'Editar carteira' : 'Nova carteira'}</span>
            </span>
        </ModalHeader>
        <form onSubmit={onSubmit}>
            <ModalBody className="wallet-modal__body">
                <div className="wallet-modal__intro">
                    <div>
                        <p className="wallet-modal__kicker">Um espaço para suas decisões</p>
                        <p className="wallet-modal__description">Agrupe contas, categorias e movimentações em um só lugar.</p>
                    </div>
                </div>
                <div className="wallet-modal__fields">
                    <Suspense fallback={<div className="h-[74px]" aria-hidden="true" />}>
                        <Inputs.ErrorSummary errors={errors} />
                        <div className="wallet-modal__field--wide">
                            <Inputs.Validation id="wallet-name" label="Nome da carteira" name="name" value={form.data.name} setData={form.setData} errors={errors} placeholder="Ex.: Conta do dia a dia" required autoFocus />
                        </div>
                    </Suspense>
                </div>
            </ModalBody>
            <ModalFooter className="wallet-modal__footer">
                <Button type="button" color="light" className="wallet-modal__cancel" onClick={onClose} disabled={submitting}>Cancelar</Button>
                <Button type="submit" color="blue" className="wallet-modal__submit" disabled={submitting}>{submitting ? (editing ? 'Salvando…' : 'Criando…') : (editing ? 'Salvar alterações' : 'Criar carteira')}</Button>
            </ModalFooter>
        </form>
    </Modal>;
}
