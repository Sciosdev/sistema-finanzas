const FINANCE_CACHE = 'finanzas-app-v1';
const FINANCE_CORE_ASSETS = [
  '/manifest.webmanifest',
  '/images/favicon.ico',
  '/images/logo-sm.png'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(FINANCE_CACHE)
      .then((cache) => cache.addAll(FINANCE_CORE_ASSETS))
      .catch(() => undefined)
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(
      keys.filter((key) => key !== FINANCE_CACHE).map((key) => caches.delete(key))
    ))
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const request = event.request;

  if (request.method !== 'GET' || !request.url.startsWith(self.location.origin)) {
    return;
  }

  event.respondWith(
    fetch(request)
      .then((response) => {
        if (response && response.ok && request.destination !== 'document') {
          const clone = response.clone();
          caches.open(FINANCE_CACHE).then((cache) => cache.put(request, clone));
        }

        return response;
      })
      .catch(() => {
        if (request.destination === 'document') {
          return new Response('Sin conexión. Vuelve a abrir Finanzas cuando tengas internet o red local.', {
            status: 503,
            headers: { 'Content-Type': 'text/plain; charset=UTF-8' }
          });
        }

        return caches.match(request);
      })
  );
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  event.waitUntil(clients.openWindow('/finanzas'));
});
