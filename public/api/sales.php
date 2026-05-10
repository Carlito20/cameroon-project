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

$method = $_SERVER['REQUEST_METHOD'];

// ── POST — record a walk-in sale ─────────────────────────────────────────────
if ($method === 'POST') {
    $body  = json_decode(file_get_contents('php://input'), true);
    $name  = trim($body['product']  ?? '');
    $qty   = max(1, (int)($body['quantity'] ?? 1));
    $price = max(0, (int)($body['price']    ?? 0));
    $note  = trim($body['note'] ?? '');

    if (!$name || !$price) { http_response_code(400); echo json_encode(['error'=>'Missing product or price']); exit; }

    try {
        $pdo = getPdo();
        $pdo->prepare('INSERT INTO walk_in_sales (product_name,quantity,unit_price,total,note) VALUES (?,?,?,?,?)')
            ->execute([$name, $qty, $price, $price*$qty, $note ?: null]);

        $stockStmt = $pdo->prepare('SELECT quantity FROM product_stock WHERE product_name = ?');
        $stockStmt->execute([$name]);
        $row = $stockStmt->fetch(PDO::FETCH_ASSOC);
        if ($row !== false) {
            $before = (int)$row['quantity'];
            $after  = max(0, $before - $qty);
            $pdo->prepare('UPDATE product_stock SET quantity=? WHERE product_name=?')->execute([$after, $name]);
            $pdo->prepare('INSERT INTO stock_transactions (product_name,action,quantity,stock_before,stock_after,note) VALUES (?,"sold",?,?,?,?)')
                ->execute([$name, $qty, $before, $after, 'Walk-in sale'.($note?": $note":'')]);
        }
        echo json_encode(['success'=>true,'total'=>$price*$qty]);
    } catch (Exception $e) { http_response_code(500); echo json_encode(['error'=>$e->getMessage()]); }
    exit;
}

// ── GET — sales stats with optional filters ───────────────────────────────────
$from    = $_GET['from']    ?? '';   // YYYY-MM-DD
$to      = $_GET['to']      ?? '';   // YYYY-MM-DD
$search  = trim($_GET['search']  ?? '');
$sortBy  = $_GET['sort']    ?? 'units'; // 'units' | 'revenue'
$type    = $_GET['type']    ?? 'all';   // 'all' | 'walkin' | 'orders'
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = max(5, min(100, (int)($_GET['per_page'] ?? 25)));
$offset  = ($page - 1) * $perPage;

// Build date clauses
$fromClauseO  = $from ? "AND DATE(completed_at) >= '$from'" : '';
$toClauseO    = $to   ? "AND DATE(completed_at) <= '$to'"   : '';
$fromClauseW  = $from ? "AND DATE(sold_at) >= '$from'" : '';
$toClauseW    = $to   ? "AND DATE(sold_at) <= '$to'"   : '';
$fromClauseT  = $from ? "AND DATE(created_at) >= '$from'" : '';
$toClauseT    = $to   ? "AND DATE(created_at) <= '$to'"   : '';

