import { storageGet, storageSet } from './safeStorage';

/**
 * Offline sync queue + file cache metadata.
 * Backed by IndexedDB when available; falls back to in-memory storage so the
 * queue still works in sandboxed/private contexts.
 */

export const SyncQueue = {
  async add(item: any) {
    const queue = (await storageGet<any[]>('syncQueue')) || [];
    queue.push({ ...item, id: Date.now(), status: 'pending', created_at: new Date().toISOString() });
    await storageSet('syncQueue', queue);
  },

  async getAll(): Promise<any[]> {
    return (await storageGet<any[]>('syncQueue')) || [];
  },

  async update(id: number, updates: any) {
    const queue = (await storageGet<any[]>('syncQueue')) || [];
    const idx = queue.findIndex((i: any) => i.id === id);
    if (idx !== -1) {
      queue[idx] = { ...queue[idx], ...updates };
      await storageSet('syncQueue', queue);
    }
  },

  async clearSynced() {
    const queue = (await storageGet<any[]>('syncQueue')) || [];
    const filtered = queue.filter((i: any) => i.status !== 'synced');
    await storageSet('syncQueue', filtered);
  },
};

export const FileCache = {
  async set(key: string, data: any) {
    await storageSet(`cache:${key}`, data);
  },
  async get(key: string) {
    return await storageGet(`cache:${key}`);
  },
};
