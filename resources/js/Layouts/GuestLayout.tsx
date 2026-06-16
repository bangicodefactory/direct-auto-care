import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';

interface GuestLayoutProps {
    children?: React.ReactNode;
}

export default function GuestLayout({ children }: GuestLayoutProps) {
    return (
        <div className="flex min-h-screen flex-col items-center bg-muted pt-6 sm:justify-center sm:pt-0">
            <div>
                <Link href="/">
                    <ApplicationLogo className="h-20 w-20 fill-current text-foreground" />
                </Link>
            </div>

            <div className="mt-6 w-full overflow-hidden rounded-lg border border-border bg-card px-6 py-4 shadow-tech sm:max-w-md">
                {children}
            </div>
        </div>
    );
}
