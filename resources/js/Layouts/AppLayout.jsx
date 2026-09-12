import AppNavigation from '@/Components/Navigation/AppNavigation';
import { ThemeProvider } from 'flowbite-react';
import Theme from "@/Themes";

export default function AppLayout({ children, mainClassName = '' }) {
    return (
        <ThemeProvider theme={Theme}>
        <div className="min-h-screen bg-orbital-background lg:flex">
            <AppNavigation />
            <main className={`ledger-shell min-w-0 flex-1 ${mainClassName}`}>{children}</main>
        </div>
        </ThemeProvider>
    );
}
