import { Link } from '@inertiajs/react';

function SidebarLink({ href, active, children }) {
    return (
        <Link
            href={href}
            className={`flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors ${
                active
                    ? 'bg-primary text-primary-foreground'
                    : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
            }`}
        >
            {children}
        </Link>
    );
}

export default function Sidebar({ role }) {
    const isAdmin = role === 'admin';

    return (
        <aside className="flex h-full flex-col border-r border-gray-200 bg-white">
            <nav className="flex-1 space-y-1 px-3 py-4">
                <SidebarLink href={route('dashboard')} active={route().current('dashboard')}>
                    Tableau de bord
                </SidebarLink>

                <SidebarLink href={route('products.index')} active={route().current('products.*')}>
                    Produits
                </SidebarLink>

                {isAdmin && (
                    <SidebarLink href={route('users.index')} active={route().current('users.*')}>
                        Utilisateurs
                    </SidebarLink>
                )}
            </nav>
        </aside>
    );
}
