<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
session_start();

if (empty($_SESSION['admin_logged_in']) || ($_SESSION['admin_role'] ?? '') !== 'admin') {
    http_response_code(401); echo json_encode(['error' => 'Not authorized']); exit;
}

function getPdo() {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec("CREATE TABLE IF NOT EXISTS walk_in_sales (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        product_name VARCHAR(500) NOT NULL,
        quantity     INT NOT NULL DEFAULT 1,
        unit_price   INT NOT NULL DEFAULT 0,
        total        INT NOT NULL DEFAULT 0,
        note         VARCHAR(255),
        sold_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    return $pdo;
}

// Validate YYYY-MM-DD format to prevent SQL injection via date params
function validDate($s) {
    return $s && preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) ? $s : '';
}

// Get current stock, seeding from products-list.json if not yet in DB (same as orders.php)
function getOrInitStock($pdo, $name) {
    $stmt = $pdo->prepare('SELECT quantity FROM product_stock WHERE product_name = ?');
    $stmt->execute([$name]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row !== false) return (int)$row['quantity'];

    $jsonPath = __DIR__ . '/products-list.json';
    $qty = 0;
    if (file_exists($jsonPath)) {
        foreach (json_decode(file_get_contents($jsonPath), true) ?? [] as $p) {
            if ($p['name'] === $name) { $qty = (int)($p['quantity'] ?? 0); break; }
        }
    }
    $pdo->prepare('INSERT IGNORE INTO product_stock (product_name, quantity) VALUES (?, ?)')
        ->execute([$name, $qty]);
    return $qty;
}

$method = $_SERVER['REQUEST_METHOD'];

// ── DELETE — reverse a walk-in sale ──────────────────────────────────────────
if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'Missing id']); exit; }
    try {
        $pdo = getPdo();
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('SELECT product_name, quantity FROM walk_in_sales WHERE id = ?');
        $stmt->execute([$id]);
        $sale = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$sale) { $pdo->rollBack(); http_response_code(404); echo json_encode(['error' => 'Sale not found']); exit; }

        // Restore stock
        $before = getOrInitStock($pdo, $sale['product_name']);
        $after  = $before + (int)$sale['quantity'];
        $pdo->prepare('UPDATE product_stock SET quantity = ? WHERE product_name = ?')
            ->execute([$after, $sale['product_name']]);
        $pdo->prepare('INSERT INTO stock_transactions (product_name,action,quantity,stock_before,stock_after,note) VALUES (?,"returned",?,?,?,"Walk-in sale reversed")')
            ->execute([$sale['product_name'], $sale['quantity'], $before, $after]);

        $pdo->prepare('DELETE FROM walk_in_sales WHERE id = ?')->execute([$id]);
        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        if (isset($pdo)) $pdo->rollBack();
        http_response_code(500); echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// ── POST — record a walk-in sale ─────────────────────────────────────────────
