export const canEditCreditCardTransaction = (transaction) => (
    Boolean(transaction?.purchase_id)
    && Boolean(transaction?.purchase)
    && Boolean(transaction?.installment)
    && transaction.invoice?.status !== 'PAID'
);
