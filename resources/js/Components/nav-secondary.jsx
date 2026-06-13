import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/Components/ui/sidebar';
import { useTranslation } from '@/helpers/i18n';
import { Link } from '@inertiajs/react';

export function NavSecondary({ items, ...props }) {
    const { t } = useTranslation();

    return (
        <SidebarGroup {...props}>
            <SidebarGroupContent>
                <SidebarMenu>
                    {items.map((item) => (
                        <SidebarMenuItem key={item.labelKey}>
                            <SidebarMenuButton
                                asChild
                                isActive={item.active}
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
    );
}
