// American Select POS — Service Worker
// Caches checkout.php after first authenticated load so it opens offline.
const CACHE = 'as-pos-v1';

// No precaching on install — we cache on first authenticated access instead,
// so we never store a login-redirect response.
self.addEventListener('install', e => {
  e.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', e => {
  // Remove old cache versions
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

  // ── checkout.php: network-first, cache on success ────────
  // Cache the plain URL (ignore query params like ?from_order=)
  if (path === '/admin/checkout.php') {
    e.respondWith(
      fetch(req)
        .then(res => {
          // Only cache a real authenticated response (200), not a login redirect
          if (res.status === 200) {
            const clone = res.clone();
            caches.open(CACHE).then(c => c.put(new Request('/admin/checkout.php'), clone));
          }
          return res;
        })
        .catch(() =>
          // Offline: serve cached version regardless of original query params
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

  // ── Images: cache-first (they rarely change) ─────────────
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
