import { Link } from '@inertiajs/react';
import { CalendarDays, FileText, LayoutDashboard, Menu, Users } from 'lucide-react';

const NAV_ITEMS = [
    {
        label: 'Accueil',
        icon: LayoutDashboard,
        href: () => route('dashboard'),
        match: 'dashboard',
    },
    {
        label: 'Clients',
        icon: Users,
        href: () => route('customers.index'),
        match: 'customers.*',
    },
    {
        label: 'Factures',
        icon: FileText,
        href: () => route('invoices.index'),
        match: 'invoices.*',
    },
    {
        label: "Aujourd'hui",
        icon: CalendarDays,
        href: () => route('appointments.today-page'),
        match: 'appointments.today-page',
    },
];

export default function BottomNav({ onMenuOpen }) {
    return (
        <nav className="fixed inset-x-0 bottom-0 z-20 border-t border-gray-200 bg-white md:hidden">
            <div className="flex h-14">
                {NAV_ITEMS.map(({ label, icon: Icon, href, match }) => {
                    const active = route().current(match);
                    return (
                        <Link
                            key={label}
                            href={href()}
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
                    aria-label="Menu"
                >
                    <Menu className="h-5 w-5" />
                    <span>Plus</span>
                </button>
            </div>
        </nav>
    );
}
