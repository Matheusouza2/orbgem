export const canEditCreditCardTransaction = (transaction) => (
    (transaction?.can_edit === true || (
        Boolean(transaction?.purchase_id)
        && Boolean(transaction?.purchase)
        && Boolean(transaction?.installment)
    ))
    && String(transaction.invoice?.status || '').toUpperCase() !== 'PAID'
);

export const canEffectivateTransaction = (transaction) => (
    transaction.status === 'PROJECTED'
    && (transaction.canManage !== false || transaction.recurring_transaction_id != null)
    && transaction.can_edit !== false
);
