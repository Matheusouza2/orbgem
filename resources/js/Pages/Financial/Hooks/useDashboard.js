import { useEffect, useRef, useState } from 'react';
import { useForm } from '@inertiajs/react';
import AccountTransfer from '@/Models/AccountTransfer';
import Merchant from '@/Models/Merchant';
import Transaction from '@/Models/Transaction';
import TransactionReversal from '@/Models/TransactionReversal';
import FinancialService from '@/Services/FinancialService';
import {
    canManageTransaction,
    canReverseTransaction,
    effectForType,
    emptySummary,
    isCurrentContext,
    normalizeErrors,
} from './dashboardState';
import { buildTransactionUpdatePayload } from './transactionUpdatePayload';

const currentMonth = () => new Date().toISOString().slice(0, 7);

const shiftMonth = (month, offset) => {
    const date = new Date(`${month}-01T12:00:00`);
    date.setMonth(date.getMonth() + offset);

    return date.toISOString().slice(0, 7);
};

const toCents = (value) => Math.round(Number(value) * 100);

export default function useDashboard() {
    const [wallets, setWallets] = useState([]);
    const [accounts, setAccounts] = useState([]);
    const [creditCards, setCreditCards] = useState([]);
    const [merchants, setMerchants] = useState([]);
    const [categories, setCategories] = useState([]);
    const [transactions, setTransactions] = useState([]);
    const [summary, setSummary] = useState(emptySummary);
    const [previousSummary, setPreviousSummary] = useState(emptySummary);
    const [nextSummary, setNextSummary] = useState(emptySummary);
    const [selectedWalletId, setSelectedWalletId] = useState('');
    const [selectedAccountId, setSelectedAccountId] = useState('');
    const [month, setMonth] = useState(currentMonth);
    const [includeThirdParty, setIncludeThirdParty] = useState(true);
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
    const [editingTransaction, setEditingTransaction] = useState(null);
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
            setCreditCards([]);
            setMerchants([]);
            setCategories([]);
            setTransactions([]);
            setSummary(emptySummary);
            setPreviousSummary(emptySummary);
            setNextSummary(emptySummary);
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
            FinancialService.listCreditCards(selectedWalletId, { signal: controller.signal }),
            FinancialService.listMerchants(selectedWalletId, { signal: controller.signal }),
            FinancialService.listCategories(selectedWalletId, { signal: controller.signal }),
            FinancialService.listTransactions(selectedWalletId, selectedAccountId, month, { signal: controller.signal }),
            FinancialService.getSummary(selectedWalletId, month, { signal: controller.signal, includeThirdParty }),
            FinancialService.getSummary(selectedWalletId, shiftMonth(month, -1), { signal: controller.signal, includeThirdParty }),
            FinancialService.getSummary(selectedWalletId, shiftMonth(month, 1), { signal: controller.signal, includeThirdParty }),
        ])
            .then(([availableAccounts, availableCreditCards, availableMerchants, availableCategories, availableTransactions, availableSummary, availablePreviousSummary, availableNextSummary]) => {
                if (context.id !== requestId.current || controller.signal.aborted) return;

                setAccounts(availableAccounts);
                setCreditCards(availableCreditCards);
                setMerchants(availableMerchants);
                setCategories(availableCategories);
                setTransactions(availableTransactions.map((transaction) => ({
                    ...transaction,
                    canReverse: canReverseTransaction(transaction),
                    canManage: canManageTransaction(transaction),
                })));
                setSummary(availableSummary);
                setPreviousSummary(availablePreviousSummary);
                setNextSummary(availableNextSummary);
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
    }, [selectedWalletId, selectedAccountId, month, includeThirdParty]);

    const reload = async () => {
        if (!selectedWalletId) return;

        const [availableAccounts, availableCreditCards, availableMerchants, availableCategories, availableTransactions, availableSummary, availablePreviousSummary, availableNextSummary] = await Promise.all([
            FinancialService.listAccounts(selectedWalletId),
            FinancialService.listCreditCards(selectedWalletId),
            FinancialService.listMerchants(selectedWalletId),
            FinancialService.listCategories(selectedWalletId),
            FinancialService.listTransactions(selectedWalletId, selectedAccountId, month),
            FinancialService.getSummary(selectedWalletId, month, { includeThirdParty }),
            FinancialService.getSummary(selectedWalletId, shiftMonth(month, -1), { includeThirdParty }),
            FinancialService.getSummary(selectedWalletId, shiftMonth(month, 1), { includeThirdParty }),
        ]);

        setAccounts(availableAccounts);
        setCreditCards(availableCreditCards);
        setMerchants(availableMerchants);
        setCategories(availableCategories);
        setTransactions(availableTransactions.map((transaction) => ({
            ...transaction,
            canReverse: canReverseTransaction(transaction),
            canManage: canManageTransaction(transaction),
        })));
        setSummary(availableSummary);
        setPreviousSummary(availablePreviousSummary);
        setNextSummary(availableNextSummary);
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
        const requestError = await runAction('transaction', () => {
            const transactionData = {
                ...form.data,
                wallet_id: Number(selectedWalletId),
                account_id: Number(form.data.account_id),
                credit_card_id: null,
                amount: toCents(form.data.amount),
                effect: effectForType(form.data.type),
                financial_instrument_type: 'ACCOUNT',
            };

            if (editingTransaction) return FinancialService.updateTransaction(editingTransaction.id, buildTransactionUpdatePayload(transactionData));

            if (form.data.financial_instrument_type === 'CREDIT_CARD') {
                return FinancialService.createCreditCardPurchase({
                    wallet_id: Number(selectedWalletId),
                    credit_card_id: Number(form.data.credit_card_id),
                    category_id: form.data.category_id ? Number(form.data.category_id) : null,
                    merchant_id: form.data.merchant_id ? Number(form.data.merchant_id) : null,
                    description: form.data.description,
                    purchase_date: form.data.transaction_date,
                    total_amount: toCents(form.data.amount),
                    installment_count: form.data.recurrence_type === 'INSTALLMENT' ? Number(form.data.installment_count) : 1,
                });
            }

            return FinancialService.createTransaction(transactionData);
        }, reload);

        if (requestError) setApiErrors(normalizeErrors(requestError));
        else {
            form.reset();
            setEditingTransaction(null);
        }

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

    const requestEdit = (transaction) => {
        setApiErrors({});
        setEditingTransaction(transaction);
        form.reset();
        form.setData({
            ...Transaction,
            ...transaction,
            amount: (Number(transaction.amount || 0) / 100).toFixed(2),
            due_date: transaction.due_date ?? '',
            paid_at: transaction.paid_at ?? null,
            recurrence_type: transaction.recurrence_type ?? 'NONE',
            payment_channel: transaction.payment_channel ?? 'PIX',
            financial_instrument_type: 'ACCOUNT',
            credit_card_id: null,
        });
    };

    const requestDelete = async (transaction) => {
        if (!window.confirm(`Excluir o lançamento “${transaction.description}”?`)) return;

        await runAction('transaction-delete', () => FinancialService.deleteTransaction(transaction.id), reload);
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
        creditCards,
        merchants,
        categories,
        transactions,
        summary,
        previousSummary,
        nextSummary,
        selectedWalletId,
        selectedAccountId,
        month,
        includeThirdParty,
        setIncludeThirdParty,
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
        editingTransaction,
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
        setEditingTransaction,
        submitTransaction,
        submitMerchant,
        submitTransfer,
        requestReversal,
        requestEdit,
        requestDelete,
        confirmReversal,
        isCurrentContext,
    };
}
