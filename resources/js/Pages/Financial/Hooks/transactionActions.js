export const canEditCreditCardTransaction = (transaction) => (
    (transaction?.can_edit === true || (
        Boolean(transaction?.purchase_id)
        && Boolean(transaction?.purchase)
        && Boolean(transaction?.installment)
    ))
    && String(transaction.invoice?.status || '').toUpperCase() !== 'PAID'
);
