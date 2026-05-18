import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import NotificationBell from '@/Components/NotificationBell';
import Sidebar from '@/Components/Sidebar';
import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function AuthenticatedLayout({ header, children }) {
    const { user, role } = usePage().props.auth;
    const flash = usePage().props.flash;
    const [sidebarOpen, setSidebarOpen] = useState(false);

    return (
        <div className="min-h-screen bg-gray-100">
            {/* Top bar */}
            <nav className="fixed inset-x-0 top-0 z-30 border-b border-gray-200 bg-white">
                <div className="flex h-16 items-center justify-between px-4 sm:px-6">
                    <div className="flex items-center gap-3">
                        <button
                            type="button"
                            className="inline-flex items-center justify-center rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 focus:outline-none md:hidden"
                            onClick={() => setSidebarOpen((prev) => !prev)}
                            aria-label="Ouvrir le menu"
                        >
                            <svg
                                className="h-6 w-6"
                                stroke="currentColor"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                {sidebarOpen ? (
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                ) : (
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                )}
                            </svg>
                        </button>

                        <Link href="/">
                            <ApplicationLogo className="block h-9 w-auto fill-current text-gray-800" />
                        </Link>
                    </div>

                    <div className="flex items-center gap-2">
                        <NotificationBell />
                        <Dropdown>
                            <Dropdown.Trigger>
                                <span className="inline-flex rounded-md">
                                    <button
                                        type="button"
                                        className="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition hover:text-gray-700 focus:outline-none"
                                    >
                                        {user.name}
                                        <svg
                                            className="-me-0.5 ms-2 h-4 w-4"
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path
                                                fillRule="evenodd"
                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                clipRule="evenodd"
                                            />
                                        </svg>
                                    </button>
                                </span>
                            </Dropdown.Trigger>

                            <Dropdown.Content>
                                <div className="border-b border-gray-100 px-4 py-2">
                                    <p className="text-sm font-medium text-gray-800">{user.name}</p>
                                    <p className="truncate text-xs text-gray-500">{user.email}</p>
                                </div>
                                <Dropdown.Link href={route('profile.edit')}>Profil</Dropdown.Link>
                                <Dropdown.Link href={route('logout')} method="post" as="button">
                                    Déconnexion
                                </Dropdown.Link>
                            </Dropdown.Content>
                        </Dropdown>
                    </div>
                </div>
            </nav>

            {/* Mobile sidebar backdrop — rendered as button for keyboard/screen-reader accessibility */}
            <button
                type="button"
                aria-label="Fermer le menu"
                className={`fixed inset-0 z-20 cursor-default bg-black/50 md:hidden ${
                    sidebarOpen ? 'block' : 'hidden'
                }`}
                onClick={() => setSidebarOpen(false)}
                onKeyDown={(e) => e.key === 'Escape' && setSidebarOpen(false)}
                tabIndex={sidebarOpen ? 0 : -1}
            />

            <div className="flex pt-16">
                {/* Sidebar — single instance, visibility controlled by CSS */}
                <div
                    className={`fixed inset-y-0 left-0 z-20 w-64 pt-16 transition-transform duration-200 md:z-auto md:translate-x-0 ${
                        sidebarOpen ? 'translate-x-0' : '-translate-x-full'
                    }`}
                >
                    <Sidebar role={role} />
                </div>

                {/* Page content */}
                <div className="flex min-h-[calc(100vh-4rem)] flex-1 flex-col md:ml-64">
                    {flash?.success && (
                        <div className="border-l-4 border-green-400 bg-green-50 p-4">
                            <p className="text-sm text-green-700">{flash.success}</p>
                        </div>
                    )}
                    {flash?.error && (
                        <div className="border-l-4 border-red-400 bg-red-50 p-4">
                            <p className="text-sm text-red-700">{flash.error}</p>
                        </div>
                    )}

                    {header && (
                        <header className="bg-white shadow">
                            <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                                {header}
                            </div>
                        </header>
                    )}

                    <main>{children}</main>
                </div>
            </div>
        </div>
    );
}
