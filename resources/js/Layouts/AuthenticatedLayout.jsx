import ApplicationLogo from '@/Components/ApplicationLogo';
import BottomNav from '@/Components/BottomNav';
import Dropdown from '@/Components/Dropdown';
import NotificationBell from '@/Components/NotificationBell';
import Sidebar from '@/Components/Sidebar';
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

    // Close sidebar automatically on every Inertia navigation
    useEffect(() => {
        return router.on('navigate', () => setSidebarOpen(false));
    }, []);

    return (
        <div className="min-h-screen bg-gray-100">
            {/* Top bar */}
            <nav className="fixed inset-x-0 top-0 z-30 border-b border-gray-200 bg-white">
                <div className="flex h-16 items-center justify-between px-4 sm:px-6">
                    <div className="flex items-center gap-3">
                        {/* X button — visible on mobile only when the sidebar drawer is open */}
                        <button
                            type="button"
                            className={`inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-md text-gray-400 hover:bg-gray-100 hover:text-gray-500 focus:outline-none md:hidden ${
                                sidebarOpen ? 'visible' : 'invisible'
                            }`}
                            onClick={() => setSidebarOpen(false)}
                            aria-label={t('ui.close_menu')}
                        >
                            <X className="h-6 w-6" />
                        </button>

                        <Link href="/">
                            <ApplicationLogo className="block h-9 w-auto fill-current text-gray-800" />
                        </Link>
                    </div>

                    <div className="flex items-center gap-2">
                        <NotificationBell />
                        <Dropdown>
                            <Dropdown.Trigger>
                                <span className="inline-flex rounded-md">
                                    <button
                                        type="button"
                                        className="inline-flex min-h-[44px] items-center rounded-md border border-transparent bg-white px-3 text-sm font-medium leading-4 text-gray-500 transition hover:text-gray-700 focus:outline-none"
                                    >
                                        {user.name}
                                        <ChevronDown className="-me-0.5 ms-2 h-4 w-4" />
                                    </button>
                                </span>
                            </Dropdown.Trigger>

                            <Dropdown.Content>
                                <div className="border-b border-gray-100 px-4 py-2">
                                    <p className="text-sm font-medium text-gray-800">{user.name}</p>
                                    <p className="truncate text-xs text-gray-500">{user.email}</p>
                                </div>
                                <Dropdown.Link href={route('profile.edit')}>
                                    {t('ui.profile')}
                                </Dropdown.Link>
                                <Dropdown.Link href={route('logout')} method="post" as="button">
                                    {t('ui.logout')}
                                </Dropdown.Link>
                            </Dropdown.Content>
                        </Dropdown>
                    </div>
                </div>
            </nav>

            {/* Mobile sidebar backdrop — opacity transition for smooth fade */}
            <div
                aria-hidden="true"
                className={`fixed inset-0 z-20 bg-black/50 transition-opacity duration-200 ease-in-out md:hidden ${
                    sidebarOpen ? 'opacity-100' : 'pointer-events-none opacity-0'
                }`}
                onClick={() => setSidebarOpen(false)}
            />

            <div className="flex pt-16">
                {/* Sidebar — desktop always visible, mobile slides in as a drawer */}
                <div
                    className={`fixed inset-y-0 start-0 z-20 w-64 pt-16 transition-transform duration-200 ease-in-out md:z-auto md:translate-x-0 ${
                        sidebarOpen ? 'translate-x-0' : 'ltr:-translate-x-full rtl:translate-x-full'
                    }`}
                >
                    <Sidebar role={role} />
                </div>

                {/* Page content — pb-14 on mobile reserves space for the bottom nav */}
                <div className="flex min-h-[calc(100vh-4rem)] flex-1 flex-col pb-14 md:ms-64 md:pb-0">
                    {header && (
                        <header className="bg-white shadow">
                            <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                                {header}
                            </div>
                        </header>
                    )}

                    <main>{children}</main>
                </div>
            </div>

            {/* Bottom navigation — mobile only, primary nav for the most-used routes */}
            <BottomNav onMenuOpen={() => setSidebarOpen(true)} />
        </div>
    );
}
