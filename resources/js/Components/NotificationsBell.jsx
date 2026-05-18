import { useEffect, useState } from 'react';
import axios from 'axios';

/* global setInterval, clearInterval */
export default function NotificationsBell() {
    const [notifications, setNotifications] = useState([]);
    const [open, setOpen] = useState(false);

    const fetchNotifications = () => {
        axios.get('/notifications').then((res) => {
            setNotifications(res.data);
        });
    };

    useEffect(() => {
        fetchNotifications();
        const interval = setInterval(fetchNotifications, 60000);
        return () => clearInterval(interval);
    }, []);

    return (
        <div className="relative">
            <button onClick={() => setOpen(!open)}>🔔</button>

            {open && (
                <div className="absolute right-0 mt-2 w-72 bg-white shadow rounded">
                    {notifications.length === 0 ? (
                        <p className="p-3 text-sm">Aucune notification</p>
                    ) : (
                        notifications.map((n) => (
                            <div key={n.id} className="p-3 border-b">
                                {n.data.message}
                            </div>
                        ))
                    )}
                </div>
            )}
        </div>
    );
}
