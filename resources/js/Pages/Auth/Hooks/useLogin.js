import { useForm } from '@inertiajs/react';
import Login from '@/Models/Login';
import AuthService from '@/Services/AuthService';

const normalizeErrors = (error) => Object.fromEntries(
    Object.entries(error?.errors ?? {}).map(([field, messages]) => [field, Array.isArray(messages) ? messages[0] : messages]),
);

export default function useLogin() {
    const form = useForm({ ...Login });

    const submit = async (event) => {
        event.preventDefault();
        form.clearErrors();

        try {
            await AuthService.login(form.data);
            window.location.assign('/dashboard-financeiro');
        } catch (error) {
            const errors = normalizeErrors(error);

            if (Object.keys(errors).length > 0) {
                form.setError(errors);
            } else {
                form.setError('general', error.message);
            }
        }
    };

    return { form, submit };
}
