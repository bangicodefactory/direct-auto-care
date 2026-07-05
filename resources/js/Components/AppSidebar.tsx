import BrandLogo from '@/Components/BrandLogo';
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
import type { User } from '@/types/inertia';
import { Link, usePage } from '@inertiajs/react';
import {
    Bell,
    Calendar,
    CalendarDays,
    Car,
    ChevronsUpDown,
    FileText,
    LayoutDashboard,
    LogOut,
    type LucideIcon,
    Package,
    UserCog,
    Users,
    Wrench,
} from 'lucide-react';

interface NavItem {
    labelKey: string;
    routeName: string;
    icon: LucideIcon;
    match: string | null;
}

const navMain: NavItem[] = [
    { labelKey: 'ui.nav_home', routeName: 'dashboard', icon: LayoutDashboard, match: 'dashboard' },
    {
        labelKey: 'ui.nav_customers',
        routeName: 'customers.index',
        icon: Users,
        match: 'customers.*',
    },
    { labelKey: 'ui.nav_vehicles', routeName: 'vehicles.index', icon: Car, match: 'vehicles.*' },
    {
        labelKey: 'ui.nav_invoices',
        routeName: 'invoices.index',
        icon: FileText,
        match: 'invoices.*',
    },
    {
        labelKey: 'ui.nav_products',
        routeName: 'products.index',
        icon: Package,
        match: 'products.*',
    },
    {
        labelKey: 'ui.nav_appointments',
        routeName: 'appointments.index',
        icon: Calendar,
        match: null,
    },
    {
        labelKey: 'ui.nav_today',
        routeName: 'appointments.today-page',
        icon: CalendarDays,
        match: 'appointments.today-page',
    },
    { labelKey: 'ui.nav_workshop', routeName: 'workshop.index', icon: Wrench, match: 'workshop.*' },
];

const navAdmin: NavItem[] = [
    {
        labelKey: 'ui.nav_reminders',
        routeName: 'reminders.index',
        icon: Bell,
        match: 'reminders.*',
    },
    { labelKey: 'ui.nav_users', routeName: 'users.index', icon: UserCog, match: 'users.*' },
];

function isActive(item: NavItem) {
    if (item.match === null) {
        return !route().current('appointments.today-page') && route().current('appointments.*');
    }
    return route().current(item.match);
}

type AppSidebarProps = { user: User; role: string | null } & Omit<
    React.ComponentProps<typeof Sidebar>,
    'role'
>;

export default function AppSidebar({ user, role, ...props }: AppSidebarProps) {
    const { t } = useTranslation();
    const { locale, appSettings } = usePage().props;
    const isAdmin = role === 'admin';
    const initials = user.name.charAt(0).toUpperCase();
    const side = locale === 'ar' ? 'right' : 'left';

    return (
        <Sidebar collapsible="icon" side={side} {...props}>
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild tooltip={appSettings.company_name}>
                            <Link href={route('dashboard')}>
                                <BrandLogo className="h-8 w-8 shrink-0" />
                                <div className="grid flex-1 text-left text-sm leading-tight">
                                    <span className="truncate font-semibold">
                                        {appSettings.company_name}
                                    </span>
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
                                <SidebarMenuItem key={item.labelKey}>
                                    <SidebarMenuButton
                                        asChild
                                        isActive={isActive(item)}
                                        tooltip={t(item.labelKey)}
                                    >
                                        <Link href={route(item.routeName)}>
                                            <item.icon />
                                            <span>{t(item.labelKey)}</span>
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
                                    <SidebarMenuItem key={item.labelKey}>
                                        <SidebarMenuButton
                                            asChild
                                            isActive={route().current(item.match!)}
                                            tooltip={t(item.labelKey)}
                                        >
                                            <Link href={route(item.routeName)}>
                                                <item.icon />
                                                <span>{t(item.labelKey)}</span>
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
                                className="w-(--radix-dropdown-menu-trigger-width) min-w-56"
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
