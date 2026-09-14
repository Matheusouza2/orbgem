export const buildCreditCardInstallmentUpdatePayload = (data) => ({
    description: data.description,
    amount: Math.round(Number(data.amount || 0) * 100),
    transaction_date: data.transaction_date,
    category_id: data.category_id ? Number(data.category_id) : null,
    merchant_id: data.merchant_id ? Number(data.merchant_id) : null,
    is_third_party: Boolean(data.is_third_party),
});
