import AppSidebar from '@/Components/AppSidebar';
import BottomNav from '@/Components/BottomNav';
import NotificationBell from '@/Components/NotificationBell';
import { Separator } from '@/Components/ui/separator';
import { SidebarInset, SidebarProvider, SidebarTrigger, useSidebar } from '@/Components/ui/sidebar';
import { router, usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';

export default function AuthenticatedLayout({ header, children }) {
    return (
        <SidebarProvider>
            <AuthenticatedLayoutInner header={header}>{children}</AuthenticatedLayoutInner>
        </SidebarProvider>
    );
}

function AuthenticatedLayoutInner({ header, children }) {
    const { setOpenMobile, toggleSidebar } = useSidebar();
    const { user, role } = usePage().props.auth;
    const flash = usePage().props.flash;
    const locale = usePage().props.locale;

    useEffect(() => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
    }, [flash?.success, flash?.error]);

    useEffect(() => {
        document.documentElement.lang = locale ?? 'fr';
        document.documentElement.dir = locale === 'ar' ? 'rtl' : 'ltr';
    }, [locale]);

    useEffect(() => {
        return router.on('navigate', () => setOpenMobile(false));
    }, [setOpenMobile]);

    return (
        <>
            <AppSidebar user={user} role={role} />
            <SidebarInset>
                <header className="flex h-16 shrink-0 items-center gap-2 border-b border-border bg-card px-4">
                    <SidebarTrigger className="-ml-1" />
                    <Separator orientation="vertical" className="mr-2 h-4" />
                    {header && <div className="flex-1">{header}</div>}
                    <div className="ms-auto flex items-center">
                        <NotificationBell />
                    </div>
                </header>
                <main className="flex-1 pb-14 md:pb-0">{children}</main>
            </SidebarInset>
            <BottomNav onMenuOpen={toggleSidebar} />
        </>
    );
}
