<?php
require_once __DIR__ . '/../api/db.php';
session_start();
if (empty($_SESSION['admin_logged_in'])) { header('Location: index.php'); exit; }

$filter  = $_GET['filter']  ?? 'all';
$search  = trim($_GET['search'] ?? '');
$days    = (int)($_GET['days'] ?? 0);
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset  = ($page - 1) * $perPage;

$orders = [];
$total  = 0;

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $validStatuses = ['completed','cancelled','damaged','returned'];
    $where  = ["status IN ('completed','cancelled','damaged','returned')"];
    $params = [];

    if (in_array($filter, $validStatuses)) {
        $where  = ['status = ?'];
        $params = [$filter];
    }
    if ($search !== '') {
        $where[] = '(customer_name LIKE ? OR customer_phone LIKE ? OR order_ref LIKE ?)';
        $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
    }
    if ($days > 0) {
        $where[] = 'created_at >= NOW() - INTERVAL ? DAY';
        $params[] = $days;
    }
    $whereSQL = 'WHERE ' . implode(' AND ', $where);

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM pending_orders $whereSQL");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT * FROM pending_orders $whereSQL ORDER BY COALESCE(completed_at, created_at) DESC LIMIT $perPage OFFSET $offset");
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $dbError = $e->getMessage();
}

$totalPages = max(1, ceil($total / $perPage));

