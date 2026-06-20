import path from 'path';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import { sentryVitePlugin } from '@sentry/vite-plugin';

// Source-map upload only runs when an auth token is present (the deploy build in
// CI). PR CI builds and local builds have no token, so the plugin is skipped and
// no source maps are emitted or shipped to the browser.
const sentryAuthToken = process.env.SENTRY_AUTH_TOKEN;
const sentryRelease = process.env.SENTRY_RELEASE;

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.tsx',
            refresh: true,
        }),
        react(),
        // Must be last so it sees the final bundle. No-op without a token.
        sentryAuthToken &&
            sentryVitePlugin({
                org: process.env.SENTRY_ORG,
                project: process.env.SENTRY_PROJECT,
                authToken: sentryAuthToken,
                release: sentryRelease ? { name: sentryRelease } : undefined,
                // 'hidden' source maps below are uploaded then deleted by the plugin.
                sourcemaps: { filesToDeleteAfterUpload: ['**/*.map'] },
            }),
    ].filter(Boolean),
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
    build: {
        // Emit source maps only for the Sentry upload build; never reference them
        // from the served JS ('hidden'), so they aren't exposed to end users.
        sourcemap: sentryAuthToken ? 'hidden' : false,
    },
    optimizeDeps: {
        include: ['react-big-calendar/lib/addons/dragAndDrop'],
    },
});
