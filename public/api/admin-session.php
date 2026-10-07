<?php
// Starts the session and logs admins out after ADMIN_IDLE_MINUTES without activity.
// Activity = loading an admin page or saving something (any non-GET request).
// Background GET polling from open pages (pending orders, stock) does not keep a
// session alive. admin/idle-logout.js pings session-ping.php while someone is
// actually using a page, and returns to the login screen once the time is up.

const ADMIN_IDLE_MINUTES = 30;

if (session_status() === PHP_SESSION_NONE) session_start();

if (!empty($_SESSION['admin_logged_in'])) {
    $now  = time();
    $last = $_SESSION['last_activity'] ?? $now;
    if ($now - $last > ADMIN_IDLE_MINUTES * 60) {
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['timed_out'] = true;
    } else {
        $isApiGet = $_SERVER['REQUEST_METHOD'] === 'GET'
            && strpos(str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? ''), '/api/') !== false;
        if (!$isApiGet || !isset($_SESSION['last_activity'])) $_SESSION['last_activity'] = $now;
    }
}
