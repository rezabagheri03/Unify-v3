import axios from 'axios';
import { storageGet, storageDel } from '../db/safeStorage';
import { getAuthTokenSync, useAuthStore } from '../stores/authStore';

/**
 * Unify V9 API client — versioned API per 23_API_VERSIONING.md (/api/v1).
 * - Bearer token from the auth store (in-memory, always available) with a
 *   storage fallback for reloads.
 * - Idempotency-Key header (UUID v4) on all mutating requests (H1)
 * - 401 -> clear session and return to login
 *
 * The API URL is injected by Vite via `define` (__UNIFY_API_URL__) so the
 * source stays free of `import.meta` (which breaks jest/CommonJS parsing).
 */
declare const __UNIFY_API_URL__: string | undefined;

const api = axios.create({
  baseURL: typeof __UNIFY_API_URL__ !== 'undefined' ? __UNIFY_API_URL__ : '/api/v1',
  withCredentials: true,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
});

/** UUID v4 with a safe fallback for non-secure/sandboxed contexts. */
function uuidv4(): string {
  try {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
      return crypto.randomUUID();
    }
  } catch {
    // fall through
  }
  // Manual v4 UUID (RFC 4122) — works everywhere.
  const bytes = new Uint8Array(16);
  if (typeof crypto !== 'undefined' && typeof crypto.getRandomValues === 'function') {
    crypto.getRandomValues(bytes);
  } else {
    for (let i = 0; i < 16; i++) bytes[i] = Math.floor(Math.random() * 256);
  }
  bytes[6] = (bytes[6] & 0x0f) | 0x40; // version 4
  bytes[8] = (bytes[8] & 0x3f) | 0x80; // variant 10
  const h = Array.from(bytes, (b) => b.toString(16).padStart(2, '0')).join('');
  return `${h.slice(0, 8)}-${h.slice(8, 12)}-${h.slice(12, 16)}-${h.slice(16, 20)}-${h.slice(20)}`;
}

api.interceptors.request.use(async (config) => {
  // In-memory token first (never misses during a session), then storage.
  const token = getAuthTokenSync() ?? (await storageGet('auth_token'));
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  if (['post', 'put', 'patch', 'delete'].includes(config.method || '')) {
    config.headers['Idempotency-Key'] = uuidv4();
  }

  return config;
});

api.interceptors.response.use(
  (res) => res,
  (error) => {
    if (error.response?.status === 401) {
      // Session invalid. Clear the token WITHOUT a hard page reload — a hard
      // reload wipes in-memory state in storage-blocked contexts (sandboxed
      // preview), permanently logging the user out. The auth store notifies
      // subscribers and ProtectedRoute does a soft redirect instead.
      storageDel('auth_token');
      useAuthStore.getState().logout();
    }
    return Promise.reject(error);
  }
);

/** Extract the documented Persian error shape: { message, errors, code, retry_after } */
export function apiErrorMessage(err: any, fallback = 'خطای ناشناخته'): string {
  // axios network-level failure (server down / no connection)
  if (err && !err.response && err.request) {
    return 'اتصال به سرور برقرار نیست. لطفاً چند لحظه بعد دوباره تلاش کنید.';
  }
  return err?.response?.data?.message || err?.message || fallback;
}

export default api;
