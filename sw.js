// Service Worker for SRMS PWA
const CACHE_NAME = 'srms-v1.0.0';
const urlsToCache = [
  '/',
  '/index.php',
  '/login.php',
  '/assets/css/iris-design-system.css',
  '/css/app.css',
  '/css/styles.css',
  '/js/app.js',
  '/manifest.json'
];

// Install event
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return cache.addAll(urlsToCache);
      })
  );
});

// Fetch event
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        // Return cached version or fetch from network
        return response || fetch(event.request);
      }
    )
  );
});

// Activate event
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// Background sync for offline data
self.addEventListener('sync', event => {
  if (event.tag === 'background-sync') {
    event.waitUntil(doBackgroundSync());
  }
});

function doBackgroundSync() {
  // Sync offline data when connection is restored
  return fetch('/api/sync-offline-data', {
    method: 'POST',
    body: JSON.stringify(getOfflineData()),
    headers: {
      'Content-Type': 'application/json'
    }
  });
}

function getOfflineData() {
  // Get data stored offline
  return JSON.parse(localStorage.getItem('offlineData') || '[]');
}