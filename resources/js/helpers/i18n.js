import { usePage } from '@inertiajs/react';

/**
 * Minimal translation hook backed by Inertia shared props.
 * Translations are served from lang/{locale}/ui.php via HandleInertiaRequests.
 *
 * Usage: const { t } = useTranslation();
 *        t('ui.confirm')           → "Confirmer" / "تأكيد"
 *        t('ui.register_submit')   → "S'inscrire" / "إنشاء حساب"
 */
export function useTranslation() {
    const { translations } = usePage().props;

    function t(key, replacements = {}) {
        const [namespace, ...parts] = key.split('.');
        let value = translations?.[namespace];

        for (const part of parts) {
            value = value?.[part];
        }

        if (typeof value !== 'string') {
            return key;
        }

        return Object.entries(replacements).reduce(
            (str, [k, v]) => str.replace(`:${k}`, String(v)),
            value,
        );
    }

    return { t };
}
