<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

function getPdo() {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->exec("CREATE TABLE IF NOT EXISTS pending_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_ref VARCHAR(20) NOT NULL UNIQUE,
        payment_method VARCHAR(50),
        items TEXT NOT NULL,
        total DECIMAL(10,2) NOT NULL,
        status ENUM('pending','completed','cancelled') DEFAULT 'pending',
        note VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL,
        cancelled_at TIMESTAMP NULL
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS stock_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_name VARCHAR(500) NOT NULL,
        action ENUM('received','sold','damaged','returned') NOT NULL,
        quantity INT NOT NULL,
        stock_before INT NOT NULL,
        stock_after INT NOT NULL,
        note VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    return $pdo;
}

$method = $_SERVER['REQUEST_METHOD'];

// Handle CORS preflight
if ($method === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit;
}

$data = [];
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
}
$action = $data['action'] ?? $_GET['action'] ?? '';

// ── CREATE (public — called from customer site) ──────────────────────────
if ($action === 'create' && $method === 'POST') {
    $items         = $data['items'] ?? [];
    $total         = (float)($data['total'] ?? 0);
    $paymentMethod = substr(trim($data['payment_method'] ?? 'Not specified'), 0, 50);

    if (empty($items) || $total < 0) {
        echo json_encode(['error' => 'Invalid order data']); exit;
    }

    try {
        $pdo      = getPdo();
        $orderRef = 'ORD-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -5));
        $pdo->prepare('INSERT INTO pending_orders (order_ref, payment_method, items, total) VALUES (?, ?, ?, ?)')
            ->execute([$orderRef, $paymentMethod, json_encode($items), $total]);
        echo json_encode(['success' => true, 'order_ref' => $orderRef]);
    } catch (Exception $e) { echo json_encode(['error' => $e->getMessage()]); }
    exit;
}

// ── ADMIN ONLY below this point ──────────────────────────────────────────
session_start();
if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// ── COMPLETE order — deduct stock ────────────────────────────────────────
if ($action === 'complete' && $method === 'POST') {
    $id   = (int)($data['id'] ?? 0);
    $note = substr(trim($data['note'] ?? ''), 0, 255);
    if (!$id) { echo json_encode(['error' => 'Invalid ID']); exit; }

    try {
        $pdo  = getPdo();
        $stmt = $pdo->prepare('SELECT * FROM pending_orders WHERE id = ? AND status = "pending"');
        $stmt->execute([$id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) { echo json_encode(['error' => 'Order not found or already processed']); exit; }

        $items = json_decode($order['items'], true) ?? [];

        foreach ($items as $item) {
            $name = $item['name'];
            $qty  = max(1, (int)($item['quantity'] ?? 1));

            $stockStmt = $pdo->prepare('SELECT quantity FROM product_stock WHERE product_name = ?');
            $stockStmt->execute([$name]);
            $row         = $stockStmt->fetch(PDO::FETCH_ASSOC);
            $stockBefore = $row ? (int)$row['quantity'] : 0;
            $stockAfter  = max(0, $stockBefore - $qty);

            $pdo->prepare('INSERT INTO product_stock (product_name, quantity) VALUES (?, ?) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)')
                ->execute([$name, $stockAfter]);

            $txNote = trim(($order['order_ref'] ?? '') . ' — ' . ($order['payment_method'] ?? '') . ($note ? ' — ' . $note : ''));
            $pdo->prepare('INSERT INTO stock_transactions (product_name, action, quantity, stock_before, stock_after, note) VALUES (?, "sold", ?, ?, ?, ?)')
                ->execute([$name, $qty, $stockBefore, $stockAfter, $txNote]);
        }

        $pdo->prepare('UPDATE pending_orders SET status = "completed", completed_at = NOW(), note = COALESCE(NULLIF(?, ""), note) WHERE id = ?')
            ->execute([$note, $id]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) { echo json_encode(['error' => $e->getMessage()]); }
    exit;
}

// ── CANCEL order ─────────────────────────────────────────────────────────
if ($action === 'cancel' && $method === 'POST') {
    $id   = (int)($data['id'] ?? 0);
    $note = substr(trim($data['note'] ?? ''), 0, 255);
    if (!$id) { echo json_encode(['error' => 'Invalid ID']); exit; }

    try {
        $pdo = getPdo();
        $pdo->prepare('UPDATE pending_orders SET status = "cancelled", cancelled_at = NOW(), note = ? WHERE id = ? AND status = "pending"')
            ->execute([$note ?: 'Cancelled by admin', $id]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) { echo json_encode(['error' => $e->getMessage()]); }
    exit;
}

// ── GET orders ────────────────────────────────────────────────────────────
if ($method === 'GET') {
    $status = $_GET['status'] ?? 'pending';
    try {
        $pdo = getPdo();
        if ($status === 'all') {
            $stmt = $pdo->query('SELECT * FROM pending_orders ORDER BY created_at DESC LIMIT 200');
        } else {
            $stmt = $pdo->prepare('SELECT * FROM pending_orders WHERE status = ? ORDER BY created_at DESC LIMIT 200');
            $stmt->execute([$status]);
        }
        echo json_encode(['orders' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (Exception $e) { echo json_encode(['error' => $e->getMessage()]); }
    exit;
}

echo json_encode(['error' => 'Invalid request']);
