import { useForm } from '@inertiajs/react';
import AuthService from '@/Services/AuthService';
import Register from '@/Models/Register';

const normalizeErrors = (error) => Object.fromEntries(
    Object.entries(error?.errors ?? {}).map(([field, messages]) => [field, Array.isArray(messages) ? messages[0] : messages]),
);

export default function useRegister() {
    const form = useForm({ ...Register });

    const submit = async (event) => {
        event.preventDefault();
        form.clearErrors();

        try {
            await AuthService.register(form.data);
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
