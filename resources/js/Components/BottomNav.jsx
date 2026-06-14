import { useTranslation } from '@/helpers/i18n';
import { Link } from '@inertiajs/react';
import { CalendarDays, FileText, LayoutDashboard, Menu, Users, Wrench } from 'lucide-react';

export default function BottomNav({ onMenuOpen }) {
    const { t } = useTranslation();

    const navItems = [
        {
            label: t('ui.nav_home'),
            icon: LayoutDashboard,
            href: route('dashboard'),
            match: 'dashboard',
        },
        {
            label: t('ui.nav_customers'),
            icon: Users,
            href: route('customers.index'),
            match: 'customers.*',
        },
        {
            label: t('ui.nav_invoices'),
            icon: FileText,
            href: route('invoices.index'),
            match: 'invoices.*',
        },
        {
            label: t('ui.nav_today'),
            icon: CalendarDays,
            href: route('appointments.today-page'),
            match: 'appointments.today-page',
        },
        {
            label: t('ui.nav_workshop'),
            icon: Wrench,
            href: route('workshop.index'),
            match: 'workshop.*',
        },
    ];

    return (
        <nav className="fixed inset-x-0 bottom-0 z-20 border-t border-gray-200 bg-white md:hidden">
            <div className="flex h-14">
                {navItems.map(({ label, icon: Icon, href, match }) => {
                    const active = route().current(match);
                    return (
                        <Link
                            key={match}
                            href={href}
                            aria-current={active ? 'page' : undefined}
                            className={`flex flex-1 flex-col items-center justify-center gap-0.5 text-xs font-medium transition-colors ${
                                active ? 'text-primary' : 'text-gray-500 hover:text-gray-700'
                            }`}
                        >
                            <Icon className="h-5 w-5" />
                            <span>{label}</span>
                        </Link>
                    );
                })}

                <button
                    type="button"
                    onClick={onMenuOpen}
                    className="flex flex-1 flex-col items-center justify-center gap-0.5 text-xs font-medium text-gray-500 hover:text-gray-700"
                    aria-label={t('ui.open_menu')}
                >
                    <Menu className="h-5 w-5" />
                    <span>{t('ui.nav_more')}</span>
                </button>
            </div>
        </nav>
    );
}
