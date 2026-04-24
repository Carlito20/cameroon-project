<?php
require_once __DIR__ . '/../api/db.php';
session_start();

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

// Load products-list.json (built by Astro)
$jsonPath = __DIR__ . '/../api/products-list.json';
$products = [];
if (file_exists($jsonPath)) {
    $products = json_decode(file_get_contents($jsonPath), true) ?? [];
}
// Merge in manually-added custom products
try {
    $pdoCustom = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdoCustom->exec("CREATE TABLE IF NOT EXISTS custom_products (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(500) NOT NULL UNIQUE, price INT NOT NULL DEFAULT 0, flagged_for_site TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $customRows = $pdoCustom->query('SELECT name, price, flagged_for_site FROM custom_products')->fetchAll(PDO::FETCH_ASSOC);
    $existingNames = array_column($products, 'name');
    $flaggedProducts = [];
    foreach ($customRows as $row) {
        if (!in_array($row['name'], $existingNames)) {
            $products[] = ['name' => $row['name'], 'price' => (int)$row['price']];
        }
        if ($row['flagged_for_site']) {
            $flaggedProducts[] = $row['name'];
        }
    }
} catch (Exception $e) { $flaggedProducts = []; }

// Load live DB stock
$dbStock = [];
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $stmt = $pdo->query('SELECT product_name, quantity FROM product_stock');
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $dbStock[$row['product_name']] = (int)$row['quantity'];
    }
} catch (Exception $e) {
    $dbError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Stock Management — American Select</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      background: #0a0a0a;
      color: #e0e0e0;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      min-height: 100vh;
      min-height: -webkit-fill-available;
      -webkit-overflow-scrolling: touch;
      overflow-x: hidden;
    }
    header {
      background: #111;
      border-bottom: 1px solid #222;
      padding: 16px 24px;
      padding-top: calc(16px + env(safe-area-inset-top, 0px));
      padding-left: calc(24px + env(safe-area-inset-left, 0px));
      padding-right: calc(24px + env(safe-area-inset-right, 0px));
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 100;
      -webkit-transform: translateZ(0);
      transform: translateZ(0);
      will-change: transform;
      -webkit-tap-highlight-color: transparent;
    }
    header h1 {
      color: #d4af37;
      font-size: 18px;
      font-weight: 700;
      letter-spacing: 1px;
    }
    header span {
      color: #666;
      font-size: 13px;
    }
    .header-actions {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .btn {
      padding: 8px 16px;
      border-radius: 6px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      border: none;
      touch-action: manipulation;
      -webkit-user-select: none;
      user-select: none;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-height: 44px;
      min-width: 44px;
      -webkit-tap-highlight-color: transparent;
    }
    .btn-gold {
      background: #d4af37;
      color: #000;
    }
    .btn-gold:hover { background: #e8c547; }
    .btn-outline {
      background: transparent;
      color: #888;
      border: 1px solid #333;
    }
    .btn-outline:hover { color: #ccc; border-color: #555; }
    .btn-danger {
      background: transparent;
      color: #e05050;
      border: 1px solid #3a1a1a;
    }
    .btn-danger:hover { background: #2a0a0a; }
    .container {
      max-width: 900px;
      margin: 0 auto;
      padding: 24px 16px;
      padding-bottom: calc(24px + env(safe-area-inset-bottom, 0px));
      padding-left: calc(16px + env(safe-area-inset-left, 0px));
      padding-right: calc(16px + env(safe-area-inset-right, 0px));
    }
    .table-scroll {
      width: 100%;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
      overscroll-behavior: contain;
    }
    .toolbar {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }
    .search-box {
      flex: 1;
      min-width: 200px;
      padding: 9px 14px;
      background: #1a1a1a;
      border: 1px solid #2a2a2a;
      border-radius: 8px;
      color: #e0e0e0;
      font-size: 16px; /* 16px prevents iOS auto-zoom */
      outline: none;
      -webkit-appearance: none;
      appearance: none;
      touch-action: manipulation;
      min-height: 44px;
    }
    .search-box:focus { border-color: #d4af37; }
    .pending-site-banner {
      background: #1a1400; border: 1px solid #3a2e00; border-radius: 10px;
      padding: 16px 18px; margin-bottom: 20px;
    }
    .pending-site-banner h3 { color: #d4af37; font-size: 14px; font-weight: 700; margin-bottom: 10px; }
    .pending-item {
      display: flex; justify-content: space-between; align-items: center;
      padding: 8px 0; border-bottom: 1px solid #2a2400; gap: 10px;
    }
    .pending-item:last-child { border-bottom: none; padding-bottom: 0; }
    .pending-item-name { font-size: 13px; color: #ccc; flex: 1; }
    .pending-item-price { font-size: 12px; color: #888; white-space: nowrap; }
    .done-btn {
      background: transparent; color: #6dbf6d; border: 1px solid #1a3a1a;
      border-radius: 6px; padding: 5px 12px; font-size: 12px; font-weight: 700;
      cursor: pointer; white-space: nowrap; min-height: 36px;
      touch-action: manipulation; -webkit-user-select: none; user-select: none;
      -webkit-tap-highlight-color: transparent;
    }
    .done-btn:hover { background: #0d1a0d; }
    .db-error {
      background: #2a0a0a;
      border: 1px solid #5c1a1a;
      color: #ff6b6b;
      border-radius: 8px;
      padding: 12px 16px;
      margin-bottom: 20px;
      font-size: 13px;
    }
    .info-banner {
      background: #0d1a0d;
      border: 1px solid #1a3a1a;
      color: #6dbf6d;
      border-radius: 8px;
      padding: 12px 16px;
      margin-bottom: 20px;
      font-size: 13px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 14px;
    }
    thead tr {
      background: #1a1a1a;
    }
    th {
      padding: 12px 14px;
      text-align: left;
      color: #888;
      font-weight: 600;
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      border-bottom: 1px solid #222;
    }
    td {
      padding: 10px 14px;
      border-bottom: 1px solid #1a1a1a;
      vertical-align: middle;
    }
    tr:hover td { background: #111; }
    .product-name {
      max-width: 480px;
      line-height: 1.4;
    }
    .qty-source {
      font-size: 11px;
      color: #555;
      margin-top: 2px;
    }
    .qty-live { color: #d4af37; }
    .qty-default { color: #555; }
    .qty-input {
      width: 80px;
      padding: 7px 10px;
      background: #1a1a1a;
      border: 1px solid #2a2a2a;
      border-radius: 6px;
      color: #e0e0e0;
      font-size: 16px; /* 16px prevents iOS auto-zoom */
      text-align: center;
      outline: none;
      -webkit-appearance: none;
      appearance: none;
      touch-action: manipulation;
      min-height: 44px;
    }
    .qty-input:focus { border-color: #d4af37; }
    .save-btn {
      padding: 7px 14px;
      background: #1a2a1a;
      color: #6dbf6d;
      border: 1px solid #2a4a2a;
      border-radius: 6px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      touch-action: manipulation;
      -webkit-user-select: none;
      user-select: none;
      min-height: 44px;
      min-width: 44px;
      -webkit-tap-highlight-color: transparent;
    }
    .save-btn:hover { background: #1e361e; }
    .save-btn:disabled { opacity: 0.5; cursor: not-allowed; }
    .status-msg {
      font-size: 12px;
      margin-left: 8px;
      display: none;
    }
    .status-ok { color: #6dbf6d; }
    .status-err { color: #ff6b6b; }
    .count-badge {
      background: #1a1a1a;
      color: #888;
      border-radius: 4px;
      padding: 2px 8px;
      font-size: 12px;
    }
    #init-status {
      font-size: 13px;
      margin-left: 8px;
    }
    /* Desktop scanner */
    .scanner-section {
      background: #111; border: 1px solid #222; border-radius: 10px;
      margin-bottom: 20px; overflow: hidden;
    }
    .scanner-header {
      display: flex; justify-content: space-between; align-items: center;
      padding: 14px 18px; cursor: pointer; user-select: none; -webkit-user-select: none;
      touch-action: manipulation; -webkit-tap-highlight-color: transparent;
      font-size: 14px; font-weight: 600; color: #aaa;
    }
    .scanner-header:hover { background: #161616; }
    .scanner-body { padding: 0 18px 18px; }
    .scanner-input-row { display: flex; gap: 10px; margin-bottom: 12px; flex-wrap: wrap; }
    .scanner-input-row .search-box { flex: 1; min-width: 200px; }
    .scanner-result { background: #161616; border-radius: 8px; padding: 16px; }
    .scanner-unknown { background: #161616; border-radius: 8px; padding: 16px; }
    .scanner-product-name { font-size: 15px; font-weight: 600; color: #e0e0e0; margin-bottom: 4px; line-height: 1.4; }
    .scanner-stock { font-size: 13px; color: #888; margin-bottom: 4px; }
    .scanner-stock span { color: #d4af37; font-weight: 700; font-size: 15px; }
    .scanner-price { font-size: 13px; color: #888; margin-bottom: 14px; }
    .scanner-price span { color: #6dbf6d; font-weight: 700; font-size: 15px; }
    .scanner-actions { display: flex; gap: 8px; margin-bottom: 14px; flex-wrap: wrap; }
    .tx-btn {
      padding: 8px 16px; border-radius: 8px; border: 1px solid; font-size: 13px;
      font-weight: 700; cursor: pointer; min-height: 44px; display: flex; align-items: center; gap: 6px;
      touch-action: manipulation; -webkit-user-select: none; user-select: none; -webkit-tap-highlight-color: transparent;
    }
    .tx-btn.received { background:#0d1a0d;color:#6dbf6d;border-color:#1a3a1a; }
    .tx-btn.sold     { background:#0d1020;color:#7b9fd4;border-color:#1a2a40; }
    .tx-btn.damaged  { background:#2a1a0a;color:#d4884a;border-color:#3a2a1a; }
    .tx-btn.returned { background:#1a0d1a;color:#b47bd4;border-color:#2a1a3a; }
    .tx-btn.selected.received { border-color:#6dbf6d;background:#1e361e; }
    .tx-btn.selected.sold     { border-color:#7b9fd4;background:#1a2a40; }
    .tx-btn.selected.damaged  { border-color:#d4884a;background:#3a2a1a; }
    .tx-btn.selected.returned { border-color:#b47bd4;background:#2a1a3a; }
    .scanner-qty-row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
    .scanner-qty-row .qty-input { width: 80px; }

    @media (max-width: 600px) {
      .product-name { max-width: 180px; font-size: 13px; }
      .qty-input { width: 64px; }
      th, td { padding: 8px 8px; }
    }
  </style>
</head>
<script src="https://cdn.jsdelivr.net/npm/qz-tray@2.2.4/qz-tray.js" defer></script>
<body>
<header>
  <div>
    <h1>AMERICAN SELECT</h1>
    <span>Stock Management</span>
  </div>
  <div class="header-actions">
    <a href="orders.php" class="btn btn-outline" style="color:#d4af37;border-color:#3a3010;" id="orders-link">📦 Orders</a>
    <a href="price-tags.php" class="btn btn-outline" style="color:#d4af37;border-color:#3a3010;">🏷 Price Tags</a>
    <a href="barcodes.php" class="btn btn-outline" style="color:#b47bd4;border-color:#2a1a3a;">🏷 Labels</a>
    <a href="order-history.php" class="btn btn-outline" style="color:#6dbf6d;border-color:#1a3a1a;">📋 Order History</a>
    <a href="history.php" class="btn btn-outline">📦 Stock History</a>
    <a href="scan.php" class="btn btn-outline">📷 Scan</a>
    <a href="checkout.php" class="btn btn-outline" style="color:#6dbf6d;border-color:#1a3a1a;">🛒 Checkout</a>
    <button class="btn btn-gold" onclick="initializeAll()">Initialize All</button>
    <span id="init-status"></span>
    <button class="btn btn-outline" id="btn-drawer" onclick="openCashDrawer()" title="Open cash drawer" style="color:#7b9fd4;border-color:#1a2a40;">🗄 <span id="drawer-label">Drawer</span></button>
    <button class="btn btn-danger" onclick="clearAllOrders()">Clear Orders</button>
    <a href="logout.php" class="btn btn-danger">Logout</a>
  </div>
</header>

<div class="container">
  <?php if (isset($dbError)): ?>
    <div class="db-error">
      <strong>Database connection failed:</strong> <?= htmlspecialchars($dbError) ?><br>
      <small>Please check your credentials in <code>public/api/db.php</code></small>
    </div>
  <?php elseif (empty($dbStock)): ?>
    <div class="info-banner">
      No stock data in database yet. Click <strong>Initialize All</strong> to load default quantities from the product catalog.
    </div>
  <?php endif; ?>

  <?php if (empty($products)): ?>
    <div class="db-error">
      <strong>products-list.json not found.</strong> Run <code>npm run build</code> first to generate it.
    </div>
  <?php endif; ?>

  <!-- Pending site addition banner -->
  <?php if (!empty($flaggedProducts)): ?>
  <div class="pending-site-banner" id="pending-site-banner">
    <h3>⚠️ <?= count($flaggedProducts) ?> product<?= count($flaggedProducts) > 1 ? 's' : '' ?> need<?= count($flaggedProducts) === 1 ? 's' : '' ?> to be added to the site</h3>
    <?php foreach ($flaggedProducts as $name): ?>
    <div class="pending-item" id="pending-<?= htmlspecialchars(base64_encode($name)) ?>">
      <span class="pending-item-name"><?= htmlspecialchars($name) ?></span>
      <button class="done-btn" onclick="markDone(<?= htmlspecialchars(json_encode($name)) ?>, this)">✓ Added to Site</button>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Desktop Barcode Scanner -->
  <div class="scanner-section" id="scanner-section">
    <div class="scanner-header" onclick="toggleScanner()">
      <span>🔍 Barcode Scanner <small style="color:#555;font-size:12px;">(USB / Bluetooth scanner or type manually)</small></span>
      <span id="scanner-toggle-icon">▼</span>
    </div>
    <div class="scanner-body" id="scanner-body" style="display:none;">
      <div class="scanner-input-row">
        <input type="text" id="barcode-input" class="search-box" placeholder="Scan or type barcode then press Enter..." autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
        <button class="btn btn-gold" onclick="lookupBarcode()">Look Up</button>
      </div>
      <div class="scanner-result" id="scanner-result" style="display:none;">
        <div class="scanner-product-name" id="scanner-product-name"></div>
        <div class="scanner-stock">Stock: <span id="scanner-stock"></span></div>
        <div class="scanner-price">Price: <span id="scanner-price"></span></div>
        <div class="scanner-actions">
          <button class="tx-btn received"  onclick="selectTx('received')"><span>📦</span> Received</button>
          <button class="tx-btn sold"      onclick="selectTx('sold')"><span>✅</span> Sold</button>
          <button class="tx-btn damaged"   onclick="selectTx('damaged')"><span>⚠️</span> Damaged</button>
          <button class="tx-btn returned"  onclick="selectTx('returned')"><span>↩️</span> Returned</button>
        </div>
        <div class="scanner-qty-row">
          <label style="color:#888;font-size:13px;">Qty:</label>
          <input type="number" id="scanner-qty" value="1" min="1" class="qty-input">
          <input type="text" id="scanner-note" class="search-box" style="flex:1;" placeholder="Note (optional)">
          <button class="btn btn-gold" id="scanner-confirm-btn" onclick="confirmDesktopTx()" disabled>Confirm</button>
        </div>
        <div id="scanner-status" style="font-size:13px;margin-top:8px;"></div>
      </div>
      <div class="scanner-unknown" id="scanner-unknown" style="display:none;">
        <div style="color:#c8b84a;font-size:13px;margin-bottom:10px;">Unknown barcode — assign to a product:</div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
          <select id="scanner-assign-select" class="search-box" style="flex:1;min-width:200px;">
            <option value="">— Select product —</option>
            <?php foreach ($products as $p): ?>
              <option value="<?= htmlspecialchars($p['name']) ?>"><?= htmlspecialchars($p['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-gold" onclick="desktopAssign()">Assign</button>
        </div>
      </div>
    </div>
  </div>

  <div class="toolbar">
    <input type="text" class="search-box" id="search" placeholder="Search products..." oninput="filterTable(this.value)">
    <span class="count-badge" id="row-count"><?= count($products) ?> products</span>
  </div>

  <div class="table-scroll">
  <table id="products-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Product Name</th>
        <th>Qty</th>
        <th>Action</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($products as $i => $product):
        $name = $product['name'];
        $defaultQty = (int)$product['quantity'];
        $liveQty = $dbStock[$name] ?? null;
        $displayQty = $liveQty !== null ? $liveQty : $defaultQty;
        $isLive = $liveQty !== null;
      ?>
      <tr data-name="<?= htmlspecialchars(strtolower($name)) ?>">
        <td style="color:#555;font-size:12px;"><?= $i + 1 ?></td>
        <td class="product-name">
          <?= htmlspecialchars($name) ?>
          <div class="qty-source <?= $isLive ? 'qty-live' : 'qty-default' ?>">
            <?= $isLive ? 'Live (DB)' : 'Default' ?>
          </div>
        </td>
        <td>
          <input
            type="number"
            class="qty-input"
            value="<?= $displayQty ?>"
            min="0"
            data-name="<?= htmlspecialchars($name) ?>"
            data-original="<?= $displayQty ?>"
          >
        </td>
        <td>
          <button class="save-btn" onclick="saveStock(this)">Save</button>
          <span class="status-msg" id="status-<?= $i ?>"></span>
        </td>
        <td></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<script>
function markDone(productName, btn) {
  btn.disabled = true; btn.textContent = 'Saving...';
  fetch('/api/barcode.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'unflag_product', product_name: productName })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      const row = btn.closest('.pending-item');
      row.style.opacity = '0'; row.style.transition = 'opacity 0.3s';
      setTimeout(() => {
        row.remove();
        const banner = document.getElementById('pending-site-banner');
        if (banner && banner.querySelectorAll('.pending-item').length === 0) banner.remove();
      }, 300);
    } else {
      btn.disabled = false; btn.textContent = '✓ Added to Site';
    }
  })
  .catch(() => { btn.disabled = false; btn.textContent = '✓ Added to Site'; });
}

function saveStock(btn) {
  const row = btn.closest('tr');
  const input = row.querySelector('.qty-input');
  const name = input.dataset.name;
  const quantity = parseInt(input.value, 10);
  const statusEl = row.querySelector('.status-msg');

  if (isNaN(quantity) || quantity < 0) {
    showStatus(statusEl, 'Invalid quantity', false);
    return;
  }

  btn.disabled = true;
  btn.textContent = '...';

  fetch('/api/stock.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ name, quantity })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      input.dataset.original = quantity;
      const srcEl = row.querySelector('.qty-source');
      srcEl.textContent = 'Live (DB)';
      srcEl.className = 'qty-source qty-live';
      showStatus(statusEl, '✓ Saved', true);
    } else {
      showStatus(statusEl, data.error || 'Error', false);
    }
  })
  .catch(() => showStatus(statusEl, 'Network error', false))
  .finally(() => {
    btn.disabled = false;
    btn.textContent = 'Save';
  });
}

function showStatus(el, msg, ok) {
  el.textContent = msg;
  el.className = 'status-msg ' + (ok ? 'status-ok' : 'status-err');
  el.style.display = 'inline';
  setTimeout(() => { el.style.display = 'none'; }, 3000);
}

function initializeAll() {
  const statusEl = document.getElementById('init-status');
  statusEl.textContent = 'Initializing...';
  statusEl.style.color = '#888';

  fetch('/api/stock.php?action=init', {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json' },
    body: '{}'
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      statusEl.textContent = '✓ ' + data.count + ' products initialized';
      statusEl.style.color = '#6dbf6d';
      setTimeout(() => location.reload(), 1000);
    } else {
      statusEl.textContent = data.error || 'Error';
      statusEl.style.color = '#ff6b6b';
    }
  })
  .catch(() => {
    statusEl.textContent = 'Network error';
    statusEl.style.color = '#ff6b6b';
  });
}

// ── Desktop barcode scanner ──────────────────────────
let desktopSelectedAction = '', desktopCurrentProduct = '', desktopCurrentBarcode = '';

function toggleScanner() {
  const body = document.getElementById('scanner-body');
  const icon = document.getElementById('scanner-toggle-icon');
  const open = body.style.display === 'none';
  body.style.display = open ? 'block' : 'none';
  icon.textContent = open ? '▲' : '▼';
  if (open) setTimeout(() => document.getElementById('barcode-input').focus(), 50);
}

document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('barcode-input').addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); lookupBarcode(); }
  });
});

function lookupBarcode() {
  const barcode = document.getElementById('barcode-input').value.trim();
  if (!barcode) return;
  resetScannerResult();
  fetch('/api/barcode.php?barcode=' + encodeURIComponent(barcode))
    .then(r => r.json())
    .then(data => {
      desktopCurrentBarcode = barcode;
      if (data.found) {
        desktopCurrentProduct = data.product_name;
        document.getElementById('scanner-product-name').textContent = data.product_name;
        document.getElementById('scanner-stock').textContent = data.quantity;
        document.getElementById('scanner-price').textContent = data.price ? data.price.toLocaleString() + ' FCFA' : '—';
        document.getElementById('scanner-result').style.display = 'block';
        document.getElementById('scanner-unknown').style.display = 'none';
      } else {
        desktopCurrentProduct = '';
        document.getElementById('scanner-result').style.display = 'none';
        document.getElementById('scanner-unknown').style.display = 'block';
        document.getElementById('scanner-assign-select').value = '';
      }
    })
    .catch(() => setScannerStatus('Network error', false));
}

function selectTx(action) {
  desktopSelectedAction = action;
  document.querySelectorAll('.tx-btn').forEach(b => b.classList.remove('selected'));
  document.querySelector('.tx-btn.' + action).classList.add('selected');
  document.getElementById('scanner-confirm-btn').disabled = false;
}

function confirmDesktopTx() {
  if (!desktopSelectedAction || !desktopCurrentProduct) return;
  const qty = parseInt(document.getElementById('scanner-qty').value, 10);
  if (isNaN(qty) || qty <= 0) { setScannerStatus('Enter a valid quantity', false); return; }
  const note = document.getElementById('scanner-note').value;
  const btn = document.getElementById('scanner-confirm-btn');
  btn.disabled = true; btn.textContent = '...';

  fetch('/api/barcode.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action:'transaction', product_name:desktopCurrentProduct, tx_action:desktopSelectedAction, quantity:qty, note })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      document.getElementById('scanner-stock').textContent = data.stock_after;
      setScannerStatus('✓ Done — stock now ' + data.stock_after, true);
      // Update the table row if visible
      const rows = document.querySelectorAll('#products-table tbody tr');
      rows.forEach(row => {
        if ((row.dataset.name || '') === desktopCurrentProduct.toLowerCase()) {
          const input = row.querySelector('.qty-input');
          if (input) { input.value = data.stock_after; input.dataset.original = data.stock_after; }
          const srcEl = row.querySelector('.qty-source');
          if (srcEl) { srcEl.textContent = 'Live (DB)'; srcEl.className = 'qty-source qty-live'; }
        }
      });
      desktopSelectedAction = '';
      document.querySelectorAll('.tx-btn').forEach(b => b.classList.remove('selected'));
      document.getElementById('barcode-input').value = '';
      document.getElementById('barcode-input').focus();
    } else {
      setScannerStatus('Error: ' + (data.error || 'Failed'), false);
    }
  })
  .catch(() => setScannerStatus('Network error', false))
  .finally(() => { btn.disabled = false; btn.textContent = 'Confirm'; });
}

function desktopAssign() {
  const productName = document.getElementById('scanner-assign-select').value;
  if (!productName) { setScannerStatus('Select a product first', false); return; }
  fetch('/api/barcode.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action:'assign', barcode:desktopCurrentBarcode, product_name:productName })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      desktopCurrentProduct = productName;
      document.getElementById('scanner-product-name').textContent = productName;
      document.getElementById('scanner-stock').textContent = data.quantity;
      document.getElementById('scanner-price').textContent = '—';
      document.getElementById('scanner-result').style.display = 'block';
      document.getElementById('scanner-unknown').style.display = 'none';
      setScannerStatus('✓ Barcode assigned', true);
    } else {
      setScannerStatus('Error: ' + (data.error || 'Failed'), false);
    }
  })
  .catch(() => setScannerStatus('Network error', false));
}

function setScannerStatus(msg, ok) {
  const el = document.getElementById('scanner-status');
  el.textContent = msg;
  el.style.color = ok ? '#6dbf6d' : '#ff6b6b';
  setTimeout(() => { el.textContent = ''; }, 4000);
}

function resetScannerResult() {
  desktopSelectedAction = ''; desktopCurrentProduct = ''; desktopCurrentBarcode = '';
  document.getElementById('scanner-result').style.display = 'none';
  document.getElementById('scanner-unknown').style.display = 'none';
  document.getElementById('scanner-status').textContent = '';
  document.getElementById('scanner-confirm-btn').disabled = true;
  document.getElementById('scanner-confirm-btn').textContent = 'Confirm';
  document.querySelectorAll('.tx-btn').forEach(b => b.classList.remove('selected'));
}
// ── End desktop scanner ──────────────────────────────

// Show pending order count on Orders link
(function checkPendingOrders() {
  fetch('/api/orders.php?status=pending')
    .then(r => r.json())
    .then(data => {
      const count = (data.orders || []).length;
      const link = document.getElementById('orders-link');
      if (link && count > 0) {
        link.textContent = '📦 Orders (' + count + ')';
        link.style.color = '#d4af37';
        link.style.fontWeight = '800';
      }
    }).catch(() => {});
})();

// ── QZ Tray / Cash Drawer ─────────────────────────────────
let qzReady = false;
let qzPrinterName = null;

function initQZ() {
  if (typeof qz === 'undefined') return;
  if (qz.websocket.isActive()) return;
  qz.security.setCertificatePromise(() => Promise.resolve(''));
  qz.security.setSignatureAlgorithm('SHA512');
  qz.security.setSignaturePromise(() => Promise.resolve(''));
  qz.websocket.connect({ retries: 2, delay: 1 })
    .then(() => qz.printers.find())
    .then(printers => {
      const thermal = printers.find(p =>
        /volcora|thermal|receipt|pos|epson|star|citizen|bixolon/i.test(p)
      ) || printers[0] || null;
      qzPrinterName = thermal;
      qzReady = !!thermal;
      updateDrawerLabel(qzReady);
    })
    .catch(() => { qzReady = false; updateDrawerLabel(false); });
}

function updateDrawerLabel(connected) {
  const lbl = document.getElementById('drawer-label');
  const btn = document.getElementById('btn-drawer');
  if (!lbl || !btn) return;
  if (connected) {
    lbl.textContent = 'Drawer';
    btn.style.color = '#7b9fd4';
    btn.title = 'Open cash drawer (' + (qzPrinterName || 'printer') + ')';
  } else {
    lbl.textContent = 'Drawer ⚫';
    btn.style.color = '#444';
    btn.title = 'Cash drawer offline — install & run QZ Tray (qz.io)';
  }
}

async function openCashDrawer() {
  const lbl = document.getElementById('drawer-label');
  if (!qzReady || !qzPrinterName) {
    if (lbl) { const t = lbl.textContent; lbl.textContent = 'Not connected'; setTimeout(() => { lbl.textContent = t; }, 2000); }
    return;
  }
  try {
    const config = qz.configs.create(qzPrinterName, { raw: true });
    await qz.print(config, [{ type: 'raw', format: 'command', data: '\x1B\x70\x00\x19\xFA' }]);
    if (lbl) { lbl.textContent = '✓ Opened'; setTimeout(() => updateDrawerLabel(true), 1500); }
  } catch(e) {
    if (lbl) { lbl.textContent = 'Error'; setTimeout(() => updateDrawerLabel(qzReady), 2000); }
  }
}

window.addEventListener('load', () => { setTimeout(initQZ, 300); });

function filterTable(query) {
  const lower = query.toLowerCase();
  const rows = document.querySelectorAll('#products-table tbody tr');
  let visible = 0;
  rows.forEach(row => {
    const name = row.dataset.name || '';
    const show = name.includes(lower);
    row.style.display = show ? '' : 'none';
    if (show) visible++;
  });
  document.getElementById('row-count').textContent = visible + ' products';
}

async function clearAllOrders() {
  const answer = prompt('Type DELETE to clear all orders (this cannot be undone):');
  if (answer !== 'DELETE') return;
  try {
    const res = await fetch('/api/orders.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'clear_all' })
    });
    const data = await res.json();
    if (data.success) alert('All orders cleared.');
    else alert('Error: ' + (data.error || 'Failed'));
  } catch { alert('Network error'); }
}

// Register Service Worker so checkout.php gets cached for offline use
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/admin/sw.js', { scope: '/admin/' }).catch(() => {});
}
</script>
</body>
</html>
