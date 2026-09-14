const UPDATE_FIELDS = [
    'wallet_id',
    'account_id',
    'category_id',
    'merchant_id',
    'description',
    'type',
    'amount',
    'financial_instrument_type',
    'transaction_date',
    'competence_date',
    'due_date',
    'auto_post_on_due_date',
    'paid_at',
    'status',
    'payment_channel',
    'notes',
    'is_third_party',
];

export const buildTransactionUpdatePayload = (data, original = {}) => Object.fromEntries(
    UPDATE_FIELDS
        .filter((field) => Object.prototype.hasOwnProperty.call(data, field))
        .map((field) => {
            const value = data[field];
            const empty = value === null || value === undefined || value === '' || (field === 'amount' && Number(value) <= 0);

            return [field, empty && Object.prototype.hasOwnProperty.call(original, field) ? original[field] : value];
        }),
);
