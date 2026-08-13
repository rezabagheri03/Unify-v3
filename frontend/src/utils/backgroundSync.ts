import { Workbox } from 'workbox-window';
import { SyncQueue } from '../db/idb';

export function registerBackgroundSync() {
  if ('serviceWorker' in navigator) {
    const wb = new Workbox('/sw.js');

    // NEVER hard-reload on SW activation: in storage-blocked contexts (sandboxed
    // preview iframe) a reload wipes the in-memory session and kicks the user to
    // login mid-flow. New SW builds are picked up on the next navigation instead.
    wb.addEventListener('controlling', () => {
      console.debug('[unify] new service worker activated; keeping session');
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
