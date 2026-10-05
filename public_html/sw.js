// ==============================================================================
// MitraNet Network OS - Progressive Web App (PWA) Service Worker
// High-Reliability Offline-Ready UI Caching & Air-Gapped Network Management
// ==============================================================================

const CACHE_NAME = 'mitranet-pwa-v1.0.0-lts';

// Pre-cached core assets for instant loading
const PRECACHE_ASSETS = [
  '/',
  '/style.css',
  '/manifest.json',
  '/vendor/bootstrap/css/bootstrap.min.css',
  '/vendor/bootstrap/js/bootstrap.bundle.min.js',
  '/vendor/jquery/jquery.min.js',
  '/includes/common.js',
  '/assets/icons/icon-192.svg',
  '/assets/icons/icon-512.svg'
];

// Install: Cache essential shell assets
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return cache.addAll(PRECACHE_ASSETS);
    }).then(() => self.skipWaiting())
  );
});

// Activate: Clean up any old caches
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => {
      return Promise.all(
        keys.map(key => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch: Smart Strategy
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);

  // 1. NEVER cache live REST API telemetry (real-time live statistics)
  if (url.pathname.startsWith('/api/')) {
    event.respondWith(fetch(event.request));
    return;
  }

  // 2. Cache-first strategy for static vendor assets, CSS, images, and fonts
  if (
    url.pathname.startsWith('/vendor/') ||
    url.pathname.startsWith('/assets/') ||
    url.pathname.endsWith('.css') ||
    url.pathname.endsWith('.js') ||
    url.pathname.endsWith('.svg') ||
    url.pathname.endsWith('.png')
  ) {
    event.respondWith(
      caches.match(event.request).then(cachedResponse => {
        if (cachedResponse) {
          return cachedResponse;
        }
        return fetch(event.request).then(networkResponse => {
          if (networkResponse && networkResponse.status === 200) {
            const responseClone = networkResponse.clone();
            caches.open(CACHE_NAME).then(cache => cache.put(event.request, responseClone));
          }
          return networkResponse;
        });
      })
    );
    return;
  }

  // 3. Network-first strategy for HTML pages with offline fallback
  event.respondWith(
    fetch(event.request).catch(() => {
      return caches.match(event.request).then(response => {
        if (response) {
          return response;
        }
        if (event.request.mode === 'navigate') {
          return caches.match('/');
        }
        return null;
      });
    })
  );
});
