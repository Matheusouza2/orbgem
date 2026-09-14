export const shouldShowCreditCardInvoicePaymentAction = (invoice) => (
    ['OPEN', 'OVERDUE', 'CLOSED'].includes(String(invoice?.status || '').toUpperCase())
);

export const canPayCreditCardInvoice = (invoice) => (
    shouldShowCreditCardInvoicePaymentAction(invoice)
    && Number(invoice?.amount || 0) > 0
);
