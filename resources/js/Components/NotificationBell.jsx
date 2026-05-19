import { useTranslation } from '@/helpers/i18n';
import { router, usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

export default function NotificationBell() {
    const { t } = useTranslation();
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

    function markRead(id) {
        setOpen(false);
        router.post(route('notifications.read', id));
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
                aria-label={t('ui.notifications')}
            >
                <Bell className="h-6 w-6" />
                {unreadCount > 0 && (
                    <span className="absolute end-1 top-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold leading-none text-white">
                        {unreadCount > 9 ? '9+' : unreadCount}
                    </span>
                )}
            </button>

            {open && (
                <div className="absolute end-0 z-50 mt-2 w-80 rounded-md shadow-lg">
                    <div className="rounded-md bg-white ring-1 ring-black ring-opacity-5">
                        <div className="flex items-center justify-between border-b border-gray-100 px-4 py-2">
                            <span className="text-sm font-semibold text-gray-700">
                                {t('ui.notifications')}
                            </span>
                            {unreadCount > 0 && (
                                <button
                                    type="button"
                                    onClick={markAllRead}
                                    className="text-xs text-blue-600 hover:underline"
                                >
                                    {t('ui.mark_all_read')}
                                </button>
                            )}
                        </div>

                        <ul className="max-h-80 divide-y divide-gray-50 overflow-y-auto">
                            {notifications.length === 0 ? (
                                <li className="px-4 py-6 text-center text-sm text-gray-400">
                                    {t('ui.no_notifications')}
                                </li>
                            ) : (
                                notifications.map((n) => (
                                    <li key={n.id}>
                                        <button
                                            type="button"
                                            onClick={() => markRead(n.id)}
                                            className={`w-full px-4 py-3 text-start transition hover:bg-gray-50 ${
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
