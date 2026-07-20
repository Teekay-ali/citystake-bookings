// CityStake service worker.
//
// Deliberately conservative: this app is multi-user against shared, live data,
// so HTML and API responses are NEVER cached (stale bookings/finances would be
// worse than a slow load). Only content-hashed build assets and a small offline
// shell are cached.
//
// Bump CACHE_VERSION to invalidate everything on the next deploy.
const CACHE_VERSION = 'v2'
const CACHE_NAME    = `citystake-${CACHE_VERSION}`
const OFFLINE_URL   = '/offline.html'

// Small shell that must be available while offline.
const PRECACHE = [
    OFFLINE_URL,
    '/favicon.ico',
    '/android-chrome-192x192.png',
    '/android-chrome-512x512.png',
]

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(PRECACHE))
            .then(() => self.skipWaiting())
    )
})

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k))
            ))
            .then(() => self.clients.claim())
    )
})

// Let the page trigger an immediate update after a deploy.
self.addEventListener('message', (event) => {
    if (event.data?.type === 'SKIP_WAITING') self.skipWaiting()
})

function notify(type) {
    self.clients.matchAll().then((clients) =>
        clients.forEach((c) => c.postMessage({ type }))
    )
}

// Content-hashed Vite output is immutable, so it's safe to serve from cache.
function isImmutableAsset(url) {
    return url.origin === self.location.origin && url.pathname.startsWith('/build/')
}

self.addEventListener('fetch', (event) => {
    const { request } = event

    if (request.method !== 'GET') return

    const url = new URL(request.url)

    // 1. Hashed build assets - cache-first (instant loads, survives flaky network).
    if (isImmutableAsset(url)) {
        event.respondWith(
            caches.match(request).then((cached) => cached || fetch(request).then((response) => {
                if (response.ok) {
                    const copy = response.clone()
                    caches.open(CACHE_NAME).then((cache) => cache.put(request, copy))
                }
                return response
            }))
        )
        return
    }

    // 2. Page navigations - always network-first, fall back to the offline shell.
    //    Never cache the response: it's authenticated, live data.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => { notify('ONLINE'); return response })
                .catch(() => {
                    notify('OFFLINE')
                    return caches.match(OFFLINE_URL)
                })
        )
        return
    }

    // 3. Everything else (API calls, images, fonts) - straight to the network.
})
