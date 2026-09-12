import { useForm } from '@inertiajs/react';
import AuthService from '@/Services/AuthService';
import ForgotPassword from '@/Models/ForgotPassword';

const normalizeErrors = (error) => Object.fromEntries(
    Object.entries(error?.errors ?? {}).map(([field, messages]) => [field, Array.isArray(messages) ? messages[0] : messages]),
);

export default function useForgotPassword() {
    const form = useForm({ ...ForgotPassword });

    const submit = async (event) => {
        event.preventDefault();
        form.clearErrors();

        try {
            await AuthService.forgotPassword(form.data);
            form.setData('submitted', true);
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
