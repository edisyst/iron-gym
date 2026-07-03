const CACHE_NAME = 'iron-gym-v2';

// Asset statici da precachare (app shell sessione)
const PRECACHE_URLS = [
    '/css/athlete.css',
];

const STATIC_EXTENSIONS = ['.css', '.js', '.woff', '.woff2', '.ttf', '.otf', '.png', '.jpg', '.webp', '.svg', '.ico'];

function isStaticAsset(url) {
    return STATIC_EXTENSIONS.some((ext) => url.pathname.endsWith(ext));
}

function isSessionPage(url) {
    return url.pathname.startsWith('/athlete/session/');
}

// ---- Lifecycle ----

self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(PRECACHE_URLS))
    );
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k))))
            .then(() => clients.claim())
    );
});

// ---- Fetch ----

self.addEventListener('fetch', function (event) {
    const url = new URL(event.request.url);

    // Solo GET same-origin
    if (event.request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    // Pagina sessione: network-first con fallback cache (app shell offline)
    if (isSessionPage(url)) {
        event.respondWith(
            fetch(event.request)
                .then(function (response) {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
                    }
                    return response;
                })
                .catch(function () { return caches.match(event.request); })
        );
        return;
    }

    // Asset statici: stale-while-revalidate
    if (isStaticAsset(url)) {
        event.respondWith(
            caches.open(CACHE_NAME).then(function (cache) {
                return cache.match(event.request).then(function (cached) {
                    const networkFetch = fetch(event.request).then(function (response) {
                        if (response.ok) { cache.put(event.request, response.clone()); }
                        return response;
                    }).catch(() => cached);
                    return cached || networkFetch;
                });
            })
        );
        return;
    }

    // Tutto il resto (Livewire, pagine dinamiche): network-only, nessuna cache
});

// ---- Push notifications ----

self.addEventListener('push', function (event) {
    if (!event.data) return;

    let data = {};
    try {
        data = event.data.json();
    } catch (e) {
        data = { title: 'Iron Gym', body: event.data.text() };
    }

    const title = data.title || 'Iron Gym';
    const options = {
        body: data.body || '',
        icon: '/icons/icon-192.png',
        badge: '/icons/icon-72.png',
        vibrate: [100, 50, 100],
        data: { url: data.url || '/' },
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    const url = event.notification.data?.url || '/';
    event.waitUntil(clients.openWindow(url));
});
