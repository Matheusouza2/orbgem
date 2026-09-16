import { useEffect, useState } from 'react';
import { useForm } from '@inertiajs/react';
import Account from '@/Models/Account';
import Transaction from '@/Models/Transaction';
import Wallet from '@/Models/Wallet';
import FinancialService from '@/Services/FinancialService';
import { canManageTransaction } from './dashboardState';
import { buildTransactionUpdatePayload } from './transactionUpdatePayload';

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
    const [transactionModalOpen, setTransactionModalOpen] = useState(false);
    const [transactionCategories, setTransactionCategories] = useState([]);
    const [transactionMerchants, setTransactionMerchants] = useState([]);
    const [transactionErrors, setTransactionErrors] = useState({});
    const [transactionSubmitting, setTransactionSubmitting] = useState(false);
    const [selectedAccountForTransaction, setSelectedAccountForTransaction] = useState(null);
    const [editingTransaction, setEditingTransaction] = useState(null);
    const [accountTransactions, setAccountTransactions] = useState([]);
    const [accountTransactionsMeta, setAccountTransactionsMeta] = useState(null);
    const [accountTransactionsLoading, setAccountTransactionsLoading] = useState(false);
    const [accountTransactionsError, setAccountTransactionsError] = useState('');
    const [accountTransactionsFilters, setAccountTransactionsFilters] = useState({ month: new Date().toISOString().slice(0, 7), status: '', page: 1, include_third_party: true });
    const [selectedAccountForTransactions, setSelectedAccountForTransactions] = useState(null);
    const form = useForm({ ...Wallet });
    const accountForm = useForm({ ...Account });
    const transactionForm = useForm({ ...Transaction });

    useEffect(() => {
        FinancialService.listWallets()
            .then(async (availableWallets) => {
                setWallets(availableWallets);
                const walletAccounts = await Promise.all(availableWallets.map((wallet) => FinancialService.listAccounts(wallet.id)));
                setAccounts(walletAccounts.flat());
                const walletCategories = await Promise.all(availableWallets.map((wallet) => FinancialService.listCategories(wallet.id)));
                const walletMerchants = await Promise.all(availableWallets.map((wallet) => FinancialService.listMerchants(wallet.id)));
                setTransactionCategories(walletCategories.flat());
                setTransactionMerchants(walletMerchants.flat());
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

    const openTransactionModal = (account) => {
        setTransactionErrors({});
        setEditingTransaction(null);
        setSelectedAccountForTransaction(account);
        transactionForm.reset();
        transactionForm.setData({
            ...Transaction,
            wallet_id: account.wallet_id,
            account_id: account.id,
            financial_instrument_type: 'ACCOUNT',
            transaction_date: new Date().toISOString().slice(0, 10),
            due_date: new Date().toISOString().slice(0, 10),
        });
        setTransactionModalOpen(true);
    };

    const closeTransactionModal = () => {
        if (!transactionSubmitting) {
            setTransactionModalOpen(false);
            setEditingTransaction(null);
        }
    };

    const requestEditTransaction = (transaction) => {
        setTransactionErrors({});
        setEditingTransaction(transaction);
        setSelectedAccountForTransaction(selectedAccountForTransactions);
        transactionForm.reset();
        transactionForm.setData({
            ...Transaction,
            ...transaction,
            amount: (Number(transaction.amount || 0) / 100).toFixed(2),
            account_id: transaction.account_id ?? selectedAccountForTransactions?.id,
            credit_card_id: null,
            financial_instrument_type: 'ACCOUNT',
            due_date: transaction.due_date ?? '',
            paid_at: transaction.paid_at ?? null,
            recurrence_type: 'NONE',
        });
        setTransactionModalOpen(true);
    };

    const effectivateTransaction = async (transaction) => {
        setAccountTransactionsError('');
        try {
            await FinancialService.effectivateTransaction(transaction.id);
            await loadAccountTransactions(selectedAccountForTransactions, accountTransactionsFilters);
        } catch (error) {
            setAccountTransactionsError(error.message);
        }
    };

    const submitTransaction = async (event) => {
        event.preventDefault();
        setTransactionErrors({});
        setTransactionSubmitting(true);

        try {
            const payload = {
                ...transactionForm.data,
                wallet_id: Number(selectedAccountForTransaction.wallet_id),
                account_id: Number(selectedAccountForTransaction.id),
                credit_card_id: null,
                amount: Math.round(Number(transactionForm.data.amount || 0) * 100),
                effect: transactionForm.data.type === 'INCOME' ? 'CREDIT' : 'DEBIT',
                financial_instrument_type: 'ACCOUNT',
            };
            if (editingTransaction) await FinancialService.updateTransaction(editingTransaction.id, buildTransactionUpdatePayload(payload, editingTransaction));
            else await FinancialService.createTransaction(payload);
            transactionForm.reset();
            setSelectedAccountForTransaction(null);
            setEditingTransaction(null);
            setTransactionModalOpen(false);
            if (selectedAccountForTransactions) await loadAccountTransactions(selectedAccountForTransactions, accountTransactionsFilters);
        } catch (error) {
            setTransactionErrors(error?.errors ?? { general: error?.message ?? 'Não foi possível registrar o lançamento.' });
        } finally {
            setTransactionSubmitting(false);
        }
    };

    const loadAccountTransactions = async (account, filters = accountTransactionsFilters) => {
        if (!account) return;
        setAccountTransactionsLoading(true);
        setAccountTransactionsError('');

        try {
            const response = await FinancialService.listAccountTransactions(account.id, {
                wallet_id: account.wallet_id,
                month: filters.month,
                status: filters.status,
                include_third_party: filters.include_third_party ? '1' : '0',
                page: filters.page,
                per_page: 20,
            });
            setAccountTransactions((response.data ?? []).map((transaction) => ({ ...transaction, canManage: canManageTransaction(transaction) })));
            setAccountTransactionsMeta(response.meta ?? null);
        } catch (error) {
            setAccountTransactionsError(error.message);
        } finally {
            setAccountTransactionsLoading(false);
        }
    };

    const openAccountTransactions = (account) => {
        const filters = { month: new Date().toISOString().slice(0, 7), status: '', page: 1, include_third_party: true };
        setSelectedAccountForTransactions(account);
        setAccountTransactionsFilters(filters);
        loadAccountTransactions(account, filters);
    };

    const closeAccountTransactions = () => {
        if (!accountTransactionsLoading) setSelectedAccountForTransactions(null);
    };

    const updateAccountTransactionsFilters = (key, value) => {
        const filters = { ...accountTransactionsFilters, [key]: value, page: 1 };
        setAccountTransactionsFilters(filters);
        loadAccountTransactions(selectedAccountForTransactions, filters);
    };

    const changeAccountTransactionsPage = (page) => {
        const filters = { ...accountTransactionsFilters, page };
        setAccountTransactionsFilters(filters);
        loadAccountTransactions(selectedAccountForTransactions, filters);
    };

    return { wallets, accounts, loading, modalOpen, editingWallet, errors, submitting, form, openModal, closeModal, submit, accountModalOpen, selectedWalletForAccount, editingAccount, accountErrors, accountSubmitting, accountForm, openAccountModal, openEditAccountModal, selectWalletForAccount, closeAccountModal, submitAccount, transactionModalOpen, transactionCategories, transactionMerchants, transactionErrors, transactionSubmitting, transactionForm, openTransactionModal, closeTransactionModal, submitTransaction, selectedAccountForTransaction, editingTransaction, requestEditTransaction, effectivateTransaction, accountTransactions, accountTransactionsMeta, accountTransactionsLoading, accountTransactionsError, accountTransactionsFilters, selectedAccountForTransactions, openAccountTransactions, closeAccountTransactions, updateAccountTransactionsFilters, changeAccountTransactionsPage };
}
