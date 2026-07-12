<?php
session_start();
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../api/db.php';

$jsonPath = __DIR__ . '/../api/products-list.json';
$products = [];
if (file_exists($jsonPath)) {
    $products = json_decode(file_get_contents($jsonPath), true) ?? [];
}

// Merge in custom products from DB
try {
    $pdoCustom = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $customRows = $pdoCustom->query('SELECT name, price FROM custom_products')->fetchAll(PDO::FETCH_ASSOC);
    $existingNames = array_column($products, 'name');
    foreach ($customRows as $row) {
        if (!in_array($row['name'], $existingNames)) {
            $products[] = ['name' => $row['name'], 'price' => (int)$row['price']];
        }
    }
} catch (Exception $e) { /* custom_products table optional */ }

// Apply live price overrides from DB
try {
    $pdoP = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $stmt = $pdoP->query('SELECT product_name, price FROM product_prices');
    $dbPrices = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $dbPrices[$row['product_name']] = (int)$row['price'];
    }
    foreach ($products as &$p) {
        if (isset($dbPrices[$p['name']])) {
            $p['price'] = $dbPrices[$p['name']];
        }
    }
    unset($p);
} catch (Exception $e) { /* price overrides optional */ }

// Load live stock from DB
try {
    $pdoS = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $stmtS = $pdoS->query('SELECT product_name, quantity FROM product_stock');
    $dbStock = [];
    foreach ($stmtS->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $dbStock[$row['product_name']] = (int)$row['quantity'];
    }
    foreach ($products as &$p) {
        if (isset($dbStock[$p['name']])) {
            $p['quantity'] = $dbStock[$p['name']];
        }
    }
    unset($p);
} catch (Exception $e) { /* stock optional */ }

usort($products, fn($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));

