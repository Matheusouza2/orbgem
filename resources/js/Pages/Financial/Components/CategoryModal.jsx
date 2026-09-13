import { Button, Modal, ModalBody, ModalFooter, ModalHeader } from 'flowbite-react';
import { Tag } from 'lucide-react';
import { Suspense } from 'react';
import Inputs from '@/Components/Inputs';
import { FINANCIAL_ICON_OPTIONS } from '@/Components/Inputs/IconPicker';

export default function CategoryModal({ open, onClose, form, categories, selectedWalletId, onSubmit, submitting, errors, editing }) {
    const parentOptions = categories
        .filter((category) => category.type === form.data.type && category.id !== editing?.id)
        .map((category) => ({
            value: category.id,
            label: category.wallet_id === null ? `${category.name} (global)` : category.name,
        }));
    const typeOptions = [
        { value: 'EXPENSE', label: 'Despesa' },
        { value: 'INCOME', label: 'Receita' },
    ];

    const categoryColor = form.data.icon_color || '#123B8F';
    const categoryType = form.data.type === 'INCOME' ? 'Receita' : 'Despesa';
    const PreviewIcon = FINANCIAL_ICON_OPTIONS.find((option) => option.id === form.data.icon)?.Icon ?? Tag;

    return <Modal show={open} onClose={onClose} dismissible={!submitting} size="xl" className="orbital-category-modal wallet-modal">
        <ModalHeader className="wallet-modal__header">
            <span className="wallet-modal__mark"><Tag className="h-5 w-5" aria-hidden="true" /></span>
            <span className="wallet-modal__heading">
                <span className="wallet-modal__eyebrow">{editing ? 'Atualize sua organização' : 'Organização financeira'}</span>
                <span className="wallet-modal__title">{editing ? 'Editar categoria' : 'Nova categoria'}</span>
            </span>
        </ModalHeader>
        <form onSubmit={onSubmit}>
            <ModalBody className="category-modal__body">
                <div className="category-modal__intro">
                    <div>
                        <p className="category-modal__kicker">Dê um nome ao que importa</p>
                        <p className="category-modal__description">Uma categoria clara deixa seu extrato mais fácil de ler e suas decisões mais rápidas.</p>
                    </div>
                    <div className="category-modal__preview" style={{ '--category-color': categoryColor }}>
                        <span className="category-modal__preview-icon"><PreviewIcon className="h-5 w-5" aria-hidden="true" /></span>
                        <span className="category-modal__preview-copy">
                            <span>Prévia</span>
                            <strong>{form.data.name || 'Sua categoria'}</strong>
                            <small>{categoryType}</small>
                        </span>
                    </div>
                </div>
                {!selectedWalletId && <p className="category-modal__notice" role="status">Selecione uma carteira para salvar a categoria.</p>}
                    <Suspense fallback={<div className="h-64" aria-hidden="true" />}>
                        <Inputs.ErrorSummary errors={errors} />
                        <div className="category-modal__fields">
                            <div className="category-modal__field--wide"><Inputs.Validation id="category-name" label="Nome" name="name" value={form.data.name} setData={form.setData} errors={errors} placeholder="Ex.: Alimentação" required autoFocus /></div>
                            <Inputs.Select name="type" label="Tipo" value={typeOptions.find((option) => option.value === form.data.type) ?? null} onChange={(option) => form.setData('type', option?.value ?? '')} errors={errors} options={typeOptions} isClearable={false} required />
                            <Inputs.Select name="parent_id" altName="parent" label="Categoria principal (opcional)" value={form.data.parent} setData={form.setData} errors={errors} options={parentOptions} />
                            <div>
                                <label className="mb-1 block text-xs font-semibold text-orbital-text-primary" htmlFor="category-icon-picker">Ícone</label>
                                <Inputs.IconPicker id="category-icon-picker" value={form.data.icon} onChange={(icon) => form.setData('icon', icon)} />
                            </div>
                            <Inputs.Color id="category-icon-color" label="Cor do ícone" name="icon_color" value={form.data.icon_color} setData={form.setData} errors={errors} />
                        </div>
                    </Suspense>
            </ModalBody>
            <ModalFooter className="category-modal__footer">
                <Button type="submit" color="blue" className="category-modal__submit" disabled={submitting}>{submitting ? 'Salvando…' : editing ? 'Salvar alterações' : 'Salvar categoria'}</Button>
                <Button type="button" color="light" className="category-modal__cancel" onClick={onClose} disabled={submitting}>Cancelar</Button>
            </ModalFooter>
        </form>
    </Modal>;
}
