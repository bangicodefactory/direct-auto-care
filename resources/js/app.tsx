import '../css/app.css';
import './bootstrap';

import { createInertiaApp, type ResolvedComponent } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import * as Sentry from '@sentry/react';
import { createRoot } from 'react-dom/client';
import { Toaster } from 'sonner';

// Browser error + performance reporting. No-op when VITE_SENTRY_DSN is unset
// (local dev without a DSN), so nothing is sent and the bundle stays inert.
const sentryDsn = import.meta.env.VITE_SENTRY_DSN;
if (sentryDsn) {
    Sentry.init({
        dsn: sentryDsn,
        release: import.meta.env.VITE_SENTRY_RELEASE || undefined,
        environment: import.meta.env.VITE_SENTRY_ENVIRONMENT || import.meta.env.MODE,
        integrations: [Sentry.browserTracingIntegration()],
        tracesSampleRate: Number(import.meta.env.VITE_SENTRY_TRACES_SAMPLE_RATE ?? 0.1),
        // Privacy: never attach request headers, cookies, or user IP automatically.
        sendDefaultPii: false,
    });
}

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
