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
        display: block !important;
        background: #fff !important;
        border: none !important;
        border-radius: 0;
        padding: 2mm 2.5mm;
        width: 25mm;
        height: 51mm;
        min-height: 51mm;
        max-height: 51mm;
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
        font-size: 14pt;
        font-weight: 900;
        color: #000 !important;
        line-height: 1.1;
        display: block;
        margin-top: 2mm;
        word-break: break-all;
      }
      body.mode-shelf .tag-name {
        font-size: 5.5pt;
        font-weight: 700;
        color: #000 !important;
        line-height: 1.3;
        display: -webkit-box;
        -webkit-line-clamp: 4;
        -webkit-box-orient: vertical;
        overflow: hidden;
        margin-top: 2mm;
        text-align: left;
      }
      body.mode-shelf .tag-store {
        font-size: 4pt;
        color: #777;
        letter-spacing: 1px;
        text-transform: uppercase;
        display: block;
        margin-top: 2mm;
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
    }
  </style>

  <!-- Dynamic @page size injected by JS based on selected mode -->
  <style id="page-size-style">
    @page { size: A4; margin: 10mm; }
  </style>
  <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
</head>
<body class="mode-sheet">

<header>
  <h1>Price Tags</h1>
  <div class="header-actions">
    <button class="btn btn-gold no-print" id="print-btn" onclick="printSelected()">Print Selected</button>
    <button class="btn btn-outline no-print" id="pdf-btn" onclick="downloadPDF()" style="color:#7b9fd4;border-color:#1a2a40;">⬇ Download PDF</button>
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

  const pageStyle = document.getElementById('page-size-style');
  const hint = document.getElementById('mode-hint');

  if (mode === 'shelf') {
    pageStyle.textContent = '@page { size: 25mm 51mm; margin: 0; }';
    hint.textContent = 'One tag per label — load 2"×1" labels on your Munbyn (portrait feed)';
  } else if (mode === '3x2') {
    pageStyle.textContent = '@page { size: 76mm 51mm; margin: 0; }';
    hint.textContent = 'One tag per label — load 3"×2" labels on your Munbyn';
  } else {
    pageStyle.textContent = '@page { size: A4; margin: 10mm; }';
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

// ── Download PDF for Munbyn label printing ──────────────
function downloadPDF() {
  const selected = [...document.querySelectorAll('.tag-card.selected')];
  if (selected.length === 0) { alert('Select at least one product first.'); return; }

  const btn = document.getElementById('pdf-btn');
  btn.textContent = '⏳ Generating…';
  btn.disabled = true;

  const mode = currentMode;
  // Label dimensions in mm
  const W = mode === '3x2' ? 76 : 25;
  const H = mode === '3x2' ? 51 : 51;

  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({ unit: 'mm', format: [W, H], orientation: 'portrait' });

  selected.forEach((card, i) => {
    if (i > 0) doc.addPage([W, H]);

    const price = card.querySelector('.tag-price')?.textContent?.trim() || '';
    const name  = card.querySelector('.tag-name')?.textContent?.trim() || '';
    const store = 'AMERICAN SELECT';

    if (mode === '3x2') {
      // 3×2: name top, price middle, store bottom
      doc.setFont('helvetica', 'bold');
      doc.setFontSize(8);
      const nameLines = doc.splitTextToSize(name, 66);
      doc.text(nameLines, 5, 8);

      doc.setFontSize(22);
      doc.text(price, 5, 32);

      doc.setDrawColor(180); doc.setLineWidth(0.3);
      doc.line(5, 39, 71, 39);

      doc.setFontSize(6); doc.setFont('helvetica', 'normal');
      doc.text(store, 5, 43);
    } else {
      // 2×1 portrait: price top, name middle, store bottom
      doc.setFont('helvetica', 'bold');
      doc.setFontSize(14);
      doc.text(price, W / 2, 14, { align: 'center' });

      doc.setFontSize(6);
      const nameLines = doc.splitTextToSize(name, W - 4);
      doc.text(nameLines.slice(0, 4), 2, 24);

      doc.setFontSize(4.5); doc.setFont('helvetica', 'normal');
      doc.text(store, W / 2, 47, { align: 'center' });
    }
  });

  const filename = 'price-tags-' + mode + '-' + selected.length + 'labels.pdf';
  doc.save(filename);

  btn.textContent = '⬇ Download PDF';
  btn.disabled = false;
}
</script>
</body>
</html>
