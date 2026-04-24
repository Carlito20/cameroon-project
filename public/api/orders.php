<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

function getPdo() {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->exec("SET time_zone = '+00:00'");
    $pdo->exec("CREATE TABLE IF NOT EXISTS pending_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_ref VARCHAR(20) NOT NULL UNIQUE,
        customer_name VARCHAR(100),
        customer_phone VARCHAR(30),
        payment_method VARCHAR(50),
        items TEXT NOT NULL,
        total DECIMAL(10,2) NOT NULL,
        status ENUM('pending','completed','cancelled','damaged','returned') DEFAULT 'pending',
        note VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL,
        cancelled_at TIMESTAMP NULL
    )");
    // Add columns to existing tables that predate this migration
    try { $pdo->exec("ALTER TABLE pending_orders ADD COLUMN customer_name VARCHAR(100) AFTER order_ref"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE pending_orders ADD COLUMN customer_phone VARCHAR(30) AFTER customer_name"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE pending_orders ADD COLUMN payment_ref VARCHAR(100) AFTER payment_method"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE pending_orders MODIFY COLUMN status ENUM('pending','completed','cancelled','damaged','returned') DEFAULT 'pending'"); } catch (Exception $e) {}
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

// Get current stock — initialise from products-list.json if never set in DB
function getOrInitStock($pdo, $productName) {
    $stmt = $pdo->prepare('SELECT quantity FROM product_stock WHERE product_name = ?');
    $stmt->execute([$productName]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row !== false) return (int)$row['quantity'];

    // Not in DB yet — seed from static products-list.json
    $jsonPath = __DIR__ . '/products-list.json';
    $qty = 0;
    if (file_exists($jsonPath)) {
        $products = json_decode(file_get_contents($jsonPath), true) ?? [];
        foreach ($products as $p) {
            if ($p['name'] === $productName) { $qty = (int)($p['quantity'] ?? 0); break; }
        }
    }
    $pdo->prepare('INSERT INTO product_stock (product_name, quantity) VALUES (?, ?) ON DUPLICATE KEY UPDATE quantity = quantity')
        ->execute([$productName, $qty]);
    return $qty;
}

// Reserve stock when order is placed — deducts immediately
function reserveStock($pdo, $items, $orderRef) {
    foreach ($items as $item) {
        $name = $item['name'];
        $qty  = max(1, (int)($item['quantity'] ?? 1));
        $stockBefore = getOrInitStock($pdo, $name);
        $stockAfter  = max(0, $stockBefore - $qty);
        $pdo->prepare('INSERT INTO product_stock (product_name, quantity) VALUES (?, ?) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)')
            ->execute([$name, $stockAfter]);
        $pdo->prepare('INSERT INTO stock_transactions (product_name, action, quantity, stock_before, stock_after, note) VALUES (?, "sold", ?, ?, ?, ?)')
            ->execute([$name, $qty, $stockBefore, $stockAfter, 'Reserved — ' . $orderRef]);
    }
}

// Restore stock when order is cancelled
function restoreStock($pdo, $items, $orderRef) {
    foreach ($items as $item) {
        $name = $item['name'];
        $qty  = max(1, (int)($item['quantity'] ?? 1));
        $stockBefore = getOrInitStock($pdo, $name);
        $stockAfter  = $stockBefore + $qty;
        $pdo->prepare('INSERT INTO product_stock (product_name, quantity) VALUES (?, ?) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)')
            ->execute([$name, $stockAfter]);
        $pdo->prepare('INSERT INTO stock_transactions (product_name, action, quantity, stock_before, stock_after, note) VALUES (?, "returned", ?, ?, ?, ?)')
            ->execute([$name, $qty, $stockBefore, $stockAfter, 'Restored — Cancelled ' . $orderRef]);
    }
}

// Auto-cancel orders older than 24 hours and restore their stock
function autoExpireOrders($pdo) {
    $stmt = $pdo->query('SELECT * FROM pending_orders WHERE status = "pending" AND created_at < NOW() - INTERVAL 24 HOUR');
    $expired = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($expired as $order) {
        $items = json_decode($order['items'], true) ?? [];
        restoreStock($pdo, $items, $order['order_ref']);
        $pdo->prepare('UPDATE pending_orders SET status = "cancelled", cancelled_at = NOW(), note = "Auto-cancelled after 24 hours — stock restored" WHERE id = ?')
            ->execute([$order['id']]);
    }
    return count($expired);
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
    $paymentMethod  = substr(trim($data['payment_method'] ?? 'Not specified'), 0, 50);
    $customerName   = substr(trim($data['customer_name'] ?? ''), 0, 100);
    $customerPhone  = substr(trim($data['customer_phone'] ?? ''), 0, 30);

    if (empty($items) || $total < 0) {
        echo json_encode(['error' => 'Invalid order data']); exit;
    }

    try {
        $pdo      = getPdo();
        $orderRef = 'ORD-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -5));
        $pdo->prepare('INSERT INTO pending_orders (order_ref, customer_name, customer_phone, payment_method, items, total) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$orderRef, $customerName ?: null, $customerPhone ?: null, $paymentMethod, json_encode($items), $total]);

        // Reserve stock immediately
        reserveStock($pdo, $items, $orderRef);

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

// ── COMPLETE order — stock already reserved on create, just mark done ────
if ($action === 'complete' && $method === 'POST') {
    $id          = (int)($data['id'] ?? 0);
    $note        = substr(trim($data['note'] ?? ''), 0, 255);
    $paymentRef  = substr(trim($data['payment_ref'] ?? ''), 0, 100);
    if (!$id) { echo json_encode(['error' => 'Invalid ID']); exit; }

    try {
        $pdo  = getPdo();
        $stmt = $pdo->prepare('SELECT * FROM pending_orders WHERE id = ? AND status = "pending"');
        $stmt->execute([$id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) { echo json_encode(['error' => 'Order not found or already processed']); exit; }

        // Stock was already deducted when order was placed — just mark as completed
        $txNote = trim($order['order_ref'] . ' — ' . ($order['payment_method'] ?? '') . ($note ? ' — ' . $note : ''));
        $pdo->prepare('UPDATE stock_transactions SET note = ? WHERE note = ? AND action = "sold"')
            ->execute(['Completed — ' . $txNote, 'Reserved — ' . $order['order_ref']]);

        $pdo->prepare('UPDATE pending_orders SET status = "completed", completed_at = NOW(), note = COALESCE(NULLIF(?, ""), note), payment_ref = COALESCE(NULLIF(?, ""), payment_ref) WHERE id = ?')
            ->execute([$note, $paymentRef, $id]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) { echo json_encode(['error' => $e->getMessage()]); }
    exit;
}

// ── CANCEL order — restore reserved stock ────────────────────────────────
if ($action === 'cancel' && $method === 'POST') {
    $id   = (int)($data['id'] ?? 0);
    $note = substr(trim($data['note'] ?? ''), 0, 255);
    if (!$id) { echo json_encode(['error' => 'Invalid ID']); exit; }

    try {
        $pdo  = getPdo();
        $stmt = $pdo->prepare('SELECT * FROM pending_orders WHERE id = ? AND status = "pending"');
        $stmt->execute([$id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) { echo json_encode(['error' => 'Order not found or already pending']); exit; }

        $items = json_decode($order['items'], true) ?? [];
        restoreStock($pdo, $items, $order['order_ref']);

        $pdo->prepare('UPDATE pending_orders SET status = "cancelled", cancelled_at = NOW(), note = ? WHERE id = ?')
            ->execute([$note ?: 'Cancelled by admin — stock restored', $id]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) { echo json_encode(['error' => $e->getMessage()]); }
    exit;
}

// ── DAMAGED / RETURNED — restore stock and update status ─────────────────
if (in_array($action, ['damaged','returned']) && $method === 'POST') {
    $id   = (int)($data['id'] ?? 0);
    $note = substr(trim($data['note'] ?? ''), 0, 255);
    if (!$id) { echo json_encode(['error' => 'Invalid ID']); exit; }

    try {
        $pdo  = getPdo();
        $stmt = $pdo->prepare('SELECT * FROM pending_orders WHERE id = ? AND status = "completed"');
        $stmt->execute([$id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) { echo json_encode(['error' => 'Order not found or not completed']); exit; }

        $items = json_decode($order['items'], true) ?? [];
        restoreStock($pdo, $items, $order['order_ref']);

        $defaultNote = $action === 'damaged' ? 'Damaged — stock restored' : 'Returned by customer — stock restored';
        $pdo->prepare('UPDATE pending_orders SET status = ?, note = COALESCE(NULLIF(?, ""), ?) WHERE id = ?')
            ->execute([$action, $note, $defaultNote, $id]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) { echo json_encode(['error' => $e->getMessage()]); }
    exit;
}

// ── POS SALE — record in-store sale as completed (stock already deducted by barcode.php) ──
if ($action === 'pos_sale' && $method === 'POST') {
    $items         = $data['items'] ?? [];
    $total         = (float)($data['total'] ?? 0);
    $paymentMethod = substr(trim($data['payment_method'] ?? 'Cash'), 0, 50);
    $customerName  = substr(trim($data['customer_name'] ?? ''), 0, 100);
    $customerPhone = substr(trim($data['customer_phone'] ?? ''), 0, 30);

    if (empty($items) || $total < 0) {
        echo json_encode(['error' => 'Invalid data']); exit;
    }

    try {
        $pdo      = getPdo();
        $orderRef = 'POS-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -5));
        $pdo->prepare('INSERT INTO pending_orders (order_ref, customer_name, customer_phone, payment_method, items, total, status, completed_at) VALUES (?, ?, ?, ?, ?, ?, "completed", NOW())')
            ->execute([$orderRef, $customerName ?: null, $customerPhone ?: null, $paymentMethod, json_encode($items), $total]);

        echo json_encode(['success' => true, 'order_ref' => $orderRef]);
    } catch (Exception $e) { echo json_encode(['error' => $e->getMessage()]); }
    exit;
}

// ── CLEAR ALL orders — remove before going live ───────────────────────────
if ($action === 'clear_all' && $method === 'POST') {
    try {
        $pdo = getPdo();
        $pdo->exec('DELETE FROM pending_orders');
        $pdo->exec('DELETE FROM stock_transactions');
        echo json_encode(['success' => true]);
    } catch (Exception $e) { echo json_encode(['error' => $e->getMessage()]); }
    exit;
}

// ── GET orders — also runs auto-expire check ─────────────────────────────
if ($method === 'GET') {
    $status = $_GET['status'] ?? 'pending';
    try {
        $pdo = getPdo();

        // Auto-expire any orders older than 24h
        autoExpireOrders($pdo);

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
