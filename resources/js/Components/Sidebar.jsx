import { Link } from '@inertiajs/react';
import {
    Bell,
    Calendar,
    CalendarDays,
    Car,
    FileText,
    LayoutDashboard,
    Package,
    Settings,
    UserCog,
    Users,
} from 'lucide-react';

function SidebarLink({ href, active, icon: Icon, children }) {
    return (
        <Link
            href={href}
            className={`flex min-h-[44px] items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors ${
                active
                    ? 'bg-primary text-primary-foreground'
                    : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
            }`}
        >
            <Icon className="h-4 w-4 shrink-0" />
            {children}
        </Link>
    );
}

export default function Sidebar({ role }) {
    const isAdmin = role === 'admin';

    return (
        <aside className="flex h-full flex-col border-r border-border bg-card">
            <nav className="flex-1 space-y-1 px-3 py-4">
                <SidebarLink
                    href={route('dashboard')}
                    active={route().current('dashboard')}
                    icon={LayoutDashboard}
                >
                    Tableau de bord
                </SidebarLink>

                <SidebarLink
                    href={route('customers.index')}
                    active={route().current('customers.*')}
                    icon={Users}
                >
                    Clients
                </SidebarLink>

                <SidebarLink
                    href={route('vehicles.index')}
                    active={route().current('vehicles.*')}
                    icon={Car}
                >
                    Véhicules
                </SidebarLink>

                <SidebarLink
                    href={route('invoices.index')}
                    active={route().current('invoices.*')}
                    icon={FileText}
                >
                    Factures & Devis
                </SidebarLink>

                <SidebarLink
                    href={route('products.index')}
                    active={route().current('products.*')}
                    icon={Package}
                >
                    Produits
                </SidebarLink>

                <SidebarLink
                    href={route('appointments.index')}
                    active={
                        !route().current('appointments.today-page') &&
                        route().current('appointments.*')
                    }
                    icon={Calendar}
                >
                    Rendez-vous
                </SidebarLink>

                <SidebarLink
                    href={route('appointments.today-page')}
                    active={route().current('appointments.today-page')}
                    icon={CalendarDays}
                >
                    Aujourd'hui
                </SidebarLink>

                {isAdmin && (
                    <SidebarLink
                        href={route('reminders.index')}
                        active={route().current('reminders.*')}
                        icon={Bell}
                    >
                        Rappels
                    </SidebarLink>
                )}

                {isAdmin && (
                    <SidebarLink
                        href={route('users.index')}
                        active={route().current('users.*')}
                        icon={UserCog}
                    >
                        Utilisateurs
                    </SidebarLink>
                )}

                {isAdmin && (
                    <SidebarLink
                        href={route('settings.edit')}
                        active={route().current('settings.*')}
                        icon={Settings}
                    >
                        Paramètres
                    </SidebarLink>
                )}
            </nav>
        </aside>
    );
}
