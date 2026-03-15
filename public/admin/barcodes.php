<?php
require_once __DIR__ . '/../api/db.php';
session_start();
if (empty($_SESSION['admin_logged_in'])) { header('Location: index.php'); exit; }

// Load products
$jsonPath = __DIR__ . '/../api/products-list.json';
$products = [];
if (file_exists($jsonPath)) {
    $products = json_decode(file_get_contents($jsonPath), true) ?? [];
    usort($products, fn($a, $b) => strcmp($a['name'], $b['name']));
}

// Load existing barcode assignments
$barcodeMap = []; // product_name => barcode
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->exec("CREATE TABLE IF NOT EXISTS barcode_map (
        barcode VARCHAR(100) PRIMARY KEY,
        product_name VARCHAR(500) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $stmt = $pdo->query('SELECT barcode, product_name FROM barcode_map');
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $barcodeMap[$row['product_name']] = $row['barcode'];
    }
} catch (Exception $e) {
    $dbError = $e->getMessage();
}

$assigned   = array_filter($products, fn($p) => isset($barcodeMap[$p['name']]));
$unassigned = array_filter($products, fn($p) => !isset($barcodeMap[$p['name']]));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Barcodes — American Select</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      background: #0a0a0a; color: #e0e0e0;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      min-height: 100vh; min-height: -webkit-fill-available;
      -webkit-overflow-scrolling: touch; overflow-x: hidden;
    }
    header {
      background: #111; border-bottom: 1px solid #222; padding: 14px 20px;
      padding-top: calc(14px + env(safe-area-inset-top, 0px));
      padding-left: calc(20px + env(safe-area-inset-left, 0px));
      padding-right: calc(20px + env(safe-area-inset-right, 0px));
      display: flex; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 100;
      -webkit-transform: translateZ(0); transform: translateZ(0); will-change: transform;
    }
    header h1 { color: #d4af37; font-size: 17px; font-weight: 800; letter-spacing: 1px; }
    header span { color: #555; font-size: 12px; }
    .header-btns { display: flex; gap: 8px; }
    .back-btn {
      background: transparent; color: #888; border: 1px solid #333; border-radius: 6px;
      padding: 7px 13px; font-size: 13px; font-weight: 600; cursor: pointer;
      text-decoration: none; display: inline-flex; align-items: center; gap: 5px;
      min-height: 44px; touch-action: manipulation; -webkit-user-select: none; user-select: none;
      -webkit-tap-highlight-color: transparent;
    }
    .back-btn:hover { color: #ccc; border-color: #555; }
    .btn-print-all {
      background: #d4af37; color: #000; border: none; border-radius: 6px;
      padding: 7px 14px; font-size: 13px; font-weight: 800; cursor: pointer;
      display: inline-flex; align-items: center; gap: 5px;
      min-height: 44px; touch-action: manipulation; -webkit-user-select: none; user-select: none;
      -webkit-tap-highlight-color: transparent;
    }
    .btn-print-all:hover { background: #c8a428; }
    .container {
      max-width: 900px; margin: 0 auto; padding: 16px;
      padding-bottom: calc(40px + env(safe-area-inset-bottom, 0px));
      padding-left: calc(16px + env(safe-area-inset-left, 0px));
      padding-right: calc(16px + env(safe-area-inset-right, 0px));
    }

    /* Stats bar */
    .stats { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
    .stat-box {
      flex: 1; min-width: 120px; background: #111; border: 1px solid #1e1e1e;
      border-radius: 10px; padding: 12px 16px;
    }
    .stat-num { font-size: 24px; font-weight: 800; color: #d4af37; }
    .stat-lbl { font-size: 12px; color: #555; margin-top: 2px; }

    /* Search */
    .search-bar {
      width: 100%; padding: 11px 16px; background: #1a1a1a;
      border: 1px solid #2a2a2a; border-radius: 8px; color: #e0e0e0;
      font-size: 16px; outline: none; -webkit-appearance: none; appearance: none;
      min-height: 48px; margin-bottom: 16px; touch-action: manipulation;
    }
    .search-bar:focus { border-color: #d4af37; }

    /* Section headers */
    .section-head {
      font-size: 12px; font-weight: 700; color: #555; text-transform: uppercase;
      letter-spacing: 0.5px; margin-bottom: 10px; padding-bottom: 6px;
      border-bottom: 1px solid #1a1a1a;
    }

    /* Product rows */
    .product-row {
      display: flex; align-items: center; gap: 12px;
      background: #111; border: 1px solid #1a1a1a; border-radius: 10px;
      padding: 12px 14px; margin-bottom: 8px; flex-wrap: wrap;
    }
    .product-row.has-barcode { border-left: 3px solid #1a3a1a; }
    .product-row.no-barcode  { border-left: 3px solid #3a3010; }
    .pr-name { flex: 1; min-width: 200px; font-size: 13px; color: #ccc; line-height: 1.4; }
    .pr-price { font-size: 12px; color: #6dbf6d; white-space: nowrap; }
    .pr-barcode-val { font-size: 11px; color: #555; font-family: monospace; }
    .pr-actions { display: flex; gap: 6px; align-items: center; flex-shrink: 0; }

    /* Checkboxes for print selection */
    .pr-check {
      width: 20px; height: 20px; min-width: 20px; cursor: pointer;
      accent-color: #d4af37; flex-shrink: 0;
    }

    /* Buttons */
    .btn-generate {
      padding: 7px 12px; background: #1a1500; color: #d4af37;
      border: 1px solid #3a3010; border-radius: 6px; font-size: 12px; font-weight: 700;
      cursor: pointer; white-space: nowrap; min-height: 36px;
      touch-action: manipulation; -webkit-user-select: none; user-select: none;
      -webkit-tap-highlight-color: transparent;
    }
    .btn-generate:hover { background: #2a2500; }
    .btn-generate:disabled { opacity: 0.5; cursor: not-allowed; }
    .btn-preview {
      padding: 7px 12px; background: #0d1020; color: #7b9fd4;
      border: 1px solid #1a2a40; border-radius: 6px; font-size: 12px; font-weight: 700;
      cursor: pointer; white-space: nowrap; min-height: 36px;
      touch-action: manipulation; -webkit-user-select: none; user-select: none;
      -webkit-tap-highlight-color: transparent;
    }
    .btn-preview:hover { background: #1a2040; }

    /* Qty input */
    .qty-input {
      width: 52px; padding: 6px 8px; background: #1a1a1a; border: 1px solid #2a2a2a;
      border-radius: 6px; color: #e0e0e0; font-size: 14px; font-weight: 700;
      text-align: center; outline: none; -webkit-appearance: none; appearance: none;
      min-height: 36px; touch-action: manipulation;
    }

    /* Print controls bar */
    .print-bar {
      position: sticky; bottom: calc(0px + env(safe-area-inset-bottom, 0px));
      background: #111; border-top: 1px solid #222; padding: 12px 16px;
      display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
      -webkit-transform: translateZ(0); transform: translateZ(0); will-change: transform;
    }
    .print-bar-label { font-size: 13px; color: #888; flex: 1; }
    .btn-do-print {
      padding: 10px 20px; background: #d4af37; color: #000; border: none;
      border-radius: 8px; font-size: 14px; font-weight: 800; cursor: pointer;
      min-height: 44px; touch-action: manipulation; -webkit-user-select: none; user-select: none;
      -webkit-tap-highlight-color: transparent;
    }
    .btn-do-print:hover { background: #c8a428; }
    .btn-do-print:disabled { background: #1e1e1e; color: #333; cursor: not-allowed; }
    .btn-select-all {
      padding: 8px 14px; background: transparent; color: #888; border: 1px solid #2a2a2a;
      border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;
      min-height: 44px; touch-action: manipulation; -webkit-user-select: none; user-select: none;
      -webkit-tap-highlight-color: transparent;
    }
    .btn-select-all:hover { color: #ccc; border-color: #555; }

    /* Toast */
    .toast {
      position: fixed; bottom: calc(80px + env(safe-area-inset-bottom, 0px)); left: 50%;
      -webkit-transform: translateX(-50%) translateZ(0); transform: translateX(-50%) translateZ(0);
      background: #1a1a1a; border: 1px solid #333; color: #e0e0e0;
      padding: 10px 20px; border-radius: 8px; font-size: 13px; z-index: 300;
      white-space: nowrap; display: none;
    }
    .toast.show { display: block; }
    .toast.ok { border-color: #1a3a1a; color: #6dbf6d; }
    .toast.err { border-color: #3a1a1a; color: #e05c5c; }

    /* ── PRINT STYLES ── */
    @media print {
      * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      body { background: white; color: black; margin: 0; padding: 0; font-family: Arial, sans-serif; }
      header, .container, .print-bar, .toast { display: none !important; }
      #print-sheet { display: block !important; }
    }
  </style>
</head>
<body>
<header>
  <div><h1>AMERICAN SELECT</h1><span>Barcode Labels</span></div>
  <div class="header-btns">
    <a href="dashboard.php" class="back-btn">← Dashboard</a>
  </div>
</header>

<div class="container">
  <?php if (isset($dbError)): ?>
    <div style="background:#2a0a0a;border:1px solid #5c1a1a;color:#ff6b6b;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:13px;">
      Database error: <?= htmlspecialchars($dbError) ?>
    </div>
  <?php endif; ?>

  <!-- Stats -->
  <div class="stats">
    <div class="stat-box">
      <div class="stat-num"><?= count($products) ?></div>
      <div class="stat-lbl">Total Products</div>
    </div>
    <div class="stat-box">
      <div class="stat-num" style="color:#6dbf6d;"><?= count($assigned) ?></div>
      <div class="stat-lbl">Barcodes Assigned</div>
    </div>
    <div class="stat-box">
      <div class="stat-num" style="color:#d4884a;"><?= count($unassigned) ?></div>
      <div class="stat-lbl">Need Barcode</div>
    </div>
  </div>

  <input type="text" class="search-bar" id="search-bar" placeholder="Search products…" oninput="filterProducts(this.value)">

  <!-- Unassigned products -->
  <?php if (count($unassigned)): ?>
  <div class="section-head" id="unassigned-head">⚠ No Barcode Yet (<?= count($unassigned) ?>)</div>
  <div id="unassigned-list">
    <?php foreach ($unassigned as $p): ?>
    <div class="product-row no-barcode" data-name="<?= htmlspecialchars($p['name']) ?>">
      <input type="checkbox" class="pr-check" data-name="<?= htmlspecialchars($p['name']) ?>" data-barcode="" onchange="updatePrintCount()">
      <div class="pr-name"><?= htmlspecialchars($p['name']) ?></div>
      <?php if ($p['price']): ?>
        <span class="pr-price"><?= number_format($p['price']) ?> FCFA</span>
      <?php endif; ?>
      <div class="pr-actions">
        <input type="number" class="qty-input" value="1" min="1" max="99" title="Copies to print">
        <button class="btn-generate" onclick="generateBarcode(this, '<?= htmlspecialchars(addslashes($p['name'])) ?>')">⚡ Generate</button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Assigned products -->
  <?php if (count($assigned)): ?>
  <div class="section-head" style="margin-top:24px;" id="assigned-head">✓ Barcodes Assigned (<?= count($assigned) ?>)</div>
  <div id="assigned-list">
    <?php foreach ($assigned as $p):
      $bc = $barcodeMap[$p['name']];
    ?>
    <div class="product-row has-barcode" data-name="<?= htmlspecialchars($p['name']) ?>">
      <input type="checkbox" class="pr-check" data-name="<?= htmlspecialchars($p['name']) ?>" data-barcode="<?= htmlspecialchars($bc) ?>" onchange="updatePrintCount()">
      <div class="pr-name">
        <?= htmlspecialchars($p['name']) ?>
        <div class="pr-barcode-val"><?= htmlspecialchars($bc) ?></div>
      </div>
      <?php if ($p['price']): ?>
        <span class="pr-price"><?= number_format($p['price']) ?> FCFA</span>
      <?php endif; ?>
      <div class="pr-actions">
        <input type="number" class="qty-input" value="1" min="1" max="99" title="Copies to print">
        <button class="btn-preview" onclick="previewBarcode('<?= htmlspecialchars(addslashes($bc)) ?>', '<?= htmlspecialchars(addslashes($p['name'])) ?>')">👁 Preview</button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Sticky print bar -->
<div class="print-bar">
  <button class="btn-select-all" onclick="toggleSelectAll()">Select All</button>
  <span class="print-bar-label" id="print-count-label">0 products selected</span>
  <button class="btn-do-print" id="btn-do-print" onclick="printSelected()" disabled>🖨 Print Labels</button>
</div>

<!-- Hidden print sheet -->
<div id="print-sheet" style="display:none;"></div>

<div class="toast" id="toast"></div>

<!-- JsBarcode library -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>

<script>
const products = <?= json_encode(array_values($products)) ?>;
const barcodeMap = <?= json_encode($barcodeMap) ?>;
let selectAllOn = false;

// ── Generate barcode for a product ──────────────────────
async function generateBarcode(btn, productName) {
  btn.disabled = true;
  btn.textContent = '…';
  const barcode = 'AS' + Date.now().toString().slice(-8);
  try {
    const res = await fetch('/api/barcode.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'assign', barcode, product_name: productName })
    });
    const data = await res.json();
    if (data.success) {
      showToast('✓ Barcode generated & assigned', 'ok');
      setTimeout(() => location.reload(), 800);
    } else {
      btn.disabled = false; btn.textContent = '⚡ Generate';
      showToast('Error: ' + (data.error || 'Failed'), 'err');
    }
  } catch {
    btn.disabled = false; btn.textContent = '⚡ Generate';
    showToast('Network error', 'err');
  }
}

// ── Preview single barcode in modal ─────────────────────
function previewBarcode(barcode, name) {
  const overlay = document.createElement('div');
  overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.9);z-index:200;display:flex;align-items:center;justify-content:center;padding:20px;-webkit-backdrop-filter:blur(4px);backdrop-filter:blur(4px);';
  overlay.onclick = () => overlay.remove();
  const card = document.createElement('div');
  card.style.cssText = 'background:white;border-radius:12px;padding:24px;text-align:center;max-width:320px;width:100%;';
  card.innerHTML = `<svg id="preview-svg"></svg>
    <div style="font-size:13px;color:#333;margin-top:10px;line-height:1.4;">${esc(name)}</div>
    <div style="font-size:11px;color:#999;margin-top:4px;">${esc(barcode)}</div>`;
  card.onclick = e => e.stopPropagation();
  overlay.appendChild(card);
  document.body.appendChild(overlay);
  JsBarcode('#preview-svg', barcode, { format:'CODE128', width:2, height:60, displayValue:false, margin:4 });
}

// ── Build print sheet & print ────────────────────────────
function printSelected() {
  const checked = [...document.querySelectorAll('.pr-check:checked')];
  if (!checked.length) return;

  // Gather labels
  const labels = [];
  checked.forEach(cb => {
    const row = cb.closest('.product-row');
    const qty = parseInt(row.querySelector('.qty-input').value, 10) || 1;
    const name = cb.dataset.name;
    let barcode = cb.dataset.barcode;
    if (!barcode) { showToast('Generate barcodes first for all selected items', 'err'); return; }
    const product = products.find(p => p.name === name);
    for (let i = 0; i < qty; i++) labels.push({ barcode, name });
  });

  if (!labels.length) { showToast('No valid labels — generate barcodes first', 'err'); return; }

  // Build print HTML — Phomemo M110, 40×30mm labels
  const sheet = document.getElementById('print-sheet');
  sheet.innerHTML = `
    <style>
      @page {
        size: 40mm 30mm;
        margin: 0;
      }
      body { margin: 0; padding: 0; }
      .label-grid {
        display: block;
        width: 100%;
      }
      .label {
        width: 40mm;
        height: 30mm;
        box-sizing: border-box;
        padding: 1.5mm 1.5mm 1mm;
        text-align: center;
        page-break-after: always;
        break-after: page;
        font-family: Arial, sans-serif;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
      }
      .label:last-child { page-break-after: avoid; break-after: avoid; }
      .label svg { width: 38mm; height: auto; max-height: 14mm; display: block; }
      .label-name {
        font-size: 6pt;
        color: #000;
        margin-top: 1mm;
        line-height: 1.2;
        word-break: break-word;
        max-height: 3.6em;
        overflow: hidden;
        width: 100%;
      }
      .label-brand { font-size: 6.5pt; font-weight: bold; color: #000; margin-bottom: 1mm; }
    </style>
    <div class="label-grid" id="label-grid"></div>`;

  const grid = sheet.querySelector('#label-grid');
  labels.forEach((lbl, i) => {
    const div = document.createElement('div');
    div.className = 'label';
    div.innerHTML = `
      <div class="label-brand">AMERICAN SELECT</div>
      <svg id="lbl-svg-${i}"></svg>
      <div class="label-name">${esc(lbl.name)}</div>`;
    grid.appendChild(div);
    JsBarcode(`#lbl-svg-${i}`, lbl.barcode, {
      format: 'CODE128', width: 1.2, height: 38,
      displayValue: true, fontSize: 7, margin: 1
    });
  });

  sheet.style.display = 'block';
  setTimeout(() => {
    window.print();
    sheet.style.display = 'none';
    sheet.innerHTML = '';
  }, 300);
}

// ── Search filter ────────────────────────────────────────
function filterProducts(q) {
  q = q.toLowerCase().trim();
  document.querySelectorAll('.product-row').forEach(row => {
    const name = (row.dataset.name || '').toLowerCase();
    row.style.display = (!q || name.includes(q)) ? '' : 'none';
  });
}

// ── Select all toggle ────────────────────────────────────
function toggleSelectAll() {
  selectAllOn = !selectAllOn;
  document.querySelectorAll('.pr-check').forEach(cb => cb.checked = selectAllOn);
  document.querySelector('.btn-select-all').textContent = selectAllOn ? 'Deselect All' : 'Select All';
  updatePrintCount();
}

function updatePrintCount() {
  const n = document.querySelectorAll('.pr-check:checked').length;
  document.getElementById('print-count-label').textContent = n + ' product' + (n === 1 ? '' : 's') + ' selected';
  document.getElementById('btn-do-print').disabled = n === 0;
}

function showToast(msg, type) {
  const t = document.getElementById('toast');
  t.textContent = msg; t.className = 'toast show ' + (type || '');
  setTimeout(() => t.className = 'toast', 3000);
}

function esc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>
</body>
</html>