try {
    $pdo = getPdo();

    // ── Summary cards (fixed periods, ignore custom date range) ──────────────
    $periods = ['1 DAY','7 DAY','30 DAY', null];
    $labels  = ['today','week','month','all'];
    $stats   = [];
    foreach ($periods as $i => $p) {
        $iw = $p ? "AND sold_at >= NOW() - INTERVAL $p" : '';
        $io = $p ? "AND completed_at >= NOW() - INTERVAL $p" : '';
        $rw = $pdo->query("SELECT COALESCE(SUM(total),0) rev, COUNT(*) cnt FROM walk_in_sales WHERE 1 $iw")->fetch(PDO::FETCH_ASSOC);
        $ro = $pdo->query("SELECT COALESCE(SUM(total),0) rev, COUNT(*) cnt FROM pending_orders WHERE status='completed' $io")->fetch(PDO::FETCH_ASSOC);
        $stats[$labels[$i]] = ['revenue'=>(int)$rw['rev']+(int)$ro['rev'], 'orders'=>(int)$rw['cnt']+(int)$ro['cnt']];
    }

    // ── Top selling products (filterable) ────────────────────────────────────
    $searchClauseT = $search ? "AND product_name LIKE '%".addslashes($search)."%'" : '';
    $sortCol = $sortBy === 'revenue' ? 'revenue' : 'units_sold';

    // Merge stock_transactions + walk_in_sales revenue
    $topStmt = $pdo->query(
        "SELECT st.product_name,
                SUM(st.quantity) AS units_sold,
                COALESCE(SUM(ws.total),0) AS revenue
         FROM stock_transactions st
         LEFT JOIN walk_in_sales ws ON ws.product_name = st.product_name
         WHERE st.action = 'sold' $searchClauseT $fromClauseT $toClauseT
         GROUP BY st.product_name
         ORDER BY $sortCol DESC
         LIMIT 50"
    );
    $topProducts = $topStmt->fetchAll(PDO::FETCH_ASSOC);

    // ── Daily chart (respects custom date range) ──────────────────────────────
    $chartDays = (!$from && !$to) ? "AND sold_at >= NOW() - INTERVAL 30 DAY" : ($fromClauseW . ' ' . $toClauseW);
    $chartDaysO = (!$from && !$to) ? "AND completed_at >= NOW() - INTERVAL 30 DAY" : ($fromClauseO . ' ' . $toClauseO);

    $dW = $pdo->query("SELECT DATE(sold_at) day, SUM(total) rev FROM walk_in_sales WHERE 1 $chartDays GROUP BY DATE(sold_at)")->fetchAll(PDO::FETCH_ASSOC);
    $dO = $pdo->query("SELECT DATE(completed_at) day, SUM(total) rev FROM pending_orders WHERE status='completed' $chartDaysO GROUP BY DATE(completed_at)")->fetchAll(PDO::FETCH_ASSOC);
    $daily = [];
    foreach ($dW as $r) $daily[$r['day']] = ($daily[$r['day']] ?? 0) + (int)$r['rev'];
    foreach ($dO as $r) $daily[$r['day']] = ($daily[$r['day']] ?? 0) + (int)$r['rev'];
    ksort($daily);

    // ── Recent walk-in sales (paginated, searchable) ──────────────────────────
    $searchClauseW = $search ? "AND product_name LIKE '%".addslashes($search)."%'" : '';
    $totalWalk = 0; $walkRows = [];
    if ($type !== 'orders') {
        $totalWalk = (int)$pdo->query("SELECT COUNT(*) FROM walk_in_sales WHERE 1 $searchClauseW $fromClauseW $toClauseW")->fetchColumn();
        $walkRows  = $pdo->query("SELECT * FROM walk_in_sales WHERE 1 $searchClauseW $fromClauseW $toClauseW ORDER BY sold_at DESC LIMIT $perPage OFFSET $offset")->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Recent website orders (paginated, searchable) ─────────────────────────
    $searchClauseO = $search ? "AND (customer_name LIKE '%".addslashes($search)."%' OR order_ref LIKE '%".addslashes($search)."%' OR items LIKE '%".addslashes($search)."%')" : '';
    $totalOrders = 0; $orderRows = [];
    if ($type !== 'walkin') {
        $totalOrders = (int)$pdo->query("SELECT COUNT(*) FROM pending_orders WHERE status='completed' $searchClauseO $fromClauseO $toClauseO")->fetchColumn();
        $orderRows   = $pdo->query("SELECT order_ref,customer_name,total,payment_method,items,completed_at FROM pending_orders WHERE status='completed' $searchClauseO $fromClauseO $toClauseO ORDER BY completed_at DESC LIMIT $perPage OFFSET $offset")->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'stats'         => $stats,
        'top_products'  => $topProducts,
        'daily_revenue' => $daily,
        'walk_in'       => ['rows' => $walkRows,  'total' => $totalWalk,   'page' => $page, 'per_page' => $perPage],
        'orders'        => ['rows' => $orderRows, 'total' => $totalOrders, 'page' => $page, 'per_page' => $perPage],
        'filters'       => compact('from','to','search','sortBy','type','page','perPage'),
    ]);
} catch (Exception $e) { http_response_code(500); echo json_encode(['error'=>$e->getMessage()]); }
