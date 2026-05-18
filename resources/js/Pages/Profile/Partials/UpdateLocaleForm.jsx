import PrimaryButton from '@/Components/PrimaryButton';
import { useTranslation } from '@/helpers/i18n';
import { Transition } from '@headlessui/react';
import { useForm, usePage } from '@inertiajs/react';

export default function UpdateLocaleForm({ className = '' }) {
    const { t } = useTranslation();
    const user = usePage().props.auth.user;

    const { data, setData, patch, processing, recentlySuccessful } = useForm({
        locale: user.locale ?? 'fr',
    });

    function submit(e) {
        e.preventDefault();
        patch(route('profile.locale'));
    }

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-gray-900">{t('ui.language_preference')}</h2>
            </header>

            <form onSubmit={submit} className="mt-6 space-y-6">
                <div className="flex gap-6">
                    {[
                        { value: 'fr', label: t('ui.locale_fr') },
                        { value: 'ar', label: t('ui.locale_ar') },
                    ].map(({ value, label }) => (
                        <label key={value} className="flex cursor-pointer items-center gap-2">
                            <input
                                type="radio"
                                name="locale"
                                value={value}
                                checked={data.locale === value}
                                onChange={() => setData('locale', value)}
                                className="text-indigo-600 focus:ring-indigo-500"
                            />
                            <span className="text-sm text-gray-700">{label}</span>
                        </label>
                    ))}
                </div>

                <div className="flex items-center gap-4">
                    <PrimaryButton disabled={processing}>{t('ui.save')}</PrimaryButton>

                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-in-out"
                        enterFrom="opacity-0"
                        leave="transition ease-in-out"
                        leaveTo="opacity-0"
                    >
                        <p className="text-sm text-gray-600">{t('ui.language_saved')}</p>
                    </Transition>
                </div>
            </form>
        </section>
    );
}
