import { useEffect, useState } from 'react';
import { useForm } from '@inertiajs/react';
import Investment from '@/Models/Investment';
import FinancialService from '@/Services/FinancialService';

const normalizeErrors = (error) => error?.errors ?? { general: error?.message ?? 'Não foi possível concluir a operação.' };

export default function useInvestments() {
    const [wallets, setWallets] = useState([]);
    const [investments, setInvestments] = useState([]);
    const [selectedWalletId, setSelectedWalletId] = useState('');
    const [loading, setLoading] = useState(true);
    const [modalOpen, setModalOpen] = useState(false);
    const [editingInvestment, setEditingInvestment] = useState(null);
    const [errors, setErrors] = useState({});
    const [submitting, setSubmitting] = useState(false);
    const [quoteLoading, setQuoteLoading] = useState(false);
    const [quoteMessage, setQuoteMessage] = useState('');
    const form = useForm({ ...Investment });

    const loadInvestments = async (walletId) => {
        if (!walletId) {
            setInvestments([]);
            return;
        }
        setInvestments(await FinancialService.listInvestments(walletId));
    };

    useEffect(() => {
        FinancialService.listWallets().then((availableWallets) => {
            setWallets(availableWallets);
            const firstWalletId = availableWallets[0]?.id ?? '';
            setSelectedWalletId(firstWalletId);
            return loadInvestments(firstWalletId);
        }).catch((error) => setErrors(normalizeErrors(error))).finally(() => setLoading(false));
    }, []);

    const selectWallet = (walletId) => {
        setSelectedWalletId(walletId);
        setErrors({});
        loadInvestments(walletId).catch((error) => setErrors(normalizeErrors(error)));
    };

    const openModal = (investment = null) => {
        setErrors({});
        setEditingInvestment(investment);
        form.reset();
        form.setData({ ...Investment, wallet_id: investment?.wallet_id ?? selectedWalletId, name: investment?.name ?? '', ticker: investment?.ticker ?? '', type: investment?.type ?? 'STOCK', institution: investment?.institution ?? '', quantity: investment?.quantity ?? '', average_price: investment ? (Number(investment.average_price || 0) / 100).toFixed(2) : '', invested_amount: investment ? (Number(investment.invested_amount || 0) / 100).toFixed(2) : '', current_value: investment ? (Number(investment.current_value || 0) / 100).toFixed(2) : '', acquired_at: investment?.acquired_at ?? '', active: investment?.active ?? true, cdi_linked: investment?.cdi_linked ?? false, cdi_percentage: investment?.cdi_percentage ?? '', last_yield_date: investment?.last_yield_date ?? null });
        setModalOpen(true);
    };

    const closeModal = () => { if (!submitting) setModalOpen(false); };

    const lookupQuote = async (symbol = form.data.ticker) => {
        const normalizedSymbol = String(symbol || '').trim().toUpperCase();
        if (!normalizedSymbol) return;

        setQuoteLoading(true);
        setQuoteMessage('');
        try {
            const quote = await FinancialService.getInvestmentQuote(normalizedSymbol);
            const price = Number(quote.regularMarketPrice ?? quote.price ?? 0);
            const quantity = Number(form.data.quantity || 0);
            const typeValue = String(quote.type ?? quote.assetType ?? quote.instrumentType ?? '').toUpperCase();
            const type = typeValue.includes('FII') || /11$/.test(normalizedSymbol) ? 'FII' : typeValue.includes('FUND') ? 'FUND' : 'STOCK';

            form.setData((current) => ({
                ...current,
                ticker: quote.symbol ?? normalizedSymbol,
                name: current.name || quote.shortName || quote.longName || normalizedSymbol,
                type,
                average_price: current.average_price || price.toFixed(2),
                current_value: quantity > 0 ? (quantity * price).toFixed(2) : current.current_value || price.toFixed(2),
            }));
            setQuoteMessage(`Cotação atualizada: ${price.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}`);
        } catch (error) {
            setQuoteMessage(error?.message ?? 'Não foi possível buscar a cotação.');
        } finally { setQuoteLoading(false); }
    };

    const submit = async (event) => {
        event.preventDefault();
        setErrors({});
        setSubmitting(true);
        try {
            const payload = { ...form.data, wallet_id: Number(form.data.wallet_id), average_price: Math.round(Number(form.data.average_price || 0) * 100), invested_amount: Math.round(Number(form.data.invested_amount || 0) * 100), current_value: Math.round(Number(form.data.current_value || 0) * 100), quantity: String(form.data.quantity), acquired_at: form.data.acquired_at || null, cdi_linked: Boolean(form.data.cdi_linked), cdi_percentage: form.data.cdi_linked ? Number(form.data.cdi_percentage || 0) : null };
            const response = editingInvestment ? await FinancialService.updateInvestment(editingInvestment.id, payload) : await FinancialService.createInvestment(payload);
            if (Number(payload.wallet_id) === Number(selectedWalletId)) setInvestments((current) => editingInvestment ? current.map((item) => item.id === response.data.id ? response.data : item) : [response.data, ...current]);
            if (Number(payload.wallet_id) !== Number(selectedWalletId)) await loadInvestments(selectedWalletId);
            setModalOpen(false);
            form.reset();
            setEditingInvestment(null);
        } catch (error) { setErrors(normalizeErrors(error)); } finally { setSubmitting(false); }
    };

    const remove = async (investment) => {
        if (!window.confirm(`Excluir o investimento “${investment.name}”?`)) return;
        try { await FinancialService.deleteInvestment(investment.id); setInvestments((current) => current.filter((item) => item.id !== investment.id)); } catch (error) { setErrors(normalizeErrors(error)); }
    };

    return { wallets, investments, selectedWalletId, selectWallet, loading, modalOpen, editingInvestment, errors, submitting, form, openModal, closeModal, submit, remove, lookupQuote, quoteLoading, quoteMessage };
}
