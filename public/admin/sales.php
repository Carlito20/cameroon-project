<?php
require_once __DIR__ . '/../api/db.php';
session_start();
if (empty($_SESSION['admin_logged_in']))              { header('Location: index.php');    exit; }
if (($_SESSION['admin_role'] ?? '') !== 'admin')      { header('Location: dashboard.php'); exit; }

// Product list for quick-sale dropdown
$products = [];
$jsonPath = __DIR__ . '/../api/products-list.json';
if (file_exists($jsonPath)) $products = json_decode(file_get_contents($jsonPath), true) ?? [];
try {
    $pdoC = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $custom = $pdoC->query('SELECT name, price FROM custom_products')->fetchAll(PDO::FETCH_ASSOC);
    $existing = array_column($products, 'name');
    foreach ($custom as $c) if (!in_array($c['name'], $existing)) $products[] = $c;
} catch (Exception $e) {}
$priceOverrides = [];
try {
    $pdoP = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    foreach ($pdoP->query('SELECT product_name, price FROM product_prices')->fetchAll(PDO::FETCH_ASSOC) as $r)
        $priceOverrides[$r['product_name']] = (int)$r['price'];
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
      min-height: 100vh; min-height: 100dvh; -webkit-overflow-scrolling: touch;
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
    .btn-outline:hover, .btn-outline.active { color: #d4af37; border-color: #d4af37; background: #1c1800; }
    .btn-gold { background: #d4af37; color: #000; }
    .btn-gold:hover { background: #e8c547; }
    .btn-sm { padding: 6px 12px; font-size: 12px; min-height: 34px; }

    .page { max-width: 1200px; margin: 0 auto; padding: 20px 16px 60px;
      padding-left: calc(16px + env(safe-area-inset-left,0px));
      padding-right: calc(16px + env(safe-area-inset-right,0px)); }

    /* ── Filter bar ─────────────────────────────────────────────────── */
    .filter-bar {
      background: #111; border: 1px solid #222; border-radius: 10px;
      padding: 14px 16px; margin-bottom: 20px;
      display: flex; flex-wrap: wrap; gap: 10px; align-items: center;
    }
    .filter-label { font-size: 11px; color: #555; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; }
    .quick-btns { display: flex; gap: 6px; flex-wrap: wrap; }
    .date-inputs { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .date-inputs input[type=date] {
      background: #1a1a1a; border: 1px solid #333; border-radius: 6px;
      color: #e0e0e0; font-size: 13px; padding: 7px 10px; min-height: 36px;
      -webkit-appearance: none; appearance: none; outline: none;
    }
    .date-inputs input[type=date]:focus { border-color: #d4af37; }
    .search-wrap { flex: 1; min-width: 180px; }
    .search-input {
      width: 100%; background: #1a1a1a; border: 1px solid #333; border-radius: 6px;
      color: #e0e0e0; font-size: 13px; padding: 8px 12px; min-height: 36px;
      -webkit-appearance: none; appearance: none; outline: none;
    }
    .search-input:focus { border-color: #d4af37; }
    .search-input::placeholder { color: #555; }
    .divider { width: 1px; height: 24px; background: #222; }

    /* ── Summary Cards ──────────────────────────────────────────────── */
    .cards { display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; margin-bottom: 24px; }
    .card { background: #161616; border: 1px solid #2a2a2a; border-radius: 10px; padding: 18px 16px; }
    .card-label { font-size: 11px; color: #555; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
    .card-value { font-size: 22px; font-weight: 800; color: #d4af37; }
    .card-sub { font-size: 12px; color: #555; margin-top: 4px; }

    /* ── Chart ──────────────────────────────────────────────────────── */
    .chart-wrap { background: #111; border: 1px solid #2a2a2a; border-radius: 10px; padding: 20px; margin-bottom: 24px; }
    .chart-bars { display: flex; align-items: flex-end; gap: 4px; height: 120px; overflow-x: auto; padding-bottom: 4px; }
    .bar-col { display: flex; flex-direction: column; align-items: center; gap: 4px; min-width: 28px; cursor: default; }
    .bar { background: #d4af37; border-radius: 3px 3px 0 0; width: 100%; min-height: 2px; }
    .bar-col:hover .bar { background: #e8c547; }
    .bar-label { font-size: 9px; color: #444; white-space: nowrap; }

    /* ── Section title ──────────────────────────────────────────────── */
    .section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; flex-wrap: wrap; gap: 8px; }
    .section-title { font-size: 13px; font-weight: 700; color: #888; text-transform: uppercase; letter-spacing: 1px; }
    .record-count { font-size: 12px; color: #555; }

    /* ── Quick Sale ─────────────────────────────────────────────────── */
    .quick-sale { background: #111; border: 1px solid #2a2a2a; border-radius: 10px; padding: 20px; margin-bottom: 24px; }
    .form-row { display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; }
    .form-group { display: flex; flex-direction: column; gap: 6px; flex: 1; min-width: 140px; }
    .form-group label { font-size: 11px; color: #555; text-transform: uppercase; letter-spacing: 0.5px; }
    .form-group select, .form-group input[type=text], .form-group input[type=number] {
      background: #1a1a1a; border: 1px solid #333; border-radius: 7px;
      color: #e0e0e0; font-size: 14px; padding: 10px 12px; min-height: 44px;
      -webkit-appearance: none; appearance: none; outline: none;
    }
    .form-group select:focus, .form-group input:focus { border-color: #d4af37; }
    .sale-msg { border-radius: 8px; padding: 10px 14px; font-size: 13px; margin-top: 12px; display: none; }
    .sale-ok  { background:#0a2a0a; border:1px solid #1a5a1a; color:#6dbf6d; }
    .sale-err { background:#2a0a0a; border:1px solid #5a1a1a; color:#ff6b6b; }

    /* ── Type tabs ──────────────────────────────────────────────────── */
    .tabs { display: flex; gap: 6px; margin-bottom: 12px; flex-wrap: wrap; }
    .tab { padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; cursor: pointer;
           border: 1px solid #333; background: transparent; color: #666; min-height: 34px;
           touch-action: manipulation; -webkit-user-select: none; user-select: none; transition: all 0.15s; }
    .tab.active { background: #1c1800; color: #d4af37; border-color: #d4af37; }

    /* ── Table ──────────────────────────────────────────────────────── */
    .table-wrap { background: #111; border: 1px solid #2a2a2a; border-radius: 10px; overflow: hidden; margin-bottom: 24px; overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; min-width: 420px; }
    th {
      background: #161616; color: #555; font-size: 11px; font-weight: 600;
      text-transform: uppercase; letter-spacing: 0.5px; padding: 10px 14px;
      text-align: left; white-space: nowrap; cursor: pointer; user-select: none; -webkit-user-select: none;
    }
    th:hover { color: #aaa; }
    th .sort-icon { color: #d4af37; margin-left: 4px; }
    td { padding: 10px 14px; font-size: 13px; border-top: 1px solid #1a1a1a; }
    tr:hover td { background: #161616; }
    .empty-row td { color: #444; text-align: center; padding: 30px; }
    .badge { border-radius: 4px; padding: 2px 8px; font-size: 11px; font-weight: 600; }
    .badge-green { background:#0a2a0a; color:#6dbf6d; }
    .badge-gold  { background:#2a2000; color:#d4af37; }
    .badge-blue  { background:#0a1a2a; color:#6db4df; }

    /* ── Pagination ─────────────────────────────────────────────────── */
    .pagination { display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; border-top: 1px solid #1a1a1a; }
    .page-info { font-size: 12px; color: #555; }
    .page-btns { display: flex; gap: 6px; }
    .page-btn {
      padding: 6px 14px; border-radius: 6px; font-size: 12px; font-weight: 600;
      cursor: pointer; border: 1px solid #333; background: transparent; color: #888; min-height: 34px;
      touch-action: manipulation; -webkit-appearance: none; appearance: none;
    }
    .page-btn:hover:not(:disabled) { color: #d4af37; border-color: #d4af37; }
    .page-btn:disabled { opacity: 0.3; cursor: not-allowed; }

    /* ── Two columns ────────────────────────────────────────────────── */
    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

    @media (max-width: 900px) {
      .cards { grid-template-columns: repeat(2,1fr); }
      .two-col { grid-template-columns: 1fr; }
    }
    @media (max-width: 480px) {
      .card-value { font-size: 17px; }
      .filter-bar { flex-direction: column; align-items: stretch; }
    }
  </style>
</head>
<body>

<header>
  <h1>📊 Sales Report</h1>
  <a href="dashboard.php" class="btn btn-outline">← Dashboard</a>
</header>

<div class="page">

  <!-- ── Filter Bar ──────────────────────────────────────────────────── -->
  <div class="filter-bar">
    <span class="filter-label">Period:</span>
    <div class="quick-btns">
      <button class="btn btn-outline btn-sm active" onclick="setQuick('today',this)">Today</button>
      <button class="btn btn-outline btn-sm" onclick="setQuick('week',this)">7 Days</button>
      <button class="btn btn-outline btn-sm" onclick="setQuick('month',this)">30 Days</button>
      <button class="btn btn-outline btn-sm" onclick="setQuick('quarter',this)">90 Days</button>
      <button class="btn btn-outline btn-sm" onclick="setQuick('all',this)">All Time</button>
    </div>
    <div class="divider"></div>
    <span class="filter-label">Custom:</span>
    <div class="date-inputs">
      <input type="date" id="date-from" onchange="applyFilters()">
      <span style="color:#555;">→</span>
      <input type="date" id="date-to" onchange="applyFilters()">
    </div>
    <div class="divider"></div>
    <div class="search-wrap">
      <input class="search-input" type="search" id="search-box" placeholder="🔍  Search product or customer…" oninput="debounceSearch()">
    </div>
    <button class="btn btn-outline btn-sm" onclick="clearFilters()">✕ Clear</button>
  </div>

  <!-- ── Summary Cards ───────────────────────────────────────────────── -->
  <div class="cards">
    <div class="card"><div class="card-label">Today</div><div class="card-value" id="rev-today">—</div><div class="card-sub" id="cnt-today">loading…</div></div>
    <div class="card"><div class="card-label">This Week</div><div class="card-value" id="rev-week">—</div><div class="card-sub" id="cnt-week"></div></div>
    <div class="card"><div class="card-label">This Month</div><div class="card-value" id="rev-month">—</div><div class="card-sub" id="cnt-month"></div></div>
    <div class="card"><div class="card-label">All Time</div><div class="card-value" id="rev-all">—</div><div class="card-sub" id="cnt-all"></div></div>
  </div>

  <!-- ── Chart ───────────────────────────────────────────────────────── -->
  <div class="chart-wrap">
    <div class="section-header">
      <div class="section-title">Daily Revenue</div>
      <span class="record-count" id="chart-range">Last 30 days</span>
    </div>
    <div class="chart-bars" id="chart-bars"><div style="color:#444;font-size:13px;">Loading…</div></div>
  </div>

  <!-- ── Quick Sale ──────────────────────────────────────────────────── -->
  <div class="quick-sale">
    <div class="section-title" style="margin-bottom:16px;">Record Walk-in Sale</div>
    <div class="form-row">
      <div class="form-group" style="flex:3;min-width:200px;">
        <label>Product</label>
        <select id="sale-product" onchange="autoPrice()">
          <option value="">— Select product —</option>
          <?php foreach ($products as $p):
            $price = $priceOverrides[$p['name']] ?? (int)($p['price'] ?? 0);
          ?>
            <option value="<?= htmlspecialchars($p['name']) ?>" data-price="<?= $price ?>"><?= htmlspecialchars($p['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="max-width:100px;">
        <label>Qty</label>
        <input type="number" id="sale-qty" value="1" min="1" max="999">
      </div>
      <div class="form-group" style="max-width:160px;">
        <label>Price (FCFA)</label>
        <input type="number" id="sale-price" placeholder="0" min="0">
      </div>
      <div class="form-group" style="flex:2;min-width:140px;">
        <label>Note (optional)</label>
        <input type="text" id="sale-note" placeholder="e.g. market, cash">
      </div>
      <div class="form-group" style="max-width:140px;">
        <label>&nbsp;</label>
        <button class="btn btn-gold" id="sale-btn" onclick="recordSale()">Record Sale</button>
      </div>
    </div>
    <div class="sale-msg" id="sale-msg"></div>
  </div>

  <!-- ── Top Selling Products ────────────────────────────────────────── -->
  <div class="section-header">
    <div class="section-title">Top Selling Products</div>
    <div style="display:flex;gap:8px;">
      <button class="btn btn-outline btn-sm active" id="sort-units" onclick="setSort('units',this)">Sort: Units</button>
      <button class="btn btn-outline btn-sm" id="sort-revenue" onclick="setSort('revenue',this)">Sort: Revenue</button>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th style="width:36px;">#</th>
          <th onclick="setSort('name',null)">Product <span class="sort-icon" id="sort-name-icon"></span></th>
          <th onclick="setSort('units',document.getElementById('sort-units'))">Units Sold <span class="sort-icon" id="sort-units-icon">▼</span></th>
          <th onclick="setSort('revenue',document.getElementById('sort-revenue'))">Revenue (Walk-in) <span class="sort-icon" id="sort-revenue-icon"></span></th>
        </tr>
      </thead>
      <tbody id="top-table"><tr class="empty-row"><td colspan="4">Loading…</td></tr></tbody>
    </table>
  </div>

  <!-- ── Recent Sales (Walk-in + Orders) ─────────────────────────────── -->
  <div class="section-header">
    <div class="section-title">Recent Sales</div>
    <span class="record-count" id="sales-count"></span>
  </div>

  <div class="tabs">
    <button class="tab active" onclick="setType('all',this)">All Sales</button>
    <button class="tab" onclick="setType('walkin',this)">Walk-in Only</button>
    <button class="tab" onclick="setType('orders',this)">Website Orders Only</button>
  </div>

  <!-- Walk-in table -->
  <div id="walk-section">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th onclick="localSort('walk','product_name',this)">Product <span class="sort-icon"></span></th>
            <th onclick="localSort('walk','quantity',this)">Qty <span class="sort-icon"></span></th>
            <th onclick="localSort('walk','unit_price',this)">Unit Price <span class="sort-icon"></span></th>
            <th onclick="localSort('walk','total',this)">Total <span class="sort-icon"></span></th>
            <th onclick="localSort('walk','sold_at',this)">Date <span class="sort-icon"></span></th>
            <th>Note</th>
            <th></th>
          </tr>
        </thead>
        <tbody id="walk-table"><tr class="empty-row"><td colspan="6">Loading…</td></tr></tbody>
      </table>
      <div class="pagination">
        <span class="page-info" id="walk-page-info"></span>
        <div class="page-btns">
          <button class="page-btn" id="walk-prev" onclick="changePage('walk',-1)">← Prev</button>
          <button class="page-btn" id="walk-next" onclick="changePage('walk',+1)">Next →</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Orders table -->
  <div id="orders-section">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th onclick="localSort('orders','order_ref',this)">Ref <span class="sort-icon"></span></th>
            <th onclick="localSort('orders','customer_name',this)">Customer <span class="sort-icon"></span></th>
            <th>Payment</th>
            <th onclick="localSort('orders','total',this)">Total <span class="sort-icon"></span></th>
            <th onclick="localSort('orders','completed_at',this)">Date <span class="sort-icon"></span></th>
          </tr>
        </thead>
        <tbody id="orders-table"><tr class="empty-row"><td colspan="5">Loading…</td></tr></tbody>
      </table>
      <div class="pagination">
        <span class="page-info" id="orders-page-info"></span>
        <div class="page-btns">
          <button class="page-btn" id="orders-prev" onclick="changePage('orders',-1)">← Prev</button>
          <button class="page-btn" id="orders-next" onclick="changePage('orders',+1)">Next →</button>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
// ── State ────────────────────────────────────────────────────────────────────
const state = {
  from: '', to: '', search: '', sort: 'units', type: 'all',
  walkPage: 1, ordersPage: 1, perPage: 25,
  localSorts: { walk: { col: 'sold_at', dir: -1 }, orders: { col: 'completed_at', dir: -1 } },
  data: null,
};

// ── Helpers ──────────────────────────────────────────────────────────────────
const fmt = n => Number(n).toLocaleString('fr-CM') + ' FCFA';
const fmtDate = s => {
  if (!s) return '—';
  const d = new Date(s);
  return d.toLocaleDateString('en-GB', {day:'2-digit',month:'short',year:'numeric'}) + ' ' +
         d.toLocaleTimeString('en-GB', {hour:'2-digit',minute:'2-digit'});
};
let searchTimer;

// ── Quick period buttons ──────────────────────────────────────────────────────
function setQuick(period, btn) {
  document.querySelectorAll('.quick-btns .btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  const now = new Date();
  const pad = n => String(n).padStart(2,'0');
  const iso = d => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
  const today = iso(now);
  let from = '', to = today;
  if (period === 'today')   { from = today; }
  else if (period === 'week')    { const d = new Date(now); d.setDate(d.getDate()-6);  from = iso(d); }
  else if (period === 'month')   { const d = new Date(now); d.setDate(d.getDate()-29); from = iso(d); }
  else if (period === 'quarter') { const d = new Date(now); d.setDate(d.getDate()-89); from = iso(d); }
  else if (period === 'all')     { from = ''; to = ''; }
  document.getElementById('date-from').value = from;
  document.getElementById('date-to').value   = to;
  state.from = from; state.to = to;
  state.walkPage = 1; state.ordersPage = 1;
  loadData();
}

function applyFilters() {
  document.querySelectorAll('.quick-btns .btn').forEach(b => b.classList.remove('active'));
  state.from = document.getElementById('date-from').value;
  state.to   = document.getElementById('date-to').value;
  state.walkPage = 1; state.ordersPage = 1;
  loadData();
}

function clearFilters() {
  state.from = ''; state.to = ''; state.search = '';
  document.getElementById('date-from').value = '';
  document.getElementById('date-to').value   = '';
  document.getElementById('search-box').value = '';
  document.querySelectorAll('.quick-btns .btn').forEach(b => b.classList.remove('active'));
  document.querySelector('.quick-btns .btn').classList.add('active');
  state.walkPage = 1; state.ordersPage = 1;
  loadData();
}

function debounceSearch() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    state.search = document.getElementById('search-box').value.trim();
    state.walkPage = 1; state.ordersPage = 1;
    loadData();
  }, 350);
}

// ── Sort ──────────────────────────────────────────────────────────────────────
function setSort(by, btn) {
  state.sort = by;
  document.querySelectorAll('[id^=sort-][id$=-icon]').forEach(el => el.textContent = '');
  if (by === 'units')   { document.getElementById('sort-units-icon').textContent   = '▼'; }
  if (by === 'revenue') { document.getElementById('sort-revenue-icon').textContent = '▼'; }
  if (by === 'name')    { document.getElementById('sort-name-icon').textContent    = '▼'; }
  document.querySelectorAll('[id^=sort-units],[id^=sort-revenue]').forEach(b => b.classList.remove('active'));
  if (btn) btn.classList.add('active');
  if (state.data) renderTop(state.data.top_products);
  else loadData();
}

function localSort(table, col, th) {
  const s = state.localSorts[table];
  s.dir = (s.col === col) ? -s.dir : -1;
  s.col = col;
  // Update icon
  th.closest('thead').querySelectorAll('.sort-icon').forEach(el => el.textContent = '');
  th.querySelector('.sort-icon').textContent = s.dir === -1 ? '▼' : '▲';
  if (state.data) {
    if (table === 'walk')   renderWalk(state.data.walk_in);
    if (table === 'orders') renderOrders(state.data.orders);
  }
}

// ── Type tabs ─────────────────────────────────────────────────────────────────
function setType(type, btn) {
  state.type = type;
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('walk-section').style.display   = type === 'orders'  ? 'none' : 'block';
  document.getElementById('orders-section').style.display = type === 'walkin'  ? 'none' : 'block';
  state.walkPage = 1; state.ordersPage = 1;
  loadData();
}

// ── Pagination ────────────────────────────────────────────────────────────────
function changePage(table, delta) {
  if (table === 'walk')   state.walkPage   = Math.max(1, state.walkPage   + delta);
  if (table === 'orders') state.ordersPage = Math.max(1, state.ordersPage + delta);
  loadData();
}

// ── Load Data ─────────────────────────────────────────────────────────────────
async function loadData() {
  const params = new URLSearchParams({
    from: state.from, to: state.to, search: state.search,
    sort: state.sort, type: state.type,
    page: state.type === 'walkin' ? state.walkPage : state.ordersPage,
    per_page: state.perPage,
  });

  try {
    const res  = await fetch('/api/sales.php?' + params);
    const data = await res.json();
    if (data.error) { console.error(data.error); return; }
    state.data = data;
    renderCards(data.stats);
    renderChart(data.daily_revenue);
    renderTop(data.top_products);
    renderWalk(data.walk_in);
    renderOrders(data.orders);
  } catch(e) { console.error(e); }
}

// ── Render: Cards ─────────────────────────────────────────────────────────────
function renderCards(stats) {
  for (const [key, label] of [['today','Today'],['week','Week'],['month','Month'],['all','All']]) {
    const s = stats[key] || { revenue:0, orders:0 };
    document.getElementById('rev-' + key).textContent = fmt(s.revenue);
    document.getElementById('cnt-' + key).textContent = s.orders + (s.orders===1?' sale':' sales');
  }
}

// ── Render: Chart ─────────────────────────────────────────────────────────────
function renderChart(daily) {
  const days = Object.keys(daily || {});
  if (!days.length) {
    document.getElementById('chart-bars').innerHTML = '<span style="color:#444;font-size:13px;padding:20px 0;">No data for selected period.</span>';
    return;
  }
  const maxVal = Math.max(...Object.values(daily), 1);
  document.getElementById('chart-bars').innerHTML = days.map(d => {
    const v = daily[d];
    const h = Math.max(3, Math.round((v / maxVal) * 110));
    return `<div class="bar-col" title="${d}: ${fmt(v)}">
      <div class="bar" style="height:${h}px;"></div>
      <div class="bar-label">${d.slice(5)}</div>
    </div>`;
  }).join('');
  const range = days.length > 1 ? `${days[0]} → ${days[days.length-1]}` : days[0] || '';
  document.getElementById('chart-range').textContent = range;
}

// ── Render: Top Products ──────────────────────────────────────────────────────
function renderTop(products) {
  if (!products) return;
  let sorted = [...products];
  if (state.sort === 'revenue')  sorted.sort((a,b) => b.revenue - a.revenue);
  else if (state.sort === 'name') sorted.sort((a,b) => a.product_name.localeCompare(b.product_name));
  else                            sorted.sort((a,b) => b.units_sold - a.units_sold);

  document.getElementById('top-table').innerHTML = sorted.length
    ? sorted.map((r,i) => `<tr>
        <td style="color:#555;">${i+1}</td>
        <td title="${r.product_name}">${r.product_name.slice(0,55)}${r.product_name.length>55?'…':''}</td>
        <td><span class="badge badge-gold">${r.units_sold} units</span></td>
        <td style="color:#d4af37;font-weight:700;">${r.total_revenue > 0 ? fmt(r.total_revenue) : '—'}</td>
      </tr>`).join('')
    : '<tr class="empty-row"><td colspan="4">No sales data for this filter.</td></tr>';
}

// ── Render: Walk-in Table ─────────────────────────────────────────────────────
function renderWalk(wi) {
  if (!wi) return;
  const { col, dir } = state.localSorts.walk;
  const rows = [...(wi.rows || [])].sort((a,b) => {
    const av = a[col] ?? ''; const bv = b[col] ?? '';
    return dir * (typeof av === 'number' ? av - bv : String(av).localeCompare(String(bv)));
  });
  document.getElementById('walk-table').innerHTML = rows.length
    ? rows.map(r => `<tr>
        <td title="${r.product_name}">${r.product_name.slice(0,45)}${r.product_name.length>45?'…':''}</td>
        <td>${r.quantity}</td>
        <td>${fmt(r.unit_price)}</td>
        <td style="color:#d4af37;font-weight:700;">${fmt(r.total)}</td>
        <td style="color:#555;">${fmtDate(r.sold_at)}</td>
        <td style="color:#555;">${r.note || '—'}</td>
        <td><button onclick="cancelSale(${r.id})" style="background:transparent;border:1px solid #3a1a1a;color:#e05050;border-radius:5px;padding:4px 10px;font-size:11px;cursor:pointer;touch-action:manipulation;min-height:30px;">✕ Cancel</button></td>
      </tr>`).join('')
    : '<tr class="empty-row"><td colspan="7">No walk-in sales for this filter.</td></tr>';

  const total = wi.total || 0;
  const pages = Math.ceil(total / state.perPage) || 1;
  document.getElementById('walk-page-info').textContent = `${total} record${total!==1?'s':''} — Page ${state.walkPage} of ${pages}`;
  document.getElementById('walk-prev').disabled = state.walkPage <= 1;
  document.getElementById('walk-next').disabled = state.walkPage >= pages;
}

// ── Render: Orders Table ──────────────────────────────────────────────────────
function renderOrders(ord) {
  if (!ord) return;
  const { col, dir } = state.localSorts.orders;
  const rows = [...(ord.rows || [])].sort((a,b) => {
    const av = a[col] ?? ''; const bv = b[col] ?? '';
    return dir * (typeof av === 'number' ? av - bv : String(av).localeCompare(String(bv)));
  });
  document.getElementById('orders-table').innerHTML = rows.length
    ? rows.map(r => `<tr>
        <td style="color:#555;font-size:12px;">${r.order_ref||'—'}</td>
        <td>${r.customer_name||'Anonymous'}</td>
        <td><span class="badge badge-green">${r.payment_method||'—'}</span></td>
        <td style="color:#d4af37;font-weight:700;">${fmt(r.total)}</td>
        <td style="color:#555;">${fmtDate(r.completed_at)}</td>
      </tr>`).join('')
    : '<tr class="empty-row"><td colspan="5">No orders for this filter.</td></tr>';

  const total = ord.total || 0;
  const pages = Math.ceil(total / state.perPage) || 1;
  document.getElementById('orders-page-info').textContent = `${total} record${total!==1?'s':''} — Page ${state.ordersPage} of ${pages}`;
  document.getElementById('orders-prev').disabled = state.ordersPage <= 1;
  document.getElementById('orders-next').disabled = state.ordersPage >= pages;
}

// ── Record walk-in sale ───────────────────────────────────────────────────────
function autoPrice() {
  const sel = document.getElementById('sale-product');
  const price = sel.options[sel.selectedIndex]?.dataset?.price;
  if (price) document.getElementById('sale-price').value = price;
}

async function recordSale() {
  const product = document.getElementById('sale-product').value.trim();
  const qty     = parseInt(document.getElementById('sale-qty').value) || 1;
  const price   = parseInt(document.getElementById('sale-price').value) || 0;
  const note    = document.getElementById('sale-note').value.trim();
  const msg     = document.getElementById('sale-msg');
  const btn     = document.getElementById('sale-btn');

  if (!product) { showMsg('Please select a product.', false); return; }
  if (!price)   { showMsg('Please enter a price.', false); return; }

  btn.disabled = true; btn.textContent = 'Saving…';
  try {
    const res  = await fetch('/api/sales.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ product, quantity: qty, price, note }),
    });
    const data = await res.json();
    if (data.success) {
      showMsg('✓ Recorded: ' + qty + ' × ' + product.slice(0,50) + ' = ' + fmt(data.total), true);
      document.getElementById('sale-product').value = '';
      document.getElementById('sale-qty').value     = '1';
      document.getElementById('sale-price').value   = '';
      document.getElementById('sale-note').value    = '';
      loadData();
    } else { showMsg('Error: ' + (data.error||'Failed'), false); }
  } catch(e) { showMsg('Network error', false); }
  btn.disabled = false; btn.textContent = 'Record Sale';
}

function showMsg(text, ok) {
  const el = document.getElementById('sale-msg');
  el.textContent = text;
  el.className = 'sale-msg ' + (ok ? 'sale-ok' : 'sale-err');
  el.style.display = 'block';
  if (ok) setTimeout(() => el.style.display = 'none', 4000);
}

// ── Cancel walk-in sale ───────────────────────────────────────────────────────
async function cancelSale(id) {
  if (!confirm('Cancel this walk-in sale? Stock will be restored.')) return;
  try {
    const res  = await fetch('/api/sales.php?id=' + id, { method: 'DELETE' });
    const data = await res.json();
    if (data.success) { showMsg('✓ Sale cancelled and stock restored.', true); loadData(); }
    else showMsg('Error: ' + (data.error || 'Failed'), false);
  } catch(e) { showMsg('Network error', false); }
}

// ── Init ──────────────────────────────────────────────────────────────────────
// Default: today
setQuick('today', document.querySelector('.quick-btns .btn'));
</script>
</body>
</html>
