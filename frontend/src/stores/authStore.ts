import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import { get as idbGet, set as idbSet, del as idbDel } from 'idb-keyval';

interface AuthState {
  user: any | null;
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
      isAuthenticated: false,
      mustChangePassword: false,

      login: (user, token) => {
        set({ user, isAuthenticated: true, mustChangePassword: user.must_change_password || false });
        if (token) {
          idbSet('auth_token', token);
        }
      },

      logout: () => {
        idbDel('auth_token');
        set({ user: null, isAuthenticated: false, mustChangePassword: false });
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
      getStorage: () => ({
        getItem: async (name) => {
          const value = await idbGet(name);
          return value ?? null;
        },
        setItem: async (name, value) => {
          await idbSet(name, value);
        },
        removeItem: async (name) => {
          await idbSet(name, undefined);
        },
      }),
    }
  )
);
