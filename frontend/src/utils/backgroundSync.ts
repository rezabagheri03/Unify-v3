import { Workbox } from 'workbox-window';
import { SyncQueue } from '../db/idb';

export function registerBackgroundSync() {
  if ('serviceWorker' in navigator) {
    const wb = new Workbox('/sw.js');

    wb.addEventListener('controlling', () => {
      window.location.reload();
    });

    // Registration may legitimately fail in dev (sw.js not served by Vite) or
    // in sandboxed iframes (SecurityError) — never let it become an unhandled
    // rejection that spams the console / page errors.
    wb.register()
      .then((registration) => {
        if (registration && 'sync' in registration) {
          (registration as any).sync
            .register('unify-offline-sync')
            .catch((err: any) => console.debug('Background sync registration unavailable:', err?.message || err));
        }
      })
      .catch((err: any) => console.debug('ServiceWorker registration unavailable:', err?.message || err));

    // Listen for sync completion
    navigator.serviceWorker.addEventListener('message', (event) => {
      if (event.data && event.data.type === 'SYNC_COMPLETE') {
        console.log('Offline queue synced successfully');
        SyncQueue.clearSynced();
      }
    });
  }
}

// Auto-register (only in real browsers; jsdom/tests skip this file's side effect)
if (typeof window !== 'undefined' && typeof navigator !== 'undefined' && 'serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    registerBackgroundSync();
  });
}
