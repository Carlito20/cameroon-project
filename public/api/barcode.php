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
    $pdo->exec("CREATE TABLE IF NOT EXISTS barcode_map (
        barcode VARCHAR(100) PRIMARY KEY,
        product_name VARCHAR(500) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    return $pdo;
}

$method = $_SERVER['REQUEST_METHOD'];

// GET: look up a barcode
if ($method === 'GET') {
    $barcode = trim($_GET['barcode'] ?? '');
    if (!$barcode) {
        echo json_encode(['error' => 'No barcode provided']);
        exit;
    }
    try {
        $pdo = getPdo();
        $stmt = $pdo->prepare('SELECT product_name FROM barcode_map WHERE barcode = ?');
        $stmt->execute([$barcode]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $stmt2 = $pdo->prepare('SELECT quantity FROM product_stock WHERE product_name = ?');
            $stmt2->execute([$row['product_name']]);
            $stock = $stmt2->fetch(PDO::FETCH_ASSOC);
            echo json_encode([
                'found' => true,
                'product_name' => $row['product_name'],
                'quantity' => $stock ? (int)$stock['quantity'] : 0
            ]);
        } else {
            echo json_encode(['found' => false]);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// POST: assign barcode or update stock
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';

    if ($action === 'assign') {
        $barcode = trim($data['barcode'] ?? '');
        $productName = trim($data['product_name'] ?? '');
        if (!$barcode || !$productName) {
            echo json_encode(['error' => 'Missing barcode or product name']);
            exit;
        }
        try {
            $pdo = getPdo();
            $stmt = $pdo->prepare('INSERT INTO barcode_map (barcode, product_name) VALUES (?, ?) ON DUPLICATE KEY UPDATE product_name = VALUES(product_name)');
            $stmt->execute([$barcode, $productName]);
            // Also get current stock
            $stmt2 = $pdo->prepare('SELECT quantity FROM product_stock WHERE product_name = ?');
            $stmt2->execute([$productName]);
            $stock = $stmt2->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'quantity' => $stock ? (int)$stock['quantity'] : 0]);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'stock') {
        $productName = trim($data['product_name'] ?? '');
        $quantity = isset($data['quantity']) ? (int)$data['quantity'] : -1;
        if (!$productName || $quantity < 0) {
            echo json_encode(['error' => 'Invalid data']);
            exit;
        }
        try {
            $pdo = getPdo();
            $stmt = $pdo->prepare('INSERT INTO product_stock (product_name, quantity) VALUES (?, ?) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)');
            $stmt->execute([$productName, $quantity]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    echo json_encode(['error' => 'Unknown action']);
    exit;
}

echo json_encode(['error' => 'Method not allowed']);
