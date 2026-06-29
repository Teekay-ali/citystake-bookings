// Take over from any previously-installed worker immediately. An older version
// of this worker intercepted every request and returned a synthetic 503 on
// failure, which broke asset loading — this replaces it.
self.addEventListener('install', () => self.skipWaiting())
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()))

function notify(type) {
    self.clients.matchAll().then((clients) =>
        clients.forEach((c) => c.postMessage({ type }))
    )
}

// Only watch top-level page navigations for connectivity. Never intercept
// scripts, styles, or other assets — let the browser load them normally so a
// transient failure can't be turned into a hard 503.
self.addEventListener('fetch', (event) => {
    if (event.request.mode !== 'navigate') return

    event.respondWith(
        fetch(event.request)
            .then((response) => { notify('ONLINE'); return response })
            .catch(() => {
                notify('OFFLINE')
                return new Response(
                    '<!doctype html><meta charset="utf-8"><title>Offline</title>' +
                    '<body style="font-family:sans-serif;padding:2rem;text-align:center">' +
                    '<h1>You\'re offline</h1><p>Check your connection and try again.</p>',
                    { status: 200, headers: { 'Content-Type': 'text/html' } }
                )
            })
    )
})
