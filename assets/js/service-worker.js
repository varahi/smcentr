let cacheName = "pwa-smcentr-dev"
let  appShellFiles = [
    '/images/favicon/favicon-16x16.png',
    '/images/favicon/apple-icon-144x144.png',
    '/images/favicon/android-chrome-512x512.png',
    '/build/app.js',
    '/build/app1.js',
]

self.addEventListener('install', (e) => {
    console.log('[Service Worker] Install');
    e.waitUntil(
        caches.open(cacheName).then((cache) => {
            console.log('[Service Worker] Caching all: app shell and content');
            return cache.addAll(appShellFiles);
        })
    );
});

self.addEventListener('fetch', (e) => {
    e.respondWith(
        caches.match(e.request).then((r) => {
            console.log('[Service Worker] Fetching resource: '+e.request.url);
            return r || fetch(e.request).then((response) => {
                return caches.open(cacheName).then((cache) => {
                    console.log('[Service Worker] Caching new resource: '+e.request.url);
                    if((e.request.url.indexOf('http') === 0)) {
                        cache.put(e.request, response.clone());
                    }
                    return response;
                });
            });
        })
    );
});