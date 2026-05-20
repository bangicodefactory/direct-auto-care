import ApplicationLogo from '@/Components/ApplicationLogo';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarRail,
} from '@/Components/ui/sidebar';
import { useTranslation } from '@/helpers/i18n';
import { Link } from '@inertiajs/react';
import {
    Bell,
    Calendar,
    CalendarDays,
    Car,
    ChevronsUpDown,
    FileText,
    LayoutDashboard,
    LogOut,
    Package,
    Settings,
    UserCog,
    Users,
} from 'lucide-react';

const navMain = [
    { label: 'Tableau de bord', routeName: 'dashboard', icon: LayoutDashboard, match: 'dashboard' },
    { label: 'Clients', routeName: 'customers.index', icon: Users, match: 'customers.*' },
    { label: 'Véhicules', routeName: 'vehicles.index', icon: Car, match: 'vehicles.*' },
    {
        label: 'Factures & Devis',
        routeName: 'invoices.index',
        icon: FileText,
        match: 'invoices.*',
    },
    { label: 'Produits', routeName: 'products.index', icon: Package, match: 'products.*' },
    {
        label: 'Rendez-vous',
        routeName: 'appointments.index',
        icon: Calendar,
        match: null,
    },
    {
        label: "Aujourd'hui",
        routeName: 'appointments.today-page',
        icon: CalendarDays,
        match: 'appointments.today-page',
    },
];

const navAdmin = [
    { label: 'Rappels', routeName: 'reminders.index', icon: Bell, match: 'reminders.*' },
    { label: 'Utilisateurs', routeName: 'users.index', icon: UserCog, match: 'users.*' },
    { label: 'Paramètres', routeName: 'settings.edit', icon: Settings, match: 'settings.*' },
];

function isActive(item) {
    if (item.match === null) {
        return !route().current('appointments.today-page') && route().current('appointments.*');
    }
    return route().current(item.match);
}

export default function AppSidebar({ user, role, ...props }) {
    const { t } = useTranslation();
    const isAdmin = role === 'admin';
    const initials = user.name.charAt(0).toUpperCase();

    return (
        <Sidebar collapsible="icon" {...props}>
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild tooltip="Direct Auto Care">
                            <Link href={route('dashboard')}>
                                <ApplicationLogo className="h-8 w-8 shrink-0 fill-current text-primary" />
                                <div className="grid flex-1 text-left text-sm leading-tight">
                                    <span className="truncate font-semibold">Direct Auto Care</span>
                                    <span className="truncate text-xs text-muted-foreground">
                                        Garage
                                    </span>
                                </div>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <SidebarGroup>
                    <SidebarGroupContent>
                        <SidebarMenu>
                            {navMain.map((item) => (
                                <SidebarMenuItem key={item.label}>
                                    <SidebarMenuButton
                                        asChild
                                        isActive={isActive(item)}
                                        tooltip={item.label}
                                    >
                                        <Link href={route(item.routeName)}>
                                            <item.icon />
                                            <span>{item.label}</span>
                                        </Link>
                                    </SidebarMenuButton>
                                </SidebarMenuItem>
                            ))}
                        </SidebarMenu>
                    </SidebarGroupContent>
                </SidebarGroup>

                {isAdmin && (
                    <SidebarGroup>
                        <SidebarGroupContent>
                            <SidebarMenu>
                                {navAdmin.map((item) => (
                                    <SidebarMenuItem key={item.label}>
                                        <SidebarMenuButton
                                            asChild
                                            isActive={route().current(item.match)}
                                            tooltip={item.label}
                                        >
                                            <Link href={route(item.routeName)}>
                                                <item.icon />
                                                <span>{item.label}</span>
                                            </Link>
                                        </SidebarMenuButton>
                                    </SidebarMenuItem>
                                ))}
                            </SidebarMenu>
                        </SidebarGroupContent>
                    </SidebarGroup>
                )}
            </SidebarContent>

            <SidebarFooter>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <SidebarMenuButton
                                    size="lg"
                                    className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                                    tooltip={user.name}
                                >
                                    <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-semibold text-primary-foreground">
                                        {initials}
                                    </div>
                                    <div className="grid flex-1 text-left text-sm leading-tight">
                                        <span className="truncate font-semibold">{user.name}</span>
                                        <span className="truncate text-xs text-muted-foreground">
                                            {user.email}
                                        </span>
                                    </div>
                                    <ChevronsUpDown className="ms-auto h-4 w-4 shrink-0" />
                                </SidebarMenuButton>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent
                                className="w-[--radix-dropdown-menu-trigger-width] min-w-56"
                                side="top"
                                align="end"
                                sideOffset={4}
                            >
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
                                        <LogOut className="me-2 h-4 w-4" />
                                        {t('ui.logout')}
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarFooter>

            <SidebarRail />
        </Sidebar>
    );
}
