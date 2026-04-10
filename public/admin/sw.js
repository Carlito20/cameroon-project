// American Select POS — Service Worker
// Caches checkout.php after first authenticated load.
// When offline, ANY /admin/ URL serves the cached checkout directly
// so the cashier never hits the PHP login wall.
const CACHE = 'as-pos-v1';

self.addEventListener('install', e => {
  e.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys()
      .then(keys => Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', e => {
  const req = e.request;
  const url = new URL(req.url);

  // Only handle same-origin GET requests
  if (req.method !== 'GET' || url.origin !== location.origin) return;

  const path = url.pathname;

  // ── Any /admin/ page: network-first ─────────────────────
  // Online  → fetch normally and cache checkout.php on success
  // Offline → serve cached checkout.php regardless of which /admin/ URL was requested
  if (path.startsWith('/admin/')) {
    e.respondWith(
      fetch(req)
        .then(res => {
          // Cache checkout.php whenever it loads successfully (status 200, not a redirect)
          if (path === '/admin/checkout.php' && res.status === 200) {
            const clone = res.clone();
            caches.open(CACHE).then(c => c.put(new Request('/admin/checkout.php'), clone));
          }
          return res;
        })
        .catch(() =>
          // Offline: always serve the cached checkout page
          caches.match('/admin/checkout.php')
        )
    );
    return;
  }

  // ── Products list: network-first, cache on success ───────
  if (path === '/api/products-list.json') {
    e.respondWith(
      fetch(req)
        .then(res => {
          if (res.status === 200) {
            const clone = res.clone();
            caches.open(CACHE).then(c => c.put(req, clone));
          }
          return res;
        })
        .catch(() => caches.match(req))
    );
    return;
  }

  // ── Images: cache-first (rarely change) ──────────────────
  if (path.startsWith('/images/')) {
    e.respondWith(
      caches.match(req).then(cached => {
        if (cached) return cached;
        return fetch(req).then(res => {
          if (res.status === 200) {
            const clone = res.clone();
            caches.open(CACHE).then(c => c.put(req, clone));
          }
          return res;
        });
      })
    );
    return;
  }
});
