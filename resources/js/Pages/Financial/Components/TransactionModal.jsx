import { Button, Modal, ModalBody, ModalFooter, ModalHeader } from 'flowbite-react';
import { ArrowDownLeft, ArrowUpRight, CalendarClock, Check, ChevronLeft, ChevronRight, CreditCard, Landmark, ReceiptText } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Suspense } from 'react';
import Inputs from '@/Components/Inputs';

const typeOptions = [{ value: 'INCOME', label: 'Entrada' }, { value: 'EXPENSE', label: 'Despesa' }];
const statusOptions = [{ value: 'POSTED', label: 'Paga' }, { value: 'PROJECTED', label: 'Previsão' }];
const recurrenceOptions = [{ value: 'NONE', label: 'Não recorrente' }, { value: 'FIXED_MONTHLY', label: 'Fixa mensal' }, { value: 'INSTALLMENT', label: 'Parcelada' }];
const periodicityOptions = [{ value: 'MONTHLY', label: 'Mensal' }, { value: 'BIMONTHLY', label: 'Bimestral' }, { value: 'QUARTERLY', label: 'Trimestral' }, { value: 'YEARLY', label: 'Anual' }];
const today = () => new Date().toISOString().slice(0, 10);

function ChoiceCard({ active, variant, icon: Icon, label, description, onClick }) {
    return <button type="button" className={`transaction-choice transaction-choice--${variant} ${active ? 'transaction-choice--active' : ''}`} onClick={onClick} aria-pressed={active}><span className="transaction-choice__icon"><Icon aria-hidden="true" /></span><span><strong>{label}</strong><small>{description}</small></span>{active && <Check className="transaction-choice__check" aria-hidden="true" />}</button>;
}

