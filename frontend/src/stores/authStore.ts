import { create } from 'zustand';
import { persist, createJSONStorage } from 'zustand/middleware';
import { storageGet, storageSet, storageDel } from '../db/safeStorage';

interface AuthState {
  user: any | null;
  token: string | null;
  isAuthenticated: boolean;
  mustChangePassword: boolean;
  login: (user: any, token?: string) => void;
  logout: () => void;
  setMustChangePassword: (value: boolean) => void;
  updateUser: (updates: Record<string, unknown>) => void;
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      user: null,
      token: null,
      isAuthenticated: false,
      mustChangePassword: false,

      login: (user, token) => {
        // Token kept in-memory (synchronous) so the API client can always
        // attach it, even where IndexedDB/localStorage are blocked (sandboxed
        // preview iframes, private browsing). Storage is a best-effort bonus
        // so a reload in a normal browser keeps the session.
        set({ user, token: token ?? null, isAuthenticated: true, mustChangePassword: user.must_change_password || false });
        if (token) {
          storageSet('auth_token', token);
        }
      },

      logout: () => {
        const token = get().token;
        // SEC-03: best-effort server-side revocation of the presenting token
        // (fire-and-forget; local cleanup below never waits on the network).
        if (token) {
          void import('../api/client')
            .then(({ default: api }) => api.post('/auth/logout').catch(() => {}))
            .catch(() => {});
        }
        storageDel('auth_token');
        // F-15 (post-audit): if a push device token is registered for this
        // browser, deactivate it so notifications stop after logout. No token
        // is stored today (push is D-006-disabled) — this is the forward hook.
        void storageGet('push_device_token').then((tok) => {
          if (tok) {
            import('../api/client')
              .then(({ default: api }) => api.delete('/devices', { data: { token: tok } }).catch(() => {}))
              .catch(() => {});
            storageDel('push_device_token');
          }
        });
        // Shared-device hygiene: pending offline intents belong to the
        // OUTGOING user (they are a per-person outbox, not a device store).
        import('../db/idb').then(({ SyncQueue }) => SyncQueue.clearAll()).catch(() => {});
        // SEC-06: wipe runtime API caches in the service worker so a session's
        // private data cannot outlive it on a shared device.
        try {
          navigator.serviceWorker?.controller?.postMessage({ type: 'CLEAR_API_CACHE' });
        } catch {
          /* no controller (first load / sandbox) — harmless */
        }
        set({ user: null, token: null, isAuthenticated: false, mustChangePassword: false });
      },

      setMustChangePassword: (value) => set((state) => ({
        mustChangePassword: value,
        user: state.user ? { ...state.user, must_change_password: value } : null,
      })),

      updateUser: (updates) => set((state) => ({
        user: state.user ? { ...state.user, ...updates } : null,
      })),
    }),
    {
      name: 'unify-auth',
      // Safe storage: IndexedDB when available, in-memory otherwise (works in
      // sandboxed preview iframes and private browsing).
      storage: createJSONStorage(() => ({
        getItem: async (name) => (await storageGet(name)) ?? null,
        setItem: async (name, value) => storageSet(name, value),
        removeItem: async (name) => storageDel(name),
      })),
    }
  )
);

/** Synchronous token accessor for the API client (never touches storage). */
export function getAuthTokenSync(): string | null {
  return useAuthStore.getState().token;
}
