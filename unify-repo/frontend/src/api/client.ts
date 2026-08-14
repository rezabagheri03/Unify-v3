import axios, { AxiosError } from 'axios';
import { storageGet, storageDel } from '../db/safeStorage';
import { getAuthTokenSync, useAuthStore } from '../stores/authStore';
import type { ApiErrorBody } from './types';

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
  // Post-audit F-14: never hang a UI spinner on a black-holed request. The
  // health probe already passes its own tighter 4s timeout per-call.
  timeout: 30000,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
});

/** UUID v4 with a safe fallback for non-secure/sandboxed contexts. */
export function uuidv4(): string {
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
    // Post-audit F-07: a caller may pin ONE key for a whole user-intent
    // (retry/double-tap ⇒ same key ⇒ server replays the first response).
    // Only mint a fresh key when the caller didn't.
    const h = config.headers as Record<string, unknown>;
    if (!h['Idempotency-Key']) {
      h['Idempotency-Key'] = uuidv4();
    }
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
export function apiErrorMessage(err: unknown, fallback = 'خطای ناشناخته'): string {
  const axErr = err as AxiosError<ApiErrorBody>;
  // axios network-level failure (server down / no connection)
  if (axErr && !axErr.response && axErr.request) {
    return 'اتصال به سرور برقرار نیست. لطفاً چند لحظه بعد دوباره تلاش کنید.';
  }
  return axErr?.response?.data?.message || axErr?.message || fallback;
}

export default api;
