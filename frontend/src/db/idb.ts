import { storageGet, storageSet } from './safeStorage';

/**
 * Offline sync queue + file cache metadata.
 * Stored as JSON strings through safeStorage (IndexedDB -> localStorage ->
 * in-memory), so it works in sandboxed/private contexts too.
 */

export const SyncQueue = {
  async add(item: any) {
    const queue = await SyncQueue.getAll();
    queue.push({ ...item, id: Date.now(), status: 'pending', created_at: new Date().toISOString() });
    await storageSet('syncQueue', JSON.stringify(queue));
  },

  async getAll(): Promise<any[]> {
    const raw = await storageGet('syncQueue');
    if (!raw) return [];
    try {
      const parsed = JSON.parse(raw);
      return Array.isArray(parsed) ? parsed : [];
    } catch {
      return [];
    }
  },

  async update(id: number, updates: any) {
    const queue = await SyncQueue.getAll();
    const idx = queue.findIndex((i: any) => i.id === id);
    if (idx !== -1) {
      queue[idx] = { ...queue[idx], ...updates };
      await storageSet('syncQueue', JSON.stringify(queue));
    }
  },

  async clearSynced() {
    const queue = await SyncQueue.getAll();
    const filtered = queue.filter((i: any) => i.status !== 'synced');
    await storageSet('syncQueue', JSON.stringify(filtered));
  },
};

export const FileCache = {
  async set(key: string, data: any) {
    await storageSet(`cache:${key}`, JSON.stringify(data));
  },
  async get(key: string) {
    const raw = await storageGet(`cache:${key}`);
    if (!raw) return undefined;
    try {
      return JSON.parse(raw);
    } catch {
      return undefined;
    }
  },
};
