import { useEffect, useState } from 'react';
import { useForm } from '@inertiajs/react';
import Account from '@/Models/Account';
import Wallet from '@/Models/Wallet';
import FinancialService from '@/Services/FinancialService';

const normalizeErrors = (error) => error?.errors ?? { general: error?.message ?? 'Não foi possível criar a carteira.' };

export default function useWallets() {
    const [wallets, setWallets] = useState([]);
    const [accounts, setAccounts] = useState([]);
    const [loading, setLoading] = useState(true);
    const [modalOpen, setModalOpen] = useState(false);
    const [editingWallet, setEditingWallet] = useState(null);
    const [errors, setErrors] = useState({});
    const [submitting, setSubmitting] = useState(false);
    const [accountModalOpen, setAccountModalOpen] = useState(false);
    const [editingAccount, setEditingAccount] = useState(null);
    const [selectedWalletForAccount, setSelectedWalletForAccount] = useState(null);
    const [accountErrors, setAccountErrors] = useState({});
    const [accountSubmitting, setAccountSubmitting] = useState(false);
    const form = useForm({ ...Wallet });
    const accountForm = useForm({ ...Account });

    useEffect(() => {
        FinancialService.listWallets()
            .then(async (availableWallets) => {
                setWallets(availableWallets);
                const walletAccounts = await Promise.all(availableWallets.map((wallet) => FinancialService.listAccounts(wallet.id)));
                setAccounts(walletAccounts.flat());
            })
            .catch((error) => setErrors(normalizeErrors(error)))
            .finally(() => setLoading(false));
    }, []);

    const openModal = (wallet = null) => {
        setErrors({});
        form.reset();
        setEditingWallet(wallet);

        if (wallet) {
            form.setData({
                ...Wallet,
                ...wallet,
            });
        }

        setModalOpen(true);
    };

    const closeModal = () => {
        if (!submitting) setModalOpen(false);
    };

    const submit = async (event) => {
        event.preventDefault();
        setErrors({});
        setSubmitting(true);

        try {
            const payload = { ...form.data };
            const response = editingWallet
                ? await FinancialService.updateWallet(editingWallet.id, payload)
                : await FinancialService.createWallet(payload);

            setWallets((currentWallets) => editingWallet
                ? currentWallets.map((wallet) => wallet.id === response.data.id ? response.data : wallet)
                : [response.data, ...currentWallets]);
            form.reset();
            setEditingWallet(null);
            setModalOpen(false);
        } catch (error) {
            setErrors(normalizeErrors(error));
        } finally {
            setSubmitting(false);
        }
    };

    const openAccountModal = (wallet = wallets[0]) => {
        if (!wallet) return;
        setAccountErrors({});
        accountForm.reset();
        accountForm.setData('wallet_id', wallet.id);
        setEditingAccount(null);
        setSelectedWalletForAccount(wallet);
        setAccountModalOpen(true);
    };

    const openEditAccountModal = (account) => {
        const wallet = wallets.find((item) => item.id === account.wallet_id);
        if (!wallet) return;

        setAccountErrors({});
        setEditingAccount(account);
        setSelectedWalletForAccount(wallet);
        accountForm.setData({ ...Account, ...account, initial_balance: (Number(account.initial_balance || 0) / 100).toFixed(2) });
        setAccountModalOpen(true);
    };

    const selectWalletForAccount = (wallet) => {
        if (!wallet) return;
        setSelectedWalletForAccount(wallet);
        accountForm.setData('wallet_id', wallet.id);
    };

    const closeAccountModal = () => {
        if (!accountSubmitting) setAccountModalOpen(false);
    };

    const submitAccount = async (event) => {
        event.preventDefault();
        setAccountErrors({});
        setAccountSubmitting(true);

        try {
            const payload = {
                ...accountForm.data,
                wallet_id: selectedWalletForAccount.id,
                initial_balance: Math.round(Number(accountForm.data.initial_balance || 0) * 100),
            };
            const response = editingAccount
                ? await FinancialService.updateAccount(editingAccount.id, payload)
                : await FinancialService.createAccount(payload);
            setAccounts((currentAccounts) => editingAccount
                ? currentAccounts.map((account) => account.id === response.data.id ? response.data : account)
                : [response.data, ...currentAccounts]);
            accountForm.reset();
            setEditingAccount(null);
            setAccountModalOpen(false);
        } catch (error) {
            setAccountErrors(normalizeErrors(error));
        } finally {
            setAccountSubmitting(false);
        }
    };

    return { wallets, accounts, loading, modalOpen, editingWallet, errors, submitting, form, openModal, closeModal, submit, accountModalOpen, selectedWalletForAccount, editingAccount, accountErrors, accountSubmitting, accountForm, openAccountModal, openEditAccountModal, selectWalletForAccount, closeAccountModal, submitAccount };
}
