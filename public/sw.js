const CACHE = 'citystake-v1';

// App shell — pages that should load even with a slow connection
const PRECACHE = [
    '/manage/dashboard',
    '/manage/bookings',
    '/manage/availability',
];

self.addEventListener('install', event => {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE).then(cache => cache.addAll(PRECACHE).catch(() => {}))
    );
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))
        ).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', event => {
    // Only handle GET requests, never intercept POST/API calls
    if (event.request.method !== 'GET') return;

    const url = new URL(event.request.url);

    // Never intercept Paystack, Monnify, external resources
    if (url.origin !== self.location.origin) return;

    // Network-first for all manage routes (always fresh data)
    event.respondWith(
        fetch(event.request)
            .then(response => {
                // Cache successful HTML responses
                if (response.ok && response.headers.get('content-type')?.includes('text/html')) {
                    const clone = response.clone();
                    caches.open(CACHE).then(cache => cache.put(event.request, clone));
                }
                return response;
            })
            .catch(() => caches.match(event.request))
    );
});
