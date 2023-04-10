let cacheName = "pwa-smcentr"
let  appShellFiles = [
    '/assets/favicon/android-icon-36x36.png',
    '/assets/favicon/android-icon-48x48.png',
    '/assets/favicon/android-icon-72x72.png',
    '/assets/favicon/android-icon-96x96.png',
    '/assets/favicon/android-icon-144x144.png',
    '/assets/favicon/android-icon-192x192.png',
    '/build/app.js',
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

    // or you can do - if ( event.request.url.indexOf( '/blog/' ) !== -1 )
    if ( event.request.url.match( '^.*(\/user\/).*$' ) ) {
        return false;
    } else {
        e.respondWith(
            caches.match(e.request).then((r) => {
                console.log('[Service Worker] Fetching resource: '+e.request.url);
                return r || fetch(e.request).then((response) => {
                    return caches.open(cacheName).then((cache) => {
                        //console.log('[Service Worker] Caching new resource: '+e.request.url);
                        if((e.request.url.indexOf('http') === 0)) {
                            cache.put(e.request, response.clone());
                        }
                        return response;
                    });
                });
            })
        );
    }
});