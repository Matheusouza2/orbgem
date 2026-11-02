import { Link, usePage } from '@inertiajs/react';
import {
    ArrowLeftRight,
    CreditCard,
    Landmark,
    Sparkles,
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
import { useEffect, useRef, useState } from 'react';
import useLogout from '@/Hooks/useLogout';

const navigationGroups = [
    { label: 'Dia a dia', items: [
        { label: 'Visão geral', href: '/dashboard-financeiro', icon: WalletCards },
        { label: 'Transações', href: '/transacoes', icon: ReceiptText },
        { label: 'Recorrências', href: '/recorrencias', icon: ArrowLeftRight },
    ] },
    { label: 'Onde está o dinheiro', items: [
        { label: 'Carteiras', href: '/carteiras', icon: Wallet },
        { label: 'Contas', href: '/contas', icon: Landmark },
        { label: 'Cartões de crédito', href: '/cartoes-de-credito', icon: CreditCard },
        { label: 'Categorias', href: '/categorias', icon: Tags },
    ] },
    { label: 'Para frente', items: [
        { label: 'Metas', href: '/metas', icon: Target },
        { label: 'Investimentos', href: '/investimentos', icon: TrendingUp },
        { label: 'Assistente IA', href: '/assistente', icon: Sparkles },
        { label: 'Open Finance', href: '/open-finance', icon: Link2 },
    ] },
];

const bottomTabs = [
    { label: 'Início', href: '/dashboard-financeiro', icon: WalletCards },
    { label: 'Transações', href: '/transacoes', icon: ReceiptText },
    { label: 'Contas', href: '/contas', icon: Landmark },
    { label: 'Cartões', href: '/cartoes-de-credito', icon: CreditCard },
];

function OrbitMark() {
    return <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white/95 shadow-sm ring-1 ring-black/5" aria-hidden="true">
        <img src="/images/icon.png" alt="" className="h-7 w-7 object-contain" />
    </span>;
}

export default function AppNavigation() {
    const { url, props } = usePage();
    const { auth } = props;
    const [mobileOpen, setMobileOpen] = useState(false);
    const [desktopNavigation, setDesktopNavigation] = useState(() => window.matchMedia('(min-width: 1024px)').matches);
    const menuButtonRef = useRef(null);
    const navigationRef = useRef(null);
    const { logout, processing } = useLogout();
    const user = auth?.user;
    const initials = (user?.name ?? 'U').split(' ').map((part) => part[0]).join('').slice(0, 2).toUpperCase();

    useEffect(() => {
        const media = window.matchMedia('(min-width: 1024px)');
        const updateNavigationMode = (event) => {
            setDesktopNavigation(event.matches);
            if (event.matches) setMobileOpen(false);
        };

        setDesktopNavigation(media.matches);
        media.addEventListener('change', updateNavigationMode);

        return () => media.removeEventListener('change', updateNavigationMode);
    }, []);

    useEffect(() => {
        if (!mobileOpen) return undefined;

        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        const closeOnEscape = (event) => {
            if (event.key === 'Escape') {
                setMobileOpen(false);
                menuButtonRef.current?.focus();
                return;
            }

            if (event.key === 'Tab') {
                const focusable = [...(navigationRef.current?.querySelectorAll('a[href], button:not([disabled])') ?? [])];
                const first = focusable[0];
                const last = focusable.at(-1);

                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last?.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first?.focus();
                }
            }
        };

        window.addEventListener('keydown', closeOnEscape);
        window.requestAnimationFrame(() => navigationRef.current?.querySelector('nav a')?.focus());

        return () => {
            document.body.style.overflow = previousOverflow;
            window.removeEventListener('keydown', closeOnEscape);
        };
    }, [mobileOpen]);

    const currentPath = url.split('?')[0];

    return <>
        <div className="orbital-topbar relative z-40 flex items-center justify-between border-b border-orbital-border bg-orbital-surface/90 px-4 py-2.5 backdrop-blur lg:hidden">
            <Link href="/dashboard-financeiro" className="flex items-center gap-2.5" aria-label="Ir para o dashboard financeiro">
                <OrbitMark />
                <span className="orbital-wordmark text-base font-bold text-orbital-primary-dark">OrbGem</span>
            </Link>
            <button ref={menuButtonRef} type="button" className="orbital-btn flex h-11 w-11 items-center justify-center rounded-xl text-orbital-primary-dark hover:bg-orbital-primary-light" onClick={() => setMobileOpen((open) => !open)} aria-label={mobileOpen ? 'Fechar navegação' : 'Abrir navegação'} aria-expanded={mobileOpen} aria-controls="app-navigation">
                {mobileOpen ? <PanelLeftClose className="h-5 w-5" aria-hidden="true" /> : <PanelLeftOpen className="h-5 w-5" aria-hidden="true" />}
            </button>
        </div>

        {mobileOpen && <button type="button" tabIndex={-1} className="orbital-scrim fixed inset-0 z-40 cursor-default bg-orbital-surface-dark/50 backdrop-blur-[3px] lg:hidden" aria-label="Fechar navegação" onClick={() => { setMobileOpen(false); menuButtonRef.current?.focus(); }} />}

        <aside ref={navigationRef} id="app-navigation" aria-label="Navegação da OrbGem" aria-hidden={!desktopNavigation && !mobileOpen} aria-modal={!desktopNavigation && mobileOpen ? true : undefined} role={!desktopNavigation && mobileOpen ? 'dialog' : undefined} inert={!desktopNavigation && !mobileOpen} className={`orbital-sidebar orbital-mobile-navigation fixed inset-y-0 left-0 z-50 flex w-[min(20rem,88vw)] flex-col overflow-y-auto px-3 py-5 lg:sticky lg:top-0 lg:z-auto lg:h-screen lg:w-64 lg:shrink-0 lg:translate-x-0 lg:px-4 lg:py-6 ${mobileOpen ? 'translate-x-0' : '-translate-x-full'}`}>
            <Link href="/dashboard-financeiro" className="mb-7 flex items-center gap-3 rounded-xl px-2 py-1" aria-label="OrbGem, ir para a visão geral">
                <OrbitMark />
                <span className="orbital-wordmark text-lg font-bold text-white">OrbGem</span>
            </Link>
            <nav aria-label="Navegação principal" className="grid flex-1 content-start gap-5">
                {navigationGroups.map((group) => <div key={group.label} className="grid gap-0.5">
                    <p className="orbital-nav-group px-3 pb-1.5 text-xs font-semibold text-white/45">{group.label}</p>
                    {group.items.map(({ label, href, icon: Icon }) => {
                        const active = currentPath === href;

                        return <Link key={href} href={href} className="orbital-nav-link" data-active={active ? 'true' : undefined} aria-current={active ? 'page' : undefined} onClick={() => setMobileOpen(false)}>
                            <Icon className="h-[18px] w-[18px] shrink-0" aria-hidden="true" /><span className="truncate">{label}</span>
                        </Link>;
                    })}
                </div>)}
            </nav>
            <div className="mt-6 border-t border-white/10 pt-4">
                <div className="mb-2 flex items-center gap-3 px-2"><span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-orbital-accent text-xs font-bold text-orbital-primary-dark" aria-hidden="true">{initials}</span><div className="min-w-0"><p className="truncate text-sm font-semibold text-white">{user?.name ?? 'Sua conta'}</p><p className="truncate text-xs text-white/55">{user?.email ?? 'Perfil pessoal'}</p></div></div>
                <div className="grid gap-0.5">
                    <Link href="/perfil" onClick={() => setMobileOpen(false)} className="orbital-nav-link" data-active={currentPath === '/perfil' ? 'true' : undefined}><UserRound className="h-[18px] w-[18px]" aria-hidden="true" />Meu perfil</Link>
                    <button type="button" onClick={() => { setMobileOpen(false); logout(); }} disabled={processing} className="orbital-nav-link text-left disabled:cursor-not-allowed disabled:opacity-50"><LogOut className="h-[18px] w-[18px]" aria-hidden="true" />{processing ? 'Saindo…' : 'Sair'}</button>
                </div>
            </div>
        </aside>

        <nav aria-label="Atalhos" className="orbital-tabbar fixed inset-x-0 bottom-0 z-30 grid grid-cols-5 lg:hidden">
            {bottomTabs.map(({ label, href, icon: Icon }) => {
                const active = currentPath === href;

                return <Link key={href} href={href} className="orbital-tab" data-active={active ? 'true' : undefined} aria-current={active ? 'page' : undefined}>
                    <Icon className="h-5 w-5" aria-hidden="true" /><span>{label}</span>
                </Link>;
            })}
            <button type="button" className="orbital-tab" onClick={() => setMobileOpen(true)} aria-expanded={mobileOpen} aria-controls="app-navigation"><PanelLeftOpen className="h-5 w-5" aria-hidden="true" /><span>Mais</span></button>
        </nav>
    </>;
}
