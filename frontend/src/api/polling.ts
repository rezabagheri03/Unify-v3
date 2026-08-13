import { useEffect, useRef, useState } from 'react';
import api from './client';
import { lsGet, lsSet } from '../db/safeStorage';
import { useAuthStore } from '../stores/authStore';

/**
 * Notification polling (F15): 30s foreground / 120s background + 5s server cache.
 * Returns new unread notifications since the last poll.
 */
export function useNotificationsPolling() {
  const [notifications, setNotifications] = useState<any[]>([]);
  const { user } = useAuthStore();
  // Post-audit F-14: localStorage is user-writable — a malformed last_poll
  // must degrade to the default 5-minute window, not reach the SQL layer.
  const storedSince = lsGet('last_poll');
  const validSince = storedSince && !Number.isNaN(Date.parse(storedSince)) ? storedSince : null;
  const sinceRef = useRef<string>(validSince ?? new Date(Date.now() - 5 * 60 * 1000).toISOString());

  useEffect(() => {
    if (!user) return;

    let cancelled = false;
    let timer: ReturnType<typeof setTimeout>;

    const poll = async () => {
      try {
        const res = await api.get(`/notifications/unread?since=${encodeURIComponent(sinceRef.current)}`);
        if (!cancelled && Array.isArray(res.data) && res.data.length > 0) {
          setNotifications((prev) => {
            const ids = new Set(prev.map((n) => n.id));
            const fresh = res.data.filter((n: any) => !ids.has(n.id));
            return [...fresh, ...prev].slice(0, 50);
          });
          sinceRef.current = new Date().toISOString();
          lsSet('last_poll', sinceRef.current);
        }
      } catch (e) {
        // silent: polling must never break the UI
      }
    };

    // Foreground 30s; background tab 120s (F15 / C5)
    const interval = document.hidden ? 120000 : 30000;
    poll();
    timer = setTimeout(function tick() {
      poll();
      timer = setTimeout(tick, document.hidden ? 120000 : 30000);
    }, interval);

    const onVisibility = () => {
      clearTimeout(timer);
      timer = setTimeout(function tick() {
        poll();
        timer = setTimeout(tick, document.hidden ? 120000 : 30000);
      }, document.hidden ? 120000 : 30000);
    };
    document.addEventListener('visibilitychange', onVisibility);

    return () => {
      cancelled = true;
      clearTimeout(timer);
      document.removeEventListener('visibilitychange', onVisibility);
    };
  }, [user]);

  return notifications;
}
