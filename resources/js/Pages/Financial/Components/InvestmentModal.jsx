import { Button, Modal, ModalBody, ModalFooter, ModalHeader } from 'flowbite-react';
import { TrendingUp } from 'lucide-react';
import { Suspense } from 'react';
import Inputs from '@/Components/Inputs';

const typeOptions = [
    { value: 'STOCK', label: 'Ação' },
    { value: 'FUND', label: 'Fundo' },
    { value: 'FII', label: 'FII' },
    { value: 'FIXED_INCOME', label: 'Renda fixa' },
    { value: 'CRYPTO', label: 'Cripto' },
    { value: 'OTHER', label: 'Outro' },
];

export default function InvestmentModal({ open, onClose, form, wallets, onSubmit, submitting, errors, editing, onLookupQuote, quoteLoading, quoteMessage }) {
    return <Modal show={open} onClose={onClose} dismissible={!submitting} size="xl" className="wallet-modal">
        <ModalHeader><span className="flex items-center gap-3"><TrendingUp className="h-5 w-5 text-orbital-primary" aria-hidden="true" />{editing ? 'Editar investimento' : 'Novo investimento'}</span></ModalHeader>
        <form onSubmit={onSubmit}><ModalBody><Suspense fallback={<div className="h-80" aria-hidden="true" />}>
            <Inputs.ErrorSummary errors={errors} />
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="sm:col-span-2"><Inputs.Validation name="name" label="Nome do investimento" value={form.data.name} setData={form.setData} errors={errors} placeholder="Ex.: Tesouro Selic 2029" required autoFocus /></div>
                <div><Inputs.Validation name="ticker" label="Ticker ou código (opcional)" value={form.data.ticker} setData={form.setData} errors={errors} placeholder="Ex.: PETR4 ou MXRF11" onBlur={() => onLookupQuote()} /><p className="mt-1 text-xs text-orbital-text-secondary" role="status">{quoteLoading ? 'Buscando cotação…' : quoteMessage || 'A cotação é buscada ao sair do campo.'}</p></div>
                <Inputs.Select name="type" label="Tipo" value={typeOptions.find((option) => option.value === form.data.type) ?? null} onChange={(option) => form.setData('type', option?.value ?? '')} errors={errors} options={typeOptions} isClearable={false} required />
                <Inputs.Validation name="institution" label="Instituição ou corretora" value={form.data.institution} setData={form.setData} errors={errors} placeholder="Ex.: XP Investimentos" />
                <Inputs.Select name="wallet_id" label="Carteira" value={wallets.map((wallet) => ({ value: wallet.id, label: wallet.name })).find((option) => String(option.value) === String(form.data.wallet_id)) ?? null} onChange={(option) => form.setData('wallet_id', option?.value ?? '')} errors={errors} options={wallets.map((wallet) => ({ value: wallet.id, label: wallet.name }))} isClearable={false} required />
                <Inputs.Number name="quantity" label="Quantidade" value={form.data.quantity} setData={form.setData} errors={errors} format="decimal" maxDigits={8} required />
                <Inputs.Number name="average_price" label="Preço médio" value={form.data.average_price} setData={form.setData} errors={errors} required />
                <Inputs.Number name="invested_amount" label="Valor investido" value={form.data.invested_amount} setData={form.setData} errors={errors} required />
                <Inputs.Number name="current_value" label="Valor atual" value={form.data.current_value} setData={form.setData} errors={errors} required />
                <Inputs.Flatpickr name="acquired_at" label="Data da posição (opcional)" value={form.data.acquired_at} setData={form.setData} errors={errors} clearable />
                <Inputs.Checkbox name="active" label="Investimento ativo" value={form.data.active} setData={form.setData} errors={errors} />
                <div className="sm:col-span-2 rounded-xl border border-orbital-border bg-orbital-background p-4"><Inputs.Checkbox name="cdi_linked" label="Vincular rendimento ao CDI" value={form.data.cdi_linked} setData={form.setData} errors={errors} /><p className="mt-1 text-xs leading-5 text-orbital-text-secondary">O valor será atualizado diariamente usando a taxa CDI disponível.</p>{form.data.cdi_linked && <div className="mt-4 max-w-xs"><Inputs.Number name="cdi_percentage" label="Percentual do CDI (%)" value={form.data.cdi_percentage} setData={form.setData} errors={errors} format="decimal" maxDigits={4} required /><p className="mt-1 text-xs text-orbital-text-secondary">Ex.: 100, 102 ou 110.</p></div>}</div>
            </div>
        </Suspense></ModalBody><ModalFooter><Button type="button" color="light" onClick={onClose} disabled={submitting}>Cancelar</Button><Button type="submit" color="blue" disabled={submitting}>{submitting ? 'Salvando…' : 'Salvar investimento'}</Button></ModalFooter></form>
    </Modal>;
}
