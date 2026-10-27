/* IMMO Digital — service worker.
 * Pages publiques : réseau d'abord, copie gardée pour la lecture hors ligne.
 * Espace client, administration et API : jamais mis en cache (données privées).
 * Fichiers /_next/static : cache d'abord (noms uniques, immuables). */
const VERSION = 'immo-v1';
const STATIC = `${VERSION}-static`;
const PAGES = `${VERSION}-pages`;
const PRECACHE = ['/offline.html', '/icons/icon-192.png', '/images/symbol-mark.png', '/images/symbol-dark.png'];
const PRIVATE = /^\/(dashboard|admin|connexion|api)(\/|$)/;
const MAX_PAGES = 30;

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(STATIC).then((c) => c.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => !k.startsWith(VERSION)).map((k) => caches.delete(k))))
      .then(() => self.clients.claim()),
  );
});

async function trim(cacheName, max) {
  const cache = await caches.open(cacheName);
  const keys = await cache.keys();
  for (const k of keys.slice(0, Math.max(0, keys.length - max))) await cache.delete(k);
}

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  if (url.origin !== self.location.origin || PRIVATE.test(url.pathname)) return;

  if (url.pathname.startsWith('/_next/static/') || url.pathname.startsWith('/icons/') || url.pathname.startsWith('/images/') || url.pathname.startsWith('/fonts/')) {
    event.respondWith(
      caches.match(req).then((hit) => hit || fetch(req).then((res) => {
        if (res.ok) { const copy = res.clone(); caches.open(STATIC).then((c) => c.put(req, copy)); }
        return res;
      })),
    );
    return;
  }

  if (req.mode === 'navigate') {
    event.respondWith(
      fetch(req)
        .then((res) => {
          if (res.ok && res.type === 'basic') {
            const copy = res.clone();
            caches.open(PAGES).then((c) => c.put(req, copy)).then(() => trim(PAGES, MAX_PAGES));
          }
          return res;
        })
        .catch(async () => (await caches.match(req)) || (await caches.match('/offline.html'))),
    );
  }
});
