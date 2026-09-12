const RecurringTransaction = {
    wallet_id: null,
    account_id: null,
    category_id: null,
    description: '',
    type: 'EXPENSE',
    amount: 0,
    frequency: 'MONTHLY',
    start_date: new Date().toISOString().slice(0, 10),
    end_date: null,
    due_day: null,
    auto_create: false,
    active: true,
};

export default RecurringTransaction;
