import { Button } from 'flowbite-react';
import { Check, CircleDollarSign, Pencil, Plus, Target, Trash2 } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import GoalContributionModal from './Components/GoalContributionModal';
import GoalModal from './Components/GoalModal';
import useGoals from './Hooks/useGoals';

const money = (value) => (Number(value || 0) / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

function GoalCard({ goal, onEdit, onContribute, onRemove }) {
    const progress = Math.min(100, Math.round((Number(goal.current_amount || 0) / Math.max(1, Number(goal.target_amount || 1))) * 100));
    const completed = goal.status === 'COMPLETED' || progress >= 100;

    return <article className="ledger-panel relative overflow-hidden p-5 transition hover:-translate-y-0.5 hover:shadow-lg">
        <div className="absolute right-0 top-0 h-24 w-24 rounded-bl-full bg-orbital-primary-light/70" aria-hidden="true" />
        <div className="relative flex items-start justify-between gap-3"><span className={`flex h-12 w-12 items-center justify-center rounded-2xl ${completed ? 'bg-orbital-accent/20 text-orbital-accent-dark' : 'bg-orbital-primary-light text-orbital-primary'}`}><Target className="h-6 w-6" aria-hidden="true" /></span><div className="flex gap-1"><Button color="light" size="xs" onClick={() => onEdit(goal)} aria-label={`Editar meta ${goal.name}`}><Pencil className="h-4 w-4" aria-hidden="true" /></Button><Button color="light" size="xs" onClick={() => onRemove(goal)} aria-label={`Excluir meta ${goal.name}`}><Trash2 className="h-4 w-4 text-red-500" aria-hidden="true" /></Button></div></div>
        <div className="relative mt-5"><div className="flex items-start justify-between gap-3"><div><h2 className="text-lg font-bold text-orbital-primary-dark">{goal.name}</h2>{goal.deadline && <p className="mt-1 text-xs text-orbital-text-secondary">Prazo: {new Date(`${goal.deadline}T12:00:00`).toLocaleDateString('pt-BR')}</p>}</div>{completed && <span className="flex items-center gap-1 rounded-full bg-orbital-accent/20 px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-orbital-accent-dark"><Check className="h-3 w-3" aria-hidden="true" />Concluída</span>}</div>
            <div className="mt-6 flex items-end justify-between"><div><p className="text-2xl font-bold tracking-tight text-orbital-primary-dark">{money(goal.current_amount)}</p><p className="text-xs text-orbital-text-secondary">de {money(goal.target_amount)}</p></div><strong className="text-xl text-orbital-primary">{progress}%</strong></div>
            <div className="mt-3 h-2 overflow-hidden rounded-full bg-orbital-primary-light" role="progressbar" aria-valuenow={progress} aria-valuemin="0" aria-valuemax="100" aria-label={`Progresso da meta ${goal.name}`}><div className="h-full rounded-full bg-orbital-primary transition-all" style={{ width: `${progress}%` }} /></div>
            <p className="mt-2 text-xs text-orbital-text-secondary">Faltam {money(goal.remaining_amount)}</p>
            <Button color="light" size="sm" className="mt-5 w-full" onClick={() => onContribute(goal)} disabled={completed}><CircleDollarSign className="mr-2 h-4 w-4" aria-hidden="true" />Registrar aporte</Button>
        </div>
    </article>;
}

export default function Goals() {
    const goals = useGoals();
    const selectedWallet = goals.wallets.find((wallet) => String(wallet.id) === String(goals.selectedWalletId));

    return <AppLayout><header className="ledger-header"><div><p className="ledger-eyebrow">Trajetórias que importam</p><h1 className="ledger-heading">Suas metas.</h1><p className="mt-3 max-w-xl text-base leading-7 text-orbital-text-secondary">Acompanhe cada aporte e transforme planos em marcos visíveis.</p></div><Button color="blue" onClick={() => goals.openModal()} disabled={!selectedWallet}><Plus className="mr-2 h-4 w-4" aria-hidden="true" />Nova meta</Button></header>
        <div className="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-orbital-border bg-orbital-surface px-4 py-3 shadow-sm"><label className="text-sm font-semibold text-orbital-primary-dark" htmlFor="goals-wallet">Carteira</label><select id="goals-wallet" value={goals.selectedWalletId} onChange={(event) => goals.selectWallet(event.target.value)} className="min-w-48 rounded-lg border-orbital-border bg-orbital-surface px-3 py-2 text-sm text-orbital-text-primary focus:border-orbital-primary focus:ring-orbital-primary"><option value="">Selecione uma carteira</option>{goals.wallets.map((wallet) => <option key={wallet.id} value={wallet.id}>{wallet.name}</option>)}</select></div>
        {Object.keys(goals.errors).length > 0 && !goals.modalOpen && !goals.contributionOpen && <div className="ledger-error" role="alert">{Object.values(goals.errors).flat()[0]}</div>}
        {goals.loading ? <div className="ledger-panel ledger-state" role="status">Carregando metas…</div> : !selectedWallet ? <div className="ledger-panel ledger-empty" role="status"><Target className="mx-auto h-8 w-8 text-orbital-primary" aria-hidden="true" /><p className="mt-3 font-semibold text-orbital-primary-dark">Crie uma carteira para começar.</p></div> : goals.goals.length === 0 ? <div className="ledger-panel ledger-empty" role="status"><Target className="mx-auto h-8 w-8 text-orbital-primary" aria-hidden="true" /><p className="mt-3 font-semibold text-orbital-primary-dark">Nenhuma meta nesta carteira.</p><p className="mt-1 text-sm text-orbital-text-secondary">Defina um destino para o seu próximo aporte.</p><Button color="blue" size="sm" className="mt-4" onClick={() => goals.openModal()}><Plus className="mr-2 h-4 w-4" aria-hidden="true" />Criar primeira meta</Button></div> : <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">{goals.goals.map((goal) => <GoalCard key={goal.id} goal={goal} onEdit={goals.openModal} onContribute={goals.openContribution} onRemove={goals.remove} />)}</div>}
        <GoalModal open={goals.modalOpen} onClose={goals.closeModal} form={goals.form} onSubmit={goals.submit} submitting={goals.submitting} errors={goals.errors} editing={goals.editingGoal} wallets={goals.wallets} />
        <GoalContributionModal open={goals.contributionOpen} onClose={() => goals.setContributionOpen(false)} form={goals.contributionForm} goal={goals.selectedGoal} onSubmit={goals.submitContribution} submitting={goals.submitting} errors={goals.errors} />
    </AppLayout>;
}
