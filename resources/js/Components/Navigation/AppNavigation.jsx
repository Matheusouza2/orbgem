import { Link, usePage } from '@inertiajs/react';
import {
    ArrowLeftRight,
    CreditCard,
    Landmark,
    PanelLeftClose,
    PanelLeftOpen,
    ReceiptText,
    Tags,
    Target,
    TrendingUp,
    WalletCards,
    Wallet,
    LogOut,
    Link2,
    UserRound,
} from 'lucide-react';
import { useState } from 'react';
import useLogout from '@/Hooks/useLogout';

const navigationItems = [
    { label: 'Visão geral', href: '/dashboard-financeiro', icon: WalletCards },
    { label: 'Carteiras', href: '/carteiras', icon: Wallet },
    { label: 'Transações', href: '/transacoes', icon: ReceiptText },
    { label: 'Contas', href: '/contas', icon: Landmark },
    { label: 'Cartões de crédito', href: '/cartoes-de-credito', icon: CreditCard },
    { label: 'Categorias', href: '/categorias', icon: Tags },
    { label: 'Metas', href: '/metas', icon: Target },
    { label: 'Investimentos', href: '/investimentos', icon: TrendingUp },
    { label: 'Open Finance', href: '/open-finance', icon: Link2 },
    { label: 'Recorrências', href: '/recorrencias', icon: ArrowLeftRight },
];

function OrbitMark() {
    return <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-orbital-primary-light text-orbital-primary" aria-hidden="true">
        <svg className="h-7 w-7" viewBox="0 0 32 32" fill="none">
            <circle cx="16" cy="16" r="3" fill="currentColor" />
            <ellipse cx="16" cy="16" rx="12" ry="5" stroke="currentColor" strokeWidth="1.4" transform="rotate(-28 16 16)" />
            <ellipse cx="16" cy="16" rx="12" ry="5" stroke="#F4B321" strokeWidth="1.4" transform="rotate(55 16 16)" />
            <circle cx="25" cy="11" r="1.5" fill="#F4B321" />
        </svg>
    </span>;
}

export default function AppNavigation() {
    const { url, props } = usePage();
    const { auth } = props;
    const [mobileOpen, setMobileOpen] = useState(false);
    const { logout, processing } = useLogout();
    const user = auth?.user;
    const initials = (user?.name ?? 'U').split(' ').map((part) => part[0]).join('').slice(0, 2).toUpperCase();

    return <>
        <div className="flex items-center justify-between border-b border-orbital-border bg-orbital-surface px-5 py-4 lg:hidden">
            <Link href="/dashboard-financeiro" className="flex items-center gap-3" aria-label="Ir para o dashboard financeiro">
                <OrbitMark />
                <span className="text-sm font-bold tracking-[.2em] text-orbital-primary-dark">ORBGEM</span>
            </Link>
            <button type="button" className="rounded-lg p-2 text-orbital-primary-dark transition hover:bg-orbital-primary-light focus-visible:ring-2 focus-visible:ring-orbital-accent" onClick={() => setMobileOpen((open) => !open)} aria-expanded={mobileOpen} aria-controls="app-navigation">
                {mobileOpen ? <PanelLeftClose className="h-5 w-5" aria-hidden="true" /> : <PanelLeftOpen className="h-5 w-5" aria-hidden="true" />}
                <span className="sr-only">{mobileOpen ? 'Fechar navegação' : 'Abrir navegação'}</span>
            </button>
        </div>

        <aside id="app-navigation" className={`${mobileOpen ? 'block' : 'hidden'} border-b border-orbital-border bg-orbital-primary-dark px-4 py-5 lg:sticky lg:top-0 lg:block lg:min-h-screen lg:w-64 lg:shrink-0 lg:border-b-0 lg:border-r lg:border-white/10 lg:px-5 lg:py-7`}>
            <div className="mb-10 hidden items-center gap-3 lg:flex">
                <OrbitMark />
                <div><p className="text-sm font-bold tracking-[.2em] text-white">ORBGEM</p><p className="mt-1 text-[10px] uppercase tracking-[.18em] text-orbital-primary-light/60">Finanças claras</p></div>
            </div>
            <p className="mb-3 px-3 text-[10px] font-bold uppercase tracking-[.2em] text-orbital-primary-light/50">Seu espaço</p>
            <nav aria-label="Navegação principal" className="grid gap-1">
                {navigationItems.map(({ label, href, icon: Icon, comingSoon }) => {
                    const active = url.split('?')[0] === href;

                    if (comingSoon) {
                        return <span key={href} className="flex cursor-not-allowed items-center justify-between rounded-xl px-3 py-3 text-sm font-medium text-white/40" aria-disabled="true" title="Disponível em breve">
                            <span className="flex items-center gap-3"><Icon className="h-[18px] w-[18px]" aria-hidden="true" />{label}</span>
                            <span className="text-[9px] font-bold uppercase tracking-wider text-orbital-accent/70">Em breve</span>
                        </span>;
                    }

                    return <Link key={href} href={href} className={`flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition ${active ? 'bg-orbital-primary-light text-orbital-primary-dark shadow-sm' : 'text-white/75 hover:bg-white/10 hover:text-white'} focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orbital-accent`} aria-current={active ? 'page' : undefined} onClick={() => setMobileOpen(false)}>
                        <Icon className="h-[18px] w-[18px]" aria-hidden="true" />{label}
                    </Link>;
                })}
            </nav>
            <div className="mt-10 hidden border-t border-white/10 px-3 pt-5 lg:block"><div className="mb-4 flex items-center gap-3"><span className="flex h-9 w-9 items-center justify-center rounded-full bg-orbital-primary-light text-xs font-bold text-orbital-primary-dark" aria-hidden="true">{initials}</span><div className="min-w-0"><p className="truncate text-sm font-semibold text-white">{user?.name ?? 'Sua conta'}</p><p className="truncate text-xs text-white/45">{user?.email ?? 'Perfil pessoal'}</p></div></div><div className="grid gap-1"><Link href="/perfil" className="flex items-center gap-3 rounded-lg px-2 py-2 text-sm font-medium text-white/70 transition hover:bg-white/10 hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orbital-accent"><UserRound className="h-4 w-4" aria-hidden="true" />Meu perfil</Link><button type="button" onClick={logout} disabled={processing} className="flex items-center gap-3 rounded-lg px-2 py-2 text-left text-sm font-medium text-white/70 transition hover:bg-white/10 hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orbital-accent disabled:cursor-not-allowed disabled:opacity-50"><LogOut className="h-4 w-4" aria-hidden="true" />{processing ? 'Saindo…' : 'Sair'}</button></div></div>
        </aside>
    </>;
}
