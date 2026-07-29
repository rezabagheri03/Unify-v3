import { Workbox } from 'workbox-window';
import { SyncQueue } from '../db/idb';

export function registerBackgroundSync() {
  if ('serviceWorker' in navigator) {
    const wb = new Workbox('/sw.js');

    wb.addEventListener('controlling', () => {
      window.location.reload();
    });

    wb.register().then((registration) => {
      if (registration && 'sync' in registration) {
        // Register background sync
        (registration as any).sync.register('unify-offline-sync')
          .catch((err: any) => console.error('Background sync registration failed:', err));
      }
    });

    // Listen for sync completion
    navigator.serviceWorker.addEventListener('message', (event) => {
      if (event.data && event.data.type === 'SYNC_COMPLETE') {
        console.log('Offline queue synced successfully');
        SyncQueue.clearSynced();
      }
    });
  }
}

// Auto-register
if (typeof window !== 'undefined') {
  window.addEventListener('load', () => {
    registerBackgroundSync();
  });
}