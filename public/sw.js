self.addEventListener('fetch', event => {
    if (event.request.method !== 'GET') return
    const url = new URL(event.request.url)
    if (url.origin !== self.location.origin) return

    event.respondWith(
        fetch(event.request)
            .then(response => {
                self.clients.matchAll().then(clients =>
                    clients.forEach(c => c.postMessage({ type: 'ONLINE' }))
                )
                return response
            })
            .catch(() => {
                self.clients.matchAll().then(clients =>
                    clients.forEach(c => c.postMessage({ type: 'OFFLINE' }))
                )
                return new Response('Offline', {
                    status: 503,
                    headers: { 'Content-Type': 'text/plain' }
                })
            })
    )
})
