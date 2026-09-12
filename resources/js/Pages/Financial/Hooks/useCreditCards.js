import { useEffect, useState } from 'react';
import { useForm } from '@inertiajs/react';
import CreditCard from '@/Models/CreditCard';
import FinancialService from '@/Services/FinancialService';

const normalizeErrors = (error) => error?.errors ?? { general: error?.message ?? 'Não foi possível carregar os cartões.' };

export default function useCreditCards() {
    const [wallets, setWallets] = useState([]);
    const [accounts, setAccounts] = useState([]);
    const [cards, setCards] = useState([]);
    const [loading, setLoading] = useState(true);
    const [modalOpen, setModalOpen] = useState(false);
    const [editingCard, setEditingCard] = useState(null);
    const [errors, setErrors] = useState({});
    const [submitting, setSubmitting] = useState(false);
    const [transactionsCard, setTransactionsCard] = useState(null);
    const [cardTransactions, setCardTransactions] = useState([]);
    const [transactionsMeta, setTransactionsMeta] = useState(null);
    const [transactionsLoading, setTransactionsLoading] = useState(false);
    const [transactionsError, setTransactionsError] = useState('');
    const [transactionsFilters, setTransactionsFilters] = useState({ month: new Date().toISOString().slice(0, 7), status: '', page: 1 });
    const form = useForm({ ...CreditCard });

    useEffect(() => {
        FinancialService.listWallets().then(async (availableWallets) => {
            setWallets(availableWallets);
            const [walletAccounts, walletCards] = await Promise.all([
                Promise.all(availableWallets.map((wallet) => FinancialService.listAccounts(wallet.id))),
                Promise.all(availableWallets.map((wallet) => FinancialService.listCreditCards(wallet.id))),
            ]);
            setAccounts(walletAccounts.flat());
            setCards(walletCards.flat());
        }).catch((error) => setErrors(normalizeErrors(error))).finally(() => setLoading(false));
    }, []);

    const openModal = (card = null) => {
        setErrors({});
        setEditingCard(card);
        if (card) form.setData({ ...CreditCard, ...card, limit: (Number(card.limit || 0) / 100).toFixed(2) });
        else { form.reset(); form.setData('wallet_id', wallets[0]?.id ?? null); }
        setModalOpen(true);
    };
    const closeModal = () => { if (!submitting) setModalOpen(false); };
    const changeWallet = (walletId) => { form.setData((current) => ({ ...current, wallet_id: Number(walletId), account_id: '' })); };
    const submit = async (event) => {
        event.preventDefault(); setErrors({}); setSubmitting(true);
        try {
            const payload = { ...form.data, wallet_id: Number(form.data.wallet_id), account_id: form.data.account_id ? Number(form.data.account_id) : null, limit: Math.round(Number(form.data.limit || 0) * 100), closing_day: Number(form.data.closing_day), due_day: Number(form.data.due_day) };
            const response = editingCard ? await FinancialService.updateCreditCard(editingCard.id, payload) : await FinancialService.createCreditCard(payload);
            setCards((current) => editingCard ? current.map((card) => card.id === response.data.id ? response.data : card) : [response.data, ...current]);
            setModalOpen(false); setEditingCard(null); form.reset();
        } catch (error) { setErrors(normalizeErrors(error)); } finally { setSubmitting(false); }
    };

    const loadCardTransactions = async (card, filters = transactionsFilters) => {
        if (!card) return;
        setTransactionsLoading(true);
        setTransactionsError('');
        try {
            const response = await FinancialService.listCreditCardTransactions(card.id, { wallet_id: card.wallet_id, month: filters.month, status: filters.status, page: filters.page, per_page: 20 });
            setCardTransactions(response.data ?? []);
            setTransactionsMeta(response.meta ?? null);
        } catch (error) {
            setTransactionsError(error.message);
        } finally {
            setTransactionsLoading(false);
        }
    };

    const openTransactions = (card) => {
        const filters = { month: new Date().toISOString().slice(0, 7), status: '', page: 1 };
        setTransactionsCard(card);
        setTransactionsFilters(filters);
        loadCardTransactions(card, filters);
    };
    const closeTransactions = () => { if (!transactionsLoading) setTransactionsCard(null); };
    const updateTransactionsFilters = (key, value) => {
        const filters = { ...transactionsFilters, [key]: value, page: 1 };
        setTransactionsFilters(filters);
        loadCardTransactions(transactionsCard, filters);
    };
    const changeTransactionsPage = (page) => {
        const filters = { ...transactionsFilters, page };
        setTransactionsFilters(filters);
        loadCardTransactions(transactionsCard, filters);
    };

    return { wallets, accounts, cards, loading, modalOpen, editingCard, errors, submitting, form, openModal, closeModal, changeWallet, submit, transactionsCard, cardTransactions, transactionsMeta, transactionsLoading, transactionsError, transactionsFilters, openTransactions, closeTransactions, updateTransactionsFilters, changeTransactionsPage };
}
