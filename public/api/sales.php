<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
session_start();

if (empty($_SESSION['admin_logged_in']) || ($_SESSION['admin_role'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Not authorized']);
    exit;
}

function getPdo() {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->exec("CREATE TABLE IF NOT EXISTS walk_in_sales (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        product_name VARCHAR(500) NOT NULL,
        quantity    INT NOT NULL DEFAULT 1,
        unit_price  INT NOT NULL DEFAULT 0,
        total       INT NOT NULL DEFAULT 0,
        note        VARCHAR(255),
        sold_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    return $pdo;
}

$method = $_SERVER['REQUEST_METHOD'];

// ── POST — record a walk-in sale ─────────────────────────────────────────────
if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    $name  = trim($body['product'] ?? '');
    $qty   = max(1, (int)($body['quantity'] ?? 1));
    $price = max(0, (int)($body['price'] ?? 0));
    $note  = trim($body['note'] ?? '');

    if (!$name || !$price) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing product or price']);
        exit;
    }

    try {
        $pdo = getPdo();

        // Record walk-in sale
        $pdo->prepare(
            'INSERT INTO walk_in_sales (product_name, quantity, unit_price, total, note)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([$name, $qty, $price, $price * $qty, $note ?: null]);

        // Deduct from stock
        $stockStmt = $pdo->prepare('SELECT quantity FROM product_stock WHERE product_name = ?');
        $stockStmt->execute([$name]);
        $row = $stockStmt->fetch(PDO::FETCH_ASSOC);
        if ($row !== false) {
            $before = (int)$row['quantity'];
            $after  = max(0, $before - $qty);
            $pdo->prepare('UPDATE product_stock SET quantity = ? WHERE product_name = ?')
                ->execute([$after, $name]);
            $pdo->prepare(
                'INSERT INTO stock_transactions (product_name, action, quantity, stock_before, stock_after, note)
                 VALUES (?, "sold", ?, ?, ?, ?)'
            )->execute([$name, $qty, $before, $after, 'Walk-in sale' . ($note ? ": $note" : '')]);
        }

        echo json_encode(['success' => true, 'total' => $price * $qty]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// ── GET — return sales stats ─────────────────────────────────────────────────
try {
    $pdo = getPdo();

    // Revenue from completed website orders
    $orderRevenue = function($pdo, $interval) {
        $where = $interval ? "AND completed_at >= NOW() - INTERVAL $interval" : '';
        $stmt = $pdo->query(
            "SELECT COALESCE(SUM(total), 0) as rev, COUNT(*) as cnt
             FROM pending_orders WHERE status = 'completed' $where"
        );
        return $stmt->fetch(PDO::FETCH_ASSOC);
    };

    // Revenue from walk-in sales
    $walkRevenue = function($pdo, $interval) {
        $where = $interval ? "AND sold_at >= NOW() - INTERVAL $interval" : '';
        $stmt = $pdo->query(
            "SELECT COALESCE(SUM(total), 0) as rev, COUNT(*) as cnt
             FROM walk_in_sales $where"
        );
        return $stmt->fetch(PDO::FETCH_ASSOC);
    };

    $periods = ['1 DAY', '7 DAY', '30 DAY', null];
    $labels  = ['today', 'week', 'month', 'all'];
    $stats = [];
    foreach ($periods as $i => $p) {
        $or = $orderRevenue($pdo, $p);
        $wr = $walkRevenue($pdo, $p);
        $stats[$labels[$i]] = [
            'revenue' => (int)$or['rev'] + (int)$wr['rev'],
            'orders'  => (int)$or['cnt'] + (int)$wr['cnt'],
        ];
    }

    // Units sold per product (stock_transactions action=sold)
    $topStmt = $pdo->query(
        "SELECT product_name, SUM(quantity) as units_sold
         FROM stock_transactions WHERE action = 'sold'
         GROUP BY product_name ORDER BY units_sold DESC LIMIT 20"
    );
    $topProducts = $topStmt->fetchAll(PDO::FETCH_ASSOC);

    // Daily revenue last 30 days (orders + walk-ins)
    $dailyOrders = $pdo->query(
        "SELECT DATE(completed_at) as day, SUM(total) as rev
         FROM pending_orders WHERE status='completed' AND completed_at >= NOW() - INTERVAL 30 DAY
         GROUP BY DATE(completed_at)"
    )->fetchAll(PDO::FETCH_ASSOC);

    $dailyWalk = $pdo->query(
        "SELECT DATE(sold_at) as day, SUM(total) as rev
         FROM walk_in_sales WHERE sold_at >= NOW() - INTERVAL 30 DAY
         GROUP BY DATE(sold_at)"
    )->fetchAll(PDO::FETCH_ASSOC);

    // Merge daily revenue
    $daily = [];
    foreach ($dailyOrders as $r) $daily[$r['day']] = ($daily[$r['day']] ?? 0) + (int)$r['rev'];
    foreach ($dailyWalk   as $r) $daily[$r['day']] = ($daily[$r['day']] ?? 0) + (int)$r['rev'];
    ksort($daily);

    // Recent walk-in sales
    $recentWalk = $pdo->query(
        "SELECT * FROM walk_in_sales ORDER BY sold_at DESC LIMIT 20"
    )->fetchAll(PDO::FETCH_ASSOC);

    // Recent completed orders
    $recentOrders = $pdo->query(
        "SELECT order_ref, customer_name, total, payment_method, completed_at
         FROM pending_orders WHERE status='completed'
         ORDER BY completed_at DESC LIMIT 20"
    )->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'stats'         => $stats,
        'top_products'  => $topProducts,
        'daily_revenue' => $daily,
        'recent_walk'   => $recentWalk,
        'recent_orders' => $recentOrders,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
