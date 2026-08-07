import axios from 'axios';
import { get } from 'idb-keyval';

/**
 * Unify V9 API client — versioned API per 23_API_VERSIONING.md (/api/v1).
 * - Bearer token from IndexedDB
 * - Idempotency-Key header (UUID v4) on all mutating requests (H1)
 * - 401 -> clear session
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

api.interceptors.request.use(async (config) => {
  const token = await get('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  if (['post', 'put', 'patch', 'delete'].includes(config.method || '')) {
    config.headers['Idempotency-Key'] = crypto.randomUUID();
  }

  return config;
});

api.interceptors.response.use(
  (res) => res,
  (error) => {
    if (error.response?.status === 401) {
      // Session expired / unauthenticated -> clear stored session
      import('idb-keyval').then(({ del }) => del('auth_token'));
      if (typeof window !== 'undefined' && !window.location.pathname.startsWith('/login')) {
        window.location.href = '/login';
      }
    }
    return Promise.reject(error);
  }
);

/** Extract the documented Persian error shape: { message, errors, code, retry_after } */
export function apiErrorMessage(err: any, fallback = 'خطای ناشناخته'): string {
  return err?.response?.data?.message || err?.message || fallback;
}

export default api;
