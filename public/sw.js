const CACHE_NAME = 'dentaflow-pwa-v2';
const OFFLINE_SHELL_URLS = [
    '/manifest.webmanifest',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
];

const SENSITIVE_PATH_PREFIXES = [
    '/login',
    '/register',
    '/logout',
    '/forgot-password',
    '/reset-password',
    '/two-factor-challenge',
    '/user/',
    '/settings',
    '/clinic',
    '/patients/',
    '/install.php',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(CACHE_NAME)
            .then((cache) => cache.addAll(OFFLINE_SHELL_URLS))
            .catch(() => undefined),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) =>
                Promise.all(
                    keys
                        .filter((k) => k !== CACHE_NAME)
                        .map((k) => caches.delete(k)),
                ),
            ),
    );
    self.clients.claim();
});

self.addEventListener('message', (event) => {
    if (!event.data) return;

    if (event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }

    if (event.data.type === 'CLEAR_CLINIC_CACHE') {
        event.waitUntil(
            caches.open(CACHE_NAME).then((cache) =>
                cache.keys().then((requests) =>
                    Promise.all(
                        requests
                            .filter((req) => {
                                const pathname = new URL(req.url).pathname;
                                return (
                                    !pathname.startsWith('/build/') &&
                                    !pathname.startsWith('/icons/') &&
                                    pathname !== '/manifest.webmanifest'
                                );
                            })
                            .map((req) => cache.delete(req)),
                    ),
                ),
            ),
        );
    }
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;

    // Cache-first for compiled static build assets and icons
    if (
        url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/icons/')
    ) {
        event.respondWith(
            caches.match(request).then(
                (cached) =>
                    cached ||
                    fetch(request).then((response) => {
                        if (response.ok) {
                            const copy = response.clone();
                            caches
                                .open(CACHE_NAME)
                                .then((cache) => cache.put(request, copy));
                        }
                        return response;
                    }),
            ),
        );
        return;
    }

    const isSensitiveRoute = SENSITIVE_PATH_PREFIXES.some((prefix) =>
        url.pathname.startsWith(prefix),
    );
    if (isSensitiveRoute) {
        return;
    }

    // Network-first with cache fallback for core workspace & offline bootstrap JSON
    if (
        request.mode === 'navigate' ||
        url.pathname === '/offline-sync/bootstrap'
    ) {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    if (response.ok && !request.headers.has('X-Inertia')) {
                        const copy = response.clone();
                        caches
                            .open(CACHE_NAME)
                            .then((cache) => cache.put(request, copy));
                    }
                    return response;
                })
                .catch(async () => {
                    const cached = await caches.match(request);
                    if (cached) return cached;
                    if (request.mode === 'navigate') {
                        const fallback = await caches.match('/dashboard');
                        if (fallback) return fallback;
                    }
                    return new Response('Sin conexión', { status: 503 });
                }),
        );
    }
});
