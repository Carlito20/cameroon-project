<?php
require_once __DIR__ . '/../api/db.php';
session_start();
if (empty($_SESSION['admin_logged_in'])) { header('Location: index.php'); exit; }
if (($_SESSION['admin_role'] ?? '') !== 'admin') { header('Location: dashboard.php'); exit; }

// Load product list for the quick-sale dropdown
$products = [];
$jsonPath = __DIR__ . '/../api/products-list.json';
if (file_exists($jsonPath)) {
    $products = json_decode(file_get_contents($jsonPath), true) ?? [];
}
// Merge custom products
try {
    $pdoC = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $custom = $pdoC->query('SELECT name, price FROM custom_products')->fetchAll(PDO::FETCH_ASSOC);
    $existing = array_column($products, 'name');
    foreach ($custom as $c) if (!in_array($c['name'], $existing)) $products[] = $c;
} catch (Exception $e) {}
// Load price overrides
$priceOverrides = [];
try {
    $pdoP = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $rows = $pdoP->query('SELECT product_name, price FROM product_prices')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) $priceOverrides[$r['product_name']] = (int)$r['price'];
} catch (Exception $e) {}
usort($products, fn($a,$b) => strcmp($a['name']??'',$b['name']??''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Sales Report — American Select</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      background: #0a0a0a; color: #e0e0e0;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      min-height: 100vh; min-height: 100dvh;
      -webkit-overflow-scrolling: touch;
    }
    header {
      background: #111; border-bottom: 1px solid #222;
      padding: 16px 24px;
      padding-top: calc(16px + env(safe-area-inset-top,0px));
      padding-left: calc(24px + env(safe-area-inset-left,0px));
      padding-right: calc(24px + env(safe-area-inset-right,0px));
      display: flex; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 100;
    }
    header h1 { color: #d4af37; font-size: 18px; font-weight: 700; letter-spacing: 1px; }
    .btn {
      padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600;
      cursor: pointer; border: none; touch-action: manipulation;
      -webkit-user-select: none; user-select: none; text-decoration: none;
      display: inline-flex; align-items: center; gap: 6px; min-height: 44px;
      -webkit-tap-highlight-color: transparent; -webkit-appearance: none; appearance: none;
    }
    .btn-outline { background: transparent; color: #888; border: 1px solid #333; }
    .btn-outline:hover { color: #ccc; border-color: #555; }
    .btn-gold { background: #d4af37; color: #000; }
    .btn-gold:hover { background: #e8c547; }

    .page { max-width: 1100px; margin: 0 auto; padding: 20px 16px 60px; }

    /* ── Summary Cards ──────────────────────────────────────────────── */
    .cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 28px; }
    .card {
      background: #161616; border: 1px solid #2a2a2a; border-radius: 10px;
      padding: 18px 16px;
    }
    .card-label { font-size: 11px; color: #555; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
    .card-value { font-size: 22px; font-weight: 800; color: #d4af37; }
    .card-sub { font-size: 12px; color: #555; margin-top: 4px; }

    /* ── Section titles ─────────────────────────────────────────────── */
    .section-title {
      font-size: 14px; font-weight: 700; color: #aaa;
      text-transform: uppercase; letter-spacing: 1px;
      margin-bottom: 14px; padding-bottom: 8px;
      border-bottom: 1px solid #1e1e1e;
    }

    /* ── Quick Sale Form ────────────────────────────────────────────── */
    .quick-sale {
      background: #111; border: 1px solid #2a2a2a; border-radius: 10px;
      padding: 20px; margin-bottom: 28px;
    }
    .form-row { display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; }
    .form-group { display: flex; flex-direction: column; gap: 6px; flex: 1; min-width: 160px; }
    .form-group label { font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; }
    .form-group select, .form-group input {
      background: #1a1a1a; border: 1px solid #333; border-radius: 7px;
      color: #e0e0e0; font-size: 14px; padding: 10px 12px; min-height: 44px;
      -webkit-appearance: none; appearance: none; outline: none;
    }
    .form-group select:focus, .form-group input:focus { border-color: #d4af37; }
    .sale-success {
      background: #0a2a0a; border: 1px solid #1a5a1a; border-radius: 8px;
      color: #6dbf6d; padding: 10px 14px; font-size: 13px; margin-top: 12px; display: none;
    }

    /* ── Chart ──────────────────────────────────────────────────────── */
    .chart-wrap {
      background: #111; border: 1px solid #2a2a2a; border-radius: 10px;
      padding: 20px; margin-bottom: 28px;
    }
    .chart-bars { display: flex; align-items: flex-end; gap: 4px; height: 120px; overflow-x: auto; }
    .bar-col { display: flex; flex-direction: column; align-items: center; gap: 4px; min-width: 28px; }
    .bar { background: #d4af37; border-radius: 3px 3px 0 0; width: 100%; min-height: 2px; transition: opacity 0.2s; }
    .bar:hover { opacity: 0.8; }
    .bar-label { font-size: 9px; color: #444; white-space: nowrap; }

    /* ── Tables ─────────────────────────────────────────────────────── */
    .table-wrap {
      background: #111; border: 1px solid #2a2a2a; border-radius: 10px;
      overflow: hidden; margin-bottom: 28px;
    }
    table { width: 100%; border-collapse: collapse; }
    th {
      background: #161616; color: #555; font-size: 11px; font-weight: 600;
      text-transform: uppercase; letter-spacing: 0.5px; padding: 10px 14px; text-align: left;
    }
    td { padding: 10px 14px; font-size: 13px; border-top: 1px solid #1a1a1a; }
    tr:hover td { background: #161616; }
    .badge-green { background: #0a2a0a; color: #6dbf6d; border-radius: 4px; padding: 2px 8px; font-size: 11px; font-weight: 600; }
    .badge-gold  { background: #2a2000; color: #d4af37; border-radius: 4px; padding: 2px 8px; font-size: 11px; font-weight: 600; }
    .empty-row td { color: #444; text-align: center; padding: 30px; }

    /* ── Two columns ────────────────────────────────────────────────── */
    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

    @media (max-width: 900px) {
      .cards { grid-template-columns: repeat(2, 1fr); }
      .two-col { grid-template-columns: 1fr; }
    }
    @media (max-width: 500px) {
      .cards { grid-template-columns: repeat(2, 1fr); }
      .card-value { font-size: 18px; }
    }
  </style>
</head>
<body>

<header>
  <h1>📊 Sales Report</h1>
  <a href="dashboard.php" class="btn btn-outline">← Dashboard</a>
</header>

<div class="page">

  <!-- Summary cards — populated by JS -->
  <div class="cards">
    <div class="card">
      <div class="card-label">Today</div>
      <div class="card-value" id="rev-today">—</div>
      <div class="card-sub" id="cnt-today">loading…</div>
    </div>
    <div class="card">
      <div class="card-label">This Week</div>
      <div class="card-value" id="rev-week">—</div>
      <div class="card-sub" id="cnt-week"></div>
    </div>
    <div class="card">
      <div class="card-label">This Month</div>
      <div class="card-value" id="rev-month">—</div>
      <div class="card-sub" id="cnt-month"></div>
    </div>
    <div class="card">
      <div class="card-label">All Time</div>
      <div class="card-value" id="rev-all">—</div>
      <div class="card-sub" id="cnt-all"></div>
    </div>
  </div>

  <!-- Daily chart -->
  <div class="chart-wrap">
    <div class="section-title">Revenue — Last 30 Days</div>
    <div class="chart-bars" id="chart-bars">
      <div style="color:#444;font-size:13px;padding:20px;">Loading chart…</div>
    </div>
  </div>

  <!-- Quick sale recorder -->
  <div class="quick-sale">
    <div class="section-title" style="margin-bottom:16px;">Record Walk-in Sale</div>
    <div class="form-row">
      <div class="form-group" style="flex:3;min-width:220px;">
        <label>Product</label>
        <select id="sale-product" onchange="autoPrice()">
          <option value="">— Select product —</option>
          <?php foreach ($products as $p):
            $price = $priceOverrides[$p['name']] ?? (int)($p['price'] ?? 0);
            $escaped = htmlspecialchars($p['name']);
          ?>
            <option value="<?= $escaped ?>" data-price="<?= $price ?>"><?= $escaped ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="min-width:80px;max-width:110px;">
        <label>Qty</label>
        <input type="number" id="sale-qty" value="1" min="1" max="999">
      </div>
      <div class="form-group" style="min-width:130px;max-width:170px;">
        <label>Price (FCFA)</label>
        <input type="number" id="sale-price" placeholder="0" min="0">
      </div>
      <div class="form-group" style="flex:2;min-width:140px;">
        <label>Note (optional)</label>
        <input type="text" id="sale-note" placeholder="e.g. cash, market">
      </div>
      <div class="form-group" style="min-width:120px;max-width:140px;">
        <label>&nbsp;</label>
        <button class="btn btn-gold" onclick="recordSale()">Record Sale</button>
      </div>
    </div>
    <div class="sale-success" id="sale-success"></div>
  </div>

  <div class="two-col">

    <!-- Top selling products -->
    <div>
      <div class="section-title">Top Selling Products</div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>#</th><th>Product</th><th>Units Sold</th></tr></thead>
          <tbody id="top-table">
            <tr class="empty-row"><td colspan="3">Loading…</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Recent walk-in sales -->
    <div>
      <div class="section-title">Recent Walk-in Sales</div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Product</th><th>Qty</th><th>Total</th><th>Date</th></tr></thead>
          <tbody id="walk-table">
            <tr class="empty-row"><td colspan="4">Loading…</td></tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <!-- Recent website orders -->
  <div class="section-title">Recent Completed Website Orders</div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Ref</th><th>Customer</th><th>Payment</th><th>Total</th><th>Date</th></tr></thead>
      <tbody id="orders-table">
        <tr class="empty-row"><td colspan="5">Loading…</td></tr>
      </tbody>
    </table>
  </div>

</div>

<script>
function fmt(n) { return Number(n).toLocaleString('fr-CM') + ' FCFA'; }
function fmtDate(s) {
  if (!s) return '—';
  const d = new Date(s);
  return d.toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' });
}

function autoPrice() {
  const sel = document.getElementById('sale-product');
  const opt = sel.options[sel.selectedIndex];
  const price = opt?.dataset?.price;
  if (price) document.getElementById('sale-price').value = price;
}

async function recordSale() {
  const product = document.getElementById('sale-product').value.trim();
  const qty     = parseInt(document.getElementById('sale-qty').value) || 1;
  const price   = parseInt(document.getElementById('sale-price').value) || 0;
  const note    = document.getElementById('sale-note').value.trim();

  if (!product) { alert('Please select a product.'); return; }
  if (!price)   { alert('Please enter a price.'); return; }

  const btn = document.querySelector('.btn-gold');
  btn.disabled = true; btn.textContent = 'Saving…';

  try {
    const res = await fetch('/api/sales.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ product, quantity: qty, price, note }),
    });
    const data = await res.json();
    if (data.success) {
      const msg = document.getElementById('sale-success');
      msg.textContent = '✓ Sale recorded: ' + qty + ' × ' + product.slice(0, 50) + ' = ' + fmt(data.total);
      msg.style.display = 'block';
      document.getElementById('sale-product').value = '';
      document.getElementById('sale-qty').value = '1';
      document.getElementById('sale-price').value = '';
      document.getElementById('sale-note').value = '';
      setTimeout(() => { msg.style.display = 'none'; }, 4000);
      loadData(); // refresh stats
    } else {
      alert('Error: ' + (data.error || 'Failed'));
    }
  } catch (e) { alert('Network error'); }
  btn.disabled = false; btn.textContent = 'Record Sale';
}

async function loadData() {
  try {
    const res  = await fetch('/api/sales.php');
    const data = await res.json();

    // Summary cards
    const periods = { today: 'Today', week: 'This Week', month: 'This Month', all: 'All Time' };
    for (const [key] of Object.entries(periods)) {
      const s = data.stats[key] || { revenue: 0, orders: 0 };
      document.getElementById('rev-' + key).textContent = fmt(s.revenue);
      document.getElementById('cnt-' + key).textContent = s.orders + (s.orders === 1 ? ' sale' : ' sales');
    }

    // Chart
    const daily = data.daily_revenue || {};
    const days  = Object.keys(daily);
    if (days.length === 0) {
      document.getElementById('chart-bars').innerHTML = '<span style="color:#444;font-size:13px;padding:20px 0;">No sales data yet.</span>';
    } else {
      const maxVal = Math.max(...Object.values(daily), 1);
      document.getElementById('chart-bars').innerHTML = days.map(d => {
        const v = daily[d];
        const h = Math.max(4, Math.round((v / maxVal) * 110));
        const label = d.slice(5); // MM-DD
        return `<div class="bar-col" title="${d}: ${fmt(v)}">
          <div class="bar" style="height:${h}px;"></div>
          <div class="bar-label">${label}</div>
        </div>`;
      }).join('');
    }

    // Top products
    const topRows = data.top_products || [];
    document.getElementById('top-table').innerHTML = topRows.length
      ? topRows.map((r, i) => `<tr>
          <td style="color:#555;">${i + 1}</td>
          <td>${r.product_name.slice(0, 50)}${r.product_name.length > 50 ? '…' : ''}</td>
          <td><span class="badge-gold">${r.units_sold} units</span></td>
        </tr>`).join('')
      : '<tr class="empty-row"><td colspan="3">No sales recorded yet.</td></tr>';

    // Recent walk-in sales
    const walkRows = data.recent_walk || [];
    document.getElementById('walk-table').innerHTML = walkRows.length
      ? walkRows.map(r => `<tr>
          <td>${r.product_name.slice(0, 35)}${r.product_name.length > 35 ? '…' : ''}</td>
          <td>${r.quantity}</td>
          <td style="color:#d4af37;font-weight:700;">${fmt(r.total)}</td>
          <td style="color:#555;">${fmtDate(r.sold_at)}</td>
        </tr>`).join('')
      : '<tr class="empty-row"><td colspan="4">No walk-in sales yet.</td></tr>';

    // Recent website orders
    const orderRows = data.recent_orders || [];
    document.getElementById('orders-table').innerHTML = orderRows.length
      ? orderRows.map(r => `<tr>
          <td style="color:#555;font-size:12px;">${r.order_ref || '—'}</td>
          <td>${r.customer_name || 'Anonymous'}</td>
          <td><span class="badge-green">${r.payment_method || '—'}</span></td>
          <td style="color:#d4af37;font-weight:700;">${fmt(r.total)}</td>
          <td style="color:#555;">${fmtDate(r.completed_at)}</td>
        </tr>`).join('')
      : '<tr class="empty-row"><td colspan="5">No completed orders yet.</td></tr>';

  } catch (e) {
    document.getElementById('rev-today').textContent = 'Error';
  }
}

loadData();
</script>
</body>
</html>
