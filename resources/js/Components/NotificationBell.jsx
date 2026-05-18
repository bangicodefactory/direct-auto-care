import { router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

export default function NotificationBell() {
    const { notifications, unreadCount } = usePage().props;
    const [open, setOpen] = useState(false);
    const ref = useRef(null);

    useEffect(() => {
        const interval = setInterval(() => {
            router.reload({ only: ['notifications', 'unreadCount'] });
        }, 60_000);
        return () => clearInterval(interval);
    }, []);

    useEffect(() => {
        function handleClickOutside(e) {
            if (ref.current && !ref.current.contains(e.target)) {
                setOpen(false);
            }
        }
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    function markRead(id, url) {
        setOpen(false);
        router.post(
            route('notifications.read', id),
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    if (url) window.location.href = url;
                },
            },
        );
    }

    function markAllRead() {
        router.post(route('notifications.read-all'), {}, { preserveScroll: true });
    }

    const iconForType = (type) => {
        if (type === 'low_stock') return '📦';
        if (type === 'reminder_failure') return '⚠️';
        return '📅';
    };

    return (
        <div className="relative" ref={ref}>
            <button
                type="button"
                onClick={() => setOpen((prev) => !prev)}
                className="relative inline-flex items-center justify-center rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 focus:outline-none"
                aria-label="Notifications"
            >
                <svg
                    className="h-6 w-6"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                    />
                </svg>
                {unreadCount > 0 && (
                    <span className="absolute right-1 top-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold leading-none text-white">
                        {unreadCount > 9 ? '9+' : unreadCount}
                    </span>
                )}
            </button>

            {open && (
                <div className="absolute end-0 z-50 mt-2 w-80 rounded-md shadow-lg">
                    <div className="rounded-md bg-white ring-1 ring-black ring-opacity-5">
                        <div className="flex items-center justify-between border-b border-gray-100 px-4 py-2">
                            <span className="text-sm font-semibold text-gray-700">
                                Notifications
                            </span>
                            {unreadCount > 0 && (
                                <button
                                    type="button"
                                    onClick={markAllRead}
                                    className="text-xs text-blue-600 hover:underline"
                                >
                                    Tout marquer comme lu
                                </button>
                            )}
                        </div>

                        <ul className="max-h-80 divide-y divide-gray-50 overflow-y-auto">
                            {notifications.length === 0 ? (
                                <li className="px-4 py-6 text-center text-sm text-gray-400">
                                    Aucune notification
                                </li>
                            ) : (
                                notifications.map((n) => (
                                    <li key={n.id}>
                                        <button
                                            type="button"
                                            onClick={() => markRead(n.id, n.url)}
                                            className={`w-full px-4 py-3 text-left transition hover:bg-gray-50 ${
                                                !n.read_at ? 'bg-blue-50' : ''
                                            }`}
                                        >
                                            <div className="flex items-start gap-2">
                                                <span className="mt-0.5 text-base leading-none">
                                                    {iconForType(n.type)}
                                                </span>
                                                <div className="min-w-0 flex-1">
                                                    <p className="truncate text-sm text-gray-800">
                                                        {n.message}
                                                    </p>
                                                    <p className="mt-0.5 text-xs text-gray-400">
                                                        {n.created_at}
                                                    </p>
                                                </div>
                                                {!n.read_at && (
                                                    <span className="mt-1.5 h-2 w-2 flex-shrink-0 rounded-full bg-blue-500" />
                                                )}
                                            </div>
                                        </button>
                                    </li>
                                ))
                            )}
                        </ul>
                    </div>
                </div>
            )}
        </div>
    );
}
