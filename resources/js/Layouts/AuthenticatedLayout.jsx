import ApplicationLogo from '@/Components/ApplicationLogo';
import BottomNav from '@/Components/BottomNav';
import NotificationBell from '@/Components/NotificationBell';
import Sidebar from '@/Components/Sidebar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { useTranslation } from '@/helpers/i18n';
import { Link, router, usePage } from '@inertiajs/react';
import { ChevronDown, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

export default function AuthenticatedLayout({ header, children }) {
    const { t } = useTranslation();
    const { user, role } = usePage().props.auth;
    const flash = usePage().props.flash;
    const locale = usePage().props.locale;
    const [sidebarOpen, setSidebarOpen] = useState(false);

    useEffect(() => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
    }, [flash?.success, flash?.error]);

    useEffect(() => {
        document.documentElement.lang = locale ?? 'fr';
        document.documentElement.dir = locale === 'ar' ? 'rtl' : 'ltr';
    }, [locale]);

    useEffect(() => {
        return router.on('navigate', () => setSidebarOpen(false));
    }, []);

    useEffect(() => {
        if (!sidebarOpen) return;
        const handler = (e) => {
            if (e.key === 'Escape') setSidebarOpen(false);
        };
        document.addEventListener('keydown', handler);
        return () => document.removeEventListener('keydown', handler);
    }, [sidebarOpen]);

    return (
        <div className="min-h-screen bg-muted">
            {/* Top bar */}
            <nav className="fixed inset-x-0 top-0 z-30 border-b border-border bg-card">
                <div className="flex h-16 items-center justify-between px-4 sm:px-6">
                    <div className="flex items-center gap-3">
                        {sidebarOpen && (
                            <button
                                type="button"
                                className="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground focus:outline-none md:hidden"
                                onClick={() => setSidebarOpen(false)}
                                aria-label={t('ui.close_menu')}
                            >
                                <X className="h-6 w-6" />
                            </button>
                        )}

                        <Link href="/">
                            <ApplicationLogo className="block h-9 w-auto fill-current text-foreground" />
                        </Link>
                    </div>

                    <div className="flex items-center gap-2">
                        <NotificationBell />
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <button
                                    type="button"
                                    className="inline-flex min-h-[44px] items-center gap-1 rounded-md border border-transparent px-3 text-sm font-medium text-muted-foreground transition hover:text-foreground focus:outline-none"
                                >
                                    {user.name}
                                    <ChevronDown className="h-4 w-4" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-48">
                                <DropdownMenuLabel className="font-normal">
                                    <p className="text-sm font-medium text-foreground">
                                        {user.name}
                                    </p>
                                    <p className="truncate text-xs text-muted-foreground">
                                        {user.email}
                                    </p>
                                </DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem asChild>
                                    <Link href={route('profile.edit')} className="cursor-pointer">
                                        {t('ui.profile')}
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem asChild>
                                    <Link
                                        href={route('logout')}
                                        method="post"
                                        as="button"
                                        className="w-full cursor-pointer"
                                    >
                                        {t('ui.logout')}
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>
            </nav>

            {/* Mobile sidebar backdrop */}
            <div
                aria-hidden="true"
                className={`fixed inset-0 z-20 bg-black/50 transition-opacity duration-200 ease-in-out md:hidden ${
                    sidebarOpen ? 'opacity-100' : 'pointer-events-none opacity-0'
                }`}
                onClick={() => setSidebarOpen(false)}
            />

            <div className="flex pt-16">
                <div
                    className={`fixed inset-y-0 start-0 z-20 w-64 pt-16 transition-transform duration-200 ease-in-out md:z-auto md:!translate-x-0 ${
                        sidebarOpen ? 'translate-x-0' : 'ltr:-translate-x-full rtl:translate-x-full'
                    }`}
                >
                    <Sidebar role={role} />
                </div>

                <div className="flex min-h-[calc(100vh-4rem)] flex-1 flex-col pb-14 md:ms-64 md:pb-0">
                    {header && (
                        <header className="bg-card shadow">
                            <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                                {header}
                            </div>
                        </header>
                    )}

                    <main>{children}</main>
                </div>
            </div>

            <BottomNav onMenuOpen={() => setSidebarOpen(true)} />
        </div>
    );
}
