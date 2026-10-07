<?php
header('Content-Type: application/json');
require_once __DIR__ . '/product-names-lib.php';
require_once __DIR__ . '/admin-session.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET — {original name: display name} for every renamed product (public)
if ($method === 'GET') {
    header('Cache-Control: no-store');
    echo json_encode((object)loadProductNames());
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}
if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}
if (($_SESSION['admin_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

$body    = json_decode(file_get_contents('php://input'), true) ?? [];
$name    = trim($body['name'] ?? '');
$display = trim(preg_replace('/\s+/', ' ', $body['display_name'] ?? ''));

if ($name === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing product']);
    exit;
}
if (mb_strlen($display) > 500) {
    http_response_code(400);
    echo json_encode(['error' => 'Name is too long']);
    exit;
}

try {
    $pdo = productNamesPdo();
    // Empty, or the same as the original → back to the original name
    if ($display === '' || $display === $name) {
        $pdo->prepare('DELETE FROM product_names WHERE product_name = ?')->execute([$name]);
        echo json_encode(['success' => true, 'display_name' => $name, 'renamed' => false]);
        exit;
    }
    $pdo->prepare(
        'INSERT INTO product_names (product_name, display_name) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE display_name = VALUES(display_name)'
    )->execute([$name, $display]);
    echo json_encode(['success' => true, 'display_name' => $display, 'renamed' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB error: ' . $e->getMessage()]);
}
