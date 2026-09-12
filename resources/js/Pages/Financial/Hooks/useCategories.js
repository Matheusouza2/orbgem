import { useEffect, useRef, useState } from 'react';
import { useForm } from '@inertiajs/react';
import Category from '@/Models/Category';
import FinancialService from '@/Services/FinancialService';

const normalizeErrors = (error) => error?.errors ?? { general: error?.message ?? 'Não foi possível concluir a operação.' };

export default function useCategories() {
    const [wallets, setWallets] = useState([]);
    const [categories, setCategories] = useState([]);
    const [selectedWalletId, setSelectedWalletId] = useState('');
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [categoryErrors, setCategoryErrors] = useState({});
    const [modalOpen, setModalOpen] = useState(false);
    const [editingCategory, setEditingCategory] = useState(null);
    const [submitting, setSubmitting] = useState(false);
    const requestId = useRef(0);
    const form = useForm({ ...Category });

    useEffect(() => {
        const controller = new AbortController();

        FinancialService.listWallets({ signal: controller.signal })
            .then((availableWallets) => {
                setWallets(availableWallets);
                setSelectedWalletId((current) => current || String(availableWallets[0]?.id ?? ''));
            })
            .catch((requestError) => {
                if (requestError.name !== 'AbortError') setError(requestError.message);
            })
            .finally(() => setLoading(false));

        return () => controller.abort();
    }, []);

    useEffect(() => {
        if (!selectedWalletId) {
            setCategories([]);
            return undefined;
        }

        const controller = new AbortController();
        const currentRequestId = ++requestId.current;

        setLoading(true);
        setError('');

        FinancialService.listCategories(selectedWalletId, { signal: controller.signal })
            .then((availableCategories) => {
                if (currentRequestId === requestId.current && !controller.signal.aborted) setCategories(availableCategories);
            })
            .catch((requestError) => {
                if (requestError.name !== 'AbortError' && currentRequestId === requestId.current) setError(requestError.message);
            })
            .finally(() => {
                if (currentRequestId === requestId.current) setLoading(false);
            });

        return () => controller.abort();
    }, [selectedWalletId]);

    const openCreateModal = () => {
        setCategoryErrors({});
        form.reset();
        setEditingCategory(null);
        form.setData('wallet_id', Number(selectedWalletId));
        setModalOpen(true);
    };

    const openEditModal = (category) => {
        setCategoryErrors({});
        form.reset();
        setEditingCategory(category);
        form.setData({
            ...Category,
            ...category,
            parent_id: category.parent_id ?? '',
            parent: category.parent_id ? categories.find((parent) => parent.id === category.parent_id) ?? null : null,
            wallet_id: Number(selectedWalletId),
        });
        setModalOpen(true);
    };

    const closeCreateModal = () => {
        if (!submitting) setModalOpen(false);
    };

    const submitCategory = async (event) => {
        event.preventDefault();

        if (!selectedWalletId) {
            setCategoryErrors({ general: 'Selecione uma carteira antes de salvar a categoria.' });
            return;
        }

        setSubmitting(true);
        setCategoryErrors({});
        setError('');

        try {
            const { parent, ...categoryData } = form.data;

            const payload = {
                ...categoryData,
                wallet_id: Number(selectedWalletId),
                parent_id: form.data.parent_id ? Number(form.data.parent_id) : null,
            };
            if (editingCategory) {
                await FinancialService.updateCategory(editingCategory.id, payload);
            } else {
                await FinancialService.createCategory(payload);
            }
            const availableCategories = await FinancialService.listCategories(selectedWalletId);
            setCategories(availableCategories);
            setModalOpen(false);
            setEditingCategory(null);
            form.reset();
        } catch (requestError) {
            setCategoryErrors(normalizeErrors(requestError));
        } finally {
            setSubmitting(false);
        }
    };

    return {
        wallets,
        categories,
        selectedWalletId,
        loading,
        error,
        categoryErrors,
        modalOpen,
        editingCategory,
        submitting,
        form,
        selectWallet: setSelectedWalletId,
        openCreateModal,
        openEditModal,
        closeCreateModal,
        submitCategory,
    };
}