if ($method === 'POST') {
    $body  = json_decode(file_get_contents('php://input'), true);
    $name  = trim($body['product']  ?? '');
    $qty   = max(1, (int)($body['quantity'] ?? 1));
    $price = max(0, (int)($body['price']    ?? 0));
    $note  = trim($body['note'] ?? '');

    if (!$name || !$price) { http_response_code(400); echo json_encode(['error' => 'Missing product or price']); exit; }

    try {
        $pdo = getPdo();
        $pdo->beginTransaction();

        // Record sale
        $pdo->prepare('INSERT INTO walk_in_sales (product_name,quantity,unit_price,total,note) VALUES (?,?,?,?,?)')
            ->execute([$name, $qty, $price, $price * $qty, $note ?: null]);
        $saleId = (int)$pdo->lastInsertId();

        // Deduct stock (initialising from JSON if first time)
        $before = getOrInitStock($pdo, $name);
        $after  = max(0, $before - $qty);
        $pdo->prepare('UPDATE product_stock SET quantity = ? WHERE product_name = ?')->execute([$after, $name]);
        $pdo->prepare('INSERT INTO stock_transactions (product_name,action,quantity,stock_before,stock_after,note) VALUES (?,"sold",?,?,?,?)')
            ->execute([$name, $qty, $before, $after, 'Walk-in sale' . ($note ? ": $note" : '')]);

        $pdo->commit();
        echo json_encode(['success' => true, 'id' => $saleId, 'total' => $price * $qty]);
    } catch (Exception $e) {
        if (isset($pdo)) $pdo->rollBack();
        http_response_code(500); echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// ── GET — sales stats with validated, safe filtering ─────────────────────────
$from    = validDate($_GET['from']    ?? '');
$to      = validDate($_GET['to']      ?? '');
$search  = trim($_GET['search']       ?? '');
$sortBy  = in_array($_GET['sort'] ?? '', ['units','revenue','name']) ? $_GET['sort'] : 'units';
$type    = in_array($_GET['type'] ?? '', ['all','walkin','orders']) ? $_GET['type'] : 'all';
$page    = max(1, (int)($_GET['page']     ?? 1));
$perPage = max(5, min(100, (int)($_GET['per_page'] ?? 25)));
$offset  = ($page - 1) * $perPage;

// Whitelist sort column to prevent SQL injection
$topSortCol = $sortBy === 'revenue' ? 'revenue' : ($sortBy === 'name' ? 'st.product_name' : 'units_sold');

try {
    $pdo = getPdo();

    // ── Summary cards (fixed periods, ignore date range) ─────────────────────
    $periods = ['1 DAY','7 DAY','30 DAY',null];
    $labels  = ['today','week','month','all'];
    $stats   = [];
    foreach ($periods as $i => $p) {
        $iw = $p ? "AND sold_at >= NOW() - INTERVAL $p" : '';
        $io = $p ? "AND completed_at >= NOW() - INTERVAL $p" : '';
        $rw = $pdo->query("SELECT COALESCE(SUM(total),0) rev, COUNT(*) cnt FROM walk_in_sales WHERE 1 $iw")->fetch(PDO::FETCH_ASSOC);
        $ro = $pdo->query("SELECT COALESCE(SUM(total),0) rev, COUNT(*) cnt FROM pending_orders WHERE status='completed' $io")->fetch(PDO::FETCH_ASSOC);
        $stats[$labels[$i]] = ['revenue'=>(int)$rw['rev']+(int)$ro['rev'], 'orders'=>(int)$rw['cnt']+(int)$ro['cnt']];
    }

    // ── Top selling products (prepared statements for LIKE) ───────────────────
    $topParams = [];
    $topWhere  = "WHERE st.action = 'sold'";
    if ($search) { $topWhere .= ' AND st.product_name LIKE ?'; $topParams[] = '%'.$search.'%'; }
    if ($from)   { $topWhere .= ' AND DATE(st.created_at) >= ?'; $topParams[] = $from; }
    if ($to)     { $topWhere .= ' AND DATE(st.created_at) <= ?'; $topParams[] = $to; }

    $topStmt = $pdo->prepare(
        "SELECT st.product_name, SUM(st.quantity) AS units_sold, COALESCE(SUM(ws.total),0) AS revenue
         FROM stock_transactions st
         LEFT JOIN walk_in_sales ws ON ws.product_name = st.product_name
         $topWhere
         GROUP BY st.product_name
         ORDER BY $topSortCol DESC
         LIMIT 50"
    );
    $topStmt->execute($topParams);
    $topProducts = $topStmt->fetchAll(PDO::FETCH_ASSOC);

    // ── Daily chart (prepared statements for date range) ──────────────────────
    $chartFromW = $from ?: null; $chartToW = $to ?: null;
    $chartFromO = $from ?: null; $chartToO = $to ?: null;
    $defaultInterval = (!$from && !$to);

    $wSql = "SELECT DATE(sold_at) day, SUM(total) rev FROM walk_in_sales WHERE 1";
    $wParams = [];
    if ($defaultInterval) { $wSql .= " AND sold_at >= NOW() - INTERVAL 30 DAY"; }
    else { if ($from) { $wSql .= ' AND DATE(sold_at) >= ?'; $wParams[] = $from; } if ($to) { $wSql .= ' AND DATE(sold_at) <= ?'; $wParams[] = $to; } }
    $wSql .= ' GROUP BY DATE(sold_at)';

    $oSql = "SELECT DATE(completed_at) day, SUM(total) rev FROM pending_orders WHERE status='completed'";
    $oParams = [];
    if ($defaultInterval) { $oSql .= " AND completed_at >= NOW() - INTERVAL 30 DAY"; }
    else { if ($from) { $oSql .= ' AND DATE(completed_at) >= ?'; $oParams[] = $from; } if ($to) { $oSql .= ' AND DATE(completed_at) <= ?'; $oParams[] = $to; } }
    $oSql .= ' GROUP BY DATE(completed_at)';

    $dW = $pdo->prepare($wSql); $dW->execute($wParams); $dW = $dW->fetchAll(PDO::FETCH_ASSOC);
    $dO = $pdo->prepare($oSql); $dO->execute($oParams); $dO = $dO->fetchAll(PDO::FETCH_ASSOC);
    $daily = [];
    foreach ($dW as $r) $daily[$r['day']] = ($daily[$r['day']] ?? 0) + (int)$r['rev'];
    foreach ($dO as $r) $daily[$r['day']] = ($daily[$r['day']] ?? 0) + (int)$r['rev'];
    ksort($daily);

    // ── Walk-in sales (prepared, paginated) ───────────────────────────────────
    $totalWalk = 0; $walkRows = [];
    if ($type !== 'orders') {
        $wWhere = []; $wParams2 = [];
        if ($search) { $wWhere[] = 'product_name LIKE ?'; $wParams2[] = '%'.$search.'%'; }
        if ($from)   { $wWhere[] = 'DATE(sold_at) >= ?';  $wParams2[] = $from; }
        if ($to)     { $wWhere[] = 'DATE(sold_at) <= ?';  $wParams2[] = $to; }
        $wSQL = $wWhere ? 'WHERE '.implode(' AND ', $wWhere) : '';

        $totalWalk = (int)$pdo->prepare("SELECT COUNT(*) FROM walk_in_sales $wSQL")->execute($wParams2) ?
            $pdo->prepare("SELECT COUNT(*) FROM walk_in_sales $wSQL") : 0;
        $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM walk_in_sales $wSQL");
        $cntStmt->execute($wParams2); $totalWalk = (int)$cntStmt->fetchColumn();

        $rowStmt = $pdo->prepare("SELECT * FROM walk_in_sales $wSQL ORDER BY sold_at DESC LIMIT $perPage OFFSET $offset");
        $rowStmt->execute($wParams2);
        $walkRows = $rowStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Website orders (prepared, paginated) ──────────────────────────────────
    $totalOrders = 0; $orderRows = [];
    if ($type !== 'walkin') {
        $oWhere = ["status='completed'"]; $oParams2 = [];
        if ($search) { $oWhere[] = '(customer_name LIKE ? OR order_ref LIKE ? OR items LIKE ?)'; $oParams2[] = '%'.$search.'%'; $oParams2[] = '%'.$search.'%'; $oParams2[] = '%'.$search.'%'; }
        if ($from)   { $oWhere[] = 'DATE(completed_at) >= ?'; $oParams2[] = $from; }
        if ($to)     { $oWhere[] = 'DATE(completed_at) <= ?'; $oParams2[] = $to; }
        $oSQL = 'WHERE '.implode(' AND ', $oWhere);

        $cntStmt2 = $pdo->prepare("SELECT COUNT(*) FROM pending_orders $oSQL");
        $cntStmt2->execute($oParams2); $totalOrders = (int)$cntStmt2->fetchColumn();

        $rowStmt2 = $pdo->prepare("SELECT order_ref,customer_name,total,payment_method,items,completed_at FROM pending_orders $oSQL ORDER BY completed_at DESC LIMIT $perPage OFFSET $offset");
        $rowStmt2->execute($oParams2);
        $orderRows = $rowStmt2->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'stats'         => $stats,
        'top_products'  => $topProducts,
        'daily_revenue' => $daily,
        'walk_in'       => ['rows' => $walkRows,  'total' => $totalWalk,   'page' => $page, 'per_page' => $perPage],
        'orders'        => ['rows' => $orderRows, 'total' => $totalOrders, 'page' => $page, 'per_page' => $perPage],
    ]);
} catch (Exception $e) { http_response_code(500); echo json_encode(['error' => $e->getMessage()]); }
