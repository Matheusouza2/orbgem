import { Button, Modal, ModalBody, ModalFooter, ModalHeader } from 'flowbite-react';
import { Landmark } from 'lucide-react';
import { banks, logoCdnUrl } from 'logos-bancos-br';
import { Suspense, useState } from 'react';
import Inputs from '@/Components/Inputs';

const bankOptions = banks().map((bank) => ({ value: bank.ispb, label: bank.name, code: bank.ispb, logo: logoCdnUrl(bank.ispb) }));

function BankOption({ option }) {
    const [showLogo, setShowLogo] = useState(Boolean(option.logo));

    return <span className="flex items-center gap-3">
        <span className="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-orbital-primary-light text-orbital-primary" aria-hidden="true">
            {showLogo ? <img src={option.logo} alt="" className="h-full w-full object-contain" onError={() => setShowLogo(false)} /> : <Landmark className="h-4 w-4" />}
        </span>
        <span className="min-w-0">
            <span className="block truncate font-medium">{option.label}</span>
            <span className="block text-xs text-orbital-text-secondary">ISPB {option.code}</span>
        </span>
    </span>;
}

export default function AccountModal({ open, onClose, form, wallet, wallets = [], onWalletChange, onSubmit, submitting, errors, editing }) {
    const typeOptions = [
        { value: 'CHECKING', label: 'Conta corrente' },
        { value: 'SAVINGS', label: 'Poupança' },
        { value: 'CASH', label: 'Dinheiro em espécie' },
        { value: 'INVESTMENT', label: 'Investimentos' },
        { value: 'DIGITAL_WALLET', label: 'Carteira digital' },
    ];

    return <Modal show={open} onClose={onClose} dismissible={!submitting} size="lg" className="wallet-modal">
        <ModalHeader className="wallet-modal__header">
            <span className="wallet-modal__mark"><Landmark className="h-5 w-5" aria-hidden="true" /></span>
            <span className="wallet-modal__heading">
                <span className="wallet-modal__eyebrow">{wallet?.name ?? 'Sua carteira'}</span>
                <span className="wallet-modal__title">{editing ? 'Editar conta' : 'Nova conta'}</span>
            </span>
        </ModalHeader>
        <form onSubmit={onSubmit}>
            <ModalBody className="wallet-modal__body">
                <div className="wallet-modal__intro">
                    <div>
                        <p className="wallet-modal__kicker">Uma conta para acompanhar de perto</p>
                        <p className="wallet-modal__description">Informe onde o dinheiro está para seus lançamentos ficarem organizados.</p>
                    </div>
                </div>
                <div className="wallet-modal__fields">
                    <Suspense fallback={<div className="h-64" aria-hidden="true" />}>
                        <Inputs.ErrorSummary errors={errors} />
                        {wallets.length > 1 && <Inputs.Select name="wallet_id" label="Carteira" value={wallet ? { value: String(wallet.id), label: wallet.name } : null} onChange={(option) => onWalletChange(wallets.find((item) => String(item.id) === String(option?.value)))} options={wallets.map((item) => ({ value: String(item.id), label: item.name }))} isClearable={false} />}
                        <div className="wallet-modal__field--wide">
                            <Inputs.Validation id="account-name" label="Nome da conta" name="name" value={form.data.name} setData={form.setData} errors={errors} placeholder="Ex.: Conta do dia a dia" required autoFocus />
                        </div>
                        <Inputs.Number id="account-initial-balance" label="Saldo inicial" name="initial_balance" value={form.data.initial_balance} setData={form.setData} errors={errors} />
                        <Inputs.Select name="type" label="Tipo de conta" value={typeOptions.find((option) => option.value === form.data.type) ?? typeOptions[0]} onChange={(option) => form.setData('type', option?.value ?? 'CHECKING')} errors={errors} options={typeOptions} isClearable={false} />
                        <div className="wallet-modal__field--wide">
                            <Inputs.Select name="bank_code" label="Banco (opcional)" value={bankOptions.find((option) => option.value === form.data.bank_code) ?? null} onChange={(option) => form.setData('bank_code', option?.value ?? '')} errors={errors} options={bankOptions} formatOptionLabel={(option) => <BankOption option={option} />} filterOption={(candidate, inputValue) => `${candidate.label} ${candidate.data.code}`.toLowerCase().includes(inputValue.toLowerCase())} placeholder="Selecione o banco ou instituição" />
                        </div>
                        <div className="wallet-modal__field--wide">
                            <Inputs.Validation id="account-number" label="Número da conta (opcional)" name="account_number" value={form.data.account_number} setData={form.setData} errors={errors} placeholder="Ex.: 12345-6" />
                        </div>
                        <div className="wallet-modal__preferences wallet-modal__field--wide">
                            <div className="wallet-modal__section-label"><span>Preferências da conta</span><small>Você pode alterar isso depois.</small></div>
                            <div className="wallet-modal__preference-grid">
                                <Inputs.Checkbox name="is_default" label="Conta padrão" value={form.data.is_default} setData={form.setData} errors={errors} />
                                <Inputs.Checkbox name="show_in_dashboard" label="Exibir no painel" value={form.data.show_in_dashboard} setData={form.setData} errors={errors} />
                                <Inputs.Checkbox name="ignore_in_totals" label="Ignorar nos totais" value={form.data.ignore_in_totals} setData={form.setData} errors={errors} />
                            </div>
                        </div>
                    </Suspense>
                </div>
            </ModalBody>
            <ModalFooter className="wallet-modal__footer">
                <Button type="button" color="light" className="wallet-modal__cancel" onClick={onClose} disabled={submitting}>Cancelar</Button>
                <Button type="submit" color="blue" className="wallet-modal__submit" disabled={submitting}>{submitting ? 'Salvando…' : editing ? 'Salvar alterações' : 'Criar conta'}</Button>
            </ModalFooter>
        </form>
    </Modal>;
}
