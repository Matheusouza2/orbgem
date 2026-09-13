import { useEffect, useState } from 'react';
import { useForm } from '@inertiajs/react';
import CreditCard from '@/Models/CreditCard';
import Transaction from '@/Models/Transaction';
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
    const [transactionsFilters, setTransactionsFilters] = useState({ month: new Date().toISOString().slice(0, 7), status: '', page: 1, include_third_party: true });
    const [transactionModalOpen, setTransactionModalOpen] = useState(false);
    const [transactionCategories, setTransactionCategories] = useState([]);
    const [transactionMerchants, setTransactionMerchants] = useState([]);
    const [transactionErrors, setTransactionErrors] = useState({});
    const [transactionSubmitting, setTransactionSubmitting] = useState(false);
    const [selectedCardForTransaction, setSelectedCardForTransaction] = useState(null);
    const form = useForm({ ...CreditCard });
    const transactionForm = useForm({ ...Transaction });

    useEffect(() => {
        FinancialService.listWallets().then(async (availableWallets) => {
            setWallets(availableWallets);
            const [walletAccounts, walletCards] = await Promise.all([
                Promise.all(availableWallets.map((wallet) => FinancialService.listAccounts(wallet.id))),
                Promise.all(availableWallets.map((wallet) => FinancialService.listCreditCards(wallet.id))),
            ]);
            setAccounts(walletAccounts.flat());
            setCards(walletCards.flat());
            const [walletCategories, walletMerchants] = await Promise.all([
                Promise.all(availableWallets.map((wallet) => FinancialService.listCategories(wallet.id))),
                Promise.all(availableWallets.map((wallet) => FinancialService.listMerchants(wallet.id))),
            ]);
            setTransactionCategories(walletCategories.flat());
            setTransactionMerchants(walletMerchants.flat());
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

    const openTransactionModal = (card) => {
        setTransactionErrors({});
        setSelectedCardForTransaction(card);
        transactionForm.reset();
        transactionForm.setData({
            ...Transaction,
            wallet_id: card.wallet_id,
            credit_card_id: card.id,
            financial_instrument_type: 'CREDIT_CARD',
            type: 'EXPENSE',
            effect: 'DEBIT',
            status: 'PROJECTED',
            transaction_date: new Date().toISOString().slice(0, 10),
            due_date: new Date().toISOString().slice(0, 10),
        });
        setTransactionModalOpen(true);
    };

    const closeTransactionModal = () => { if (!transactionSubmitting) setTransactionModalOpen(false); };

    const submitTransaction = async (event) => {
        event.preventDefault();
        setTransactionErrors({});
        setTransactionSubmitting(true);

        try {
            await FinancialService.createCreditCardPurchase({
                wallet_id: Number(selectedCardForTransaction.wallet_id),
                credit_card_id: Number(selectedCardForTransaction.id),
                category_id: transactionForm.data.category_id ? Number(transactionForm.data.category_id) : null,
                merchant_id: transactionForm.data.merchant_id ? Number(transactionForm.data.merchant_id) : null,
                is_third_party: Boolean(transactionForm.data.is_third_party),
                description: transactionForm.data.description,
                purchase_date: transactionForm.data.transaction_date,
                total_amount: Math.round(Number(transactionForm.data.amount || 0) * 100),
                installment_count: transactionForm.data.recurrence_type === 'INSTALLMENT' ? Number(transactionForm.data.installment_count) : 1,
            });
            transactionForm.reset();
            setSelectedCardForTransaction(null);
            setTransactionModalOpen(false);
        } catch (error) {
            setTransactionErrors(error?.errors ?? { general: error?.message ?? 'Não foi possível registrar a compra.' });
        } finally {
            setTransactionSubmitting(false);
        }
    };

    const loadCardTransactions = async (card, filters = transactionsFilters) => {
        if (!card) return;
        setTransactionsLoading(true);
        setTransactionsError('');
        try {
            const response = await FinancialService.listCreditCardTransactions(card.id, { wallet_id: card.wallet_id, month: filters.month, status: filters.status, include_third_party: filters.include_third_party ? '1' : '0', page: filters.page, per_page: 20 });
            setCardTransactions(response.data ?? []);
            setTransactionsMeta(response.meta ?? null);
        } catch (error) {
            setTransactionsError(error.message);
        } finally {
            setTransactionsLoading(false);
        }
    };

    const openTransactions = (card) => {
        const filters = { month: new Date().toISOString().slice(0, 7), status: '', page: 1, include_third_party: true };
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

    return { wallets, accounts, cards, loading, modalOpen, editingCard, errors, submitting, form, openModal, closeModal, changeWallet, submit, transactionsCard, cardTransactions, transactionsMeta, transactionsLoading, transactionsError, transactionsFilters, openTransactions, closeTransactions, updateTransactionsFilters, changeTransactionsPage, transactionModalOpen, transactionCategories, transactionMerchants, transactionErrors, transactionSubmitting, transactionForm, openTransactionModal, closeTransactionModal, submitTransaction, selectedCardForTransaction };
}
