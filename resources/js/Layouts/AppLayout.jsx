import AppNavigation from '@/Components/Navigation/AppNavigation';
import { Head } from '@inertiajs/react';
import { ThemeProvider } from 'flowbite-react';
import Theme from "@/Themes";

const pageTitles = {
    '/dashboard-financeiro': 'Visão geral',
    '/carteiras': 'Carteiras',
    '/transacoes': 'Transações',
    '/contas': 'Contas',
    '/cartoes-de-credito': 'Cartões de crédito',
    '/categorias': 'Categorias',
    '/metas': 'Metas',
    '/investimentos': 'Investimentos',
    '/open-finance': 'Open Finance',
    '/recorrencias': 'Recorrências',
    '/perfil': 'Perfil',
};

export default function AppLayout({ children, mainClassName = '' }) {
    const pageTitle = pageTitles[window.location.pathname] ?? 'OrbGem';

    return (
        <ThemeProvider theme={Theme}>
        <Head title={pageTitle} />
        <div className="min-h-screen bg-orbital-background lg:flex">
            <AppNavigation />
            <main className={`ledger-shell min-w-0 flex-1 ${mainClassName}`}>{children}</main>
        </div>
        </ThemeProvider>
    );
}