export default function TransactionModal({ open, onClose, form, accounts, creditCards = [], categories = [], merchants = [], onSubmit, onCreateMerchant, submitting, apiErrors }) {
    const [step, setStep] = useState(1);
    const [stepError, setStepError] = useState('');
    const errors = Object.fromEntries(Object.entries({ ...form.errors, ...apiErrors }).map(([key, value]) => [key, Array.isArray(value) ? value[0] : value]));
    const accountOptions = accounts.map((account) => ({ value: String(account.id), label: account.name }));
    const creditCardOptions = creditCards.filter((card) => card.active).map((card) => ({ value: String(card.id), label: card.name }));
    const categoryOptions = categories.filter((category) => category.type === form.data.type).map((category) => ({ value: String(category.id), label: category.name }));
    const merchantOptions = merchants.map((merchant) => ({ value: String(merchant.id), label: merchant.name }));
    const installment = form.data.recurrence_type === 'INSTALLMENT';
    const creditCardSelected = form.data.financial_instrument_type === 'CREDIT_CARD';
    const instrumentOptions = [{ value: 'ACCOUNT', label: 'Conta' }, { value: 'CREDIT_CARD', label: 'Cartão de crédito' }];

    useEffect(() => {
        if (open) {
            setStep(1);
            setStepError('');
        }
    }, [open]);

    const continueToPlanning = (event) => {
        event?.preventDefault();

        if (!form.data.description || !form.data.amount || (!creditCardSelected && !form.data.account_id) || (creditCardSelected && !form.data.credit_card_id)) {
            setStepError('Preencha descrição, valor e o meio onde o lançamento será registrado.');
            return;
        }
        setStepError('');
        setStep(2);
    };

    const handleSubmit = (event) => {
        event.preventDefault();

        if (step === 1) {
            continueToPlanning();
            return;
        }

        onSubmit(event);
    };

    return <Modal show={open} onClose={onClose} dismissible={!submitting} size="lg" className="transaction-modal wallet-modal"><ModalHeader className="wallet-modal__header"><span className="wallet-modal__mark"><ReceiptText className="h-5 w-5" aria-hidden="true" /></span><span className="wallet-modal__heading"><span className="wallet-modal__eyebrow">Movimento financeiro</span><span className="wallet-modal__title">Novo lançamento</span></span></ModalHeader><form onSubmit={handleSubmit}><ModalBody className="transaction-modal__body"><Suspense fallback={<div className="h-96" aria-hidden="true" />}><Inputs.ErrorSummary errors={errors} />{stepError && <p className="transaction-modal__step-error" role="alert">{stepError}</p>}{step === 1 ? <div className="transaction-modal__phase"><div className="transaction-modal__phase-heading"><span className="transaction-modal__phase-number">01</span><div><p className="transaction-modal__phase-kicker">O essencial</p><h2>O que aconteceu?</h2><p>Registre o movimento em poucos segundos.</p></div></div><div className="transaction-choice-grid"><ChoiceCard variant="income" active={form.data.type === 'INCOME'} icon={ArrowDownLeft} label="Entrada" description="Dinheiro que chegou" onClick={() => form.setData((current) => ({ ...current, type: 'INCOME', effect: 'CREDIT', category_id: null, financial_instrument_type: 'ACCOUNT', credit_card_id: null }))} /><ChoiceCard variant="expense" active={form.data.type === 'EXPENSE'} icon={ArrowUpRight} label="Despesa" description="Dinheiro que saiu" onClick={() => form.setData((current) => ({ ...current, type: 'EXPENSE', effect: 'DEBIT', category_id: null }))} /></div><div className="transaction-modal__primary-field"><Inputs.Validation id="transaction-description" label="Descrição" name="description" value={form.data.description} setData={form.setData} errors={errors} placeholder={form.data.type === 'INCOME' ? 'Ex.: salário, aluguel recebido…' : 'Ex.: mercado, assinatura…'} required autoFocus /><Inputs.Number id="transaction-amount" label="Valor (R$)" name="amount" value={form.data.amount} setData={form.setData} errors={errors} format="currency" required /></div><div><p className="transaction-modal__section-label">Onde registrar?</p><div className="transaction-choice-grid"><ChoiceCard active={!creditCardSelected} icon={Landmark} label="Conta" description="Movimento no saldo" onClick={() => form.setData((current) => ({ ...current, financial_instrument_type: 'ACCOUNT', account_id: current.account_id || accounts[0]?.id || '', credit_card_id: null, status: current.type === 'INCOME' ? current.status : 'POSTED' }))} /><ChoiceCard active={creditCardSelected} icon={CreditCard} label="Cartão de crédito" description="Compra na fatura" onClick={() => form.setData((current) => ({ ...current, type: 'EXPENSE', effect: 'DEBIT', financial_instrument_type: 'CREDIT_CARD', account_id: null, credit_card_id: current.credit_card_id || creditCards.find((card) => card.active)?.id || '', status: 'PROJECTED' }))} /></div></div><div><Inputs.Select name={creditCardSelected ? 'credit_card_id' : 'account_id'} label={creditCardSelected ? 'Cartão selecionado' : 'Conta selecionada'} value={(creditCardSelected ? creditCardOptions : accountOptions).find((option) => option.value === String(creditCardSelected ? form.data.credit_card_id : form.data.account_id)) ?? null} onChange={(option) => form.setData(creditCardSelected ? 'credit_card_id' : 'account_id', option?.value ?? '')} errors={errors} options={creditCardSelected ? creditCardOptions : accountOptions} isClearable={false} required /></div></div> : <div className="transaction-modal__phase"><div className="transaction-modal__phase-heading"><span className="transaction-modal__phase-number">02</span><div><p className="transaction-modal__phase-kicker">Planejamento</p><h2>Como este movimento acontece?</h2><p>Deixe o futuro organizado sem poluir o lançamento.</p></div></div><div className="transaction-modal__planning-grid"><Inputs.Validation id="transaction-due-date" label="Data de vencimento" name="due_date" value={form.data.due_date ?? ''} setData={form.setData} errors={errors} type="date" required /><Inputs.Select name="status" label="Situação" value={statusOptions.find((option) => option.value === form.data.status) ?? statusOptions[0]} onChange={(option) => form.setData((current) => ({ ...current, status: option?.value ?? 'PROJECTED', paid_at: option?.value === 'POSTED' ? `${today()} 00:00:00` : null, auto_post_on_due_date: option?.value === 'POSTED' ? false : current.auto_post_on_due_date }))} options={statusOptions} isClearable={false} required /></div><div className="transaction-modal__planning-section"><p className="transaction-modal__section-label"><CalendarClock aria-hidden="true" />Recorrência</p><Inputs.Select name="recurrence_type" label="Como se repete?" value={recurrenceOptions.find((option) => option.value === form.data.recurrence_type) ?? recurrenceOptions[0]} onChange={(option) => form.setData((current) => ({ ...current, recurrence_type: option?.value ?? 'NONE', installment_initial: null, installment_count: null, installment_periodicity: null }))} options={recurrenceOptions} isClearable={false} required />{installment && <div className="transaction-installment-grid"><Inputs.Validation id="installment-initial" label="Parcela inicial" name="installment_initial" value={form.data.installment_initial ?? ''} setData={form.setData} errors={errors} type="number" min="1" required /><Inputs.Validation id="installment-count" label="Quantidade" name="installment_count" value={form.data.installment_count ?? ''} setData={form.setData} errors={errors} type="number" min="1" required /><Inputs.Select name="installment_periodicity" label="Periodicidade" value={periodicityOptions.find((option) => option.value === form.data.installment_periodicity) ?? null} onChange={(option) => form.setData('installment_periodicity', option?.value ?? '')} errors={errors} options={periodicityOptions} isClearable={false} required /></div>}</div><div className="transaction-modal__detail-grid"><Inputs.Select name="category_id" label="Categoria" value={categoryOptions.find((option) => option.value === String(form.data.category_id)) ?? null} onChange={(option) => form.setData('category_id', option?.value ?? '')} errors={errors} options={categoryOptions} isClearable={false} required /><Inputs.Select name="merchant_id" label="Estabelecimento (opcional)" value={merchantOptions.find((option) => option.value === String(form.data.merchant_id)) ?? null} onChange={(option) => form.setData('merchant_id', option?.value ?? '')} errors={errors} options={merchantOptions} placeholder="Sem estabelecimento" /></div>{onCreateMerchant && <Button type="button" color="light" onClick={onCreateMerchant} className="w-full">Novo estabelecimento</Button>}{!creditCardSelected && form.data.status === 'PROJECTED' && <div className="transaction-modal__auto-post"><Inputs.Checkbox name="auto_post_on_due_date" label="Efetivar automaticamente no vencimento" value={form.data.auto_post_on_due_date} setData={form.setData} errors={errors} /></div>}{form.data.type === 'EXPENSE' && <div className="transaction-modal__third-party"><Inputs.Checkbox name="is_third_party" label="Despesa de terceiro" value={form.data.is_third_party} setData={form.setData} errors={errors} /></div>}<Inputs.Textarea id="transaction-notes" label="Observação (opcional)" name="notes" value={form.data.notes} setData={form.setData} errors={errors} rows={3} /></div>}</Suspense></ModalBody><ModalFooter className="transaction-modal__footer">{step === 1 ? <><Button type="button" color="light" onClick={onClose} disabled={submitting}>Cancelar</Button><Button type="button" color="blue" onClick={continueToPlanning}><span>Continuar</span><ChevronRight className="ml-2 h-4 w-4" aria-hidden="true" /></Button></> : <><Button type="button" color="light" onClick={() => setStep(1)} disabled={submitting}><ChevronLeft className="mr-2 h-4 w-4" aria-hidden="true" />Voltar</Button><Button type="submit" color="blue" disabled={submitting || (accounts.length === 0 && creditCardOptions.length === 0)}>{submitting ? 'Salvando…' : 'Salvar lançamento'}</Button></>}</ModalFooter></form></Modal>;
}
