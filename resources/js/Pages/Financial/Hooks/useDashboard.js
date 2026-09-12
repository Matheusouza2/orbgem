import { useEffect, useRef, useState } from 'react';
import { useForm } from '@inertiajs/react';
import AccountTransfer from '@/Models/AccountTransfer';
import Merchant from '@/Models/Merchant';
import Transaction from '@/Models/Transaction';
import TransactionReversal from '@/Models/TransactionReversal';
import FinancialService from '@/Services/FinancialService';
import {
    canReverseTransaction,
    effectForType,
    emptySummary,
    isCurrentContext,
    normalizeErrors,
} from './dashboardState';

const currentMonth = () => new Date().toISOString().slice(0, 7);

const toCents = (value) => Math.round(Number(value) * 100);

export default function useDashboard() {
    const [wallets, setWallets] = useState([]);
    const [accounts, setAccounts] = useState([]);
    const [merchants, setMerchants] = useState([]);
    const [categories, setCategories] = useState([]);
    const [transactions, setTransactions] = useState([]);
    const [summary, setSummary] = useState(emptySummary);
    const [selectedWalletId, setSelectedWalletId] = useState('');
    const [selectedAccountId, setSelectedAccountId] = useState('');
    const [month, setMonth] = useState(currentMonth);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [action, setAction] = useState(null);
    const [error, setError] = useState('');
    const [apiErrors, setApiErrors] = useState({});
    const [transferErrors, setTransferErrors] = useState({});
    const [merchantErrors, setMerchantErrors] = useState({});
    const [reversalErrors, setReversalErrors] = useState({});
    const [transferOpen, setTransferOpen] = useState(false);
    const [merchantOpen, setMerchantOpen] = useState(false);
    const [reversalTransaction, setReversalTransaction] = useState(null);
    const requestId = useRef(0);

    const form = useForm({ ...Transaction });
    const transferForm = useForm({ ...AccountTransfer });
    const merchantForm = useForm({ ...Merchant });
    const reversalForm = useForm({ ...TransactionReversal });

    useEffect(() => {
        const controller = new AbortController();

        FinancialService.listWallets({ signal: controller.signal })
            .then((availableWallets) => {
                setWallets(availableWallets);
                setSelectedWalletId((current) => current || String(availableWallets[0]?.id ?? ''));
            })
            .catch((requestError) => {
                if (requestError.name !== 'AbortError') {
                    setError(requestError.message);
                }
            })
            .finally(() => setLoading(false));

        return () => controller.abort();
    }, []);

    useEffect(() => {
        if (!selectedWalletId) {
            setAccounts([]);
            setMerchants([]);
            setCategories([]);
            setTransactions([]);
            setSummary(emptySummary);
            return undefined;
        }

        const controller = new AbortController();
        const context = {
            id: ++requestId.current,
            walletId: String(selectedWalletId),
            accountId: String(selectedAccountId),
            month,
        };

        setLoading(true);
        setError('');

        Promise.all([
            FinancialService.listAccounts(selectedWalletId, { signal: controller.signal }),
            FinancialService.listMerchants(selectedWalletId, { signal: controller.signal }),
            FinancialService.listCategories(selectedWalletId, { signal: controller.signal }),
            FinancialService.listTransactions(selectedWalletId, selectedAccountId, month, { signal: controller.signal }),
            FinancialService.getSummary(selectedWalletId, month, { signal: controller.signal }),
        ])
            .then(([availableAccounts, availableMerchants, availableCategories, availableTransactions, availableSummary]) => {
                if (context.id !== requestId.current || controller.signal.aborted) return;

                setAccounts(availableAccounts);
                setMerchants(availableMerchants);
                setCategories(availableCategories);
                setTransactions(availableTransactions.map((transaction) => ({
                    ...transaction,
                    canReverse: canReverseTransaction(transaction),
                })));
                setSummary(availableSummary);
            })
            .catch((requestError) => {
                if (requestError.name !== 'AbortError' && context.id === requestId.current) {
                    setError(requestError.message);
                }
            })
            .finally(() => {
                if (context.id === requestId.current) setLoading(false);
            });

        return () => controller.abort();
    }, [selectedWalletId, selectedAccountId, month]);

    const reload = async () => {
        if (!selectedWalletId) return;

        const [availableAccounts, availableMerchants, availableCategories, availableTransactions, availableSummary] = await Promise.all([
            FinancialService.listAccounts(selectedWalletId),
            FinancialService.listMerchants(selectedWalletId),
            FinancialService.listCategories(selectedWalletId),
            FinancialService.listTransactions(selectedWalletId, selectedAccountId, month),
            FinancialService.getSummary(selectedWalletId, month),
        ]);

        setAccounts(availableAccounts);
        setMerchants(availableMerchants);
        setCategories(availableCategories);
        setTransactions(availableTransactions.map((transaction) => ({
            ...transaction,
            canReverse: canReverseTransaction(transaction),
        })));
        setSummary(availableSummary);
    };

    const runAction = async (name, operation, onSuccess) => {
        setAction(name);
        setSubmitting(true);
        setError('');

        try {
            await operation();
            await onSuccess();
        } catch (requestError) {
            setError(requestError.message);
            return requestError;
        } finally {
            setSubmitting(false);
            setAction(null);
        }

        return null;
    };

    const submitTransaction = async (event) => {
        event.preventDefault();
        setApiErrors({});
        const requestError = await runAction('transaction', () => FinancialService.createTransaction({
            ...form.data,
            wallet_id: Number(selectedWalletId),
            amount: toCents(form.data.amount),
            effect: effectForType(form.data.type),
            financial_instrument_type: 'ACCOUNT',
        }), reload);

        if (requestError) setApiErrors(normalizeErrors(requestError));
        else form.reset();

        return requestError;
    };

    const submitMerchant = async (event) => {
        event.preventDefault();
        setMerchantErrors({});
        const requestError = await runAction('merchant', () => FinancialService.createMerchant({
            ...merchantForm.data,
            wallet_id: Number(selectedWalletId),
        }), async () => {
            await reload();
            setMerchantOpen(false);
            merchantForm.reset();
        });

        if (requestError) setMerchantErrors(normalizeErrors(requestError));
    };

    const submitTransfer = async (event) => {
        event.preventDefault();
        setTransferErrors({});
        const requestError = await runAction('transfer', () => FinancialService.createTransfer({
            ...transferForm.data,
            wallet_id: Number(selectedWalletId),
            amount: toCents(transferForm.data.amount),
        }), async () => {
            await reload();
            setTransferOpen(false);
            transferForm.reset();
        });

        if (requestError) setTransferErrors(normalizeErrors(requestError));
    };

    const requestReversal = (transaction) => {
        setReversalTransaction(transaction);
        setReversalErrors({});
    };

    const confirmReversal = async (event) => {
        event.preventDefault();
        setReversalErrors({});
        const requestError = await runAction('reversal', () => FinancialService.reverseTransaction(reversalTransaction.id, reversalForm.data), async () => {
            await reload();
            setReversalTransaction(null);
            reversalForm.reset();
        });

        if (requestError) setReversalErrors(normalizeErrors(requestError));
    };

    return {
        wallets,
        accounts,
        merchants,
        categories,
        transactions,
        summary,
        selectedWalletId,
        selectedAccountId,
        month,
        loading,
        submitting,
        action,
        error,
        apiErrors,
        transferErrors,
        merchantErrors,
        reversalErrors,
        transferOpen,
        merchantOpen,
        reversalTransaction,
        form,
        transferForm,
        merchantForm,
        reversalForm,
        selectWallet: (walletId) => {
            setSelectedWalletId(walletId);
            setSelectedAccountId('');
        },
        selectAccount: setSelectedAccountId,
        setMonth,
        setTransferOpen,
        setMerchantOpen,
        setReversalTransaction,
        submitTransaction,
        submitMerchant,
        submitTransfer,
        requestReversal,
        confirmReversal,
        isCurrentContext,
    };
}
