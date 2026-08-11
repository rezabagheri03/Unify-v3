import { SyncQueue } from '../db/idb';
import api from '../api/client';

export async function syncOfflineQueue() {
  const queue = await SyncQueue.getAll();
  const pending = queue.filter((item: any) => item.status === 'pending');

  for (const item of pending) {
    try {
      await SyncQueue.update(item.id, { status: 'syncing' });

      // Only safe types are synced
      if (['rating', 'sticky', 'ticket_reply', 'assignment', 'curriculum_checkbox'].includes(item.type)) {
        await api.post(item.endpoint, item.payload);
        await SyncQueue.update(item.id, { status: 'synced' });
      }
    } catch (e) {
      await SyncQueue.update(item.id, { 
        status: 'failed', 
        last_error: e instanceof Error ? e.message : String(e),
      });
    }
  }

  await SyncQueue.clearSynced();
}

// Auto sync: immediately when connectivity returns, plus a 2-minute safety net.
// The interval now short-circuits on an empty queue (one localStorage read) so
// idle sessions are not woken for nothing (PERF-14).
if (typeof window !== 'undefined') {
  window.addEventListener('online', () => {
    syncOfflineQueue();
  });
  setInterval(() => {
    if (navigator.onLine) {
      syncOfflineQueue();
    }
  }, 120000);
}