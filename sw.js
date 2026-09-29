const CACHE_NAME = 'myges-shell-v8';
const STATIC_ASSETS = [
    './assets/css/style.css',
    './assets/js/api.js',
    './assets/js/storage.js',
    './assets/js/app.js',
    './assets/img/favicon.jpeg',
    './manifest.json'
];

self.addEventListener('install', event => {
    event.waitUntil(caches.open(CACHE_NAME).then(cache => cache.addAll(STATIC_ASSETS)));
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => Promise.all(
            keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))
        ))
    );
    self.clients.claim();
});

self.addEventListener('fetch', event => {
    const requestUrl = new URL(event.request.url);
    const isStaticAsset = /\.(?:css|js|png|jpe?g|svg|webp|ico|json)$/i.test(requestUrl.pathname);
    if (event.request.method !== 'GET' || requestUrl.origin !== self.location.origin || requestUrl.pathname.includes('/api/') || !isStaticAsset) return;

    event.respondWith((async () => {
        let cache;
        try {
            cache = await caches.open(CACHE_NAME);
            const cached = await cache.match(event.request);
            if (cached) return cached;
        } catch {}

        try {
            const response = await fetch(event.request);
            if (response.ok && cache) await cache.put(event.request, response.clone()).catch(() => {});
            return response;
        } catch {
            if (cache) {
                const cached = await cache.match(event.request).catch(() => undefined);
                if (cached) return cached;
            }
            return await caches.match('./login.php');
        }
    })());
});
