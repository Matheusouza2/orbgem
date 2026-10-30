import { useCallback, useEffect, useRef, useState } from 'react';
import FinancialService from '@/Services/FinancialService';

export const MAX_MESSAGE_LENGTH = 1000;
const REFRESH_RELOAD_DELAY = 20000;

const friendlyError = (error) => {
    if (error?.status === 429) return 'Muitas solicitações em pouco tempo. Aguarde um instante e tente de novo.';
    if (error?.status === 403) return 'Você não tem permissão para esta ação nesta carteira.';

    return error?.message ?? 'Não foi possível concluir a operação.';
};

export default function useAssistant() {
    const [wallets, setWallets] = useState([]);
    const [selectedWalletId, setSelectedWalletId] = useState('');
    const [walletsLoading, setWalletsLoading] = useState(true);
    const [summary, setSummary] = useState(null);
    const [summaryStatus, setSummaryStatus] = useState('idle');
    const [summaryError, setSummaryError] = useState('');
    const [refreshNotice, setRefreshNotice] = useState('');
    const [messages, setMessages] = useState([]);
    const [draft, setDraft] = useState('');
    const [sending, setSending] = useState(false);
    const activeWallet = useRef('');
    const messageId = useRef(0);
    const reloadTimer = useRef(null);

    const loadSummary = useCallback(async (walletId) => {
        if (!walletId) {
            setSummary(null);
            setSummaryStatus('idle');
            return;
        }
        setSummaryStatus('loading');
        setSummaryError('');
        try {
            const data = await FinancialService.getAiSummary(walletId);
            if (String(activeWallet.current) !== String(walletId)) return;
            setSummary(data);
            setSummaryStatus('ready');
        } catch (error) {
            if (String(activeWallet.current) !== String(walletId)) return;
            setSummary(null);
            setSummaryError(friendlyError(error));
            setSummaryStatus('error');
        }
    }, []);

    useEffect(() => {
        FinancialService.listWallets()
            .then((available) => {
                setWallets(available);
                const firstId = available[0]?.id ?? '';
                activeWallet.current = firstId;
                setSelectedWalletId(firstId);
                return loadSummary(firstId);
            })
            .catch((error) => { setSummaryError(friendlyError(error)); setSummaryStatus('error'); })
            .finally(() => setWalletsLoading(false));

        return () => clearTimeout(reloadTimer.current);
    }, [loadSummary]);

    const selectWallet = (walletId) => {
        clearTimeout(reloadTimer.current);
        activeWallet.current = walletId;
        setSelectedWalletId(walletId);
        setMessages([]);
        setDraft('');
        setRefreshNotice('');
        loadSummary(walletId);
    };

    const requestRefresh = async () => {
        setRefreshNotice('');
        try {
            await FinancialService.refreshAiSummary(Number(selectedWalletId));
            const walletId = selectedWalletId;
            setRefreshNotice('Atualização solicitada. A nova análise aparece em instantes.');
            clearTimeout(reloadTimer.current);
            reloadTimer.current = setTimeout(() => loadSummary(walletId), REFRESH_RELOAD_DELAY);
        } catch (error) {
            setRefreshNotice(friendlyError(error));
        }
    };

    const send = async (text = draft) => {
        const content = text.trim();
        if (!content || sending || !selectedWalletId || content.length > MAX_MESSAGE_LENGTH) return;

        const walletId = selectedWalletId;
        const history = messages.filter((message) => !message.error).slice(-10).map(({ role, content: body }) => ({ role, content: body }));
        setMessages((current) => [...current.filter((message) => !message.error), { id: ++messageId.current, role: 'user', content }]);
        setDraft('');
        setSending(true);
        try {
            const data = await FinancialService.sendAiMessage({ wallet_id: Number(walletId), message: content, history });
            if (String(activeWallet.current) !== String(walletId)) return;
            setMessages((current) => [...current, { id: ++messageId.current, role: 'assistant', content: data.reply }]);
        } catch (error) {
            if (String(activeWallet.current) !== String(walletId)) return;
            setMessages((current) => [...current, { id: ++messageId.current, role: 'assistant', content: friendlyError(error), error: true, retry: content }]);
        } finally {
            if (String(activeWallet.current) === String(walletId)) setSending(false);
        }
    };

    return { wallets, selectedWalletId, walletsLoading, selectWallet, summary, summaryStatus, summaryError, refreshNotice, requestRefresh, messages, draft, setDraft, sending, send };
}
