// Cache static public resources only. Clinical data must never be available offline.
const BASE = new URL('./', self.location.href);
const CACHE_PREFIX = `clinicapp-static-${encodeURIComponent(BASE.pathname)}-`;
const CACHE_NAME = CACHE_PREFIX + 'audit1';
const publicUrl = path => new URL(path, BASE).href;
const ASSETS = ['assets/img/icon-192.png', 'assets/img/icon-512.png'];
self.addEventListener('install', event => {
  event.waitUntil(caches.open(CACHE_NAME).then(cache => cache.addAll(ASSETS.map(publicUrl))));
  self.skipWaiting();
});
self.addEventListener('activate', event => {
  const legacy = ['clinicapp-v110', 'clinicapp-v130-vite-pages'];
  event.waitUntil(caches.keys().then(keys => Promise.all(keys.filter(key => (key.startsWith(CACHE_PREFIX) && key !== CACHE_NAME) || legacy.includes(key)).map(key => caches.delete(key)))).then(() => self.clients.claim()));
});
self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET') return;
  const url = new URL(event.request.url);
  const relative = url.pathname.startsWith(BASE.pathname) ? url.pathname.slice(BASE.pathname.length) : '';
  // No documents, PHP, APIs, uploads, foreign origins, query strings or private manifests.
  if (url.origin !== BASE.origin || url.search || !/^(assets\/build\/assets\/.*\.(css|js|woff2?)|assets\/img\/icon-(192|512)\.png)$/.test(relative)) return;
  event.respondWith(caches.open(CACHE_NAME).then(async cache => {
    const cached = await cache.match(event.request);
    if (cached) return cached;
    const response = await fetch(event.request);
    if (response.ok && !response.redirected && response.type !== 'opaque') await cache.put(event.request, response.clone());
    return response;
  }));
});
function safeTarget(value) {
  try {
    const url = new URL(value || 'dashboard.php', BASE);
    if (url.origin === BASE.origin && url.pathname.startsWith(BASE.pathname)) return url.href;
  } catch (_) { /* Invalid notification URL: return to dashboard. */ }
  return publicUrl('dashboard.php');
}
self.addEventListener('message', event => {
  const data = event.data || {};
  if (data.type === 'show-notification') event.waitUntil(self.registration.showNotification(data.title || 'ClinicApp', {
    body: data.body || '', icon: publicUrl('assets/img/icon-192.png'), badge: publicUrl('assets/img/icon-192.png'),
    tag: data.tag || 'clinicapp-notification', data: { url: safeTarget(data.url) },
  }));
});
self.addEventListener('notificationclick', event => {
  event.notification.close();
  const target = safeTarget(event.notification.data?.url);
  event.waitUntil(clients.matchAll({ type: 'window', includeUncontrolled: true }).then(async windows => {
    const own = windows.find(client => client.url.startsWith(BASE.href));
    if (own) { await own.navigate(target); return own.focus(); }
    return clients.openWindow(target);
  }));
});
