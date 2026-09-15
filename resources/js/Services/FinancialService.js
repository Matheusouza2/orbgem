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
    listAccounts: async (walletId, options = {}) => {
        const { month, ...requestOptions } = options;
        const params = new URLSearchParams({ wallet_id: String(walletId) });
        if (month) params.set('month', month);
        return (await request(`/api/v1/accounts?${params.toString()}`, requestOptions)).data;
    },
    createAccount: async (payload, options = {}) => request('/api/v1/accounts', { method: 'POST', body: JSON.stringify(payload), ...options }),
    updateAccount: async (accountId, payload, options = {}) => request(`/api/v1/accounts/${accountId}`, { method: 'PATCH', body: JSON.stringify(payload), ...options }),
    listCreditCards: async (walletId, options = {}) => {
        const { month, ...requestOptions } = options;
        const params = new URLSearchParams({ wallet_id: String(walletId) });
        if (month) params.set('month', month);
        return (await request(`/api/v1/credit-cards?${params.toString()}`, requestOptions)).data;
    },
    listCreditCardTransactions: async (cardId, params = {}, options = {}) => request(`/api/v1/credit-cards/${cardId}/transactions?${new URLSearchParams(params)}`, options),
    payCreditCardInvoice: async (invoiceId, payload, options = {}) => request(`/api/v1/credit-card-invoices/${invoiceId}/payments`, { method: 'POST', body: JSON.stringify(payload), ...options }),
    createCreditCard: async (payload, options = {}) => request('/api/v1/credit-cards', { method: 'POST', body: JSON.stringify(payload), ...options }),
    createCreditCardPurchase: async (payload, options = {}) => request('/api/v1/credit-card-purchases', { method: 'POST', body: JSON.stringify(payload), ...options }),
    updateCreditCardPurchase: async (purchaseId, payload, options = {}) => request(`/api/v1/credit-card-purchases/${purchaseId}`, { method: 'PUT', body: JSON.stringify(payload), ...options }),
    deleteCreditCardPurchase: async (purchaseId, options = {}) => request(`/api/v1/credit-card-purchases/${purchaseId}`, { method: 'DELETE', ...options }),
    updateCreditCardInstallment: async (transactionId, payload, options = {}) => request(`/api/v1/credit-card-transactions/${transactionId}`, { method: 'PUT', body: JSON.stringify(payload), ...options }),
    deleteCreditCardInstallment: async (transactionId, options = {}) => request(`/api/v1/credit-card-transactions/${transactionId}`, { method: 'DELETE', ...options }),
    updateCreditCard: async (cardId, payload, options = {}) => request(`/api/v1/credit-cards/${cardId}`, { method: 'PUT', body: JSON.stringify(payload), ...options }),
    listMerchants: async (walletId, options = {}) => (await request(`/api/v1/merchants?wallet_id=${walletId}`, options)).data,
    listCategories: async (walletId, options = {}) => (await request(`/api/v1/categories?wallet_id=${walletId}`, options)).data,
    listGoals: async (walletId, options = {}) => (await request(`/api/v1/financial-goals?wallet_id=${walletId}`, options)).data,
    createGoal: async (payload, options = {}) => request('/api/v1/financial-goals', { method: 'POST', body: JSON.stringify(payload), ...options }),
    updateGoal: async (goalId, payload, options = {}) => request(`/api/v1/financial-goals/${goalId}`, { method: 'PUT', body: JSON.stringify(payload), ...options }),
    deleteGoal: async (goalId, options = {}) => request(`/api/v1/financial-goals/${goalId}`, { method: 'DELETE', ...options }),
    contributeToGoal: async (goalId, payload, options = {}) => request(`/api/v1/financial-goals/${goalId}/contributions`, { method: 'POST', body: JSON.stringify(payload), ...options }),
    listInvestments: async (walletId, options = {}) => (await request(`/api/v1/investments?wallet_id=${walletId}`, options)).data,
    listInvestmentYields: async (investmentId, params = {}, options = {}) => (await request(`/api/v1/investments/${investmentId}/yields?${new URLSearchParams(params)}`, options)).data,
    listInvestmentIncome: async (walletId, params = {}, options = {}) => (await request(`/api/v1/investment-income?${new URLSearchParams({ wallet_id: walletId, ...params })}`, options)).data,
    createInvestmentIncome: async (payload, options = {}) => request('/api/v1/investment-income', { method: 'POST', body: JSON.stringify(payload), ...options }),
    listInvestmentPositions: async (investmentId, options = {}) => (await request(`/api/v1/investments/${investmentId}/positions`, options)).data,
    upsertInvestmentPosition: async (investmentId, payload, options = {}) => request(`/api/v1/investments/${investmentId}/positions`, { method: 'POST', body: JSON.stringify(payload), ...options }),
    deleteInvestmentPosition: async (positionId, options = {}) => request(`/api/v1/investment-positions/${positionId}`, { method: 'DELETE', ...options }),
    listInvestmentPositionHistory: async (walletId, options = {}) => (await request(`/api/v1/investment-position-history?wallet_id=${walletId}`, options)).data,
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
    listAccountTransactions: async (accountId, params = {}, options = {}) => request(`/api/v1/transactions?${new URLSearchParams({ account_id: accountId, ...params })}`, options),
    getSummary: async (walletId, month, options = {}) => {
        const { includeThirdParty = true, ...requestOptions } = options;
        const params = new URLSearchParams({ wallet_id: walletId, month, include_third_party: includeThirdParty ? '1' : '0' });

        return (await request(`/api/v1/monthly-summary?${params}`, requestOptions)).data;
    },
    createTransaction: async (payload, options = {}) => request('/api/v1/transactions', {
        method: 'POST',
        body: JSON.stringify(payload),
        ...options,
    }),
    updateTransaction: async (transactionId, payload, options = {}) => request(`/api/v1/transactions/${transactionId}`, { method: 'PUT', body: JSON.stringify(payload), ...options }),
    deleteTransaction: async (transactionId, options = {}) => request(`/api/v1/transactions/${transactionId}`, { method: 'DELETE', ...options }),
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
