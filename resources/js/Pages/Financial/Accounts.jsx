import { Button } from 'flowbite-react';
import { Landmark, Pencil, Plus } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import AccountIcon from './Components/AccountIcon';
import AccountModal from './Components/AccountModal';
import useWallets from './Hooks/useWallets';

const accountTypeLabels = {
    CHECKING: 'Conta corrente',
    SAVINGS: 'Poupança',
    CASH: 'Dinheiro em espécie',
    INVESTMENT: 'Investimentos',
    DIGITAL_WALLET: 'Carteira digital',
};

export default function Accounts() {
    const accounts = useWallets();

    return <AppLayout>
        <header className="ledger-header">
            <div><p className="ledger-eyebrow">Organização financeira</p><h1 className="ledger-heading">Suas contas.</h1><p className="mt-3 max-w-xl text-base leading-7 text-orbital-text-secondary">Veja onde seu dinheiro está e mantenha cada conta ligada à carteira certa.</p></div>
            <Button color="blue" onClick={() => accounts.openAccountModal()} disabled={accounts.wallets.length === 0}><Plus className="mr-2 h-4 w-4" aria-hidden="true" />Nova conta</Button>
        </header>
        {Object.keys(accounts.errors).length > 0 && <div className="ledger-error" role="alert">{Object.values(accounts.errors).flat()[0]}</div>}
        {accounts.loading ? <div className="ledger-panel ledger-state" role="status">Carregando contas…</div> : accounts.accounts.length === 0 ? <div className="ledger-panel ledger-empty" role="status"><Landmark className="mx-auto h-8 w-8 text-orbital-primary" aria-hidden="true" /><p className="mt-3 font-semibold text-orbital-primary-dark">Nenhuma conta criada ainda.</p><p className="mt-1 text-sm text-orbital-text-secondary">Use o botão acima para criar uma conta.</p></div> : <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">{accounts.accounts.map((account) => { const wallet = accounts.wallets.find((item) => item.id === account.wallet_id); return <article key={account.id} className="ledger-panel"><div className="flex items-start justify-between gap-3"><AccountIcon bankCode={account.bank_code} />{account.is_default && <span className="rounded-full bg-orbital-primary-light px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-orbital-primary">Padrão</span>}</div><div className="mt-5 flex items-start justify-between gap-3"><div className="min-w-0"><h2 className="truncate text-lg font-bold text-orbital-primary-dark">{account.name}</h2><p className="mt-1 text-sm text-orbital-text-secondary">{wallet?.name ?? 'Carteira'} · {accountTypeLabels[account.type] ?? account.type}</p>{account.account_number && <p className="mt-3 text-sm text-orbital-text-secondary">Conta {account.account_number}</p>}</div><Button color="light" size="xs" onClick={() => accounts.openEditAccountModal(account)} aria-label={`Editar conta ${account.name}`}><Pencil className="h-4 w-4" aria-hidden="true" /></Button></div></article>; })}</div>}
        <AccountModal open={accounts.accountModalOpen} onClose={accounts.closeAccountModal} form={accounts.accountForm} wallet={accounts.selectedWalletForAccount} wallets={accounts.wallets} onWalletChange={accounts.selectWalletForAccount} onSubmit={accounts.submitAccount} submitting={accounts.accountSubmitting} errors={accounts.accountErrors} editing={accounts.editingAccount} />
    </AppLayout>;
}
