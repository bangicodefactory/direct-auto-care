import ApplicationLogo from '@/Components/ApplicationLogo';
import { NavMain } from '@/Components/nav-main';
import { NavSecondary } from '@/Components/nav-secondary';
import { NavUser } from '@/Components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarRail,
} from '@/Components/ui/sidebar';
import { Link, usePage } from '@inertiajs/react';
import {
    Bell,
    Calendar,
    Car,
    FileText,
    LayoutDashboard,
    Package,
    Settings,
    UserCog,
    Users,
} from 'lucide-react';

function buildNavMain() {
    return [
        {
            labelKey: 'ui.nav_home',
            routeName: 'dashboard',
            icon: LayoutDashboard,
            active: route().current('dashboard'),
        },
        {
            labelKey: 'ui.nav_customers',
            routeName: 'customers.index',
            icon: Users,
            active: route().current('customers.*'),
        },
        {
            labelKey: 'ui.nav_vehicles',
            routeName: 'vehicles.index',
            icon: Car,
            active: route().current('vehicles.*'),
        },
        {
            labelKey: 'ui.nav_invoices',
            routeName: 'invoices.index',
            icon: FileText,
            active: route().current('invoices.*'),
        },
        {
            labelKey: 'ui.nav_products',
            routeName: 'products.index',
            icon: Package,
            active: route().current('products.*'),
        },
        {
            labelKey: 'ui.nav_appointments',
            icon: Calendar,
            active: route().current('appointments.*'),
            items: [
                {
                    labelKey: 'ui.nav_all',
                    routeName: 'appointments.index',
                    active:
                        route().current('appointments.*') &&
                        !route().current('appointments.today-page'),
                },
                {
                    labelKey: 'ui.nav_today',
                    routeName: 'appointments.today-page',
                    active: route().current('appointments.today-page'),
                },
            ],
        },
    ];
}

function buildNavAdmin() {
    return [
        {
            labelKey: 'ui.nav_reminders',
            routeName: 'reminders.index',
            icon: Bell,
            active: route().current('reminders.*'),
        },
        {
            labelKey: 'ui.nav_users',
            routeName: 'users.index',
            icon: UserCog,
            active: route().current('users.*'),
        },
        {
            labelKey: 'ui.nav_settings',
            routeName: 'settings.edit',
            icon: Settings,
            active: route().current('settings.*'),
        },
    ];
}

export default function AppSidebar({ user, role, ...props }) {
    const locale = usePage().props.locale;
    const isAdmin = role === 'admin';
    const side = locale === 'ar' ? 'right' : 'left';

    return (
        <Sidebar collapsible="icon" side={side} {...props}>
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild tooltip="Direct Auto Care">
                            <Link href={route('dashboard')}>
                                <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                                    <ApplicationLogo className="size-4 fill-current" />
                                </div>
                                <div className="grid flex-1 text-left text-sm leading-tight">
                                    <span className="truncate font-semibold">Direct Auto Care</span>
                                    <span className="truncate text-xs">Garage</span>
                                </div>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={buildNavMain()} />
                {isAdmin && <NavSecondary items={buildNavAdmin()} className="mt-auto" />}
            </SidebarContent>

            <SidebarFooter>
                <NavUser user={user} />
            </SidebarFooter>

            <SidebarRail />
        </Sidebar>
    );
}
