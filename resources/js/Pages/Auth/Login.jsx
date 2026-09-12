import { Button } from 'flowbite-react';
import { Link } from '@inertiajs/react';
import { Suspense } from 'react';
import Inputs from '@/Components/Inputs';
import useLogin from './Hooks/useLogin';

function OrbitMark() {
    return <svg aria-hidden="true" className="h-20 w-20" viewBox="0 0 80 80" fill="none"><circle cx="40" cy="40" r="7" fill="#F4B321" /><ellipse cx="40" cy="40" rx="31" ry="13" stroke="#E8F0FF" strokeWidth="1.5" transform="rotate(-28 40 40)" /><ellipse cx="40" cy="40" rx="31" ry="13" stroke="#F4B321" strokeOpacity=".75" strokeWidth="1.5" transform="rotate(55 40 40)" /><circle cx="65" cy="28" r="3" fill="#FFFFFF" /></svg>;
}

export default function Login() {
    const { form, submit } = useLogin();
    const errors = { ...form.errors };
    const generalError = errors.general;
    delete errors.general;

    return <main className="min-h-screen bg-[#F8FAFC] text-[#172033] lg:grid lg:grid-cols-[minmax(360px,0.9fr)_minmax(460px,1.1fr)]">
        <section className="relative hidden min-h-screen overflow-hidden bg-[#0B2454] px-10 py-12 text-white lg:flex lg:flex-col lg:justify-between xl:px-16">
            <div className="absolute -right-40 top-1/3 h-[34rem] w-[34rem] rounded-full border border-[#E8F0FF]/15" />
            <div className="absolute -right-20 top-[38%] h-[24rem] w-[24rem] rounded-full border border-[#F4B321]/25" />
            <div className="relative z-10 flex items-center gap-3"><OrbitMark /><span className="text-lg font-semibold tracking-[.22em]">ORBGEM</span></div>
            <div className="relative z-10 max-w-md">
                <p className="mb-5 text-xs font-semibold uppercase tracking-[.24em] text-[#F4B321]">Clareza para decidir</p>
                <h1 className="orbital-display text-5xl leading-[.98] tracking-[-.045em] xl:text-6xl">Seu dinheiro em uma órbita mais clara.</h1>
                <p className="mt-6 max-w-sm text-base leading-7 text-[#E8F0FF]/75">Carteiras compartilhadas, movimentos registrados e uma visão segura do que importa.</p>
            </div>
            <p className="relative z-10 text-xs text-[#E8F0FF]/55">Gestão financeira feita para a vida real.</p>
        </section>

        <section className="flex min-h-screen items-center justify-center px-5 py-10 sm:px-8">
            <div className="w-full max-w-md">
                <div className="mb-10 lg:hidden"><div className="mb-6 flex items-center gap-3"><OrbitMark /><span className="text-lg font-semibold tracking-[.22em] text-[#0B2454]">ORBGEM</span></div><p className="text-xs font-semibold uppercase tracking-[.2em] text-[#123B8F]">Clareza para decidir</p></div>
                <div className="mb-8"><p className="mb-3 text-sm font-semibold text-[#123B8F]">Bem-vindo de volta</p><h2 className="orbital-display text-4xl leading-tight tracking-[-.04em] text-[#0B2454]">Entre na sua conta</h2><p className="mt-3 text-sm leading-6 text-[#667085]">Acesse suas carteiras e acompanhe seus movimentos.</p></div>
                {generalError && <div className="mb-5 rounded-xl border border-[#C98A00]/40 bg-[#F4B321]/10 p-3 text-sm text-[#172033]" role="alert">{generalError}</div>}
                <form className="grid gap-5" onSubmit={submit} noValidate>
                    <Inputs.ErrorSummary errors={errors} />
                    <Suspense fallback={<div className="h-[74px]" aria-hidden="true" />}>
                        <Inputs.Validation id="login-email" label="E-mail" name="email" type="email" autoComplete="email" placeholder="voce@exemplo.com" value={form.data.email} setData={form.setData} errors={errors} required />
                        <Inputs.Validation id="login-password" label="Senha" name="password" type="password" autoComplete="current-password" placeholder="Digite sua senha" value={form.data.password} setData={form.setData} errors={errors} required />
                    </Suspense>
                    <Button type="submit" color="blue" className="!mt-2 !w-full !bg-[#123B8F] !px-5 !py-2.5 text-sm font-semibold shadow-lg shadow-[#123B8F]/20 hover:!bg-[#0B2454] focus:ring-4 focus:ring-[#F4B321]/40" disabled={form.processing}>{form.processing ? 'Entrando…' : 'Entrar na conta'}</Button>
                </form>
                <div className="mt-8 grid gap-3 text-center text-sm text-[#667085]"><Link href="/forgot-password" className="font-semibold text-[#123B8F] underline decoration-[#F4B321] decoration-2 underline-offset-4 hover:text-[#0B2454]">Esqueci minha senha</Link><p>Ainda não tem uma conta? <Link href="/register" className="font-semibold text-[#123B8F] underline decoration-[#F4B321] decoration-2 underline-offset-4 hover:text-[#0B2454]">Criar conta</Link></p></div>
            </div>
        </section>
    </main>;
}
