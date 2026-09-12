import { useEffect, useState } from 'react';
import FinancialService from '@/Services/FinancialService';

const normalizeErrors = (error) => error?.errors ?? { general: error?.message ?? 'Não foi possível concluir a operação.' };

export default function useOpenFinance() {
    const [wallets, setWallets] = useState([]);
    const [items, setItems] = useState([]);
    const [selectedWalletId, setSelectedWalletId] = useState('');
    const [loading, setLoading] = useState(true);
    const [connecting, setConnecting] = useState(false);
    const [errors, setErrors] = useState({});
    const [syncingConnection, setSyncingConnection] = useState(null);
    const [syncForm, setSyncForm] = useState({ period: '1y', from: '', to: '' });
    const [syncing, setSyncing] = useState(false);

    const loadItems = () => FinancialService.listOpenFinanceItems().then(setItems).catch((error) => setErrors(normalizeErrors(error)));

    useEffect(() => {
        Promise.allSettled([FinancialService.listWallets(), FinancialService.listOpenFinanceItems()]).then(([walletResult, itemResult]) => {
            if (walletResult.status === 'fulfilled') {
                setWallets(walletResult.value);
                setSelectedWalletId(walletResult.value[0]?.id ?? '');
            } else {
                setErrors(normalizeErrors(walletResult.reason));
            }

            if (itemResult.status === 'fulfilled') {
                setItems(itemResult.value);
            } else {
                setErrors((current) => ({ ...current, integration: itemResult.reason?.message ?? 'Não foi possível carregar as conexões Open Finance.' }));
            }
        }).finally(() => setLoading(false));
    }, []);

    const loadWidgetScript = () => new Promise((resolve, reject) => {
        if (window.PluggyConnect) { resolve(); return; }
        const existing = document.querySelector('script[data-pluggy-connect]');
        if (existing) { existing.addEventListener('load', resolve, { once: true }); existing.addEventListener('error', reject, { once: true }); return; }
        const script = document.createElement('script');
        script.src = 'https://cdn.pluggy.ai/pluggy-connect/v2.8.2/pluggy-connect.js';
        script.async = true;
        script.dataset.pluggyConnect = 'true';
        script.onload = resolve;
        script.onerror = reject;
        document.head.appendChild(script);
    });

    const connect = async () => {
        if (!selectedWalletId) return;
        setConnecting(true);
        setErrors({});
        try {
            const [{ accessToken }] = await Promise.all([FinancialService.getOpenFinanceToken(Number(selectedWalletId)), loadWidgetScript()]);
            const widget = new window.PluggyConnect({ connectToken: accessToken, includeSandbox: false, onSuccess: async (data) => { await FinancialService.storeOpenFinanceItem({ wallet_id: Number(selectedWalletId), item_id: data.item.id }); await loadItems(); setConnecting(false); }, onError: (error) => { setErrors({ general: error?.message ?? 'A conexão não foi concluída.' }); setConnecting(false); } });
            widget.init();
        } catch (error) { setErrors(normalizeErrors(error)); setConnecting(false); }
    };

    const disconnect = async (item) => {
        if (!window.confirm(`Desconectar ${item.connector_name || 'esta instituição'}?`)) return;
        try { await FinancialService.deleteOpenFinanceItem(item.id); setItems((current) => current.filter((entry) => entry.id !== item.id)); } catch (error) { setErrors(normalizeErrors(error)); }
    };

    const openSync = (item) => { setSyncingConnection(item); setSyncForm({ period: '1y', from: '', to: '' }); setErrors({}); };
    const closeSync = () => { if (!syncing) setSyncingConnection(null); };
    const sync = async (event) => {
        event.preventDefault();
        if (!syncingConnection) return;
        setSyncing(true); setErrors({});
        const today = new Date();
        const to = today.toISOString().slice(0, 10);
        const fromDate = new Date(today);
        if (syncForm.period === '30d') fromDate.setDate(fromDate.getDate() - 30);
        if (syncForm.period === '6m') fromDate.setMonth(fromDate.getMonth() - 6);
        if (syncForm.period === '1y') fromDate.setFullYear(fromDate.getFullYear() - 1);
        try {
            await FinancialService.syncOpenFinanceConnection(syncingConnection.id, { from: syncForm.period === 'custom' ? syncForm.from : fromDate.toISOString().slice(0, 10), to: syncForm.period === 'custom' ? syncForm.to : to });
            setSyncingConnection(null); await loadItems();
        } catch (error) { setErrors(normalizeErrors(error)); } finally { setSyncing(false); }
    };

    return { wallets, items, selectedWalletId, setSelectedWalletId, loading, connecting, errors, connect, disconnect, reload: loadItems, syncingConnection, syncForm, setSyncForm, syncing, openSync, closeSync, sync };
}
