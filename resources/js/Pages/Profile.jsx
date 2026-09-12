import { Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Mail, UserRound } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';

export default function Profile() {
    const { auth } = usePage().props;
    const user = auth?.user ?? {};
    const initials = (user.name ?? 'U').split(' ').map((part) => part[0]).join('').slice(0, 2).toUpperCase();

    return <AppLayout>
            <Link href="/dashboard-financeiro" className="mb-8 inline-flex items-center gap-2 text-sm font-semibold text-orbital-primary transition hover:text-orbital-primary-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orbital-accent"><ArrowLeft className="h-4 w-4" aria-hidden="true" />Voltar para a visão geral</Link>
            <header className="mb-8 max-w-2xl"><p className="ledger-eyebrow">Sua conta</p><h1 className="ledger-heading mt-2">Meu perfil.</h1><p className="mt-3 text-base leading-7 text-orbital-text-secondary">Confira os dados usados para identificar sua conta no OrbGem.</p></header>
            <section className="ledger-panel max-w-2xl" aria-labelledby="profile-title">
                <div className="flex flex-col gap-5 border-b border-orbital-border pb-6 sm:flex-row sm:items-center"><span className="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-orbital-primary text-xl font-bold text-white" aria-hidden="true">{initials}</span><div><p className="text-xs font-bold uppercase tracking-[.14em] text-orbital-primary">Perfil pessoal</p><h2 id="profile-title" className="mt-1 text-xl font-bold text-orbital-primary-dark">{user.name}</h2><p className="mt-1 text-sm text-orbital-text-secondary">Sua identidade no espaço financeiro</p></div></div>
                <dl className="grid gap-5 pt-6 sm:grid-cols-2"><div className="flex items-start gap-3"><UserRound className="mt-0.5 h-5 w-5 text-orbital-primary" aria-hidden="true" /><div><dt className="text-xs font-bold uppercase tracking-[.12em] text-orbital-text-secondary">Nome</dt><dd className="mt-1 font-semibold text-orbital-text-primary">{user.name}</dd></div></div><div className="flex items-start gap-3"><Mail className="mt-0.5 h-5 w-5 text-orbital-primary" aria-hidden="true" /><div><dt className="text-xs font-bold uppercase tracking-[.12em] text-orbital-text-secondary">E-mail</dt><dd className="mt-1 break-all font-semibold text-orbital-text-primary">{user.email}</dd></div></div></dl>
            </section>
    </AppLayout>;
}
