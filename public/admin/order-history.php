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
      <img src="/images/as-logo.jpeg" alt="American Select" style="height:72px;object-fit:contain;display:block;margin:0 auto 8px;">
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
