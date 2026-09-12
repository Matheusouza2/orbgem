import { useEffect, useState } from 'react';
import { useForm } from '@inertiajs/react';
import FinancialGoal from '@/Models/FinancialGoal';
import FinancialService from '@/Services/FinancialService';

const normalizeErrors = (error) => error?.errors ?? { general: error?.message ?? 'Não foi possível concluir a operação.' };

export default function useGoals() {
    const [wallets, setWallets] = useState([]);
    const [goals, setGoals] = useState([]);
    const [loading, setLoading] = useState(true);
    const [selectedWalletId, setSelectedWalletId] = useState('');
    const [modalOpen, setModalOpen] = useState(false);
    const [contributionOpen, setContributionOpen] = useState(false);
    const [editingGoal, setEditingGoal] = useState(null);
    const [selectedGoal, setSelectedGoal] = useState(null);
    const [errors, setErrors] = useState({});
    const [submitting, setSubmitting] = useState(false);
    const form = useForm({ ...FinancialGoal });
    const contributionForm = useForm({ amount: '', contributed_at: new Date().toISOString().slice(0, 10), note: '' });

    const loadGoals = async (walletId) => {
        if (!walletId) {
            setGoals([]);
            return;
        }
        setGoals(await FinancialService.listGoals(walletId));
    };

    useEffect(() => {
        FinancialService.listWallets()
            .then((availableWallets) => {
                setWallets(availableWallets);
                const firstWalletId = availableWallets[0]?.id ?? '';
                setSelectedWalletId(firstWalletId);
                return loadGoals(firstWalletId);
            })
            .catch((error) => setErrors(normalizeErrors(error)))
            .finally(() => setLoading(false));
    }, []);

    const selectWallet = (walletId) => {
        setSelectedWalletId(walletId);
        setErrors({});
        loadGoals(walletId).catch((error) => setErrors(normalizeErrors(error)));
    };

    const openModal = (goal = null) => {
        setErrors({});
        setEditingGoal(goal);
        form.reset();
        form.setData({ ...FinancialGoal, wallet_id: goal?.wallet_id ?? selectedWalletId, name: goal?.name ?? '', target_amount: goal ? (Number(goal.target_amount || 0) / 100).toFixed(2) : '', deadline: goal?.deadline ?? '' });
        setModalOpen(true);
    };

    const closeModal = () => { if (!submitting) setModalOpen(false); };

    const submit = async (event) => {
        event.preventDefault();
        setErrors({});
        setSubmitting(true);
        try {
            const payload = { wallet_id: Number(form.data.wallet_id), name: form.data.name, target_amount: Math.round(Number(form.data.target_amount || 0) * 100), deadline: form.data.deadline || null };
            const response = editingGoal ? await FinancialService.updateGoal(editingGoal.id, payload) : await FinancialService.createGoal(payload);
            if (Number(payload.wallet_id) === Number(selectedWalletId)) setGoals((current) => editingGoal ? current.map((goal) => goal.id === response.data.id ? response.data : goal) : [response.data, ...current]);
            if (Number(payload.wallet_id) !== Number(selectedWalletId)) await loadGoals(selectedWalletId);
            setModalOpen(false);
            form.reset();
        } catch (error) { setErrors(normalizeErrors(error)); } finally { setSubmitting(false); }
    };

    const openContribution = (goal) => {
        setSelectedGoal(goal);
        setErrors({});
        contributionForm.reset();
        contributionForm.setData('contributed_at', new Date().toISOString().slice(0, 10));
        setContributionOpen(true);
    };

    const submitContribution = async (event) => {
        event.preventDefault();
        setErrors({});
        setSubmitting(true);
        try {
            const response = await FinancialService.contributeToGoal(selectedGoal.id, { ...contributionForm.data, amount: Math.round(Number(contributionForm.data.amount || 0) * 100) });
            setGoals((current) => current.map((goal) => goal.id === response.data.id ? response.data : goal));
            setContributionOpen(false);
            contributionForm.reset();
        } catch (error) { setErrors(normalizeErrors(error)); } finally { setSubmitting(false); }
    };

    const remove = async (goal) => {
        if (!window.confirm(`Excluir a meta “${goal.name}”?`)) return;
        try { await FinancialService.deleteGoal(goal.id); setGoals((current) => current.filter((item) => item.id !== goal.id)); } catch (error) { setErrors(normalizeErrors(error)); }
    };

    return { wallets, goals, loading, selectedWalletId, selectWallet, modalOpen, contributionOpen, editingGoal, selectedGoal, errors, submitting, form, contributionForm, openModal, closeModal, openContribution, submit, submitContribution, setContributionOpen, remove };
}
