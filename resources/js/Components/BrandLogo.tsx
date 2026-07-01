import ApplicationLogo from '@/Components/ApplicationLogo';
import { usePage } from '@inertiajs/react';

/**
 * White-label logo. Renders the client's uploaded logo when one is provisioned
 * (COMPANY_LOGO), otherwise falls back to the themed Direct Auto Care mark, which
 * tracks --primary. Pass sizing via `className` (e.g. `h-8 w-8`).
 */
export default function BrandLogo({ className }: { className?: string }) {
    const { appSettings } = usePage().props;

    if (appSettings.logo_url) {
        return (
            <img src={appSettings.logo_url} alt={appSettings.company_name} className={className} />
        );
    }

    return <ApplicationLogo className={className} />;
}
