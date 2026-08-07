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
    (set) => ({
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
        storageDel('auth_token');
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
