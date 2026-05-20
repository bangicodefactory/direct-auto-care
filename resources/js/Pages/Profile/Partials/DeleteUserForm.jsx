import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import { Button } from '@/Components/ui/button';
import TextInput from '@/Components/TextInput';
import { useTranslation } from '@/helpers/i18n';
import { useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';

export default function DeleteUserForm({ className = '' }) {
    const { t } = useTranslation();
    const [confirmingUserDeletion, setConfirmingUserDeletion] = useState(false);
    const passwordInput = useRef();

    const {
        data,
        setData,
        delete: destroy,
        processing,
        reset,
        errors,
        clearErrors,
    } = useForm({
        password: '',
    });

    const confirmUserDeletion = () => {
        setConfirmingUserDeletion(true);
    };

    const deleteUser = (e) => {
        e.preventDefault();

        destroy(route('profile.destroy'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onError: () => passwordInput.current.focus(),
            onFinish: () => reset(),
        });
    };

    const closeModal = () => {
        setConfirmingUserDeletion(false);

        clearErrors();
        reset();
    };

    return (
        <section className={`space-y-6 ${className}`}>
            <header>
                <h2 className="text-lg font-medium text-foreground">
                    {t('ui.delete_account_title')}
                </h2>

                <p className="mt-1 text-sm text-muted-foreground">{t('ui.delete_account_desc')}</p>
            </header>

            <Button variant="destructive" onClick={confirmUserDeletion}>
                {t('ui.delete')}
            </Button>

            <Modal show={confirmingUserDeletion} onClose={closeModal}>
                <form onSubmit={deleteUser} className="p-6">
                    <h2 className="text-lg font-medium text-foreground">
                        {t('ui.delete_account_confirm_title')}
                    </h2>

                    <p className="mt-1 text-sm text-muted-foreground">
                        {t('ui.delete_account_confirm_desc')}
                    </p>

                    <div className="mt-6">
                        <InputLabel
                            htmlFor="password"
                            value={t('ui.password')}
                            className="sr-only"
                        />

                        <TextInput
                            id="password"
                            type="password"
                            name="password"
                            ref={passwordInput}
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            className="mt-1 block w-3/4"
                            isFocused
                            placeholder={t('ui.password')}
                        />

                        <InputError message={errors.password} className="mt-2" />
                    </div>

                    <div className="mt-6 flex justify-end">
                        <Button variant="secondary" onClick={closeModal}>
                            {t('ui.cancel')}
                        </Button>

                        <Button variant="destructive" className="ms-3" disabled={processing}>
                            {t('ui.delete')}
                        </Button>
                    </div>
                </form>
            </Modal>
        </section>
    );
}
