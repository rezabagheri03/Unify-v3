import { get, set, del, keys } from 'idb-keyval';

export const SyncQueue = {
  async add(item: any) {
    const queue = (await get('syncQueue')) || [];
    queue.push({ ...item, id: Date.now(), status: 'pending', created_at: new Date().toISOString() });
    await set('syncQueue', queue);
  },

  async getAll() {
    return (await get('syncQueue')) || [];
  },

  async update(id: number, updates: any) {
    const queue = (await get('syncQueue')) || [];
    const idx = queue.findIndex((i: any) => i.id === id);
    if (idx !== -1) {
      queue[idx] = { ...queue[idx], ...updates };
      await set('syncQueue', queue);
    }
  },

  async clearSynced() {
    const queue = (await get('syncQueue')) || [];
    const filtered = queue.filter((i: any) => i.status !== 'synced');
    await set('syncQueue', filtered);
  }
};

export const FileCache = {
  async set(key: string, data: any) {
    await set(`cache:${key}`, data);
  },
  async get(key: string) {
    return await get(`cache:${key}`);
  }
};