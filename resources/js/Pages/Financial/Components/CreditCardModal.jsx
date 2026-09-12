import { Button, Modal, ModalBody, ModalFooter, ModalHeader } from 'flowbite-react';
import { CreditCard as CreditCardIcon, Landmark } from 'lucide-react';
import { banks, logoCdnUrl } from 'logos-bancos-br';
import { Suspense } from 'react';
import { useState } from 'react';
import Inputs from '@/Components/Inputs';

const bankOptions = banks().map((bank) => ({ value: bank.name, label: bank.name, code: bank.ispb, logo: logoCdnUrl(bank.ispb) }));

function BankOption({ option }) {
    const [showLogo, setShowLogo] = useState(Boolean(option.logo));

    return <span className="flex items-center gap-3">
        <span className="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-orbital-primary-light text-orbital-primary" aria-hidden="true">
            {showLogo ? <img src={option.logo} alt="" className="h-full w-full object-contain" onError={() => setShowLogo(false)} /> : <Landmark className="h-4 w-4" />}
        </span>
        <span className="min-w-0"><span className="block truncate font-medium">{option.label}</span><span className="block text-xs text-orbital-text-secondary">ISPB {option.code}</span></span>
    </span>;
}

export default function CreditCardModal({ open, onClose, form, wallets, accounts, onWalletChange, onSubmit, submitting, errors, editing }) {
    const walletAccounts = accounts.filter((account) => account.wallet_id === form.data.wallet_id);

    return <Modal show={open} onClose={onClose} dismissible={!submitting} size="lg" className="wallet-modal">
        <ModalHeader className="wallet-modal__header"><span className="wallet-modal__mark"><CreditCardIcon className="h-5 w-5" aria-hidden="true" /></span><span className="wallet-modal__heading"><span className="wallet-modal__eyebrow">Cartão de crédito</span><span className="wallet-modal__title">{editing ? 'Editar cartão' : 'Novo cartão'}</span></span></ModalHeader>
        <form onSubmit={onSubmit}><ModalBody className="wallet-modal__body"><div className="wallet-modal__fields"><Suspense fallback={<div className="h-64" aria-hidden="true" />}><Inputs.ErrorSummary errors={errors} />
            {wallets.length > 1 && <Inputs.Select name="wallet_id" label="Carteira" value={wallets.map((wallet) => ({ value: String(wallet.id), label: wallet.name })).find((option) => option.value === String(form.data.wallet_id)) ?? null} onChange={(option) => onWalletChange(option?.value)} options={wallets.map((wallet) => ({ value: String(wallet.id), label: wallet.name }))} isClearable={false} />}
            <div className="wallet-modal__field--wide"><Inputs.Validation id="credit-card-name" label="Nome do cartão" name="name" value={form.data.name} setData={form.setData} errors={errors} placeholder="Ex.: Cartão principal" required autoFocus /></div>
            <div className="wallet-modal__field--wide"><Inputs.Select name="institution" label="Instituição (opcional)" value={bankOptions.find((option) => option.value === form.data.institution) ?? null} onChange={(option) => form.setData('institution', option?.value ?? '')} errors={errors} options={bankOptions} formatOptionLabel={(option) => <BankOption option={option} />} filterOption={(candidate, inputValue) => `${candidate.label} ${candidate.data.code}`.toLowerCase().includes(inputValue.toLowerCase())} placeholder="Selecione o banco ou instituição" /></div>
            <Inputs.Number id="credit-card-limit" label="Limite" name="limit" value={form.data.limit} setData={form.setData} errors={errors} />
            <Inputs.Select name="account_id" label="Conta de pagamento (opcional)" value={walletAccounts.map((account) => ({ value: String(account.id), label: account.name })).find((option) => option.value === String(form.data.account_id)) ?? null} onChange={(option) => form.setData('account_id', option?.value ?? '')} options={walletAccounts.map((account) => ({ value: String(account.id), label: account.name }))} />
            <Inputs.Validation id="credit-card-closing-day" label="Dia de fechamento" name="closing_day" value={form.data.closing_day} setData={form.setData} errors={errors} type="number" min="1" max="31" required />
            <Inputs.Validation id="credit-card-due-day" label="Dia de vencimento" name="due_day" value={form.data.due_day} setData={form.setData} errors={errors} type="number" min="1" max="31" required />
            <Inputs.Checkbox name="active" label="Cartão ativo" value={form.data.active} setData={form.setData} errors={errors} />
        </Suspense></div></ModalBody><ModalFooter className="wallet-modal__footer"><Button type="button" color="light" onClick={onClose} disabled={submitting}>Cancelar</Button><Button type="submit" color="blue" disabled={submitting}>{submitting ? 'Salvando…' : editing ? 'Salvar alterações' : 'Criar cartão'}</Button></ModalFooter></form>
    </Modal>;
}
