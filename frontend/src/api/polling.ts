import { useEffect, useState } from 'react';
import api from './client';
import { useAuthStore } from '../stores/authStore';

export function useNotificationsPolling(intervalMs = 30000) {
  const [notifications, setNotifications] = useState<any[]>([]);
  const { user } = useAuthStore();

  useEffect(() => {
    if (!user) return;

    const fetchNotifications = async () => {
      try {
        const since = localStorage.getItem('last_poll') || new Date(Date.now() - 5 * 60 * 1000).toISOString();
        const res = await api.get(`/notifications/unread?since=${since}`);
        
        if (res.data.length > 0) {
          setNotifications(res.data);
          localStorage.setItem('last_poll', new Date().toISOString());
        }
      } catch (e) {
        console.error('Polling error', e);
      }
    };

    fetchNotifications();
    const interval = setInterval(fetchNotifications, intervalMs);

    return () => clearInterval(interval);
  }, [user, intervalMs]);

  return notifications;
}