let csrfCookieRequest;

const ensureCsrfCookie = async () => {
    csrfCookieRequest ??= fetch('/sanctum/csrf-cookie', {
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    }).then((response) => {
        if (!response.ok) throw new Error('Não foi possível iniciar a sessão segura.');
    });

    await csrfCookieRequest;
};

const xsrfToken = () => {
    const cookie = document.cookie.split('; ').find((item) => item.startsWith('XSRF-TOKEN='));

    return cookie ? decodeURIComponent(cookie.split('=').slice(1).join('=')) : '';
};

const request = async (url, options = {}) => {
    const { headers = {}, ...requestOptions } = options;

    if (requestOptions.method && requestOptions.method !== 'GET') await ensureCsrfCookie();

    const response = await fetch(url, {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(requestOptions.method && requestOptions.method !== 'GET' ? { 'X-XSRF-TOKEN': xsrfToken() } : {}),
            ...(requestOptions.body ? { 'Content-Type': 'application/json' } : {}),
            ...headers,
        },
        ...requestOptions,
    });

    if (!response.ok) {
        const payload = await response.json().catch(() => ({}));
        const error = new Error(payload.message ?? 'Não foi possível carregar os dados financeiros.');
        error.errors = payload.errors ?? {};
        error.status = response.status;
        throw error;
    }

    return response.json();
};

const FinancialService = {
    listWallets: async (options = {}) => (await request('/api/v1/wallets', options)).data,
    createWallet: async (payload, options = {}) => request('/api/v1/wallets', { method: 'POST', body: JSON.stringify(payload), ...options }),
    updateWallet: async (walletId, payload, options = {}) => request(`/api/v1/wallets/${walletId}`, { method: 'PATCH', body: JSON.stringify(payload), ...options }),
    listAccounts: async (walletId, options = {}) => (await request(`/api/v1/accounts?wallet_id=${walletId}`, options)).data,
    createAccount: async (payload, options = {}) => request('/api/v1/accounts', { method: 'POST', body: JSON.stringify(payload), ...options }),
    updateAccount: async (accountId, payload, options = {}) => request(`/api/v1/accounts/${accountId}`, { method: 'PATCH', body: JSON.stringify(payload), ...options }),
    listCreditCards: async (walletId, options = {}) => (await request(`/api/v1/credit-cards?wallet_id=${walletId}`, options)).data,
    createCreditCard: async (payload, options = {}) => request('/api/v1/credit-cards', { method: 'POST', body: JSON.stringify(payload), ...options }),
    updateCreditCard: async (cardId, payload, options = {}) => request(`/api/v1/credit-cards/${cardId}`, { method: 'PUT', body: JSON.stringify(payload), ...options }),
    listMerchants: async (walletId, options = {}) => (await request(`/api/v1/merchants?wallet_id=${walletId}`, options)).data,
    listCategories: async (walletId, options = {}) => (await request(`/api/v1/categories?wallet_id=${walletId}`, options)).data,
    listGoals: async (walletId, options = {}) => (await request(`/api/v1/financial-goals?wallet_id=${walletId}`, options)).data,
    createGoal: async (payload, options = {}) => request('/api/v1/financial-goals', { method: 'POST', body: JSON.stringify(payload), ...options }),
    updateGoal: async (goalId, payload, options = {}) => request(`/api/v1/financial-goals/${goalId}`, { method: 'PUT', body: JSON.stringify(payload), ...options }),
    deleteGoal: async (goalId, options = {}) => request(`/api/v1/financial-goals/${goalId}`, { method: 'DELETE', ...options }),
    contributeToGoal: async (goalId, payload, options = {}) => request(`/api/v1/financial-goals/${goalId}/contributions`, { method: 'POST', body: JSON.stringify(payload), ...options }),
    listInvestments: async (walletId, options = {}) => (await request(`/api/v1/investments?wallet_id=${walletId}`, options)).data,
    createInvestment: async (payload, options = {}) => request('/api/v1/investments', { method: 'POST', body: JSON.stringify(payload), ...options }),
    updateInvestment: async (investmentId, payload, options = {}) => request(`/api/v1/investments/${investmentId}`, { method: 'PUT', body: JSON.stringify(payload), ...options }),
    deleteInvestment: async (investmentId, options = {}) => request(`/api/v1/investments/${investmentId}`, { method: 'DELETE', ...options }),
    getInvestmentQuote: async (symbol, options = {}) => (await request(`/api/v1/market/quote?symbol=${encodeURIComponent(symbol)}`, options)).data,
    getOpenFinanceToken: async (walletId, itemId = null, options = {}) => request('/api/v1/open-finance/connect-token', { method: 'POST', body: JSON.stringify({ wallet_id: walletId, item_id: itemId }), ...options }),
    listOpenFinanceItems: async (options = {}) => (await request('/api/v1/open-finance/items', options)).data,
    storeOpenFinanceItem: async (payload, options = {}) => request('/api/v1/open-finance/items', { method: 'POST', body: JSON.stringify(payload), ...options }),
    deleteOpenFinanceItem: async (itemId, options = {}) => request(`/api/v1/open-finance/items/${itemId}`, { method: 'DELETE', ...options }),
    syncOpenFinanceConnection: async (connectionId, payload, options = {}) => request(`/api/v1/open-finance/connections/${connectionId}/sync`, { method: 'POST', body: JSON.stringify(payload), ...options }),
    listTransactions: async (walletId, accountId, month, options = {}) => {
        const params = new URLSearchParams({ wallet_id: walletId, month });

        if (accountId) params.set('account_id', accountId);

        return (await request(`/api/v1/transactions?${params}`, options)).data;
    },
    getSummary: async (walletId, month, options = {}) => (await request(`/api/v1/monthly-summary?wallet_id=${walletId}&month=${month}`, options)).data,
    createTransaction: async (payload, options = {}) => request('/api/v1/transactions', {
        method: 'POST',
        body: JSON.stringify(payload),
        ...options,
    }),
    listRecurringTransactions: async (walletId, options = {}) => (await request(`/api/v1/recurring-transactions?wallet_id=${walletId}`, options)).data,
    createRecurringTransaction: async (payload, options = {}) => request('/api/v1/recurring-transactions', { method: 'POST', body: JSON.stringify(payload), ...options }),
    updateRecurringTransaction: async (recurringId, payload, options = {}) => request(`/api/v1/recurring-transactions/${recurringId}`, { method: 'PUT', body: JSON.stringify(payload), ...options }),
    deleteRecurringTransaction: async (recurringId, options = {}) => request(`/api/v1/recurring-transactions/${recurringId}`, { method: 'DELETE', ...options }),
    generateRecurringTransactions: async (recurringId, payload, options = {}) => request(`/api/v1/recurring-transactions/${recurringId}/generate`, { method: 'POST', body: JSON.stringify(payload), ...options }),
    createMerchant: async (payload, options = {}) => request('/api/v1/merchants', { method: 'POST', body: JSON.stringify(payload), ...options }),
    createCategory: async (payload, options = {}) => request('/api/v1/categories', { method: 'POST', body: JSON.stringify(payload), ...options }),
    updateCategory: async (categoryId, payload, options = {}) => request(`/api/v1/categories/${categoryId}`, { method: 'PATCH', body: JSON.stringify(payload), ...options }),
    createTransfer: async (payload, options = {}) => request('/api/v1/account-transfers', { method: 'POST', body: JSON.stringify(payload), ...options }),
    reverseTransaction: async (transactionId, payload, options = {}) => request(`/api/v1/transactions/${transactionId}/reversal`, { method: 'POST', body: JSON.stringify(payload), ...options }),
};

export default FinancialService;
