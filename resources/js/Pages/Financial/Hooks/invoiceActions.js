export const canPayCreditCardInvoice = (invoice) => (
    ['OPEN', 'OVERDUE', 'CLOSED'].includes(invoice?.status)
    && Number(invoice?.amount || 0) > 0
);
