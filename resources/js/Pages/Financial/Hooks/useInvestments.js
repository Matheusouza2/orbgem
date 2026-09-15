import { useEffect, useState } from 'react';
import { useForm } from '@inertiajs/react';
import Investment from '@/Models/Investment';
import FinancialService from '@/Services/FinancialService';

const normalizeErrors = (error) => error?.errors ?? { general: error?.message ?? 'Não foi possível concluir a operação.' };
const today = () => new Date().toISOString().slice(0, 10);

export default function useInvestments() {
    const [wallets, setWallets] = useState([]);
    const [investments, setInvestments] = useState([]);
    const [income, setIncome] = useState([]);
    const [incomeFilters, setIncomeFilters] = useState({ investment_id: '', from: '', to: '' });
    const [incomeLoading, setIncomeLoading] = useState(false);
    const [investmentFilters, setInvestmentFilters] = useState({ type: '', institution: '' });
    const [yieldInvestment, setYieldInvestment] = useState(null);
    const [yields, setYields] = useState([]);
    const [yieldsLoading, setYieldsLoading] = useState(false);
    const [selectedWalletId, setSelectedWalletId] = useState('');
    const [loading, setLoading] = useState(true);
    const [modalOpen, setModalOpen] = useState(false);
    const [editingInvestment, setEditingInvestment] = useState(null);
    const [errors, setErrors] = useState({});
    const [submitting, setSubmitting] = useState(false);
    const [quoteLoading, setQuoteLoading] = useState(false);
    const [quoteMessage, setQuoteMessage] = useState('');
    const [positionsOpen, setPositionsOpen] = useState(false);
    const [filtersOpen, setFiltersOpen] = useState(false);
    const [incomeOpen, setIncomeOpen] = useState(false);
    const [manualIncomeOpen, setManualIncomeOpen] = useState(false);
    const [manualIncomeErrors, setManualIncomeErrors] = useState({});
    const [manualIncomeSubmitting, setManualIncomeSubmitting] = useState(false);
    const [positionInvestment, setPositionInvestment] = useState(null);
    const [positionHistory, setPositionHistory] = useState([]);
    const [positions, setPositions] = useState([]);
    const [positionModalOpen, setPositionModalOpen] = useState(false);
    const [positionHistoryOpen, setPositionHistoryOpen] = useState(false);
    const [positionErrors, setPositionErrors] = useState({});
    const [positionSubmitting, setPositionSubmitting] = useState(false);
    const form = useForm({ ...Investment });
    const positionForm = useForm({ investment_id: '', value: '', position_date: today(), quantity: '', unit_price: '' });
    const manualIncomeForm = useForm({ investment_id: '', amount: '', transaction_date: today() });

    const loadInvestments = async (walletId) => {
        if (!walletId) {
            setInvestments([]);
            return;
        }
        setInvestments(await FinancialService.listInvestments(walletId));
    };

    const loadIncome = async (walletId, filters = incomeFilters) => {
        if (!walletId) {
            setIncome([]);
            return;
        }
        setIncomeLoading(true);
        try {
            setIncome(await FinancialService.listInvestmentIncome(walletId, Object.fromEntries(Object.entries(filters).filter(([, value]) => value))));
        } finally {
            setIncomeLoading(false);
        }
    };

    useEffect(() => {
        FinancialService.listWallets().then((availableWallets) => {
            setWallets(availableWallets);
            const firstWalletId = availableWallets[0]?.id ?? '';
            setSelectedWalletId(firstWalletId);
            return Promise.all([loadInvestments(firstWalletId), loadIncome(firstWalletId), loadPositionHistory(firstWalletId)]);
        }).catch((error) => setErrors(normalizeErrors(error))).finally(() => setLoading(false));
    }, []);

    const selectWallet = (walletId) => {
        setSelectedWalletId(walletId);
        setErrors({});
        loadInvestments(walletId).catch((error) => setErrors(normalizeErrors(error)));
        loadIncome(walletId).catch((error) => setErrors(normalizeErrors(error)));
        loadPositionHistory(walletId).catch((error) => setErrors(normalizeErrors(error)));
    };

    const loadPositionHistory = async (walletId) => {
        if (!walletId) {
            setPositionHistory([]);
            return;
        }
        setPositionHistory(await FinancialService.listInvestmentPositionHistory(walletId));
    };

    const loadPositions = async (investmentId) => {
        if (!investmentId) {
            setPositions([]);
            return;
        }
        setPositions(await FinancialService.listInvestmentPositions(investmentId));
    };

    const updateIncomeFilters = (field, value) => setIncomeFilters((current) => ({ ...current, [field]: value }));
    const applyIncomeFilters = () => loadIncome(selectedWalletId);

    const openIncomeModal = () => {
        setManualIncomeErrors({});
        manualIncomeForm.reset();
        manualIncomeForm.setData({ investment_id: investments[0]?.id ?? '', amount: '', transaction_date: today() });
        setManualIncomeOpen(true);
    };

    const submitIncome = async (event) => {
        event.preventDefault();
        setManualIncomeErrors({});
        setManualIncomeSubmitting(true);
        try {
            await FinancialService.createInvestmentIncome({ investment_id: Number(manualIncomeForm.data.investment_id), amount: Math.round(Number(manualIncomeForm.data.amount || 0) * 100), transaction_date: manualIncomeForm.data.transaction_date });
            await loadIncome(selectedWalletId);
            setManualIncomeOpen(false);
            manualIncomeForm.reset();
        } catch (error) {
            setManualIncomeErrors(normalizeErrors(error));
        } finally {
            setManualIncomeSubmitting(false);
        }
    };

    const filteredInvestments = investments.filter((investment) => (
        (!investmentFilters.type || investment.type === investmentFilters.type)
        && (!investmentFilters.institution || investment.institution === investmentFilters.institution)
    ));
    const updateInvestmentFilter = (field, value) => setInvestmentFilters((current) => ({ ...current, [field]: value }));

    const openYields = async (investment) => {
        setYieldInvestment(investment);
        setYieldsLoading(true);
        try {
            setYields(await FinancialService.listInvestmentYields(investment.id));
        } catch (error) {
            setErrors(normalizeErrors(error));
        } finally {
            setYieldsLoading(false);
        }
    };
    const closeYields = () => setYieldInvestment(null);

    const openPositionModal = async (investment = null, position = null) => {
        const selected = investment ?? investments[0] ?? null;
        setPositionInvestment(selected);
        setPositionErrors({});
        positionForm.reset();
        positionForm.setData({
            investment_id: selected?.id ?? '',
            value: position ? (Number(position.value || 0) / 100).toFixed(2) : '',
            position_date: position?.position_date ?? today(),
            quantity: position?.quantity ?? '',
            unit_price: position ? (Number(position.unit_price || 0) / 100).toFixed(2) : '',
        });
        setPositionModalOpen(true);
        await loadPositions(selected?.id);
    };

    const changePositionInvestment = async (investmentId) => {
        const selected = investments.find((item) => String(item.id) === String(investmentId)) ?? null;
        setPositionInvestment(selected);
        positionForm.setData('investment_id', investmentId);
        await loadPositions(investmentId);
    };

    const submitPosition = async (event) => {
        event.preventDefault();
        setPositionErrors({});
        setPositionSubmitting(true);
        try {
            const payload = {
                value: Math.round(Number(positionForm.data.value || 0) * 100),
                position_date: positionForm.data.position_date,
                quantity: positionForm.data.quantity || null,
                unit_price: positionForm.data.unit_price ? Math.round(Number(positionForm.data.unit_price) * 100) : null,
            };
            await FinancialService.upsertInvestmentPosition(positionInvestment.id, payload);
            await Promise.all([loadPositions(positionInvestment.id), loadPositionHistory(selectedWalletId)]);
            positionForm.reset();
            positionForm.setData({ investment_id: positionInvestment.id, value: '', position_date: today(), quantity: '', unit_price: '' });
        } catch (error) {
            setPositionErrors(normalizeErrors(error));
        } finally {
            setPositionSubmitting(false);
        }
    };

    const removePosition = async (position) => {
        if (!window.confirm('Excluir esta posição?')) return;
        try {
            await FinancialService.deleteInvestmentPosition(position.id);
            await Promise.all([loadPositions(positionInvestment.id), loadPositionHistory(selectedWalletId)]);
        } catch (error) {
            setPositionErrors(normalizeErrors(error));
        }
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

    return { wallets, investments: filteredInvestments, allInvestments: investments, investmentFilters, updateInvestmentFilter, selectedWalletId, selectWallet, loading, modalOpen, editingInvestment, errors, submitting, form, openModal, closeModal, submit, remove, lookupQuote, quoteLoading, quoteMessage, income, incomeFilters, incomeLoading, updateIncomeFilters, applyIncomeFilters, yieldInvestment, yields, yieldsLoading, openYields, closeYields, positionsOpen, setPositionsOpen, filtersOpen, setFiltersOpen, incomeOpen, setIncomeOpen, manualIncomeOpen, setManualIncomeOpen, manualIncomeForm, manualIncomeErrors, manualIncomeSubmitting, openIncomeModal, submitIncome, positionInvestment, positions, positionHistory, positionModalOpen, positionHistoryOpen, positionForm, positionErrors, positionSubmitting, openPositionModal, changePositionInvestment, submitPosition, removePosition, setPositionModalOpen, setPositionHistoryOpen };
}
