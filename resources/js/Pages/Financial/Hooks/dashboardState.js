export const emptySummary = {
    balance: 0,
    actual_expenses: 0,
    forecast_expenses: 0,
    actual_income: 0,
    forecast_income: 0,
};

export const normalizeErrors = (requestError) => {
    const fieldErrors = requestError?.errors ?? {};

    return Object.keys(fieldErrors).length > 0
        ? fieldErrors
        : { general: requestError?.message || 'Não foi possível concluir a operação.' };
};

export const effectForType = (type) => type === 'INCOME' ? 'CREDIT' : 'DEBIT';

export const canReverseTransaction = (transaction) => (
    transaction.status !== 'CANCELLED'
    && transaction.effect !== 'NONE'
    && !transaction.reversal_of_transaction_id
    && !transaction.has_reversal
    && (transaction.type !== 'TRANSFER' || transaction.effect === 'DEBIT')
);

export const canManageTransaction = (transaction) => (
    transaction.financial_instrument_type === 'ACCOUNT'
    && transaction.type !== 'TRANSFER'
    && !transaction.reversal_of_transaction_id
    && !transaction.transfer_group_id
    && !transaction.installment_id
    && !transaction.credit_card_invoice_id
    && !transaction.recurring_transaction_id
    && !transaction.financial_commitment_id
    && !transaction.has_reversal
);

export const isCurrentContext = (currentContext, candidateContext) => (
    currentContext.id === candidateContext.id
    && currentContext.walletId === candidateContext.walletId
    && currentContext.accountId === candidateContext.accountId
    && currentContext.month === candidateContext.month
);
