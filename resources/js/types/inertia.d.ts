import { route as routeFn } from 'ziggy-js';

declare global {
    /**
     * Ziggy's `route()` helper, exposed globally by the `@routes` Blade directive.
     * Typed from the generated Ziggy definitions in `vendor/tightenco/ziggy`.
     */
    const route: typeof routeFn;
}

/** The authenticated user as shared on every Inertia response. */
export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string | null;
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
            locale: string;
            notifications: AppNotification[];
            unreadCount: number;
        };
    }
}
