<?php
require_once __DIR__ . '/db.php';
session_start();

header('Content-Type: application/json');

if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

function getPdo() {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    // Barcode map
    $pdo->exec("CREATE TABLE IF NOT EXISTS barcode_map (
        barcode VARCHAR(100) PRIMARY KEY,
        product_name VARCHAR(500) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    // Transaction log
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

function getCurrentStock($pdo, $productName) {
    $stmt = $pdo->prepare('SELECT quantity FROM product_stock WHERE product_name = ?');
    $stmt->execute([$productName]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int)$row['quantity'] : 0;
}

function getProductPrice($productName) {
    $jsonPath = __DIR__ . '/products-list.json';
    if (!file_exists($jsonPath)) return 0;
    $products = json_decode(file_get_contents($jsonPath), true) ?? [];
    foreach ($products as $p) {
        if ($p['name'] === $productName) return (int)($p['price'] ?? 0);
    }
    return 0;
}

$method = $_SERVER['REQUEST_METHOD'];

// GET: look up by product name (for manual add in checkout)
if ($method === 'GET' && isset($_GET['name'])) {
    $productName = trim($_GET['name']);
    if (!$productName) { echo json_encode(['error' => 'No name']); exit; }
    try {
        $pdo = getPdo();
        $qty = getCurrentStock($pdo, $productName);
        $price = getProductPrice($productName);
        echo json_encode(['found' => true, 'product_name' => $productName, 'quantity' => $qty, 'price' => $price]);
    } catch (Exception $e) { echo json_encode(['error' => $e->getMessage()]); }
    exit;
}

// GET: look up barcode
if ($method === 'GET') {
    $barcode = trim($_GET['barcode'] ?? '');
    if (!$barcode) { echo json_encode(['error' => 'No barcode']); exit; }
    try {
        $pdo = getPdo();
        $stmt = $pdo->prepare('SELECT product_name FROM barcode_map WHERE barcode = ?');
        $stmt->execute([$barcode]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $qty = getCurrentStock($pdo, $row['product_name']);
            $price = getProductPrice($row['product_name']);
            echo json_encode(['found' => true, 'product_name' => $row['product_name'], 'quantity' => $qty, 'price' => $price]);
        } else {
            echo json_encode(['found' => false]);
        }
    } catch (Exception $e) { echo json_encode(['error' => $e->getMessage()]); }
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';

    // Assign barcode to product
    if ($action === 'assign') {
        $barcode = trim($data['barcode'] ?? '');
        $productName = trim($data['product_name'] ?? '');
        if (!$barcode || !$productName) { echo json_encode(['error' => 'Missing data']); exit; }
        try {
            $pdo = getPdo();
            $stmt = $pdo->prepare('INSERT INTO barcode_map (barcode, product_name) VALUES (?, ?) ON DUPLICATE KEY UPDATE product_name = VALUES(product_name)');
            $stmt->execute([$barcode, $productName]);
            $qty = getCurrentStock($pdo, $productName);
            echo json_encode(['success' => true, 'quantity' => $qty]);
        } catch (Exception $e) { echo json_encode(['error' => $e->getMessage()]); }
        exit;
    }

    // Log a stock transaction (received, sold, damaged, returned)
    if ($action === 'transaction') {
        $productName = trim($data['product_name'] ?? '');
        $txAction    = $data['tx_action'] ?? '';
        $qty         = (int)($data['quantity'] ?? 0);
        $note        = substr(trim($data['note'] ?? ''), 0, 255);

        if (!$productName || !in_array($txAction, ['received','sold','damaged','returned']) || $qty <= 0) {
            echo json_encode(['error' => 'Invalid data']); exit;
        }
        try {
            $pdo = getPdo();
            $stockBefore = getCurrentStock($pdo, $productName);

            if ($txAction === 'received' || $txAction === 'returned') {
                $stockAfter = $stockBefore + $qty;
            } else {
                $stockAfter = max(0, $stockBefore - $qty);
            }

            // Update stock
            $pdo->prepare('INSERT INTO product_stock (product_name, quantity) VALUES (?, ?) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)')
                ->execute([$productName, $stockAfter]);

            // Log transaction
            $pdo->prepare('INSERT INTO stock_transactions (product_name, action, quantity, stock_before, stock_after, note) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$productName, $txAction, $qty, $stockBefore, $stockAfter, $note ?: null]);

            echo json_encode(['success' => true, 'stock_before' => $stockBefore, 'stock_after' => $stockAfter]);
        } catch (Exception $e) { echo json_encode(['error' => $e->getMessage()]); }
        exit;
    }

    echo json_encode(['error' => 'Unknown action']);
    exit;
}

echo json_encode(['error' => 'Method not allowed']);
