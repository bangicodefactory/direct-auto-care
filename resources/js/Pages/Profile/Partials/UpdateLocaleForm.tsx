import { Button } from '@/Components/ui/button';
import { useTranslation } from '@/helpers/i18n';
import { useForm, usePage } from '@inertiajs/react';

interface UpdateLocaleFormProps {
    className?: string;
}

export default function UpdateLocaleForm({ className = '' }: UpdateLocaleFormProps) {
    const { t } = useTranslation();
    const user = usePage().props.auth.user;

    const { data, setData, patch, processing, recentlySuccessful } = useForm({
        locale: user.locale ?? 'fr',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        patch(route('profile.locale'));
    }

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-foreground">
                    {t('ui.language_preference')}
                </h2>
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
                                className="text-primary focus:ring-ring"
                            />
                            <span className="text-sm text-foreground">{label}</span>
                        </label>
                    ))}
                </div>

                <div className="flex items-center gap-4">
                    <Button disabled={processing}>{t('ui.save')}</Button>

                    {recentlySuccessful && (
                        <p className="text-sm text-muted-foreground">{t('ui.language_saved')}</p>
                    )}
                </div>
            </form>
        </section>
    );
}
