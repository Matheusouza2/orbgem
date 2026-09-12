import { Button } from 'flowbite-react';
import { Folder, Pencil, Plus } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import AccountModal from './Components/AccountModal';
import WalletModal from './Components/WalletModal';
import useWallets from './Hooks/useWallets';

export default function Wallets() {
    const wallets = useWallets();

    return <AppLayout>
        <header className="ledger-header">
            <div><p className="ledger-eyebrow">Seu espaço financeiro</p><h1 className="ledger-heading">Suas carteiras.</h1><p className="mt-3 max-w-xl text-base leading-7 text-orbital-text-secondary">Crie e organize os espaços que você usa para acompanhar seu dinheiro.</p></div>
            <Button color="blue" onClick={() => wallets.openModal()}><Plus className="mr-2 h-4 w-4" aria-hidden="true" />Nova carteira</Button>
        </header>
        {Object.keys(wallets.errors).length > 0 && !wallets.modalOpen && <div className="ledger-error" role="alert">{Object.values(wallets.errors).flat()[0]}</div>}
        {wallets.loading ? <div className="ledger-panel ledger-state" role="status">Carregando carteiras…</div> : wallets.wallets.length === 0 ? <div className="ledger-panel ledger-empty" role="status"><Folder className="mx-auto h-8 w-8 text-orbital-primary" aria-hidden="true" /><p className="mt-3 font-semibold text-orbital-primary-dark">Nenhuma carteira criada ainda.</p><p className="mt-1 text-sm text-orbital-text-secondary">Comece criando uma carteira para registrar suas movimentações.</p></div> : <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">{wallets.wallets.map((wallet) => { const walletAccounts = wallets.accounts.filter((account) => account.wallet_id === wallet.id); return <article key={wallet.id} className="ledger-panel ledger-wallet-card"><div className="flex items-start justify-between gap-3"><span className="flex h-12 w-12 items-center justify-center rounded-2xl bg-orbital-primary-light text-orbital-primary"><Folder className="h-6 w-6" aria-hidden="true" /></span><Button color="light" size="xs" onClick={() => wallets.openModal(wallet)} aria-label={`Editar carteira ${wallet.name}`}><Pencil className="h-4 w-4" aria-hidden="true" /></Button></div><h2 className="mt-5 text-lg font-bold text-orbital-primary-dark">{wallet.name}</h2><p className="mt-1 text-sm text-orbital-text-secondary">{walletAccounts.length} {walletAccounts.length === 1 ? 'conta' : 'contas'}</p><div className="mt-4 space-y-2">{walletAccounts.map((account) => <div key={account.id} className="flex items-center justify-between rounded-lg bg-orbital-background px-3 py-2"><span className="truncate text-sm font-medium text-orbital-text-primary">{account.name}</span>{account.is_default && <span className="ml-2 shrink-0 text-[10px] font-bold uppercase tracking-wider text-orbital-primary">Padrão</span>}</div>)}</div><Button color="light" size="sm" className="mt-4 w-full" onClick={() => wallets.openAccountModal(wallet)}><Plus className="mr-2 h-4 w-4" aria-hidden="true" />Nova conta</Button></article>; })}</div>}
        <WalletModal open={wallets.modalOpen} onClose={wallets.closeModal} form={wallets.form} onSubmit={wallets.submit} submitting={wallets.submitting} errors={wallets.errors} editing={wallets.editingWallet} />
        <AccountModal open={wallets.accountModalOpen} onClose={wallets.closeAccountModal} form={wallets.accountForm} wallet={wallets.selectedWalletForAccount} onSubmit={wallets.submitAccount} submitting={wallets.accountSubmitting} errors={wallets.accountErrors} />
    </AppLayout>;
}
