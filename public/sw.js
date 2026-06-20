const CACHE_NAME = 'iron-gym-v1';

const STATIC_EXTENSIONS = ['.css', '.js', '.woff', '.woff2', '.ttf', '.otf', '.png', '.jpg', '.webp', '.svg', '.ico'];

function isStaticAsset(url) {
    return STATIC_EXTENSIONS.some((ext) => url.pathname.endsWith(ext));
}

function isLivewireRequest(url) {
    return url.pathname.startsWith('/livewire') || url.searchParams.has('_token');
}

// ---- Lifecycle ----

self.addEventListener('install', function (event) {
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k))))
            .then(() => clients.claim())
    );
});

// ---- Fetch: cache-first per statici, network-first per Livewire ----

self.addEventListener('fetch', function (event) {
    const url = new URL(event.request.url);

    if (event.request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    if (isLivewireRequest(url)) {
        event.respondWith(
            fetch(event.request)
                .then(function (response) {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then(function (cache) { cache.put(event.request, clone); });
                    }
                    return response;
                })
                .catch(function () { return caches.match(event.request); })
        );
        return;
    }

    if (isStaticAsset(url)) {
        event.respondWith(
            caches.open(CACHE_NAME).then(function (cache) {
                return cache.match(event.request).then(function (cached) {
                    if (cached) return cached;
                    return fetch(event.request).then(function (response) {
                        if (response.ok) { cache.put(event.request, response.clone()); }
                        return response;
                    });
                });
            })
        );
    }
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
