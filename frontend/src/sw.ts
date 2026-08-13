/// <reference lib="webworker" />
/* eslint-disable no-restricted-globals */
import { clientsClaim, setCacheNameDetails } from 'workbox-core';
import { precacheAndRoute, createHandlerBoundToURL } from 'workbox-precaching';
import { registerRoute, NavigationRoute } from 'workbox-routing';
import { NetworkFirst, CacheFirst } from 'workbox-strategies';
import { ExpirationPlugin } from 'workbox-expiration';

declare const self: ServiceWorkerGlobalScope;

// PWA basics (F19): precache build output, claim clients on activation.
self.skipWaiting();
clientsClaim();
setCacheNameDetails({ prefix: 'unify' });
precacheAndRoute(self.__WB_MANIFEST);

// SEC-06 fix: ONLY public, unauthenticated GET endpoints may be runtime-cached.
// The previous rule cached every GET /api/* response (messages, notifications,
// enrollments, grades) into a persistent CacheStorage jar that survived logout
// and was readable by the next user of a shared device.
registerRoute(
  ({ url }) => url.pathname === '/api/v1/branding',
  new NetworkFirst({
    cacheName: 'unify-api-public',
    plugins: [new ExpirationPlugin({ maxEntries: 10, maxAgeSeconds: 300 })],
  }),
  'GET'
);

// File downloads: authorized, quota-checked controller endpoints. Cache-first
// for offline reading (F19 client cache). The jar is wiped on logout (below).
// Note: ids are UUIDs — the pre-fix /\d+/ pattern never matched anything.
registerRoute(
  ({ url }) => /\/api\/v1\/(resources|forms)\/[^/]+\/download$/.test(url.pathname),
  new CacheFirst({
    cacheName: 'unify-files',
    plugins: [
      new ExpirationPlugin({ maxEntries: 60, maxAgeSeconds: 60 * 60 * 24 * 90, purgeOnQuotaError: true }),
    ],
  })
);

// Post-audit F-09: the SW-side background-sync queue was REMOVED. Offline
// rating/sticky intents already travel through the app-level SyncQueue →
// /offline/sync (validated, authorized, idempotency-keyed, testable); the SW
// queue was a second, divergent replay mechanism for the same two writes.
// SEC-06 note: never replay raw mutations here — the old plugin persisted
// request bodies (incl. credentials) in the SW's IndexedDB area.

// SPA navigation fallback: always the precached app shell (offline.html is
// static-asset heritage — the PWA shell hosts its own offline/error states).
registerRoute(new NavigationRoute(createHandlerBoundToURL('/index.html')));

self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') self.skipWaiting();

  // Sent by the auth store on logout: drop every runtime API cache so a
  // session's private data never outlives it on a shared device.
  if (event.data && event.data.type === 'CLEAR_API_CACHE') {
    event.waitUntil(
      Promise.all(
        ['unify-api-public', 'unify-files', 'unify-api'].map((name) => caches.delete(name))
      )
    );
  }
});
