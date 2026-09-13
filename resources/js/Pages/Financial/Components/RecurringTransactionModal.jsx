import { Button, Modal, ModalBody, ModalFooter, ModalHeader } from 'flowbite-react';
import { CalendarClock } from 'lucide-react';
import { Suspense } from 'react';
import Inputs from '@/Components/Inputs';

const typeOptions = [{ value: 'EXPENSE', label: 'Despesa' }, { value: 'INCOME', label: 'Receita' }];
const frequencyOptions = [{ value: 'WEEKLY', label: 'Semanal' }, { value: 'MONTHLY', label: 'Mensal' }, { value: 'YEARLY', label: 'Anual' }];

export default function RecurringTransactionModal({ open, onClose, form, accounts, categories, onSubmit, submitting, errors, editing }) {
    const accountOptions = accounts.map((account) => ({ value: String(account.id), label: account.name }));
    const categoryOptions = categories.filter((category) => category.type === form.data.type).map((category) => ({ value: String(category.id), label: category.name }));
    const errorMap = Object.fromEntries(Object.entries({ ...form.errors, ...errors }).map(([key, value]) => [key, Array.isArray(value) ? value[0] : value]));

    return <Modal show={open} onClose={onClose} dismissible={!submitting} size="lg" className="wallet-modal">
        <ModalHeader className="wallet-modal__header">
            <span className="wallet-modal__mark"><CalendarClock className="h-5 w-5" aria-hidden="true" /></span>
            <span className="wallet-modal__heading">
                <span className="wallet-modal__eyebrow">{editing ? 'Atualize seu planejamento' : 'Planejamento financeiro'}</span>
                <span className="wallet-modal__title">{editing ? 'Editar recorrência' : 'Nova recorrência'}</span>
            </span>
        </ModalHeader>
        <form onSubmit={onSubmit}>
            <ModalBody className="wallet-modal__body"><div className="wallet-modal__intro"><div><p className="wallet-modal__kicker">Automatize o que se repete</p><p className="wallet-modal__description">Mantenha seus lançamentos recorrentes organizados e previsíveis.</p></div></div><Suspense fallback={<div className="h-72" aria-hidden="true" />}><div className="grid gap-4 sm:grid-cols-2"><div className="sm:col-span-2"><Inputs.ErrorSummary errors={errorMap} /></div><Inputs.Validation name="description" label="Descrição" value={form.data.description} setData={form.setData} errors={errorMap} placeholder="Ex.: Aluguel" required autoFocus /><Inputs.Number name="amount" label="Valor (R$)" value={form.data.amount} setData={form.setData} errors={errorMap} format="currency" required /><Inputs.Select name="type" label="Tipo" value={typeOptions.find((option) => option.value === form.data.type) ?? null} onChange={(option) => form.setData((current) => ({ ...current, type: option?.value ?? 'EXPENSE', category_id: null }))} options={typeOptions} isClearable={false} required /><Inputs.Select name="frequency" label="Frequência" value={frequencyOptions.find((option) => option.value === form.data.frequency) ?? null} onChange={(option) => form.setData('frequency', option?.value ?? 'MONTHLY')} options={frequencyOptions} isClearable={false} required /><Inputs.Select name="account_id" label="Conta (opcional)" value={accountOptions.find((option) => option.value === String(form.data.account_id)) ?? null} onChange={(option) => form.setData('account_id', option?.value ?? '')} options={accountOptions} /><Inputs.Select name="category_id" label="Categoria (opcional)" value={categoryOptions.find((option) => option.value === String(form.data.category_id)) ?? null} onChange={(option) => form.setData('category_id', option?.value ?? '')} options={categoryOptions} /><Inputs.Flatpickr name="start_date" label="Começa em" value={form.data.start_date} setData={form.setData} errors={errorMap} required /><Inputs.Flatpickr name="end_date" label="Termina em (opcional)" value={form.data.end_date ?? ''} setData={form.setData} errors={errorMap} clearable /><Inputs.Validation name="due_day" label="Dia de vencimento" value={form.data.due_day ?? ''} setData={form.setData} errors={errorMap} type="number" min="1" max="31" placeholder="1 a 31" /><div className="flex items-end"><Inputs.Checkbox name="auto_create" label="Criar já efetivada" value={form.data.auto_create} setData={form.setData} errors={errorMap} /></div><div className="sm:col-span-2"><Inputs.Checkbox name="active" label="Recorrência ativa" value={form.data.active} setData={form.setData} errors={errorMap} /></div></div></Suspense></ModalBody>
            <ModalFooter className="wallet-modal__footer"><Button type="button" color="light" onClick={onClose} disabled={submitting}>Cancelar</Button><Button type="submit" color="blue" disabled={submitting}>{submitting ? 'Salvando…' : editing ? 'Salvar alterações' : 'Criar recorrência'}</Button></ModalFooter>
        </form>
    </Modal>;
}
