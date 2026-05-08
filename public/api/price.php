<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
session_start();

function getDB() {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->exec("CREATE TABLE IF NOT EXISTS product_prices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_name VARCHAR(500) NOT NULL UNIQUE,
        price INT NOT NULL DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    return $pdo;
}

$method = $_SERVER['REQUEST_METHOD'];

// GET — return all price overrides as {product_name: price}
if ($method === 'GET') {
    try {
        $pdo = getDB();
        $rows = $pdo->query('SELECT product_name, price FROM product_prices')->fetchAll(PDO::FETCH_ASSOC);
        $result = [];
        foreach ($rows as $row) {
            $result[$row['product_name']] = (int)$row['price'];
        }
        echo json_encode($result);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'DB error']);
    }
    exit;
}

if ($method === 'POST') {
    if (empty($_SESSION['admin_logged_in'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }

    $body = json_decode(file_get_contents('php://input'), true);
    $action = $body['action'] ?? 'save';

    // Reset price to catalog default (delete override)
    if ($action === 'reset') {
        if (empty($body['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing name']);
            exit;
        }
        try {
            $pdo = getDB();
            $pdo->prepare('DELETE FROM product_prices WHERE product_name = ?')
                ->execute([trim($body['name'])]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'DB error']);
        }
        exit;
    }

    // Save price override
    if (empty($body['name']) || !isset($body['price'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing name or price']);
        exit;
    }
    $name  = trim($body['name']);
    $price = max(0, (int)$body['price']);
    try {
        $pdo = getDB();
        $pdo->prepare(
            'INSERT INTO product_prices (product_name, price)
             VALUES (:name, :price)
             ON DUPLICATE KEY UPDATE price = VALUES(price)'
        )->execute([':name' => $name, ':price' => $price]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'DB error: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
