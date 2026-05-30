self.addEventListener('fetch', event => {
    if (event.request.method !== 'GET') return
    const url = new URL(event.request.url)
    if (url.origin !== self.location.origin) return

    event.respondWith(
        fetch(event.request)
            .then(response => {
                // Connection restored — notify clients
                self.clients.matchAll().then(clients =>
                    clients.forEach(c => c.postMessage({ type: 'ONLINE' }))
                )
                if (response.ok && response.headers.get('content-type')?.includes('text/html')) {
                    const clone = response.clone()
                    caches.open(CACHE).then(cache => cache.put(event.request, clone))
                }
                return response
            })
            .catch(() => {
                // Fetch failed — notify clients
                self.clients.matchAll().then(clients =>
                    clients.forEach(c => c.postMessage({ type: 'OFFLINE' }))
                )
                return caches.match(event.request)
            })
    )
})
