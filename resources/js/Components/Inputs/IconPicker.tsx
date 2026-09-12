import {
    Car,
    ChevronDown,
    Clapperboard,
    DollarSign,
    FileText,
    Gift,
    GraduationCap,
    HeartPulse,
    Home,
    Luggage,
    PiggyBank,
    ShoppingBag,
    Tag,
    TrendingUp,
    Utensils,
    Wallet,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { LucideIcon } from 'lucide-react';

type IconOption = {
    id: string;
    label: string;
    keywords: string[];
    Icon: LucideIcon;
};

export type IconPickerProps = {
    value?: string;
    onChange: (value: string) => void;
    className?: string;
    id?: string;
};

export const FINANCIAL_ICON_OPTIONS: readonly IconOption[] = [
    { id: 'Utensils', label: 'Alimentação', keywords: ['comida', 'restaurante', 'mercado', 'feira'], Icon: Utensils },
    { id: 'Car', label: 'Transporte', keywords: ['carro', 'combustível', 'gasolina', 'uber'], Icon: Car },
    { id: 'Home', label: 'Moradia', keywords: ['casa', 'aluguel', 'lar'], Icon: Home },
    { id: 'HeartPulse', label: 'Saúde', keywords: ['médico', 'farmácia', 'bem-estar'], Icon: HeartPulse },
    { id: 'GraduationCap', label: 'Educação', keywords: ['escola', 'curso', 'faculdade'], Icon: GraduationCap },
    { id: 'Clapperboard', label: 'Lazer', keywords: ['cinema', 'filme', 'entretenimento'], Icon: Clapperboard },
    { id: 'ShoppingBag', label: 'Compras', keywords: ['roupas', 'vestuário', 'loja'], Icon: ShoppingBag },
    { id: 'TrendingUp', label: 'Investimentos', keywords: ['aportes', 'ações', 'renda'], Icon: TrendingUp },
    { id: 'PiggyBank', label: 'Reserva', keywords: ['poupança', 'guardar', 'emergência'], Icon: PiggyBank },
    { id: 'FileText', label: 'Contas e boletos', keywords: ['fatura', 'conta', 'boleto'], Icon: FileText },
    { id: 'DollarSign', label: 'Salário', keywords: ['receita', 'renda', 'pagamento'], Icon: DollarSign },
    { id: 'Wallet', label: 'Carteira', keywords: ['dinheiro', 'banco', 'conta corrente'], Icon: Wallet },
    { id: 'Luggage', label: 'Viagens', keywords: ['férias', 'turismo', 'passagem'], Icon: Luggage },
    { id: 'Gift', label: 'Presentes', keywords: ['doação', 'comemoração'], Icon: Gift },
    { id: 'Tag', label: 'Outros', keywords: ['geral', 'diversos'], Icon: Tag },
];

const normalize = (text: string): string => text
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase();

export function IconPicker({ value, onChange, className = '', id }: IconPickerProps) {
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');
    const containerRef = useRef<HTMLDivElement>(null);
    const searchRef = useRef<HTMLInputElement>(null);
    const selected = FINANCIAL_ICON_OPTIONS.find((option) => option.id === value) ?? FINANCIAL_ICON_OPTIONS[0];
    const filteredOptions = FINANCIAL_ICON_OPTIONS.filter((option) => {
        const searchableText = [option.id, option.label, ...option.keywords].join(' ');
        return normalize(searchableText).includes(normalize(search));
    });

    useEffect(() => {
        if (!open) return;

        searchRef.current?.focus();
        const handlePointerDown = (event: MouseEvent) => {
            if (!containerRef.current?.contains(event.target as Node)) setOpen(false);
        };
        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') setOpen(false);
        };

        document.addEventListener('mousedown', handlePointerDown);
        document.addEventListener('keydown', handleKeyDown);
        return () => {
            document.removeEventListener('mousedown', handlePointerDown);
            document.removeEventListener('keydown', handleKeyDown);
        };
    }, [open]);

    const selectIcon = (iconId: string) => {
        onChange(iconId);
        setOpen(false);
        setSearch('');
    };

    const SelectedIcon = selected.Icon;

    return (
        <div ref={containerRef} className={`relative w-full ${className}`}>
            <button
                type="button"
                id={id}
                aria-expanded={open}
                aria-haspopup="dialog"
                aria-label={`Ícone: ${selected.label}`}
                onClick={() => setOpen((currentOpen) => !currentOpen)}
                className="flex min-h-11 w-full items-center justify-between gap-3 rounded-lg border border-orbital-border bg-orbital-surface px-3 py-2 text-left text-sm text-orbital-text-primary shadow-sm transition hover:border-orbital-primary focus:border-orbital-accent focus:outline-none focus:ring-2 focus:ring-orbital-accent/30"
            >
                <span className="flex min-w-0 items-center gap-2">
                    <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-orbital-primary-light text-orbital-primary">
                        <SelectedIcon className="h-4 w-4" aria-hidden="true" />
                    </span>
                    <span className="truncate">{selected.label}</span>
                </span>
                <ChevronDown className={`h-4 w-4 shrink-0 text-orbital-text-secondary transition-transform ${open ? 'rotate-180' : ''}`} aria-hidden="true" />
            </button>

            {open && (
                <div role="dialog" aria-label="Selecionar ícone" className="absolute left-0 top-[calc(100%+0.5rem)] z-[100] w-full min-w-72 rounded-xl border border-orbital-border bg-orbital-surface p-3 shadow-[0_18px_45px_rgb(11_36_84/18%)]">
                    <div className="relative mb-3">
                        <input
                            ref={searchRef}
                            type="search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Buscar alimentação, mercado..."
                            aria-label="Buscar ícone"
                            className="h-10 w-full rounded-lg border border-orbital-border bg-orbital-background px-3 text-sm text-orbital-text-primary outline-none transition placeholder:text-orbital-text-secondary focus:border-orbital-accent focus:ring-2 focus:ring-orbital-accent/30"
                        />
                    </div>
                    <div className="grid max-h-64 grid-cols-4 gap-2 overflow-y-auto pr-1" role="listbox" aria-label="Ícones financeiros">
                        {filteredOptions.map(({ id, label, Icon }) => {
                            const isSelected = id === selected.id;
                            return (
                                <button
                                    key={id}
                                    type="button"
                                    role="option"
                                    aria-selected={isSelected}
                                    aria-label={label}
                                    title={label}
                                    onClick={() => selectIcon(id)}
                                    className={`group flex min-h-16 flex-col items-center justify-center gap-1 rounded-lg border p-2 text-center transition focus:outline-none focus:ring-2 focus:ring-orbital-accent ${isSelected ? 'border-orbital-primary bg-orbital-primary-light text-orbital-primary-dark' : 'border-transparent text-orbital-text-secondary hover:border-orbital-border hover:bg-orbital-background hover:text-orbital-primary-dark'}`}
                                >
                                    <Icon className={`h-5 w-5 ${isSelected ? 'text-orbital-primary' : 'text-orbital-text-secondary group-hover:text-orbital-primary'}`} aria-hidden="true" />
                                    <span className="w-full truncate text-[11px] font-medium">{label}</span>
                                </button>
                            );
                        })}
                    </div>
                    {filteredOptions.length === 0 && <p className="px-2 py-5 text-center text-sm text-orbital-text-secondary">Nenhuma categoria encontrada.</p>}
                </div>
            )}
        </div>
    );
}

export default IconPicker;
