import axios from 'axios';
import { useEffect, useState } from 'react';

// Create an axios instance with default config
const api = axios.create({
    baseURL: 'http://localhost:8000/api',
    withCredentials: true, // Important for Sanctum cookies
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

const NotificationBadge = () => {
    const [unreadCount, setUnreadCount] = useState(0);
    const [error, setError] = useState(null);

    useEffect(() => {
        const fetchUnreadCount = async () => {
            try {
                // First ensure CSRF cookie is set (only needed for SPA if not already handled globally)
                await api.get('/sanctum/csrf-cookie');

                const response = await api.get('/notifications/unread-count');
                setUnreadCount(response.data.count);
            } catch (err) {
                if (err.response && err.response.status === 401) {
                    setError('Unauthorized - Please log in');
                    console.error('Auth error:', err);
                } else {
                    setError('Failed to fetch notifications');
                    console.error('Notification error:', err);
                }
            }
        };

        fetchUnreadCount();
    }, []);

    if (error) return <div className="text-red-500">{error}</div>;

    return (
        <div className="relative">
            <span className="icon">🔔</span>
            {unreadCount > 0 && (
                <span className="absolute top-0 right-0 bg-red-500 text-white rounded-full px-2 text-xs">
                    {unreadCount}
                </span>
            )}
        </div>
    );
};

export default NotificationBadge;
