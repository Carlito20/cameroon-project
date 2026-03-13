<?php
require_once __DIR__ . '/../api/db.php';
session_start();
if (empty($_SESSION['admin_logged_in'])) { header('Location: index.php'); exit; }

$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset  = ($page - 1) * $perPage;

$transactions = [];
$total = 0;

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
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

    $where = [];
    $params = [];
    if ($filter !== 'all') { $where[] = 'action = ?'; $params[] = $filter; }
    if ($search !== '') { $where[] = 'product_name LIKE ?'; $params[] = '%' . $search . '%'; }
    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM stock_transactions $whereSQL");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT * FROM stock_transactions $whereSQL ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $dbError = $e->getMessage();
}

$totalPages = ceil($total / $perPage);

$actionColors = [
    'received' => ['bg'=>'#0d1a0d','color'=>'#6dbf6d','border'=>'#1a3a1a','label'=>'Received'],
    'sold'     => ['bg'=>'#0d1020','color'=>'#7b9fd4','border'=>'#1a2a40','label'=>'Sold'],
    'damaged'  => ['bg'=>'#2a1a0a','color'=>'#d4884a','border'=>'#3a2a1a','label'=>'Damaged'],
    'returned' => ['bg'=>'#1a0d1a','color'=>'#b47bd4','border'=>'#2a1a3a','label'=>'Returned'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Stock History — American Select</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      background: #0a0a0a; color: #e0e0e0;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      min-height: 100vh; -webkit-overflow-scrolling: touch; overflow-x: hidden;
    }
    header {
      background: #111; border-bottom: 1px solid #222;
      padding: 16px 24px;
      padding-top: calc(16px + env(safe-area-inset-top, 0px));
      padding-left: calc(24px + env(safe-area-inset-left, 0px));
      padding-right: calc(24px + env(safe-area-inset-right, 0px));
      display: flex; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 100;
      -webkit-transform: translateZ(0); transform: translateZ(0); will-change: transform;
    }
    header h1 { color: #d4af37; font-size: 18px; font-weight: 700; letter-spacing: 1px; }
    header span { color: #666; font-size: 13px; }
    .back-btn {
      background: transparent; color: #888; border: 1px solid #333; border-radius: 6px;
      padding: 8px 16px; font-size: 13px; font-weight: 600; cursor: pointer;
      text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
      min-height: 44px; touch-action: manipulation; -webkit-user-select: none; user-select: none; -webkit-tap-highlight-color: transparent;
    }
    .back-btn:hover { color: #ccc; border-color: #555; }
    .container {
      max-width: 900px; margin: 0 auto;
      padding: 20px 16px;
      padding-bottom: calc(40px + env(safe-area-inset-bottom, 0px));
      padding-left: calc(16px + env(safe-area-inset-left, 0px));
      padding-right: calc(16px + env(safe-area-inset-right, 0px));
    }
    .toolbar {
      display: flex; align-items: center; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;
    }
    .search-box {
      flex: 1; min-width: 180px; padding: 9px 14px; background: #1a1a1a;
      border: 1px solid #2a2a2a; border-radius: 8px; color: #e0e0e0;
      font-size: 16px; outline: none; -webkit-appearance: none; appearance: none;
      touch-action: manipulation; min-height: 44px;
    }
    .search-box:focus { border-color: #d4af37; }
    .filter-tabs { display: flex; gap: 6px; flex-wrap: wrap; }
    .filter-tab {
      padding: 8px 14px; border-radius: 6px; font-size: 13px; font-weight: 600;
      cursor: pointer; text-decoration: none; border: 1px solid #2a2a2a;
      color: #666; background: transparent; min-height: 44px; display: flex;
      align-items: center; touch-action: manipulation; -webkit-tap-highlight-color: transparent;
      -webkit-user-select: none; user-select: none;
    }
    .filter-tab:hover { color: #ccc; border-color: #555; }
    .filter-tab.active { background: #d4af37; color: #000; border-color: #d4af37; }
    .count-badge { background: #1a1a1a; color: #666; border-radius: 4px; padding: 4px 10px; font-size: 12px; }
    .db-error { background:#2a0a0a;border:1px solid #5c1a1a;color:#ff6b6b;border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:13px; }
    .empty-state { text-align: center; padding: 60px 20px; color: #444; font-size: 15px; }

    .table-scroll { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; overscroll-behavior: contain; }
    table { width: 100%; border-collapse: collapse; font-size: 14px; }
    thead tr { background: #1a1a1a; }
    th { padding: 12px 14px; text-align: left; color: #888; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #222; }
    td { padding: 10px 14px; border-bottom: 1px solid #1a1a1a; vertical-align: middle; }
    tr:hover td { background: #111; }
    .product-col { max-width: 340px; line-height: 1.4; font-size: 13px; }
    .action-badge {
      display: inline-block; padding: 3px 10px; border-radius: 20px;
      font-size: 12px; font-weight: 700; border: 1px solid;
    }
    .qty-cell { font-size: 15px; font-weight: 700; }
    .stock-change { font-size: 12px; color: #555; }
    .note-cell { font-size: 12px; color: #666; font-style: italic; max-width: 150px; }
    .date-cell { font-size: 12px; color: #555; white-space: nowrap; }

    .pagination { display: flex; gap: 8px; justify-content: center; margin-top: 24px; flex-wrap: wrap; }
    .page-btn {
      padding: 8px 14px; border-radius: 6px; border: 1px solid #2a2a2a;
      background: transparent; color: #888; cursor: pointer; font-size: 13px;
      text-decoration: none; min-height: 44px; display: flex; align-items: center;
      touch-action: manipulation; -webkit-tap-highlight-color: transparent;
    }
    .page-btn.active { background: #d4af37; color: #000; border-color: #d4af37; }
    .page-btn:hover:not(.active) { border-color: #555; color: #ccc; }
    @media (max-width: 600px) {
      .product-col { max-width: 160px; }
      th, td { padding: 8px 8px; }
      .note-cell { display: none; }
    }
  </style>
</head>
<body>
<header>
  <div><h1>AMERICAN SELECT</h1><span>Stock History</span></div>
  <a href="dashboard.php" class="back-btn">← Dashboard</a>
</header>

<div class="container">
  <?php if (isset($dbError)): ?>
    <div class="db-error"><strong>Database error:</strong> <?= htmlspecialchars($dbError) ?></div>
  <?php endif; ?>

  <form method="GET" action="">
    <div class="toolbar">
      <input type="text" class="search-box" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search products..." oninput="this.form.submit()">
      <span class="count-badge"><?= $total ?> records</span>
    </div>
    <div class="filter-tabs" style="margin-bottom:20px;">
      <?php
      $tabs = ['all'=>'All','received'=>'📦 Received','sold'=>'✅ Sold','damaged'=>'⚠️ Damaged','returned'=>'↩️ Returned'];
      foreach ($tabs as $val => $label):
        $active = $filter === $val ? 'active' : '';
        $href = '?filter=' . $val . ($search ? '&search=' . urlencode($search) : '');
      ?>
        <a href="<?= $href ?>" class="filter-tab <?= $active ?>"><?= $label ?></a>
      <?php endforeach; ?>
    </div>
  </form>

  <?php if (empty($transactions)): ?>
    <div class="empty-state">No transactions yet<?= $filter !== 'all' ? ' for this filter' : '' ?><?= $search ? ' matching "' . htmlspecialchars($search) . '"' : '' ?>.</div>
  <?php else: ?>
  <div class="table-scroll">
    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Product</th>
          <th>Action</th>
          <th>Qty</th>
          <th>Stock Change</th>
          <th>Note</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($transactions as $tx):
          $ac = $actionColors[$tx['action']] ?? ['bg'=>'#1a1a1a','color'=>'#888','border'=>'#2a2a2a','label'=>$tx['action']];
          $date = date('M j, Y g:i A', strtotime($tx['created_at']));
        ?>
        <tr>
          <td class="date-cell"><?= $date ?></td>
          <td class="product-col"><?= htmlspecialchars($tx['product_name']) ?></td>
          <td>
            <span class="action-badge" style="background:<?=$ac['bg']?>;color:<?=$ac['color']?>;border-color:<?=$ac['border']?>;">
              <?= $ac['label'] ?>
            </span>
          </td>
          <td class="qty-cell" style="color:<?=$ac['color']?>;"><?= $tx['quantity'] ?></td>
          <td class="stock-change"><?= $tx['stock_before'] ?> → <?= $tx['stock_after'] ?></td>
          <td class="note-cell"><?= $tx['note'] ? htmlspecialchars($tx['note']) : '—' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <?php for ($p = 1; $p <= $totalPages; $p++):
        $href = '?filter=' . urlencode($filter) . '&page=' . $p . ($search ? '&search=' . urlencode($search) : '');
      ?>
        <a href="<?= $href ?>" class="page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
  <?php endif; ?>
</div>
</body>
</html>
