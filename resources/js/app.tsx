import '../css/app.css';
import './bootstrap';

import { createInertiaApp, type ResolvedComponent } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { Toaster } from 'sonner';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent<ResolvedComponent>(
            [`./Pages/${name}.tsx`, `./Pages/${name}.jsx`],
            import.meta.glob<ResolvedComponent>('./Pages/**/*.{jsx,tsx}'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        const locale = (props.initialPage.props.locale as string | undefined) ?? 'fr';
        document.documentElement.lang = locale;
        document.documentElement.dir = locale === 'ar' ? 'rtl' : 'ltr';

        root.render(
            <>
                <App {...props} />
                <Toaster richColors position="top-right" />
            </>,
        );
    },
    progress: {
        color: '#4B5563',
    },
});
