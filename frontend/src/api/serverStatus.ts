import { useEffect, useState } from 'react';
import api from './client';

export type ServerStatus = 'checking' | 'online' | 'offline';

/**
 * Lightweight backend reachability probe. The sandbox preview environment can
 * recycle the backend process between sessions, so the UI shows a clear banner
 * and keeps retrying instead of failing silently.
 */
export function useServerStatus(intervalMs = 15000): ServerStatus {
  const [status, setStatus] = useState<ServerStatus>('checking');

  useEffect(() => {
    let cancelled = false;
    let timer: ReturnType<typeof setTimeout>;

    const probe = async () => {
      try {
        // Use a short timeout so an unreachable backend is detected fast.
        await api.get('/health', { timeout: 4000 });
        if (!cancelled) setStatus('online');
      } catch {
        if (!cancelled) setStatus('offline');
      }
    };

    probe();
    timer = setTimeout(function tick() {
      probe();
      timer = setTimeout(tick, intervalMs);
    }, intervalMs);

    return () => {
      cancelled = true;
      clearTimeout(timer);
    };
  }, [intervalMs]);

  return status;
}
