/// <reference lib="webworker" />
/* eslint-disable no-restricted-globals */
import { clientsClaim, setCacheNameDetails } from 'workbox-core';
import { precacheAndRoute, createHandlerBoundToURL } from 'workbox-precaching';
import { registerRoute, NavigationRoute } from 'workbox-routing';
import { NetworkFirst, CacheFirst } from 'workbox-strategies';
import { ExpirationPlugin } from 'workbox-expiration';
import { BackgroundSyncPlugin } from 'workbox-background-sync';

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

// SEC-06 fix: background sync replays ONLY explicit offline-safe endpoints.
// Previously ALL mutations (including login and password changes) were queued
// into IndexedDB — credentials persisted in plaintext in the SyncStorage area.
const bgSync = new BackgroundSyncPlugin('unify-offline-sync', {
  maxRetentionTime: 24 * 60, // retry for 24h
});
registerRoute(
  ({ url }) => /^\/api\/v1\/resources\/[^/]+\/(rating|sticky-note)$/.test(url.pathname),
  new NetworkFirst({ plugins: [bgSync] }),
  'POST'
);

// SPA navigation fallback -> offline.html when offline, index.html otherwise.
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
