const CACHE_NAME = 'myges-campus-v9';
const BASE_PATH = new URL('./', self.registration.scope).pathname;
const APP_SHELL = ['index.html', 'styles.css', 'src/app.js', 'src/api.js', 'src/storage.js', 'src/ui.js', 'manifest.json', 'icons/icon.svg'].map((path) => `${BASE_PATH}${path}`);

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(APP_SHELL)));
  self.skipWaiting();
});
self.addEventListener('activate', (event) => {
  event.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)))));
  self.clients.claim();
});
self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return;
  event.respondWith((async () => {
    const cached = await caches.match(event.request);
    if (cached) return cached;
    try {
      const response = await fetch(event.request);
      const copy = response.clone();
      await caches.open(CACHE_NAME).then((cache) => cache.put(event.request, copy));
      return response;
    } catch {
      return caches.match(`${BASE_PATH}index.html`);
    }
  })());
});
