import { Button } from 'flowbite-react';
import { Plus } from 'lucide-react';
import { Suspense } from 'react';
import Inputs from '@/Components/Inputs';
import AppLayout from '@/Layouts/AppLayout';
import CategoryList from './Components/CategoryList';
import CategoryModal from './Components/CategoryModal';
import useCategories from './Hooks/useCategories';

export default function Categories() {
    const categories = useCategories();

    return <AppLayout>
            <header className="ledger-header">
                <div><p className="ledger-eyebrow">Organização</p><h1 className="ledger-heading">Tudo no lugar certo.</h1><p className="mt-3 max-w-xl text-base leading-7 text-orbital-text-secondary">Separe entradas e despesas para entender melhor cada decisão da sua carteira.</p></div>
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <Suspense fallback={<div className="h-[74px] min-w-52" aria-hidden="true" />}>
                        <Inputs.Select name="wallet_id" label="Carteira" value={categories.wallets.map((wallet) => ({ value: String(wallet.id), label: wallet.name })).find((wallet) => wallet.value === String(categories.selectedWalletId)) ?? null} onChange={(option) => categories.selectWallet(option?.value ?? '')} options={categories.wallets.map((wallet) => ({ value: String(wallet.id), label: wallet.name }))} isClearable={false} isDisabled={categories.loading} className="min-w-52" />
                    </Suspense>
                    <Button color="blue" onClick={categories.openCreateModal}><Plus className="mr-2 h-4 w-4" aria-hidden="true" />Nova categoria</Button>
                </div>
            </header>
            {categories.error && <div className="ledger-error" role="alert">{categories.error}</div>}
            {!categories.selectedWalletId && !categories.loading && <div className="ledger-panel mb-5" role="status">Você ainda não possui uma carteira disponível.</div>}
            {categories.selectedWalletId && <CategoryList categories={categories.categories} loading={categories.loading} onEdit={categories.openEditModal} />}
            <CategoryModal open={categories.modalOpen} onClose={categories.closeCreateModal} form={categories.form} categories={categories.categories} selectedWalletId={categories.selectedWalletId} onSubmit={categories.submitCategory} submitting={categories.submitting} errors={categories.categoryErrors} editing={categories.editingCategory} />
    </AppLayout>;
}
