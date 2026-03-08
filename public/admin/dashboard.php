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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Stock Management — American Select</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      background: #0a0a0a;
      color: #e0e0e0;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      min-height: 100vh;
    }
    header {
      background: #111;
      border-bottom: 1px solid #222;
      padding: 16px 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 100;
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
      display: inline-block;
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
      font-size: 14px;
      outline: none;
      -webkit-appearance: none;
      appearance: none;
    }
    .search-box:focus { border-color: #d4af37; }
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
      font-size: 14px;
      text-align: center;
      outline: none;
      -webkit-appearance: none;
      appearance: none;
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
    @media (max-width: 600px) {
      .product-name { max-width: 180px; font-size: 13px; }
      .qty-input { width: 64px; }
      th, td { padding: 8px 8px; }
    }
  </style>
</head>
<body>
<header>
  <div>
    <h1>AMERICAN SELECT</h1>
    <span>Stock Management</span>
  </div>
  <div class="header-actions">
    <button class="btn btn-gold" onclick="initializeAll()">Initialize All</button>
    <span id="init-status"></span>
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

  <div class="toolbar">
    <input type="text" class="search-box" id="search" placeholder="Search products..." oninput="filterTable(this.value)">
    <span class="count-badge" id="row-count"><?= count($products) ?> products</span>
  </div>

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

<script>
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
</script>
</body>
</html>
