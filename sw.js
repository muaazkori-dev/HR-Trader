// HR Traders Service Worker for PWA Support
const CACHE_NAME = 'hr-traders-cache-v1';

self.addEventListener('install', (e) => {
    self.skipWaiting();
});

self.addEventListener('activate', (e) => {
    e.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (e) => {
    // Basic network-first strategy to ensure fresh data for ecommerce
    e.respondWith(
        fetch(e.request).catch(() => {
            return caches.match(e.request);
        })
    );
});
