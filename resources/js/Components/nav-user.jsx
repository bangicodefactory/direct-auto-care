import { Avatar, AvatarFallback } from '@/Components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/Components/ui/sidebar';
import { useTranslation } from '@/helpers/i18n';
import { Link, usePage } from '@inertiajs/react';
import { LogOut, MoreVertical, UserCircle } from 'lucide-react';

export function NavUser({ user }) {
    const { isMobile } = useSidebar();
    const { t } = useTranslation();
    const locale = usePage().props.locale;
    const initials = user.name.charAt(0).toUpperCase();
    const desktopSide = locale === 'ar' ? 'left' : 'right';

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                            tooltip={user.name}
                        >
                            <Avatar className="h-8 w-8 rounded-lg">
                                <AvatarFallback className="rounded-lg bg-primary text-sm font-semibold text-primary-foreground">
                                    {initials}
                                </AvatarFallback>
                            </Avatar>
                            <div className="grid flex-1 text-left text-sm leading-tight">
                                <span className="truncate font-medium">{user.name}</span>
                                <span className="truncate text-xs text-muted-foreground">
                                    {user.email}
                                </span>
                            </div>
                            <MoreVertical className="ms-auto size-4" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                        side={isMobile ? 'bottom' : desktopSide}
                        align="end"
                        sideOffset={4}
                    >
                        <DropdownMenuLabel className="p-0 font-normal">
                            <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                <Avatar className="h-8 w-8 rounded-lg">
                                    <AvatarFallback className="rounded-lg bg-primary text-sm font-semibold text-primary-foreground">
                                        {initials}
                                    </AvatarFallback>
                                </Avatar>
                                <div className="grid flex-1 text-left text-sm leading-tight">
                                    <span className="truncate font-medium">{user.name}</span>
                                    <span className="truncate text-xs text-muted-foreground">
                                        {user.email}
                                    </span>
                                </div>
                            </div>
                        </DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem asChild>
                            <Link href={route('profile.edit')} className="cursor-pointer">
                                <UserCircle className="me-2 h-4 w-4" />
                                {t('ui.profile')}
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
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
    );
}
