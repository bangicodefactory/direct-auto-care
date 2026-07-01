import { route as routeFn } from 'ziggy-js';
import type { User } from './models';

// Single source of truth for the entity lives in `./models`; re-exported here so
// `@/types/inertia` consumers (e.g. the shared layout/sidebar) keep one import path.
export type { User };

declare global {
    /**
     * Ziggy's `route()` helper, exposed globally by the `@routes` Blade directive.
     * Typed from the generated Ziggy definitions in `vendor/tightenco/ziggy`.
     */
    const route: typeof routeFn;
}

/** A database notification as shared with the notification bell. */
export interface AppNotification {
    id: string;
    type: string;
    message: string;
    read_at: string | null;
    created_at: string;
}

/**
 * Props shared with every page via `HandleInertiaRequests`. Typing them here
 * makes `usePage().props.auth` (etc.) available everywhere without a generic.
 * Domain/page-specific props are added per-page in Phase 5.
 */
declare module '@inertiajs/core' {
    interface InertiaConfig {
        sharedPageProps: {
            auth: {
                user: User;
                role: string | null;
            };
            flash: {
                success?: string;
                error?: string;
            };
            /** Per-client white-label identity, resolved from config on the server. */
            appSettings: {
                company_name: string;
                logo_url: string | null;
            };
            locale: string;
            notifications: AppNotification[];
            unreadCount: number;
        };
    }
}
