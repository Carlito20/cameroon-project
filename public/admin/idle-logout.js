// Admin idle timeout (server side: api/admin-session.php).
// While someone is using the page, ping the server so the session stays alive.
// After the idle limit with no taps/keys/scrolling, go back to the login screen.
(function () {
  const PING_URL = '/api/session-ping.php';
  const PING_EVERY = 60 * 1000;         // at most one keep-alive per minute
  let idleLimit = 15 * 60 * 1000;       // replaced by the server's value
  let lastActivity = Date.now();
  let lastPing = 0;

  function goToLogin() {
    window.location.href = '/admin/logout.php?timeout=1';
  }

  function ping(active) {
    lastPing = Date.now();
    return fetch(PING_URL, { method: active ? 'POST' : 'GET', credentials: 'same-origin', cache: 'no-store' })
      .then(r => r.json())
      .then(d => {
        if (d.idle_limit) idleLimit = d.idle_limit * 1000;
        if (!d.logged_in) goToLogin();
      })
      .catch(() => {}); // offline (e.g. checkout offline mode) — never log out for that
  }

  function onActivity() {
    lastActivity = Date.now();
    if (Date.now() - lastPing > PING_EVERY) ping(true);
  }

  function check() {
    if (Date.now() - lastActivity >= idleLimit) { goToLogin(); return; }
    ping(false);
  }

  ['pointerdown', 'keydown', 'touchstart', 'scroll', 'input'].forEach(ev =>
    window.addEventListener(ev, onActivity, { passive: true, capture: true }));

  // Phones pause timers in the background — check as soon as the page is visible again
  document.addEventListener('visibilitychange', () => { if (!document.hidden) check(); });

  setInterval(check, 60 * 1000);
  ping(false);
})();
