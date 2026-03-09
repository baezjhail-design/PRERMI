const CACHE_NAME = 'prermi-cache-v1';
const ASSETS = [
  './',
  'index.php',
  'assets/css/style.css',
  'manifest.json'
];

// Install event
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS).catch(() => {
        // Si algún asset falla, continuamos
        console.log('Some assets could not be cached');
      });
    })
  );
  // Forzar que el SW nuevo tome control inmediatamente
  self.skipWaiting();
});

// Activate event
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// Fetch event
self.addEventListener('fetch', (event) => {
  // No cachear APIs JSON
  if (event.request.url.includes('/api/')) {
    event.respondWith(
      fetch(event.request).catch(() => {
        return new Response(
          JSON.stringify({ error: 'Offline' }),
          { headers: { 'Content-Type': 'application/json' } }
        );
      })
    );
    return;
  }

  // Cachear recursos estáticos
  event.respondWith(
    caches.match(event.request).then((response) => {
      return response || fetch(event.request).catch(() => {
        return new Response('Offline');
      });
    })
  );
});

// Message handler para evitar errores de listener
self.addEventListener('message', (event) => {
  // Simplemente responder si se envía un mensaje
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});
