const login = async (payload) => {
    const response = await fetch('/api/v1/auth/login', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(payload),
    });

    const result = await response.json().catch(() => ({}));

    if (!response.ok) {
        const error = new Error(result.message ?? 'Não foi possível entrar na sua conta.');
        error.errors = result.errors ?? {};
        throw error;
    }

    window.localStorage.setItem('orbgem_token', result.data.token);

    return result.data;
};

const register = async (payload) => {
    const response = await fetch('/api/v1/auth/register', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(payload),
    });

    const result = await response.json().catch(() => ({}));

    if (!response.ok) {
        const error = new Error(result.message ?? 'Não foi possível criar sua conta.');
        error.errors = result.errors ?? {};
        throw error;
    }

    window.localStorage.setItem('orbgem_token', result.data.token);

    return result.data;
};

const forgotPassword = async (payload) => {
    const response = await fetch('/api/v1/auth/forgot-password', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(payload),
    });

    const result = await response.json().catch(() => ({}));

    if (!response.ok) {
        const error = new Error(result.message ?? 'Não foi possível solicitar a recuperação.');
        error.errors = result.errors ?? {};
        throw error;
    }

    return result;
};

const AuthService = { login, register, forgotPassword };

export default AuthService;