function buildWaReceiptLink(array $o): string {
    $phone = preg_replace('/\D/', '', $o['customer_phone'] ?? '');
    if (!str_starts_with($phone, '237') && strlen($phone) === 9) $phone = '237' . $phone;
    if (!str_starts_with($phone, '1')   && strlen($phone) === 10) $phone = '1'   . $phone;

    $items = json_decode($o['items'] ?? '[]', true) ?: [];
    $total = 0; $lines = '';
    foreach ($items as $i) {
        $line   = ($i['price'] ?? 0) * ($i['quantity'] ?? 1);
        $total += $line;
        $lines .= '- ' . ($i['name'] ?? '') . ' x' . ($i['quantity'] ?? 1) . ($i['price'] ? ' - ' . number_format($line) . ' FCFA' : '') . "\n";
    }
    $name   = $o['customer_name'] ?: 'there';
    $txLine = !empty($o['payment_ref']) ? "\nTransaction ID: " . $o['payment_ref'] : '';
    $msg =
        "*Payment Confirmed - American Select*\n" .
        "Hi {$name}! Your payment has been confirmed.\n" .
        "Bonjour {$name} ! Votre paiement a ete confirme.\n\n" .
        "Order Ref / Ref Commande: *{$o['order_ref']}*\n\n" .
        $lines .
        "\n*Total: " . number_format((float)($o['total'] ?: $total)) . " FCFA*\n" .
        "Paid via / Payé via : " . ($o['payment_method'] ?? 'N/A') . $txLine . "\n\n" .
        "Thank you for shopping with American Select!\n" .
        "Merci de votre visite chez American Select !\n" .
        "Questions? Call/WhatsApp / Appelez/WhatsApp :\nMTN: 679 457 181 | Orange: 686 271 567";
    return 'https://wa.me/' . $phone . '?text=' . rawurlencode($msg);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Order History — American Select</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      background: #0a0a0a; color: #e0e0e0;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      min-height: 100vh; min-height: -webkit-fill-available;
      -webkit-overflow-scrolling: touch; overflow-x: hidden;
    }
    header {
      background: #111; border-bottom: 1px solid #222;
      padding: 14px 20px;
      padding-top: calc(14px + env(safe-area-inset-top, 0px));
      padding-left: calc(20px + env(safe-area-inset-left, 0px));
      padding-right: calc(20px + env(safe-area-inset-right, 0px));
      display: flex; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 100;
      -webkit-transform: translateZ(0); transform: translateZ(0); will-change: transform;
    }
    header h1 { color: #d4af37; font-size: 17px; font-weight: 800; letter-spacing: 1px; }
    header span { color: #555; font-size: 12px; }
    .back-btn {
      background: transparent; color: #888; border: 1px solid #333; border-radius: 6px;
      padding: 7px 13px; font-size: 13px; font-weight: 600; cursor: pointer;
      text-decoration: none; display: inline-flex; align-items: center; gap: 5px;
      min-height: 44px; touch-action: manipulation; -webkit-user-select: none; user-select: none;
      -webkit-tap-highlight-color: transparent;
    }
    .back-btn:hover { color: #ccc; border-color: #555; }
    .container {
      max-width: 780px; margin: 0 auto; padding: 16px;
      padding-bottom: calc(40px + env(safe-area-inset-bottom, 0px));
      padding-left: calc(16px + env(safe-area-inset-left, 0px));
      padding-right: calc(16px + env(safe-area-inset-right, 0px));
    }

    /* Toolbar */
    .toolbar { display: flex; gap: 8px; margin-bottom: 14px; flex-wrap: wrap; align-items: center; }
    .search-input {
      flex: 1; min-width: 180px; padding: 10px 14px; background: #1a1a1a;
      border: 1px solid #2a2a2a; border-radius: 8px; color: #e0e0e0;
      font-size: 16px; outline: none; -webkit-appearance: none; appearance: none;
      min-height: 44px; touch-action: manipulation;
    }
    .search-input:focus { border-color: #d4af37; }
    .count-badge { background: #1a1a1a; color: #666; border-radius: 6px; padding: 6px 12px; font-size: 12px; white-space: nowrap; }

    /* Tabs */
    .filter-tabs { display: flex; gap: 6px; margin-bottom: 12px; flex-wrap: wrap; }
    .date-tabs   { display: flex; gap: 6px; margin-bottom: 20px; flex-wrap: wrap; }
    .tab {
      padding: 8px 14px; border-radius: 6px; font-size: 13px; font-weight: 600;
      cursor: pointer; text-decoration: none; border: 1px solid #2a2a2a; color: #666;
      background: transparent; min-height: 44px; display: flex; align-items: center;
      touch-action: manipulation; -webkit-tap-highlight-color: transparent;
      -webkit-user-select: none; user-select: none;
    }
    .tab:hover:not([class*="tab-"]) { color: #ccc; border-color: #555; }
    .tab:active { opacity: 0.75; }
    .tab-all       { background: #d4af37; color: #000; border-color: #d4af37; }
    .tab-completed { background: #0d1a0d; color: #6dbf6d; border-color: #1a3a1a; }
    .tab-cancelled { background: #1a0d0d; color: #e05c5c; border-color: #3a1a1a; }
    .tab-damaged   { background: #2a1a0a; color: #d4884a; border-color: #3a2a1a; }
    .tab-returned  { background: #1a0d1a; color: #b47bd4; border-color: #2a1a3a; }
    .tab-days      { background: #1a2a3a; color: #7b9fd4; border-color: #2a4a6a; }

    /* Cards */
    .empty-state { text-align: center; padding: 60px 20px; color: #333; font-size: 15px; }
    .empty-icon  { font-size: 48px; margin-bottom: 12px; opacity: 0.3; }
    .order-card  {
      background: #111; border: 1px solid #1e1e1e; border-radius: 12px;
      margin-bottom: 14px; overflow: hidden;
    }
    .order-card.completed { border-left: 3px solid #6dbf6d; }
    .order-card.cancelled { border-left: 3px solid #555; opacity: 0.75; }
    .order-card.damaged   { border-left: 3px solid #d4884a; opacity: 0.85; }
    .order-card.returned  { border-left: 3px solid #b47bd4; }
    .order-head {
      padding: 14px 16px 10px;
      display: flex; align-items: flex-start; justify-content: space-between; gap: 10px;
    }
    .order-ref      { font-size: 13px; font-weight: 800; color: #d4af37; letter-spacing: 0.5px; }
    .order-customer { font-size: 14px; font-weight: 700; color: #e0e0e0; margin-top: 4px; }
    .order-phone    { font-size: 13px; color: #7b9fd4; margin-top: 2px; }
    .order-time     { font-size: 12px; color: #888; margin-top: 3px; }
    .order-pay      { font-size: 12px; color: #888; margin-top: 3px; }
    .order-txref    { font-size: 12px; color: #a98fd4; margin-top: 3px; }
    .status-badge {
      font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px;
      border: 1px solid; white-space: nowrap; flex-shrink: 0;
    }
    .status-badge.completed { background:#0d1a0d; color:#6dbf6d; border-color:#1a3a1a; }
    .status-badge.cancelled { background:#1a1a1a; color:#555;    border-color:#2a2a2a; }
    .status-badge.damaged   { background:#2a1a0a; color:#d4884a; border-color:#3a2a1a; }
    .status-badge.returned  { background:#1a0d1a; color:#b47bd4; border-color:#2a1a3a; }

    .order-items { padding: 0 16px 10px; }
    .order-item {
      display: flex; justify-content: space-between; align-items: center;
      padding: 6px 0; border-bottom: 1px solid #161616; gap: 10px;
    }
    .order-item:last-child { border-bottom: none; }
    .oi-name  { font-size: 13px; color: #ccc; flex: 1; line-height: 1.4; }
    .oi-qty   { font-size: 12px; color: #555; white-space: nowrap; }
    .oi-price { font-size: 13px; color: #d4af37; font-weight: 600; white-space: nowrap; }
    .order-total {
      display: flex; justify-content: space-between; align-items: center;
      padding: 10px 16px; background: #0d0d0d; border-top: 1px solid #1a1a1a;
    }
    .ot-label  { font-size: 12px; color: #555; text-transform: uppercase; letter-spacing: 0.5px; }
    .ot-amount { font-size: 18px; font-weight: 800; color: #d4af37; }
    .order-note { padding: 6px 16px 10px; font-size: 12px; color: #555; font-style: italic; }
    .order-actions {
      display: flex; gap: 8px; padding: 12px 16px;
      border-top: 1px solid #1a1a1a; flex-wrap: wrap;
    }
    .btn-print {
      flex: 1; padding: 10px 12px; background: #1a1520; color: #a98fd4;
      border: 1px solid #2a1a40; border-radius: 8px; font-size: 13px; font-weight: 700;
      cursor: pointer; min-height: 44px; touch-action: manipulation;
      -webkit-user-select: none; user-select: none; -webkit-tap-highlight-color: transparent;
    }
    .btn-print:hover { background: #221a2e; }
    .btn-wa {
      flex: 1; padding: 10px 12px; background: #0d2010; color: #25d366;
      border: 1px solid #1a4a20; border-radius: 8px; font-size: 13px; font-weight: 700;
      cursor: pointer; min-height: 44px; touch-action: manipulation;
      -webkit-user-select: none; user-select: none; -webkit-tap-highlight-color: transparent;
      text-decoration: none; display: flex; align-items: center; justify-content: center;
    }
    .btn-wa:hover { background: #112a18; }
    .btn-damaged {
      flex: 1; padding: 10px 12px; background: #1a0f00; color: #d4884a;
      border: 1px solid #3a2a1a; border-radius: 8px; font-size: 13px; font-weight: 700;
      cursor: pointer; min-height: 44px; touch-action: manipulation;
      -webkit-user-select: none; user-select: none; -webkit-tap-highlight-color: transparent;
    }
    .btn-damaged:hover { background: #261500; }
    .btn-returned {
      flex: 1; padding: 10px 12px; background: #100a18; color: #b47bd4;
      border: 1px solid #2a1a3a; border-radius: 8px; font-size: 13px; font-weight: 700;
      cursor: pointer; min-height: 44px; touch-action: manipulation;
      -webkit-user-select: none; user-select: none; -webkit-tap-highlight-color: transparent;
    }
    .btn-returned:hover { background: #18102a; }

    /* Status-change modal */
    .modal-overlay {
      position: fixed; inset: 0; background: rgba(0,0,0,0.85);
      -webkit-backdrop-filter: blur(4px); backdrop-filter: blur(4px);
      -webkit-transform: translateZ(0); transform: translateZ(0); will-change: transform;
      z-index: 200; display: none; align-items: center; justify-content: center;
      padding: 20px;
      padding-top: calc(20px + env(safe-area-inset-top, 0px));
      padding-bottom: calc(20px + env(safe-area-inset-bottom, 0px));
    }
    .modal-overlay.open { display: flex; }
    .modal-box {
      background: #111; border: 1px solid #2a2a2a; border-radius: 14px;
      padding: 24px 20px; max-width: 400px; width: 100%;
    }
    .modal-box h3 { font-size: 16px; color: #e0e0e0; margin-bottom: 10px; }
    .modal-box p  { font-size: 13px; color: #666; margin-bottom: 16px; line-height: 1.5; }
    .modal-note {
      width: 100%; padding: 10px 14px; background: #1a1a1a;
      border: 1px solid #2a2a2a; border-radius: 8px; color: #e0e0e0;
      font-size: 14px; outline: none; -webkit-appearance: none; appearance: none;
      min-height: 44px; touch-action: manipulation; margin-bottom: 14px;
    }
    .modal-note:focus { border-color: #555; }
    .modal-btns { display: flex; gap: 10px; }
    .modal-btn-confirm {
      flex: 1; padding: 11px; border-radius: 8px; font-size: 14px; font-weight: 700;
      cursor: pointer; min-height: 44px; touch-action: manipulation;
      -webkit-user-select: none; user-select: none; -webkit-tap-highlight-color: transparent;
      border: 1px solid;
    }
    .modal-btn-confirm.damaged  { background:#2a1a0a; color:#d4884a; border-color:#3a2a1a; }
    .modal-btn-confirm.returned { background:#1a0d1a; color:#b47bd4; border-color:#2a1a3a; }
    .modal-btn-back {
      flex: 1; padding: 11px; background: transparent; color: #888;
      border: 1px solid #2a2a2a; border-radius: 8px; font-size: 14px; font-weight: 600;
      cursor: pointer; min-height: 44px; touch-action: manipulation;
      -webkit-user-select: none; user-select: none; -webkit-tap-highlight-color: transparent;
    }

    /* Pagination */
    .pagination { display: flex; gap: 8px; justify-content: center; margin-top: 24px; flex-wrap: wrap; }
    .page-btn {
      padding: 8px 14px; border-radius: 6px; border: 1px solid #2a2a2a;
      background: transparent; color: #888; cursor: pointer; font-size: 13px;
      text-decoration: none; min-height: 44px; display: flex; align-items: center;
      touch-action: manipulation; -webkit-tap-highlight-color: transparent;
    }
    .page-btn.active { background: #d4af37; color: #000; border-color: #d4af37; }
    .page-btn:hover:not(.active) { border-color: #555; color: #ccc; }

    /* Toast */
    .toast {
      position: fixed; bottom: calc(24px + env(safe-area-inset-bottom, 0px)); left: 50%;
      transform: translateX(-50%) translateY(20px); background: #222; color: #e0e0e0;
      padding: 10px 20px; border-radius: 8px; font-size: 14px; opacity: 0;
      transition: opacity 0.2s, transform 0.2s; pointer-events: none; z-index: 500;
      border: 1px solid #333; white-space: nowrap;
    }
    .toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }

    @media (max-width: 480px) {
      .filter-tabs, .date-tabs { gap: 4px; }
      .tab { font-size: 12px; padding: 7px 10px; }
      table { font-size: 13px; }
      th, td { padding: 8px 8px; }
      .btn-print, .btn-wa, .btn-damaged, .btn-returned { font-size: 12px; padding: 8px 10px; }
    }
    @media print {
      body > *:not(#print-area) { display: none !important; }
      #print-area { display: block !important; padding: 10mm 15mm; color: #000; background: #fff; }
      @page { size: A5; margin: 10mm; }
    }
    .db-error { background:#2a0a0a; border:1px solid #5c1a1a; color:#ff6b6b; border-radius:8px; padding:12px 16px; margin-bottom:20px; font-size:13px; }
  </style>
</head>
<body>
<header>
  <div><h1>AMERICAN SELECT</h1><span>Order History</span></div>
  <a href="dashboard.php" class="back-btn">← Dashboard</a>
</header>

<div class="container">
  <?php if (isset($dbError)): ?>
    <div class="db-error"><strong>Database error:</strong> <?= htmlspecialchars($dbError) ?></div>
  <?php endif; ?>

  <form method="GET" action="">
    <div class="toolbar">
      <input type="text" class="search-input" name="search" value="<?= htmlspecialchars($search) ?>"
        placeholder="Search name, phone, order ref…" oninput="this.form.submit()" autocomplete="off">
      <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
      <input type="hidden" name="days"   value="<?= $days ?>">
      <span class="count-badge"><?= $total ?> order<?= $total !== 1 ? 's' : '' ?></span>
    </div>
  </form>

  <!-- Status filters -->
  <div class="filter-tabs">
    <?php
    $filterTabs = [
      'all'       => 'All',
      'completed' => '✅ Completed',
      'cancelled' => '✗ Cancelled',
      'damaged'   => '⚠️ Damaged',
      'returned'  => '↩️ Returned',
    ];
    foreach ($filterTabs as $val => $label):
      $cls  = $filter === $val ? 'tab-' . $val : '';
      $href = '?' . http_build_query(['filter' => $val, 'days' => $days, 'search' => $search]);
    ?>
      <a href="<?= $href ?>" class="tab <?= $cls ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </div>

  <!-- Date range -->
  <div class="date-tabs">
    <?php
    $dateRanges = [0 => 'All Time', 7 => 'Last 7 Days', 30 => 'Last 30 Days', 90 => 'Last 90 Days'];
    foreach ($dateRanges as $d => $label):
      $cls  = $days === $d ? 'tab-days' : '';
      $href = '?' . http_build_query(['filter' => $filter, 'days' => $d, 'search' => $search]);
    ?>
      <a href="<?= $href ?>" class="tab <?= $cls ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </div>

  <?php if (empty($orders)): ?>
    <div class="empty-state">
      <div class="empty-icon">📋</div>
      No <?= $filter !== 'all' ? htmlspecialchars($filter) : '' ?> orders found<?= $search ? ' matching "' . htmlspecialchars($search) . '"' : '' ?>.
    </div>
  <?php else: ?>

    <?php foreach ($orders as $o):
      $items    = json_decode($o['items'] ?? '[]', true) ?: [];
      $payIcon  = str_contains($o['payment_method'] ?? '', 'MTN') ? '🟡' : (str_contains($o['payment_method'] ?? '', 'Orange') ? '🟠' : '💵');
      $dateStr  = $o['completed_at'] ?? $o['created_at'] ?? '';
      $d        = new DateTime($dateStr);
      $dateLabel = $d->format('m/d/Y') . ' · ' . $d->format('g:i A');
    ?>
    <div class="order-card <?= htmlspecialchars($o['status']) ?>">
      <div class="order-head">
        <div>
          <div class="order-ref">
            <?= htmlspecialchars($o['order_ref']) ?>
            <?php if (str_starts_with($o['order_ref'], 'POS-')): ?>
              <span style="margin-left:6px;font-size:10px;font-weight:700;background:#1a2e1a;color:#6dbf6d;border:1px solid #2a4a2a;border-radius:4px;padding:1px 5px;vertical-align:middle;">IN-STORE</span>
            <?php else: ?>
              <span style="margin-left:6px;font-size:10px;font-weight:700;background:#0d1a2e;color:#7b9fd4;border:1px solid #1a3050;border-radius:4px;padding:1px 5px;vertical-align:middle;">ONLINE</span>
            <?php endif; ?>
          </div>
          <?php if ($o['customer_name']): ?>
            <div class="order-customer">👤 <?= htmlspecialchars($o['customer_name']) ?></div>
          <?php endif; ?>
          <?php if ($o['customer_phone']): ?>
            <div class="order-phone">📞 <a href="tel:<?= htmlspecialchars($o['customer_phone']) ?>" style="color:#7b9fd4;text-decoration:none;"><?= htmlspecialchars($o['customer_phone']) ?></a></div>
          <?php endif; ?>
          <div class="order-time"><?= $dateLabel ?></div>
          <div class="order-pay"><?= $payIcon ?> <?= htmlspecialchars($o['payment_method'] ?? 'Payment not specified') ?></div>
          <?php if (!empty($o['payment_ref'])): ?>
            <div class="order-txref">🔖 Txn: <?= htmlspecialchars($o['payment_ref']) ?></div>
          <?php endif; ?>
        </div>
        <span class="status-badge <?= htmlspecialchars($o['status']) ?>"><?= ucfirst($o['status']) ?></span>
      </div>

      <div class="order-items">
        <?php foreach ($items as $i):
          $line = ($i['price'] ?? 0) * ($i['quantity'] ?? 1);
        ?>
          <div class="order-item">
            <span class="oi-name"><?= htmlspecialchars($i['name'] ?? '') ?></span>
            <span class="oi-qty">x<?= (int)($i['quantity'] ?? 1) ?></span>
            <?php if (!empty($i['price'])): ?>
              <span class="oi-price"><?= number_format($line) ?> FCFA</span>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ($o['note']): ?>
        <div class="order-note"><?= htmlspecialchars($o['note']) ?></div>
      <?php endif; ?>

      <div class="order-total">
        <span class="ot-label">Total</span>
        <span class="ot-amount"><?= number_format((float)$o['total']) ?> FCFA</span>
      </div>

      <div class="order-actions">
        <?php if (in_array($o['status'], ['completed','damaged','returned'])): ?>
          <button class="btn-print" onclick="printOrderReceipt(<?= (int)$o['id'] ?>)">🖨 Print Receipt</button>
        <?php endif; ?>
        <?php if (!empty($o['customer_phone']) && $o['status'] === 'completed'): ?>
          <a class="btn-wa" href="<?= buildWaReceiptLink($o) ?>" target="_blank" rel="noopener noreferrer">📱 Resend Receipt</a>
        <?php endif; ?>
        <?php if ($o['status'] === 'completed'): ?>
          <button class="btn-damaged"  onclick="openStatusModal(<?= (int)$o['id'] ?>, 'damaged')">⚠️ Damaged</button>
          <button class="btn-returned" onclick="openStatusModal(<?= (int)$o['id'] ?>, 'returned')">↩️ Returned</button>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>

    <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <?php for ($p = 1; $p <= $totalPages; $p++):
          $href = '?' . http_build_query(['filter' => $filter, 'days' => $days, 'search' => $search, 'page' => $p]);
        ?>
          <a href="<?= $href ?>" class="page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>

  <?php endif; ?>
</div>

<!-- Status-change modal -->
<div class="modal-overlay" id="status-modal">
  <div class="modal-box">
    <h3 id="modal-title">Update Order Status</h3>
    <p id="modal-desc">Stock will be restored. Add a note (optional).</p>
    <input type="text" class="modal-note" id="modal-note" placeholder="Note (optional)" maxlength="255" autocomplete="off">
    <div class="modal-btns">
      <button class="modal-btn-back" onclick="closeStatusModal()">Back</button>
      <button class="modal-btn-confirm" id="modal-confirm-btn" onclick="confirmStatusChange()">Confirm</button>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>
<div id="print-area" style="display:none;"></div>

<script>
const ALL_ORDERS = <?= json_encode(array_values($orders)) ?>;
let modalTargetId   = null;
let modalTargetType = null;

function showToast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg; t.className = 'toast show';
  setTimeout(() => t.className = 'toast', 3000);
}
function esc(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function openStatusModal(id, type) {
  modalTargetId   = id;
  modalTargetType = type;
  document.getElementById('modal-title').textContent = type === 'damaged' ? '⚠️ Mark as Damaged' : '↩️ Mark as Returned';
  document.getElementById('modal-desc').textContent  = type === 'damaged'
    ? 'Order will be marked damaged and stock restored.'
    : 'Order will be marked returned and stock restored.';
  document.getElementById('modal-note').value = '';
  const btn = document.getElementById('modal-confirm-btn');
  btn.textContent = type === 'damaged' ? 'Mark Damaged' : 'Mark Returned';
  btn.className   = 'modal-btn-confirm ' + type;
  document.getElementById('status-modal').classList.add('open');
  setTimeout(() => document.getElementById('modal-note').focus(), 100);
}

function closeStatusModal() {
  modalTargetId = null; modalTargetType = null;
  document.getElementById('status-modal').classList.remove('open');
}

async function confirmStatusChange() {
  if (!modalTargetId || !modalTargetType) return;
  const note = document.getElementById('modal-note').value.trim();
  const btn  = document.getElementById('modal-confirm-btn');
  btn.textContent = 'Saving…'; btn.disabled = true;
  try {
    const res = await fetch('/api/orders.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: modalTargetType, id: modalTargetId, note })
    });
    const data = await res.json();
    if (data.success) {
      closeStatusModal();
      showToast('✓ Order marked as ' + modalTargetType + ' — stock restored');
      setTimeout(() => location.reload(), 1200);
    } else {
      showToast('Error: ' + (data.error || 'Failed'));
    }
  } catch { showToast('Network error'); }
  btn.disabled = false;
}

function printOrderReceipt(id) {
  const o = ALL_ORDERS.find(x => x.id == id);
  if (!o) return;
  const items = JSON.parse(o.items || '[]');
  let total = 0;
  const itemsHtml = items.map(i => {
    const line = (i.price || 0) * (i.quantity || 1);
    total += line;
    return `<div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:13px;">
      <div>
        <div>${esc(i.name)}</div>
        <div style="color:#888;">x${i.quantity || 1}${i.price ? ' @ ' + Number(i.price).toLocaleString() + ' FCFA / l\'unité' : ''}</div>
      </div>
      <div style="font-weight:bold;">${i.price ? line.toLocaleString() + ' FCFA' : '—'}</div>
    </div>`;
  }).join('');

  const payIcon = (o.payment_method||'').includes('MTN') ? '🟡' : (o.payment_method||'').includes('Orange') ? '🟠' : '💵';
  const date = new Date((o.created_at||'') + ' UTC').toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' });

  document.getElementById('print-area').innerHTML = `
    <div style="text-align:center;margin-bottom:12px;">
      <img src="data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAYGBgYHBgcICAcKCwoLCg8ODAwODxYQERAREBYiFRkVFRkVIh4kHhweJB42KiYmKjY+NDI0PkxERExfWl98fKcBBgYGBgcGBwgIBwoLCgsKDw4MDA4PFhAREBEQFiIVGRUVGRUiHiQeHB4kHjYqJiYqNj40MjQ+TERETF9aX3x8p//CABEIBAAEAAMBIgACEQEDEQH/xAAxAAEAAwEBAQAAAAAAAAAAAAAABAUGAwECAQEAAwEBAAAAAAAAAAAAAAAAAgMEAQX/2gAMAwEAAhADEAAAAsoAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABbV2xzzplyospl0KXy7FIuxlYWqyuqoLYgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAXN7Hk+fo8eq5AAAMjrqe6FAN1AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACZD0FcrcefoAAAAc+jrE+WdZ6OYJcAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA92Wf0mO4M9gAAAAEHLbfL6qoDpz01B0AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAOvGisPPfO0hHoDn0pp8uUGdEHOgAOXV1XV2iWQxfxtKe+ujfXzfAOgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAF1S66ickYrwAGS0eS1VWmjxWyj37GewAAAADhmtZ82wxSyrdtASAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAATNXUW+G8KZgDwoqbtx9HM0Oemx7qhg0AAAAAAeZrTfFkcWkR9+cOgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAHvlnHug6ee+dpDgBBnZ2yNUPQzga+RR3nnaAhIAeHqJL7wOdAPBDyu3zGqqvGqoAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABp87sc1noyXAAfON0Ob2UhorA76/E6nNZOGS4ACk5XWP107ZUW+a0I9AQJ/kuYl9/Ho5g6AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAub6NJ8/QFcgAM3WS4no5gnwBa1X3Du0fP152kABmNPXWwzuxxV7fC6cGW3u4Dv5xFBXW9RuzhZwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABLiaCuVuPP0AAPPRloOkze/OFsQANJZ5jT4LwqmA89GO43dJ6OYJ8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA92We0uS0M1oAACrtEuY3ltq7TVmlpAuhyE+Njjr2id0MV556AcMft8tpqgjXUAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAOvGhsfPfO0hHoAAAAAEWvuk45WJtfi6GYnya+XLaXj/jndozU+uVtWTO0JYl05+hmDoAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABc02tonKGK8AA49ug4AAAAAARZTvKav1K2OJ76ytthRcpUW+sJAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAJesp7jDeFMwB8me5wvj0M+y64q2z2X6NJomHOgAAAAAAK2yS5juOzzOymELoAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAPfLKLQ9DztQcAKyzy9sIA30AJEdxcz8uqntfcVOqlqFLYUzlCEgAAAAHz9DKQ9jkd1HwLoAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAANPndjms9GS4ADljb2i20BfAAAAADpOrUe6Gfj1MtuyE6qehQJ1U/RHoACjvOU440ejnAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAub6NJ8/QFcgBF7zORT0swdAAAAAAAAJUVxr5GQ1uG/wChVMAeGTi2lX6OYJ8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAATIegrlbjz9AAChvcdfXz2WM28+cnVms5Oo5QLSJLmTHo5wAAAAAGlzV3TO8GG8B56KKlu6TfnC2IAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAH1sc/o8d3oz2AAQMvZ1m7O2mL1kJSxkuAeejFfNtU+jmCfAAAAAF/Qa6ickYrwB4UVNZVvoZwsiAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAO3Gin+e+dpCPQHx91MuUHyejmaDPz65agYNAAHLJbKLdDJO3HbQHQAAAnxddH8feHQFcgBG7zMcD0swdAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAALql0tM7FGY7pSKJSL6Scrd5fRWGqoDXyMxpvP0evPa5AAfFPdp8x/Db8b68c1XxOOYafpxlJen6wlVWnqiwI9AHgorjI6K/gbKQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAF/Qew7tfa2xwaPRHoAAAAAAAADz2gnGPXm/OEgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAC9okO7b3LaDHfJee1SAAAAAAAeR87bCXUG2kJcAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAe+Cyt8splt/MdOps0ink1yno33HvZx+eJCHwlyzUECyOkp6pfD3wugAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABIuuVzjuq1orljuVvUbaAnwCwso95juq1orlm63V5TVUF0EqLoK5fS0Y7quHoKyfM4NtEy2gaPJbVrRVOrWgq1oKai1uS01O3GTbC5WjDoq1oKv5txna3acrI41Ki66X38dS9WvmDRn6nR5zXSFsXvXT1Sp7CxZbYfzORlTVWuWRxK1qtdIS4AtqrZUTrlozW56q2OP01eC+AC9otfROEtGa3JxdVlddIWxtJq1xXVme2uLsj8DTWAAAAAAAAAAAAAAAAJ8Wi6nnajl1ImU22S01RhqqAuryjvMF4VT9yGupL66Qbafdlm9PktDNarLOssjnB6Gez0ec0mK/zhIoIdsmVaK9Uyo0mbLIJUWVJrPfPfN08Y8fPaatTKxnWXNi+frJdDyu3xWun568uuivZeevM1Vec0ec20CxthdyvXnaXHnlbY6CRlF9e38qLjJd5k9bUWRz43UAWOlrrHBec/quf1mNPWWQzg30ANfkNfms7vWS5m9Jxsjjn38b8+htaq1waPcXtMXbD4GuoAAAAAAAAAAAAAAABos/ss9n0QMttRpsTsL6+1LdcaZ48ejnAuryjvMF/seRRx7eRpCMsSlRvSy6G0+PvztPLrS3Xee1dnWdZwehns9Jm9Hiv9oL74r7jGvaK8g14yCbCvrSosp3We+PN01We01Lsphp1hZGy7e+YNHuL1uP01OvLrpq2XnrzNVXnNHnNtDR5zWEvz1ivzVb05+lmCXOltSodtI0QBPjpztod0A87TU9Knrrq0nz6y24z4tar0MwT41+Q1+ayR8+8Mt3dX2HeU1Ftclqqubaptc9nuL2mLth8DXUAAAAAAAAAAAAAAABaaKDOwaGe0Oe7yqvocq+u698Yr8tC0Oe9DOFkbq9or3BeyOux1nNf9VtjROmhaCFdCz98i0TzmtxO20V+1dpV1Tzg9DPZ6TN6TFe89g1Sm+0yfLnynECBIj7aEqLKNYe+bp898AD35zVkelWbqHXl17zZHnmaqzOaPObaGuyOr52Z56x3Yh9/Hp5Q6AAAarObDLb5z6ws9mW9sG7Po/qJKwaIWW22Q1VcBpra/Ia/NZIjyI+a3M63E3+mq3r7D3NbU20fv17i9pi7q/ga6gAAAAAAAAAAAAAAHXlcQ7fDztIA9PAfGN2ua011w103V5R3mC/3HbHHWRmaTF7Lnfvwz2KK9x99fHbYnbT57V2lXTPOD0M9npM3pMV6gv6DinG6gABKiyo91nvnvm6arO6HPbqNFaZDXZ7PrOaLlHuOfXzvzuvLqbIeZqq85o85toaPOWMuaUYNGartZlt1Hwe3Q6Xy0x3U0bRU/FANtN3d8JHnaPHqEvHo8ApLuPOORHo52vyGvzWd+HfhmtyHTm9HNs/ugv8GgIS9xe0xeir4GuoAAAAAAAAAAAAAABrM7rMtrz2Bnszvx8vRzfT5E7UYnYZbe1dY+UWYp9/Ho5rq8o7zDf7jtjjrI8dFnZ90NP74waIeVt6jdQ22J21cvau0q6Z5wehns9Jm9Hiv9+PpTP4fY+HQc8ZtsTpqSosrRXrPfPfN01Wd0Oe3UL6h6Sjsvfj7waaWi22R10x+vLrfXsh5mqrzmjzm2gL4auXj9Phvk/P0qnC79nePfPjnWS7wtlCZD0E+W/h5+isz1hW78/wBPlZH60mZtKpaM8w6MpE0Gf9DO1+Q19UpEfvwzW5Aelm91mSnUy1AxaPcXs8Zoq+BrqAAAAAAAAAAAAAAAlzqZX25hQwFnAFhXorn2lQlIjlkZk6lQ7dVHw6CfLlTKpdOZZFdUqPbqNXOdCyMmxpVfbpSudulKLpSi6pSXHXkly59pVcrCvJxCSxkUyuV1Dgh9fKcbpSq5WNcTiEj3wWs7OKpajnm0e3NVzWRCfFtUo9ulKh36+SyIdPv4cXSlVytqknxbVIufipQ6FsQLXpTK5XNR8u8CfAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAHSRp6Z0km1ZrKzlcudzFft6+6GYe+aqnbjNj2avPcV1EvRRL0UXDRwZcy4209rSFqc1lHC1NJHtINdQAHS2rtbmso14pnRr0US98KOl22JvrDRA6aWuVNPuGW2r+LdHuerNpysjjUyHrpSY1pHvZesd1EvBRrwUa8FHC1NHZGlGuoAAAAAAAAAAAAAAAAAAAAAAAAAAAAB75L403U83Uic8xfXppuM7TjsD3JdQU+nzG6hOgzZx1PvnvnafPKWk0V7ZiUubaDl3eBprm6nLanHcpLukh2kG+gACTrclrcdwqqZ2vmKaK9t5ihtcUWwFjONzMPO0vjjlbY6aRjvbobX2vn5bfjI7GkthSWlW11bZiWezbMTrq5SBTMxLVVtqOlSiGiAAAAAAAAAAAAAAAAAAAAAAAAAAAAACZD9i2rj287VwzupWRxLaQr68/ztquyPIWRTYU6HdQPO00lJr+GmrLtQnHLtRQz5FFsZupy2px3e0d3SQ7SDfQABJ1uS1uO5VWvKqeOahppy7UDLp8C6DR5zW1TknuK/M1338elmCXJNpRK+3EWCAs4A12R12ayQMl2JHqZQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAJWjySqW2ZawzW3KHMrk+fpzuZr9jjttCdBnTjqB52l7EizjaKt1aZizproRhqqm6nLanHcpLukh2kG+gACTrclrcdwj0WSfKxONmrBAq5sLbQ1+Q1tU5J7juxD6+fTyh0AAAA12R12ayQMl2JHqZQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAHTnaw7G66VmtyHC/oNFTtxT5rZMCf5+n3HbHG3V8p0Gdor1A87TSUl1S784WxAAm6nLanHcpLukh2kG+gACTrclrcdyqtaquWeHoZwAGlzVpVLRDBoy8HU5ffn8Fsetz92uS2ji6am52iGykBrsjrs1kgZbsSPTygAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAO3Fxtfc5osGj2BPR7TTJqXByhLjlJMbdnToM6XNQPP0/Hz1OcvOw5cZfDrID0s03U5bU47nz9KLOfv2Ph9jO1drVb88nW5LW57Hz9KLOLsc5Oo55DaYvTX8e+NVWukZHT4b+/DuqnVypSUR8xl5kpEHZQF8AGuyOuzWSDzLdih6eUAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABIjuL6XllM9ZyzDnbqp5rYBPjvwcXqiVyvVE4vVEL3nTOgtj3tqJX29USPb1RC9UQlxC2PW4oke3qiR7eqIXqiF7SfKXAnx9/Di4nZlVPV/GXR7eVPFbAJ8AAXFOh28UaPQtiAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAB/8QAAv/aAAwDAQACAAMAAAAhAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA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AAAAEPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPIzw00XPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPMcwww8vPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPKowwww0FvPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPOgwwzzy39/PPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPOSAwxk+4w4E09vPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPLwww/wBD+sMMMNPXzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzyEMMfbvuMMMMMNfbzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz4gMMbyyIsMckMM9Xzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzy4MMO3zzkMMMOsMNDfzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzyIMMP7zysMMPkgkBDXzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzqMMNNXzzwIMMNbzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzwEIMMMHTPz0MsM7zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz2IMMMMMPELt54BDfzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzysIMMIMMMMMMOFsZ/zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzxoIIZSwkMMMMMMvPJHzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzsAIMbzz45kIMMMMMML7zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzyIMMN3zzzzzxy8YQMMNHTzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzygMNHzzzzzzzzzzwkMMf7zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzyMMNYjHHbzzzzzzz48NP7zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzx5OsNfyMsN/zzzzzy78sf7zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz2pYNZ7uMMOP3zzzz9iMNX/zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz7UwoDzwYsNPLHHMrEMMenzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzyqYMMMPusMsMMV3zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzywsMPOMMMNNzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzyw98QAskU5zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzrHXzykFzzrzzw08857nf+FXy4Z3za8zjzzzLxrzyr1LxFrzzzzzzzzzzzzzzzzz+oLjzxb8/wA3/q84kscfqrN+3/8AG3/UW3W8PPHfrvPPs0tnyvPPPPPPPPPPPPPPPPJ5te9PPj3OH+XvLC8MN/r/ADRbSxt+lNTz57yt/B3fywj5k17zzzzzzzzzzzzzzzzyLPa/XypiyS8wLwBGBBT7uf8Aq+suXvX/APPPPOtDZ+fPEDqjPvPPPPPPPPPPPPPPPPozy17PPq1j/eAPAAPPPOq3env/ACne797hELxqfvu7zyj9jnbzzzzzzzzzzzzzzzzBY0077/8Av/v28G8ENOyU+rd/hR8p382tttu8fkNJHe8M8LBO8888888888888888vf8APLbPr3PPHbzvLzDDH/bPfHnPzPfPDfzPPPPvL7PPL/PLffPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPLXYxtPiww2fNf8Azzx68MfvyUNGDT4M9/6vzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzhySjtS2vU37xz3zzx69xw61+Vmw64OPsMPzzzzzzzzzzzzzzzzzzzzzzzzzzzzzy9T7Xzzn9b/AM8cd888+q//APbe1vLf/PO//PPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPGk8cXqf5fufPPfPPPvEsvOa/vPPPPK//PPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPLzF7QG6f3vPPEO/PPNufPPLf+eNevPL+fPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPXsoytqUTW7/ABi2w74KH88uyoC2B/zy8nzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzy7767z81/+7w/+9/y00017y/zwzzzz/wB888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888/8QAShEAAQMCAQQLDQYGAgIDAQAAAQIDBAARBRASITEGExUgIjRBUVNxsRQzUFJhcnOBkZKiwdEyNUJUgqEWIzBAYrJk4USQQ2BjcP/aAAgBAgEBPwD/ANDuM4g7G2ptleatVyTYHR663XxL8wfdT9K3YxL8wfdT9K3YxL8wfdT9K3YxH8x8KfpWEzFSo11m7iVWV4bxGR3RMdWDwb5qeob3BJO0zAgngujN9fJ4axOR3PCdUDZRGanrO+SopUFJNiDcGoz4fjtOj8SQeo+GcfkZzzbA1IFz1nf4DMQhDjDiwmxzk3NtesUlSVC4II8LrUlCVKUbAAkmn3lPvOOq1qUTlhRzJlNNchOnqGk080pl5xtWtKiN4lSkG6SQecaKYxaczqeKhzL4VRMdju2S8NrVz600CCAQbg+FMckbVE2sHS6beoa95sej6Xnz5ie01j8fNfQ8NSxY9Y38DE34igPtN8qD8qYkNSGg42q6T4TxqRts1SQeC2M0dfLvIMfueIy3yhN1dZ0msUj7fCdSBwkjOT1j+hAnOQ3goaUH7aecU24hxCVoN0qFwfCMp8MR3XT+FN+s0pRUSSbkm5OXCo+3zWknUk56upOXEI/c8t1sDRe6eo7xxpxvNz0kZwBHlB3mASzdUZR/yR8x4R2QSLNtMA6VHOV1DebH4+aw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" alt="American Select" style="height:72px;object-fit:contain;display:block;margin:0 auto 8px;">
      <h2 style="font-size:17px;letter-spacing:1px;margin-bottom:2px;">AMERICAN SELECT</h2>
      <p style="font-size:11px;color:#555;margin:0;">Quality Imports from the USA &amp; Canada</p>
      <p style="font-size:10px;color:#777;margin:1px 0 0;font-style:italic;">Importations de qualité des États-Unis et Canada</p>
      <p style="font-size:11px;color:#555;margin:4px 0 0;">Yaoundé, Cameroon / Cameroun</p>
      <p style="font-size:11px;color:#555;margin:2px 0 0;">MTN: 679 457 181 &nbsp;|&nbsp; Orange: 686 271 567</p>
      <p style="font-size:11px;color:#555;margin:2px 0 0;">americanselect.net</p>
    </div>
    <hr style="border:none;border-top:1px dashed #aaa;margin:10px 0;">
    <div style="display:flex;justify-content:space-between;font-size:12px;color:#555;margin-bottom:10px;">
      <span>Order: <strong style="color:#222;">${esc(o.order_ref)}</strong></span>
      <span>${date}</span>
    </div>
    ${o.customer_name ? `<p style="font-size:12px;color:#555;margin-bottom:8px;">Customer: <strong style="color:#222;">${esc(o.customer_name)}${o.customer_phone ? ' · ' + esc(o.customer_phone) : ''}</strong></p>` : ''}
    ${itemsHtml}
    <hr style="border:none;border-top:1px dashed #aaa;margin:10px 0;">
    <div style="display:flex;justify-content:space-between;font-size:18px;font-weight:bold;">
      <span>TOTAL</span><span>${(o.total || total).toLocaleString()} FCFA</span>
    </div>
    <p style="margin-top:6px;font-size:12px;color:#888;">${payIcon} Paid via / Payé via : ${esc(o.payment_method || 'N/A')}</p>
    ${o.payment_ref ? `<p style="font-size:12px;color:#888;">🔖 Transaction ID: <strong style="color:#222;">${esc(o.payment_ref)}</strong></p>` : ''}
    <hr style="border:none;border-top:1px dashed #aaa;margin:14px 0 8px;">
    <p style="text-align:center;font-size:11px;color:#888;">Thank you for shopping with American Select!</p>
    <p style="text-align:center;font-size:11px;color:#888;margin:3px 0 0;font-style:italic;">Merci de votre visite chez American Select !</p>`;

  const printArea = document.getElementById('print-area');
  printArea.style.display = 'block';
  const hidePrint = () => { printArea.style.display = 'none'; window.removeEventListener('afterprint', hidePrint); };
  window.addEventListener('afterprint', hidePrint);
  setTimeout(() => window.print(), 100);
}
</script>
</body>
</html>
