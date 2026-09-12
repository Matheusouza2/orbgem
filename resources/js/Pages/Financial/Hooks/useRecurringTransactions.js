import { useEffect, useState } from 'react';
import { useForm } from '@inertiajs/react';
import RecurringTransaction from '@/Models/RecurringTransaction';
import FinancialService from '@/Services/FinancialService';

const normalizeErrors = (error) => error?.errors ?? { general: error?.message ?? 'Não foi possível concluir a operação.' };

export default function useRecurringTransactions() {
    const [wallets, setWallets] = useState([]);
    const [accounts, setAccounts] = useState([]);
    const [categories, setCategories] = useState([]);
    const [recurrences, setRecurrences] = useState([]);
    const [selectedWalletId, setSelectedWalletId] = useState('');
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [modalOpen, setModalOpen] = useState(false);
    const [generateOpen, setGenerateOpen] = useState(false);
    const [editingRecurrence, setEditingRecurrence] = useState(null);
    const [selectedRecurrence, setSelectedRecurrence] = useState(null);
    const [errors, setErrors] = useState({});
    const [message, setMessage] = useState('');
    const form = useForm({ ...RecurringTransaction });
    const generateForm = useForm({ until: new Date().toISOString().slice(0, 10) });

    const loadWalletData = async (walletId) => {
        if (!walletId) return;
        const [availableAccounts, availableCategories, availableRecurrences] = await Promise.all([
            FinancialService.listAccounts(walletId),
            FinancialService.listCategories(walletId),
            FinancialService.listRecurringTransactions(walletId),
        ]);
        setAccounts(availableAccounts);
        setCategories(availableCategories);
        setRecurrences(availableRecurrences);
    };

    useEffect(() => {
        FinancialService.listWallets()
            .then((availableWallets) => {
                setWallets(availableWallets);
                const firstWalletId = String(availableWallets[0]?.id ?? '');
                setSelectedWalletId(firstWalletId);
                return loadWalletData(firstWalletId);
            })
            .catch((error) => setErrors(normalizeErrors(error)))
            .finally(() => setLoading(false));
    }, []);

    const selectWallet = (walletId) => {
        setSelectedWalletId(walletId);
        setErrors({});
        setMessage('');
        setLoading(true);
        loadWalletData(walletId).catch((error) => setErrors(normalizeErrors(error))).finally(() => setLoading(false));
    };

    const openModal = (recurrence = null) => {
        setEditingRecurrence(recurrence);
        setErrors({});
        form.reset();
        form.setData({ ...RecurringTransaction, wallet_id: recurrence?.wallet_id ?? Number(selectedWalletId), account_id: recurrence?.account_id ?? null, category_id: recurrence?.category_id ?? null, description: recurrence?.description ?? '', type: recurrence?.type ?? 'EXPENSE', amount: recurrence ? (Number(recurrence.amount) / 100).toFixed(2) : '', frequency: recurrence?.frequency ?? 'MONTHLY', start_date: recurrence?.start_date ?? new Date().toISOString().slice(0, 10), end_date: recurrence?.end_date ?? null, due_day: recurrence?.due_day ?? null, auto_create: recurrence?.auto_create ?? false, active: recurrence?.active ?? true });
        setModalOpen(true);
    };

    const submit = async (event) => {
        event.preventDefault();
        setSubmitting(true);
        setErrors({});
        try {
            const payload = { ...form.data, wallet_id: Number(form.data.wallet_id), account_id: form.data.account_id ? Number(form.data.account_id) : null, category_id: form.data.category_id ? Number(form.data.category_id) : null, amount: Math.round(Number(form.data.amount || 0) * 100), due_day: form.data.due_day ? Number(form.data.due_day) : null, end_date: form.data.end_date || null };
            const response = editingRecurrence ? await FinancialService.updateRecurringTransaction(editingRecurrence.id, payload) : await FinancialService.createRecurringTransaction(payload);
            setRecurrences((current) => editingRecurrence ? current.map((item) => item.id === response.data.id ? response.data : item) : [response.data, ...current]);
            setModalOpen(false);
            form.reset();
        } catch (error) { setErrors(normalizeErrors(error)); } finally { setSubmitting(false); }
    };

    const remove = async (recurrence) => {
        if (!window.confirm(`Excluir a recorrência “${recurrence.description}”?`)) return;
        try { await FinancialService.deleteRecurringTransaction(recurrence.id); setRecurrences((current) => current.filter((item) => item.id !== recurrence.id)); } catch (error) { setErrors(normalizeErrors(error)); }
    };

    const openGenerate = (recurrence) => { setSelectedRecurrence(recurrence); setGenerateOpen(true); setErrors({}); };
    const generate = async (event) => {
        event.preventDefault();
        setSubmitting(true);
        setErrors({});
        try { const response = await FinancialService.generateRecurringTransactions(selectedRecurrence.id, generateForm.data); setMessage(`${response.data.length} lançamento(s) gerado(s).`); setGenerateOpen(false); } catch (error) { setErrors(normalizeErrors(error)); } finally { setSubmitting(false); }
    };

    return { wallets, accounts, categories, recurrences, selectedWalletId, selectWallet, loading, submitting, modalOpen, generateOpen, editingRecurrence, selectedRecurrence, errors, message, form, generateForm, openModal, setModalOpen, openGenerate, setGenerateOpen, submit, generate, remove };
}
