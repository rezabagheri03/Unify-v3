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
        last_error: e.message 
      });
    }
  }

  await SyncQueue.clearSynced();
}

// Auto sync every 2 minutes when online
if (typeof window !== 'undefined') {
  setInterval(() => {
    if (navigator.onLine) {
      syncOfflineQueue();
    }
  }, 120000);
}