function fmt_price($n) {
    return number_format((float)$n, 0, '.', ' ') . ' FCFA';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Price Tags — American Select</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      background: #0a0a0a;
      color: #e0e0e0;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      min-height: 100vh;
      min-height: 100dvh;
      -webkit-overflow-scrolling: touch;
    }

    /* ── Header ─────────────────────────────────────────────── */
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
      flex-wrap: wrap;
      gap: 12px;
      position: sticky;
      top: 0;
      z-index: 100;
    }
    header h1 { color: #d4af37; font-size: 18px; font-weight: 700; letter-spacing: 1px; }
    .header-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
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
      gap: 6px;
      min-height: 44px;
      -webkit-tap-highlight-color: transparent;
      -webkit-appearance: none;
      appearance: none;
    }
    .btn-gold { background: #d4af37; color: #000; }
    .btn-gold:hover { background: #e8c547; }
    .btn-outline { background: transparent; color: #888; border: 1px solid #333; }
    .btn-outline:hover { color: #ccc; border-color: #555; }
    .btn-danger { background: transparent; color: #e05050; border: 1px solid #3a1a1a; }

    /* ── Controls ────────────────────────────────────────────── */
    .controls {
      max-width: 1100px;
      margin: 20px auto 0;
      padding: 0 16px;
      padding-left: calc(16px + env(safe-area-inset-left, 0px));
      padding-right: calc(16px + env(safe-area-inset-right, 0px));
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
    }
    .search-input {
      flex: 1;
      min-width: 220px;
      padding: 10px 14px;
      background: #1a1a1a;
      border: 1px solid #333;
      border-radius: 8px;
      color: #e0e0e0;
      font-size: 14px;
      -webkit-appearance: none;
      appearance: none;
      min-height: 44px;
    }
    .search-input::placeholder { color: #555; }
    .search-input:focus { outline: none; border-color: #d4af37; }
    .select-all-wrap {
      display: flex; align-items: center; gap: 8px;
      color: #888; font-size: 13px; cursor: pointer;
      -webkit-user-select: none; user-select: none;
    }
    .select-all-wrap input[type=checkbox] { width: 18px; height: 18px; accent-color: #d4af37; cursor: pointer; }
    .count-badge {
      background: #1e1e1e; border: 1px solid #2a2a2a;
      border-radius: 20px; padding: 4px 12px; font-size: 12px; color: #888;
    }

    /* ── Print mode toggle ───────────────────────────────────── */
    .mode-row {
      max-width: 1100px;
      margin: 12px auto 0;
      padding: 0 16px;
      padding-left: calc(16px + env(safe-area-inset-left, 0px));
      padding-right: calc(16px + env(safe-area-inset-right, 0px));
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
    }
    .mode-label { font-size: 12px; color: #666; margin-right: 4px; }
    .mode-btn {
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      border: 1px solid #333;
      background: transparent;
      color: #666;
      touch-action: manipulation;
      -webkit-user-select: none;
      user-select: none;
      min-height: 36px;
      -webkit-tap-highlight-color: transparent;
      -webkit-appearance: none;
      appearance: none;
      transition: background 0.15s, color 0.15s, border-color 0.15s;
    }
    .mode-btn.active {
      background: #1e1e1e;
      color: #d4af37;
      border-color: #3a3010;
    }
    .mode-hint { font-size: 11px; color: #444; }

    /* ── Tag Grid (screen) ──────────────────────────────────── */
    .tag-grid {
      max-width: 1100px;
      margin: 16px auto 40px;
      padding: 0 16px;
      padding-left: calc(16px + env(safe-area-inset-left, 0px));
      padding-right: calc(16px + env(safe-area-inset-right, 0px));
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
      gap: 12px;
    }
    .tag-card {
      background: #161616;
      border: 1px solid #2a2a2a;
      border-radius: 10px;
      padding: 14px 14px 12px;
      display: flex;
      flex-direction: column;
      gap: 8px;
      cursor: pointer;
      transition: border-color 0.15s, background 0.15s;
      -webkit-tap-highlight-color: transparent;
      touch-action: manipulation;
    }
    .tag-card:hover { border-color: #444; background: #1c1c1c; }
    .tag-card.selected { border-color: #d4af37; background: #1c1810; }
    .tag-card-top { display: flex; align-items: flex-start; gap: 10px; }
    .tag-card-top input[type=checkbox] {
      margin-top: 3px; width: 16px; height: 16px;
      accent-color: #d4af37; cursor: pointer; flex-shrink: 0;
    }
    .tag-name { font-size: 13px; font-weight: 600; color: #ddd; line-height: 1.4; }
    .tag-price { font-size: 18px; font-weight: 800; color: #d4af37; letter-spacing: 0.5px; }
    .tag-store { font-size: 10px; color: #555; letter-spacing: 1px; text-transform: uppercase; }
    .tag-qty { font-size: 11px; color: #555; }

    /* ── Empty ───────────────────────────────────────────────── */
    .empty { text-align: center; color: #444; padding: 60px 20px; font-size: 15px; }

    /* ════════════════════════════════════════════════════════════
       PRINT — A4 SHEET MODE (default)
    ════════════════════════════════════════════════════════════ */
    @media print {
      body.mode-sheet { font-family: Arial, sans-serif; background: #fff !important; color: #000 !important; }
      body.mode-sheet header,
      body.mode-sheet .controls,
      body.mode-sheet .mode-row,
      body.mode-sheet .no-print { display: none !important; }

      body.mode-sheet .tag-grid {
        max-width: 100%; margin: 0; padding: 0;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 6mm;
      }
      body.mode-sheet @page { size: A4; margin: 10mm; }

      body.mode-sheet .tag-card { display: none !important; }
      body.mode-sheet .tag-card.selected {
        display: flex !important;
        background: #fff !important;
        border: 1.5px solid #000 !important;
        border-radius: 6px;
        padding: 7mm 7mm 6mm;
        gap: 4px;
        page-break-inside: avoid;
        break-inside: avoid;
        flex-direction: column;
      }
      body.mode-sheet .tag-card-top input[type=checkbox] { display: none !important; }
      body.mode-sheet .tag-card-top { gap: 0; }
      body.mode-sheet .tag-name { font-size: 10pt; font-weight: 700; color: #000 !important; line-height: 1.35; }
      body.mode-sheet .tag-price { font-size: 16pt; font-weight: 900; color: #000 !important; }
      body.mode-sheet .tag-store { font-size: 7pt; color: #555; letter-spacing: 1px; text-transform: uppercase; margin-top: 2px; }
      body.mode-sheet .tag-qty { display: none !important; }
      body.mode-sheet .empty { display: none !important; }

      /* ════════════════════════════════════════════════════════
         PRINT — SHELF MODE (Munbyn 2" × 1" shelf labels)
      ════════════════════════════════════════════════════════ */
      body.mode-shelf { font-family: Arial, sans-serif; background: #fff !important; color: #000 !important; }
      body.mode-shelf header,
      body.mode-shelf .controls,
      body.mode-shelf .mode-row,
      body.mode-shelf .no-print { display: none !important; }

      body.mode-shelf .tag-grid {
        max-width: 100%; margin: 0; padding: 0;
        display: block;
        page-break-after: avoid;
        break-after: avoid;
      }
      body.mode-shelf .tag-card { display: none !important; }
      body.mode-shelf .tag-card.selected {
        display: flex !important;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #fff !important;
        border: none !important;
        border-radius: 0;
        padding: 1mm 3mm;
        width: 51mm;
        height: 25mm;
        min-height: 25mm;
        max-height: 25mm;
        overflow: hidden;
        box-sizing: border-box;
        text-align: center;
        page-break-after: always;
        break-after: page;
      }
      body.mode-shelf .tag-card.selected:last-child {
        page-break-after: avoid;
        break-after: avoid;
      }
      body.mode-shelf .tag-card-top { display: none !important; }
      body.mode-shelf .tag-price {
        font-size: 15pt;
        font-weight: 900;
        color: #000 !important;
        line-height: 1.1;
        word-break: break-all;
      }
      body.mode-shelf .tag-name {
        font-size: 6.5pt;
        font-weight: 700;
        color: #000 !important;
        line-height: 1.2;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        margin-top: 1mm;
      }
      body.mode-shelf .tag-store {
        font-size: 4pt;
        color: #777;
        letter-spacing: 1px;
        text-transform: uppercase;
        margin-top: 0.5mm;
      }
      body.mode-shelf .tag-qty { display: none !important; }
      body.mode-shelf .empty { display: none !important; }

      /* ════════════════════════════════════════════════════════
         PRINT — 3×2 MODE (Munbyn 3" × 2" product labels)
      ════════════════════════════════════════════════════════ */
      body.mode-3x2 { font-family: Arial, sans-serif; background: #fff !important; color: #000 !important; }
      body.mode-3x2 header,
      body.mode-3x2 .controls,
      body.mode-3x2 .mode-row,
      body.mode-3x2 .no-print { display: none !important; }

      body.mode-3x2 .tag-grid {
        max-width: 100%; margin: 0; padding: 0;
        display: block;
        page-break-after: avoid;
        break-after: avoid;
      }
      body.mode-3x2 .tag-card { display: none !important; }
      body.mode-3x2 .tag-card.selected {
        display: block !important;
        position: relative;
        background: #fff !important;
        border: 1pt solid #000 !important;
        border-radius: 0;
        padding: 4mm 5mm;
        width: 76mm;
        height: 51mm;
        min-height: 51mm;
        max-height: 51mm;
        page-break-after: always;
        break-after: page;
        overflow: hidden;
        box-sizing: border-box;
      }
      body.mode-3x2 .tag-card.selected:last-child {
        page-break-after: avoid;
        break-after: avoid;
      }
      body.mode-3x2 .tag-card-top { display: none !important; }
      body.mode-3x2 .tag-name {
        order: 1;
        font-size: 9pt;
        font-weight: 700;
        color: #000 !important;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
      }
      body.mode-3x2 .tag-price {
        order: 2;
        font-size: 26pt;
        font-weight: 900;
        color: #000 !important;
        letter-spacing: 0.5px;
        line-height: 1;
        margin: 2mm 0 1mm;
      }
      body.mode-3x2 .tag-store {
        order: 3;
        font-size: 6.5pt;
        color: #555;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        border-top: 0.5pt solid #bbb;
        padding-top: 1.5mm;
      }
      body.mode-3x2 .tag-qty { display: none !important; }
      body.mode-3x2 .empty { display: none !important; }

      /* ════════════════════════════════════════════════════════
         PRINT — PHOMEMO MODE (Phomemo M110 40mm × 30mm labels)
         Not sent to a Windows printer — used only as a preview;
         actual output comes from "Download Labels (PNG)" below,
         printed via the Phomemo phone app.
      ════════════════════════════════════════════════════════ */
      body.mode-phomemo { font-family: Arial, sans-serif; background: #fff !important; color: #000 !important; }
      body.mode-phomemo header,
      body.mode-phomemo .controls,
      body.mode-phomemo .mode-row,
      body.mode-phomemo .no-print { display: none !important; }

      body.mode-phomemo .tag-grid {
        max-width: 100%; margin: 0; padding: 0;
        display: block;
        page-break-after: avoid;
        break-after: avoid;
      }
      body.mode-phomemo .tag-card { display: none !important; }
      body.mode-phomemo .tag-card.selected {
        display: flex !important;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #fff !important;
        border: none !important;
        border-radius: 0;
        padding: 1.5mm 2.5mm;
        width: 40mm;
        height: 30mm;
        min-height: 30mm;
        max-height: 30mm;
        overflow: hidden;
        box-sizing: border-box;
        text-align: center;
        page-break-after: always;
        break-after: page;
      }
      body.mode-phomemo .tag-card.selected:last-child {
        page-break-after: avoid;
        break-after: avoid;
      }
      body.mode-phomemo .tag-card-top { display: none !important; }
      body.mode-phomemo .tag-price {
        font-size: 15pt;
        font-weight: 900;
        color: #000 !important;
        line-height: 1.1;
        word-break: break-all;
      }
      body.mode-phomemo .tag-name {
        font-size: 6.5pt;
        font-weight: 700;
        color: #000 !important;
        line-height: 1.25;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        margin-top: 1mm;
      }
      body.mode-phomemo .tag-store {
        font-size: 4.5pt;
        color: #777;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        margin-top: 0.5mm;
      }
      body.mode-phomemo .tag-qty { display: none !important; }
      body.mode-phomemo .empty { display: none !important; }
    }
  </style>

  <!-- Dynamic @page size injected by JS based on selected mode -->
  <style id="page-size-style">
    @page { size: A4; margin: 10mm; }
  </style>
  <script src="https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js"></script>
</head>
<body class="mode-sheet">

<header>
  <h1>Price Tags</h1>
  <div class="header-actions">
    <button class="btn btn-gold no-print" id="print-btn" onclick="printSelected()">Print Selected</button>
    <button class="btn btn-outline no-print" id="munbyn-btn" onclick="printToMunbyn()" style="color:#6dbf6d;border-color:#1a3020;">&#9889; Print to Munbyn</button>
    <button class="btn btn-outline no-print" id="pdf-btn" onclick="downloadPNGs()" style="color:#7b9fd4;border-color:#1a2a40;">&#11015; Download Labels (PNG)</button>
    <a href="dashboard.php" class="btn btn-outline no-print">Dashboard</a>
    <a href="logout.php" class="btn btn-danger no-print">Logout</a>
  </div>
</header>

<div class="controls no-print">
  <input class="search-input" type="search" id="search" placeholder="Search products..." oninput="filterTags()">
  <label class="select-all-wrap">
    <input type="checkbox" id="select-all" onchange="toggleSelectAll(this.checked)">
    Select All
  </label>
  <span class="count-badge" id="count-badge">0 selected</span>
</div>

<div class="mode-row no-print">
  <span class="mode-label">Print on:</span>
  <button class="mode-btn active" id="mode-sheet" onclick="setMode('sheet')">A4 Sheet (3 per row)</button>
  <button class="mode-btn" id="mode-shelf" onclick="setMode('shelf')">2"×1" Label (Shelf)</button>
  <button class="mode-btn" id="mode-3x2" onclick="setMode('3x2')">3"×2" Label (Product)</button>
  <button class="mode-btn" id="mode-phomemo" onclick="setMode('phomemo')">Phomemo M110 (40×30mm)</button>
  <span class="mode-hint" id="mode-hint">Multiple tags per page, cut apart after printing</span>
</div>

<div class="tag-grid" id="tag-grid">
<?php foreach ($products as $p):
  $name  = htmlspecialchars($p['name'] ?? '');
  $price = isset($p['price']) ? fmt_price($p['price']) : '';
  $qty   = isset($p['quantity']) ? (int)$p['quantity'] : 0;
  if (!$name || !$price) continue;
?>
  <div class="tag-card" data-name="<?= strtolower($name) ?>" onclick="toggleCard(this)">
    <div class="tag-card-top">
      <input type="checkbox" class="tag-check" onclick="event.stopPropagation(); syncCheck(this)" onchange="updateCount()">
    </div>
    <div class="tag-price"><?= $price ?></div>
    <div class="tag-name"><?= $name ?></div>
    <div class="tag-store">American Select</div>
  </div>
<?php endforeach; ?>
</div>

<div class="empty" id="empty-msg" style="display:none;">No products match your search.</div>

<script>
let currentMode = 'sheet';

function setMode(mode) {
  currentMode = mode;
  document.body.className = 'mode-' + mode;
  document.getElementById('mode-sheet').classList.toggle('active', mode === 'sheet');
  document.getElementById('mode-shelf').classList.toggle('active', mode === 'shelf');
  document.getElementById('mode-3x2').classList.toggle('active', mode === '3x2');
  document.getElementById('mode-phomemo').classList.toggle('active', mode === 'phomemo');

  const pageStyle = document.getElementById('page-size-style');
  const hint = document.getElementById('mode-hint');
  const munbynBtn = document.getElementById('munbyn-btn');

  if (mode === 'shelf') {
    pageStyle.textContent = '@page { size: 51mm 25mm; margin: 0; }';
    hint.textContent = 'One tag per label — load 2"×1" labels on your Munbyn';
  } else if (mode === '3x2') {
    pageStyle.textContent = '@page { size: 76mm 51mm; margin: 0; }';
    hint.textContent = 'One tag per label — load 3"×2" labels on your Munbyn';
  } else if (mode === 'phomemo') {
    pageStyle.textContent = '@page { size: 40mm 30mm; margin: 0; }';
    hint.textContent = 'Not printed from here — use "Download Labels (PNG)" and print from the Phomemo app on your phone';
  } else {
    pageStyle.textContent = '@page { size: A4; margin: 10mm; }';
    hint.textContent = 'Multiple tags per page, cut apart after printing';
  }

  // Munbyn direct-print only supports its two physical label modes
  munbynBtn.style.display = (mode === 'shelf' || mode === '3x2') ? '' : 'none';
}

function toggleCard(card) {
  const cb = card.querySelector('.tag-check');
  cb.checked = !cb.checked;
  card.classList.toggle('selected', cb.checked);
  updateCount();
}

function syncCheck(cb) {
  cb.closest('.tag-card').classList.toggle('selected', cb.checked);
  updateCount();
}

function toggleSelectAll(checked) {
  document.querySelectorAll('.tag-card:not([style*="display: none"])').forEach(card => {
    const cb = card.querySelector('.tag-check');
    cb.checked = checked;
    card.classList.toggle('selected', checked);
  });
  updateCount();
}

function updateCount() {
  const n = document.querySelectorAll('.tag-card.selected').length;
  document.getElementById('count-badge').textContent = n + ' selected';
  document.getElementById('print-btn').textContent =
    n > 0 ? 'Print ' + n + ' Tag' + (n !== 1 ? 's' : '') : 'Print Selected';
}

function filterTags() {
  const q = document.getElementById('search').value.toLowerCase().trim();
  let visible = 0;
  document.querySelectorAll('.tag-card').forEach(card => {
    const match = !q || card.dataset.name.includes(q);
    card.style.display = match ? '' : 'none';
    if (match) visible++;
  });
  document.getElementById('empty-msg').style.display = visible === 0 ? 'block' : 'none';
  document.getElementById('select-all').checked = false;
}

function printSelected() {
  const n = document.querySelectorAll('.tag-card.selected').length;
  if (n === 0) { alert('Select at least one product to print.'); return; }
  window.print();
}

updateCount();

// ── Download PNG labels for Munbyn app import ──────────
async function downloadPNGs() {
  const selected = [...document.querySelectorAll('.tag-card.selected')];
  if (selected.length === 0) { alert('Select at least one product first.'); return; }

  const btn = document.getElementById('pdf-btn');
  btn.textContent = '⏳ Generating…';
  btn.disabled = true;

  const DPI = 203; // Munbyn ITPP130 resolution
  const mode = currentMode;

  // Label size in pixels at 203 DPI
  const MM_PER_IN = 25.4;
  let PW, PH;
  if (mode === '3x2') { PW = Math.round(3 * DPI); PH = Math.round(2 * DPI); } // 609x406
  else if (mode === 'phomemo') { PW = Math.round(40 / MM_PER_IN * DPI); PH = Math.round(30 / MM_PER_IN * DPI); } // 320x240
  else { PW = Math.round(2 * DPI); PH = Math.round(1 * DPI); } // shelf: 406x203

  const canvas = document.createElement('canvas');
  canvas.width = PW;
  canvas.height = PH;
  const ctx = canvas.getContext('2d');

  function wrapText(ctx, text, x, y, maxWidth, lineHeight, maxLines = Infinity) {
    const words = text.split(' ');
    let line = '';
    let lines = [];
    for (const word of words) {
      const test = line ? line + ' ' + word : word;
      if (ctx.measureText(test).width > maxWidth && line) {
        lines.push(line);
        line = word;
      } else { line = test; }
    }
    if (line) lines.push(line);
    lines = lines.slice(0, maxLines);
    lines.forEach((l, i) => ctx.fillText(l, x, y + i * lineHeight));
    return lines.length;
  }

  function drawLabel(price, name) {
    ctx.clearRect(0, 0, PW, PH);
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, PW, PH);

    const pad = Math.round(0.08 * DPI); // ~8% of inch padding

    if (mode === '3x2') {
      // Name at top
      ctx.fillStyle = '#000';
      ctx.font = `bold ${Math.round(0.1 * DPI)}px Arial`;
      const nameLines = wrapText(ctx, name, pad, Math.round(0.2 * DPI), PW - pad * 2, Math.round(0.13 * DPI));

      // Price in middle
      ctx.font = `bold ${Math.round(0.28 * DPI)}px Arial`;
      ctx.fillText(price, pad, Math.round(0.7 * DPI));

      // Divider
      ctx.strokeStyle = '#ccc'; ctx.lineWidth = 1;
      ctx.beginPath(); ctx.moveTo(pad, Math.round(0.78 * DPI)); ctx.lineTo(PW - pad, Math.round(0.78 * DPI)); ctx.stroke();

      // Store
      ctx.font = `${Math.round(0.07 * DPI)}px Arial`;
      ctx.fillStyle = '#555';
      ctx.fillText('AMERICAN SELECT', pad, Math.round(0.88 * DPI));
    } else if (mode === 'phomemo') {
      // 40mm×30mm: name top, price center, store bottom — all centered
      const cx = PW / 2;
      ctx.fillStyle = '#000';
      ctx.textAlign = 'center';
      ctx.font = `bold ${Math.round(0.09 * DPI)}px Arial`;
      const nameLineHeight = Math.round(0.12 * DPI);
      const nameLines = wrapText(ctx, name, cx, Math.round(0.16 * DPI), PW - pad * 2, nameLineHeight, 2);

      ctx.font = `bold ${Math.round(0.26 * DPI)}px Arial`;
      const priceY = Math.round(0.16 * DPI) + nameLines * nameLineHeight + Math.round(0.22 * DPI);
      ctx.fillText(price, cx, priceY);

      ctx.font = `${Math.round(0.055 * DPI)}px Arial`;
      ctx.fillStyle = '#777';
      ctx.fillText('AMERICAN SELECT', cx, PH - Math.round(0.08 * DPI));
    } else {
      // 2"×1" landscape: price centered top, name below, store at bottom
      const cx = PW / 2;
      ctx.fillStyle = '#000';
      ctx.font = `bold ${Math.round(0.34 * DPI)}px Arial`;
      ctx.textAlign = 'center';
      ctx.fillText(price, cx, Math.round(0.42 * DPI));

      ctx.font = `bold ${Math.round(0.09 * DPI)}px Arial`;
      const nameLineHeight = Math.round(0.13 * DPI);
      const nameLines = wrapText(ctx, name, cx, Math.round(0.62 * DPI), PW - pad * 2, nameLineHeight, 2);

      ctx.font = `${Math.round(0.055 * DPI)}px Arial`;
      ctx.fillStyle = '#777';
      ctx.fillText('AMERICAN SELECT', cx, Math.round(0.62 * DPI) + nameLines * nameLineHeight + Math.round(0.08 * DPI));
    }
    ctx.textAlign = 'left';
  }

  // Single label — direct download
  if (selected.length === 1) {
    const card = selected[0];
    const price = card.querySelector('.tag-price')?.textContent?.trim() || '';
    const name  = card.querySelector('.tag-name')?.textContent?.trim() || '';
    drawLabel(price, name);
    const a = document.createElement('a');
    a.href = canvas.toDataURL('image/png');
    a.download = 'label-' + name.substring(0, 30).replace(/[^a-z0-9]/gi, '_') + '.png';
    a.click();
  } else {
    // Multiple labels — zip them
    const zip = new JSZip();
    for (let i = 0; i < selected.length; i++) {
      const card = selected[i];
      const price = card.querySelector('.tag-price')?.textContent?.trim() || '';
      const name  = card.querySelector('.tag-name')?.textContent?.trim() || '';
      drawLabel(price, name);
      const blob = await new Promise(r => canvas.toBlob(r, 'image/png'));
      const fname = (i + 1) + '-' + name.substring(0, 25).replace(/[^a-z0-9]/gi, '_') + '.png';
      zip.file(fname, blob);
    }
    const zipBlob = await zip.generateAsync({ type: 'blob' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(zipBlob);
    a.download = 'labels-' + selected.length + '.zip';
    a.click();
  }

  btn.textContent = '⬇ Download Labels (PNG)';
  btn.disabled = false;
}

// ── Direct print to Munbyn via relay ─────────────────────
async function printToMunbyn() {
  const selected = [...document.querySelectorAll('.tag-card.selected')];
  if (selected.length === 0) { alert('Select at least one product to print.'); return; }
  if (currentMode !== 'shelf' && currentMode !== '3x2') {
    alert('Direct Munbyn printing only works with a Munbyn label size selected above — pick "2"×1" Label" or "3"×2" Label" first. For Phomemo, use "Download Labels (PNG)" instead.');
    return;
  }

  const btn = document.getElementById('munbyn-btn');
  const orig = btn.textContent;
  btn.disabled = true;

  const isShelf = currentMode === 'shelf';
  // 300 DPI canvas sized to match the physical label
  const W = isShelf ? 600 : 900;
  const H = isShelf ? 300 : 600;
  const widthIn = isShelf ? 2 : 3;
  const heightIn = isShelf ? 1 : 2;

  const canvas = document.createElement('canvas');
  canvas.width = W; canvas.height = H;
  const ctx = canvas.getContext('2d');

  function wrapLines(text, maxWidth, maxLines = Infinity) {
    const words = text.split(' ');
    const lines = [];
    let line = '';
    for (const word of words) {
      const test = line ? line + ' ' + word : word;
      if (ctx.measureText(test).width > maxWidth && line) {
        lines.push(line); line = word;
      } else line = test;
    }
    if (line) lines.push(line);
    return lines.slice(0, maxLines);
  }

  function drawPriceTag3x2(price, name) {
    ctx.clearRect(0, 0, W, H);
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, W, H);
    const pad = 44;

    // Product name — big, fills top portion
    ctx.fillStyle = '#000';
    ctx.font = 'bold 68px Arial';
    ctx.textAlign = 'left';
    const nameLines = wrapLines(name, W - pad * 2, 3);
    nameLines.forEach((l, i) => ctx.fillText(l, pad, 88 + i * 82));

    // Divider below name
    const divY = nameLines.length === 1 ? 130 : nameLines.length === 2 ? 214 : 298;
    ctx.strokeStyle = '#aaa'; ctx.lineWidth = 2;
    ctx.beginPath(); ctx.moveTo(pad, divY + 20); ctx.lineTo(W - pad, divY + 20); ctx.stroke();

    // Price — very large, pushed to lower portion
    ctx.font = 'bold 158px Arial';
    ctx.textAlign = 'center';
    ctx.fillStyle = '#000';
    ctx.fillText(price, W / 2, 468);

    // Bottom rule
    ctx.strokeStyle = '#aaa';
    ctx.beginPath(); ctx.moveTo(pad, 466); ctx.lineTo(W - pad, 466); ctx.stroke();

    // American Select branding — big and clear
    ctx.font = 'bold 44px Arial';
    ctx.fillStyle = '#111';
    ctx.fillText('American Select', W / 2, 516);

    // Website
    ctx.font = '26px Arial';
    ctx.fillStyle = '#777';
    ctx.fillText('americanselect.net', W / 2, 552);
    ctx.textAlign = 'left';
  }

  function drawPriceTagShelf(price, name) {
    ctx.clearRect(0, 0, W, H);
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, W, H);
    const cx = W / 2;

    // Price — dominant, centered near the top
    ctx.fillStyle = '#000';
    ctx.font = 'bold 92px Arial';
    ctx.textAlign = 'center';
    ctx.fillText(price, cx, 118);

    // Product name — small, wraps to at most 2 lines
    ctx.font = 'bold 26px Arial';
    const nameLines = wrapLines(name, W - 60, 2);
    nameLines.forEach((l, i) => ctx.fillText(l, cx, 168 + i * 32));

    // Store name — tiny, bottom
    ctx.font = '18px Arial';
    ctx.fillStyle = '#777';
    ctx.fillText('AMERICAN SELECT', cx, 168 + nameLines.length * 32 + 20);
    ctx.textAlign = 'left';
  }

  const drawFn = isShelf ? drawPriceTagShelf : drawPriceTag3x2;

  let printed = 0, failed = 0;
  for (let i = 0; i < selected.length; i++) {
    const card = selected[i];
    const price = card.querySelector('.tag-price')?.textContent?.trim() || '';
    const name  = card.querySelector('.tag-name')?.textContent?.trim()  || '';
    btn.textContent = 'Printing ' + (i + 1) + '/' + selected.length + '…';

    drawFn(price, name);
    const image = canvas.toDataURL('image/png');

    try {
      const r = await fetch('http://localhost:3099/barcode', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ image, widthIn, heightIn })
      });
      if (!r.ok) { const e = await r.json().catch(() => ({})); throw new Error(e.error || r.status); }
      printed++;
    } catch(e) {
      failed++;
      console.error('Label print failed for', name, e.message);
    }
  }

  if (failed === 0) {
    btn.textContent = '✓ Printed ' + printed;
    btn.style.color = '#6dbf6d';
  } else {
    btn.textContent = '⚠ ' + printed + ' ok, ' + failed + ' failed';
    btn.style.color = '#e05c5c';
  }
  setTimeout(() => { btn.textContent = orig; btn.style.color = ''; btn.disabled = false; }, 3000);
}
</script>
</body>
</html>
