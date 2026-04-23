<?php
session_start();
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$jsonPath = __DIR__ . '/../api/products-list.json';
$products = [];
if (file_exists($jsonPath)) {
    $products = json_decode(file_get_contents($jsonPath), true) ?? [];
}

usort($products, fn($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));

function fmt_price($n) {
    return number_format((float)$n, 0, '.', ' ') . ' XAF';
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
      -webkit-transform: translateZ(0);
      transform: translateZ(0);
      will-change: transform;
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
         PRINT — LABEL MODE (Phomemo M110, 57mm wide)
      ════════════════════════════════════════════════════════ */
      body.mode-label { font-family: Arial, sans-serif; background: #fff !important; color: #000 !important; }
      body.mode-label header,
      body.mode-label .controls,
      body.mode-label .mode-row,
      body.mode-label .no-print { display: none !important; }

      body.mode-label .tag-grid {
        max-width: 100%; margin: 0; padding: 0;
        display: block;
      }
      body.mode-label .tag-card { display: none !important; }
      body.mode-label .tag-card.selected {
        display: flex !important;
        flex-direction: column;
        background: #fff !important;
        border: none !important;
        border-radius: 0;
        padding: 3mm 3mm 2mm;
        gap: 2px;
        width: 51mm;
        page-break-after: always;
        break-after: page;
      }
      body.mode-label .tag-card.selected:last-child {
        page-break-after: avoid;
        break-after: avoid;
      }
      body.mode-label .tag-card-top input[type=checkbox] { display: none !important; }
      body.mode-label .tag-card-top { gap: 0; }
      body.mode-label .tag-name {
        font-size: 9pt;
        font-weight: 700;
        color: #000 !important;
        line-height: 1.3;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
      }
      body.mode-label .tag-price {
        font-size: 18pt;
        font-weight: 900;
        color: #000 !important;
        margin-top: 2mm;
        letter-spacing: 0.5px;
      }
      body.mode-label .tag-store {
        font-size: 6pt;
        color: #555;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        margin-top: 1mm;
        border-top: 0.5pt solid #ccc;
        padding-top: 1mm;
      }
      body.mode-label .tag-qty { display: none !important; }
      body.mode-label .empty { display: none !important; }
    }
  </style>

  <!-- Dynamic @page size injected by JS based on selected mode -->
  <style id="page-size-style">
    @media print { @page { size: A4; margin: 10mm; } }
  </style>
</head>
<body class="mode-sheet">

<header>
  <h1>Price Tags</h1>
  <div class="header-actions">
    <button class="btn btn-gold no-print" id="print-btn" onclick="printSelected()">Print Selected</button>
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
  <button class="mode-btn" id="mode-label" onclick="setMode('label')">Label Printer — Phomemo M110 (57mm)</button>
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
      <span class="tag-name"><?= $name ?></span>
    </div>
    <div class="tag-price"><?= $price ?></div>
    <div class="tag-store">American Select</div>
    <div class="tag-qty">Stock: <?= $qty ?></div>
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
  document.getElementById('mode-label').classList.toggle('active', mode === 'label');

  const pageStyle = document.getElementById('page-size-style');
  const hint = document.getElementById('mode-hint');

  if (mode === 'label') {
    pageStyle.textContent = '@media print { @page { size: 57mm 70mm; margin: 0; } }';
    hint.textContent = 'One tag per label — make sure Phomemo M110 is set as your printer';
  } else {
    pageStyle.textContent = '@media print { @page { size: A4; margin: 10mm; } }';
    hint.textContent = 'Multiple tags per page, cut apart after printing';
  }
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
</script>
</body>
</html>
