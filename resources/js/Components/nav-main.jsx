import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/Components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/Components/ui/sidebar';
import { useTranslation } from '@/helpers/i18n';
import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';

export function NavMain({ label, items }) {
    const { t } = useTranslation();

    return (
        <SidebarGroup>
            {label && <SidebarGroupLabel>{label}</SidebarGroupLabel>}
            <SidebarMenu>
                {items.map((item) =>
                    item.items?.length ? (
                        <Collapsible
                            key={item.labelKey}
                            asChild
                            defaultOpen={item.active}
                            className="group/collapsible"
                        >
                            <SidebarMenuItem>
                                <CollapsibleTrigger asChild>
                                    <SidebarMenuButton tooltip={t(item.labelKey)}>
                                        {item.icon && <item.icon />}
                                        <span>{t(item.labelKey)}</span>
                                        <ChevronRight className="ms-auto transition-transform duration-200 rtl:-scale-x-100 group-data-[state=open]/collapsible:rotate-90" />
                                    </SidebarMenuButton>
                                </CollapsibleTrigger>
                                <CollapsibleContent>
                                    <SidebarMenuSub>
                                        {item.items.map((subItem) => (
                                            <SidebarMenuSubItem key={subItem.labelKey}>
                                                <SidebarMenuSubButton
                                                    asChild
                                                    isActive={subItem.active}
                                                >
                                                    <Link href={route(subItem.routeName)}>
                                                        <span>{t(subItem.labelKey)}</span>
                                                    </Link>
                                                </SidebarMenuSubButton>
                                            </SidebarMenuSubItem>
                                        ))}
                                    </SidebarMenuSub>
                                </CollapsibleContent>
                            </SidebarMenuItem>
                        </Collapsible>
                    ) : (
                        <SidebarMenuItem key={item.labelKey}>
                            <SidebarMenuButton
                                asChild
                                isActive={item.active}
                                tooltip={t(item.labelKey)}
                            >
                                <Link href={route(item.routeName)}>
                                    {item.icon && <item.icon />}
                                    <span>{t(item.labelKey)}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    ),
                )}
            </SidebarMenu>
        </SidebarGroup>
    );
}
