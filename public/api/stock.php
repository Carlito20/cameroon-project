<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/db.php';
session_start();

function getDB() {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->exec("CREATE TABLE IF NOT EXISTS product_stock (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_name VARCHAR(500) NOT NULL UNIQUE,
        quantity INT NOT NULL DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    return $pdo;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// GET ?action=all — returns all stock overrides (public, no auth)
if ($method === 'GET' && $action === 'all') {
    try {
        $pdo = getDB();
        $stmt = $pdo->query('SELECT product_name, quantity FROM product_stock');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = [];
        foreach ($rows as $row) {
            $result[$row['product_name']] = (int)$row['quantity'];
        }
        echo json_encode($result);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'DB error']);
    }
    exit;
}

// POST — requires session auth
if ($method === 'POST') {
    if (empty($_SESSION['admin_logged_in'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }

    $body = json_decode(file_get_contents('php://input'), true);

    // POST ?action=init — bulk initialize from products-list.json
    if ($action === 'init') {
        $jsonPath = __DIR__ . '/../api/products-list.json';
        if (!file_exists($jsonPath)) {
            http_response_code(404);
            echo json_encode(['error' => 'products-list.json not found']);
            exit;
        }
        $products = json_decode(file_get_contents($jsonPath), true);
        if (!is_array($products)) {
            http_response_code(500);
            echo json_encode(['error' => 'Invalid products-list.json']);
            exit;
        }
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare(
                'INSERT INTO product_stock (product_name, quantity)
                 VALUES (:name, :qty)
                 ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)'
            );
            foreach ($products as $product) {
                $stmt->execute([
                    ':name' => $product['name'],
                    ':qty'  => (int)$product['quantity']
                ]);
            }
            echo json_encode(['success' => true, 'count' => count($products)]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'DB error: ' . $e->getMessage()]);
        }
        exit;
    }

    // POST — update single product stock {name, quantity}
    if (empty($body['name']) || !isset($body['quantity'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing name or quantity']);
        exit;
    }
    $name = trim($body['name']);
    $qty  = max(0, (int)$body['quantity']);
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare(
            'INSERT INTO product_stock (product_name, quantity)
             VALUES (:name, :qty)
             ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)'
        );
        $stmt->execute([':name' => $name, ':qty' => $qty]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'DB error: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
