const CACHE_NAME = 'myges-shell-v5';
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
        try {
            const response = await fetch(event.request);
            if (response.ok && requestUrl.origin === self.location.origin) {
                const cache = await caches.open(CACHE_NAME);
                await cache.put(event.request, response.clone());
            }
            return response;
        } catch {
            const cached = await caches.match(event.request);
            return cached || caches.match('./login.php');
        }
    })());
});
