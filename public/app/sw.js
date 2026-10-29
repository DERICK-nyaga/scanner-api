self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open('scanner-v1').then((cache) => {
      return cache.addAll([
        '/app/',
        '/app/index.html',
        '/app/app.js'
      ]);
    })
  );
});

self.addEventListener('fetch', (e) => {
  e.respondWith(
    caches.match(e.request).then((response) => {
      return response || fetch(e.request);
    })
  );
});