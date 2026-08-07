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

// API GETs: network-first with 5-min cache (works offline for cached reads).
registerRoute(
  ({ url }) => url.pathname.startsWith('/api/'),
  new NetworkFirst({
    cacheName: 'unify-api',
    plugins: [new ExpirationPlugin({ maxEntries: 100, maxAgeSeconds: 300 })],
  }),
  'GET'
);

// File downloads (resources/forms): cache-first 100MB LRU (F05 client cache).
registerRoute(
  ({ url }) => /\/api\/v1\/resources\/\d+\/download$|\/api\/v1\/forms\/\d+\/download$/.test(url.pathname),
  new CacheFirst({
    cacheName: 'unify-files',
    plugins: [
      new ExpirationPlugin({ maxEntries: 60, maxAgeSeconds: 60 * 60 * 24 * 90, purgeOnQuotaError: true }),
    ],
  })
);

// Background sync queue: replays queued mutating requests when back online (F19).
const bgSync = new BackgroundSyncPlugin('unify-offline-sync', {
  maxRetentionTime: 24 * 60, // retry for 24h
});
registerRoute(
  ({ url }) => url.pathname.startsWith('/api/') && ['POST', 'PATCH', 'PUT', 'DELETE'].includes((self as any).fetchEvent?.request?.method || 'POST'),
  new NetworkFirst({ plugins: [bgSync] }),
  'POST'
);

// SPA navigation fallback -> offline.html when offline, index.html otherwise.
registerRoute(new NavigationRoute(createHandlerBoundToURL('/index.html')));

self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') self.skipWaiting();
});
