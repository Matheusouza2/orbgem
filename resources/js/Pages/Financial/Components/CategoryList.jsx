import { FolderTree, Pencil, Tag } from 'lucide-react';
import { FINANCIAL_ICON_OPTIONS } from '@/Components/Inputs/IconPicker';

const typeLabels = { EXPENSE: 'Despesas', INCOME: 'Receitas' };

export default function CategoryList({ categories, loading, onEdit }) {
    const parentNames = new Map(categories.map((category) => [category.id, category.name]));

    if (loading) return <div className="ledger-panel ledger-state" role="status">Carregando categorias…</div>;
    if (categories.length === 0) return <div className="ledger-panel ledger-state" role="status">Nenhuma categoria encontrada para esta carteira.</div>;

    return <div className="grid gap-5 lg:grid-cols-2">
        {Object.entries(typeLabels).map(([type, label]) => {
            const typeCategories = categories.filter((category) => category.type === type);

            return <section key={type} className="ledger-panel" aria-labelledby={`category-${type.toLowerCase()}`}>
                <div className="mb-4 flex items-center gap-3 border-b border-orbital-border pb-4">
                    <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-orbital-primary-light text-orbital-primary"><FolderTree className="h-5 w-5" aria-hidden="true" /></span>
                    <div><h2 id={`category-${type.toLowerCase()}`} className="ledger-title text-xl">{label}</h2><p className="text-xs text-orbital-text-secondary">{typeCategories.length} {typeCategories.length === 1 ? 'categoria' : 'categorias'}</p></div>
                </div>
                {typeCategories.length === 0 ? <p className="text-sm text-orbital-text-secondary">Nenhuma categoria deste tipo.</p> : <ul className="divide-y divide-orbital-border">
                    {typeCategories.map((category) => {
                        const CategoryIcon = FINANCIAL_ICON_OPTIONS.find((option) => option.id === category.icon)?.Icon ?? Tag;

                        return <li key={category.id} className="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0">
                        <div className="flex min-w-0 items-center gap-3"><span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-orbital-primary-light" style={{ color: category.icon_color || '#123B8F' }} aria-hidden="true"><CategoryIcon className="h-4 w-4" /></span><div className="min-w-0"><p className="truncate font-semibold text-orbital-text-primary">{category.name}</p><p className="text-xs text-orbital-text-secondary">{category.parent_id ? `Subcategoria de ${parentNames.get(category.parent_id) ?? 'categoria principal'}` : category.wallet_id === null ? 'Categoria global' : 'Categoria principal'}</p></div></div>
                        <div className="flex shrink-0 items-center gap-2"><span className={`rounded-full px-2 py-1 text-[10px] font-bold uppercase tracking-wider ${category.active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'}`}>{category.active ? 'Ativa' : 'Inativa'}</span>{category.wallet_id !== null && <button type="button" className="rounded-lg p-2 text-orbital-text-secondary transition hover:bg-orbital-primary-light hover:text-orbital-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orbital-accent" onClick={() => onEdit(category)} aria-label={`Editar categoria ${category.name}`}><Pencil className="h-4 w-4" aria-hidden="true" /></button>}</div>
                    </li>;
                    })}
                </ul>}
            </section>;
        })}
    </div>;
}
