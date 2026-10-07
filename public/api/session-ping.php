<?php
// Called by admin/idle-logout.js: POST = the person is active (extends the session),
// GET = just report whether the session is still alive.
header('Content-Type: application/json');
header('Cache-Control: no-store');
require_once __DIR__ . '/admin-session.php';

$remaining = 0;
if (!empty($_SESSION['admin_logged_in'])) {
    $remaining = ADMIN_IDLE_MINUTES * 60 - (time() - ($_SESSION['last_activity'] ?? time()));
}
echo json_encode([
    'logged_in'   => !empty($_SESSION['admin_logged_in']),
    'remaining'   => max(0, $remaining),
    'idle_limit'  => ADMIN_IDLE_MINUTES * 60,
]);
