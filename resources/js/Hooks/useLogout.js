import { router } from '@inertiajs/react';
import { useState } from 'react';

export default function useLogout() {
    const [processing, setProcessing] = useState(false);

    const logout = () => {
        setProcessing(true);
        router.post('/logout', {}, {
            onSuccess: () => window.localStorage.removeItem('orbgem_token'),
            onFinish: () => setProcessing(false),
        });
    };

    return { logout, processing };
}
