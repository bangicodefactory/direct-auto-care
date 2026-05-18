import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

function SidebarLink({ href, active, children }) {
    return (
        <Link
            href={href}
            className={`flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors ${
                active
                    ? 'bg-primary text-primary-foreground'
                    : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
            }`}
        >
            {children}
        </Link>
    );
}

export default function Sidebar({ role }) {
    const { t } = useTranslation();

    const isAdmin = role === 'admin';

    return (
        <aside className="flex h-full flex-col border-r border-gray-200 bg-white">
            <nav className="flex-1 space-y-1 px-3 py-4">
                <SidebarLink href={route('dashboard')} active={route().current('dashboard')}>
                    {t('sidebar.dashboard')}
                </SidebarLink>

                <SidebarLink
                    href={route('customers.index')}
                    active={route().current('customers.*')}
                >
                    {t('sidebar.customers')}
                </SidebarLink>

                <SidebarLink href={route('invoices.index')} active={route().current('invoices.*')}>
                    {t('sidebar.invoices')}
                </SidebarLink>

                <SidebarLink href={route('products.index')} active={route().current('products.*')}>
                    {t('sidebar.products')}
                </SidebarLink>

                <SidebarLink
                    href={route('appointments.index')}
                    active={
                        !route().current('appointments.today-page') &&
                        route().current('appointments.*')
                    }
                >
                    {t('sidebar.appointments')}
                </SidebarLink>

                <SidebarLink
                    href={route('appointments.today-page')}
                    active={route().current('appointments.today-page')}
                >
                    {t('sidebar.today')}
                </SidebarLink>

                {isAdmin && (
                    <SidebarLink
                        href={route('reminders.index')}
                        active={route().current('reminders.*')}
                    >
                        {t('sidebar.reminders')}
                    </SidebarLink>
                )}

                {isAdmin && (
                    <SidebarLink href={route('users.index')} active={route().current('users.*')}>
                        {t('sidebar.users')}
                    </SidebarLink>
                )}

                {isAdmin && (
                    <SidebarLink
                        href={route('settings.edit')}
                        active={route().current('settings.*')}
                    >
                        {t('sidebar.settings')}
                    </SidebarLink>
                )}
            </nav>
        </aside>
    );
}
