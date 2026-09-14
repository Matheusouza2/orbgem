export const canPayCreditCardInvoice = (invoice) => (
    ['OPEN', 'OVERDUE', 'CLOSED'].includes(String(invoice?.status || '').toUpperCase())
    && Number(invoice?.amount || 0) > 0
);
