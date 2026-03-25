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
    .btn-unassign {
      padding: 7px 12px; background: #200a0a; color: #e05c5c;
      border: 1px solid #3a1010; border-radius: 6px; font-size: 12px; font-weight: 700;
      cursor: pointer; white-space: nowrap; min-height: 36px;
      touch-action: manipulation; -webkit-user-select: none; user-select: none;
      -webkit-tap-highlight-color: transparent;
    }
    .btn-unassign:hover { background: #300a0a; }
    .btn-do-export {
      padding: 10px 20px; background: #0d2010; color: #6dbf6d; border: 1px solid #1a4020;
      border-radius: 8px; font-size: 14px; font-weight: 800; cursor: pointer;
      min-height: 44px; touch-action: manipulation; -webkit-user-select: none; user-select: none;
    }
    .btn-do-export:disabled { opacity: 0.4; cursor: not-allowed; }
    .btn-do-export:not(:disabled):hover { background: #143020; }
    .scan-input {
      width: 160px; padding: 6px 10px; background: #0d1a0d; border: 1px solid #1a3a1a;
      border-radius: 6px; color: #e0e0e0; font-size: 13px; outline: none;
      min-height: 36px; -webkit-appearance: none; appearance: none;
      touch-action: manipulation; font-size: 16px;
    }
    .scan-input:focus { border-color: #6dbf6d; }
    .scan-input::placeholder { color: #445; }
    .btn-scan-assign {
      padding: 7px 12px; background: #0d2010; color: #6dbf6d;
      border: 1px solid #1a4020; border-radius: 6px; font-size: 12px; font-weight: 700;
      cursor: pointer; white-space: nowrap; min-height: 36px;
      touch-action: manipulation; -webkit-user-select: none; user-select: none;
    }
    .btn-scan-assign:hover { background: #143020; }

    /* Qty input */
    .qty-input {
      width: 52px; padding: 6px 8px; background: #1a1a1a; border: 1px solid #2a2a2a;
      border-radius: 6px; color: #e0e0e0; font-size: 14px; font-weight: 700;
      text-align: center; outline: none; -webkit-appearance: none; appearance: none;
      min-height: 36px; touch-action: manipulation;
    }

    /* Action sheet */
    .action-backdrop {
      position: fixed; inset: 0; background: rgba(0,0,0,0.55); z-index: 200;
      display: none; -webkit-backdrop-filter: blur(2px); backdrop-filter: blur(2px);
      -webkit-tap-highlight-color: transparent;
    }
    .action-backdrop.open { display: block; }
    .action-sheet {
      position: fixed; left: 0; right: 0;
      bottom: calc(0px + env(safe-area-inset-bottom, 0px));
      background: #161616; border-top: 1px solid #2a2a2a; border-radius: 18px 18px 0 0;
      padding: 12px 16px calc(16px + env(safe-area-inset-bottom, 0px));
      z-index: 201; transform: translateY(100%) translateZ(0); will-change: transform;
      transition: transform 0.28s cubic-bezier(0.4,0,0.2,1);
      -webkit-transform: translateY(100%) translateZ(0);
    }
    .action-sheet.open {
      transform: translateY(0) translateZ(0);
      -webkit-transform: translateY(0) translateZ(0);
    }
    .action-sheet-handle {
      width: 36px; height: 4px; background: #333; border-radius: 4px;
      margin: 0 auto 14px;
    }
    .action-sheet-title {
      font-size: 12px; color: #555; text-transform: uppercase; letter-spacing: 0.5px;
      margin-bottom: 12px; text-align: center;
    }
    .action-btns { display: flex; flex-direction: column; gap: 8px; }
    .action-btn {
      width: 100%; padding: 14px 18px; border-radius: 10px; border: none;
      font-size: 15px; font-weight: 700; cursor: pointer; text-align: left;
      display: flex; align-items: center; gap: 10px;
      touch-action: manipulation; -webkit-user-select: none; user-select: none;
      -webkit-tap-highlight-color: transparent; min-height: 52px;
    }
    .action-btn-print { background: #d4af37; color: #000; }
    .action-btn-print:hover { background: #c8a428; }
    .action-btn-generate { background: #1a1500; color: #d4af37; border: 1px solid #3a3010; }
    .action-btn-generate:hover { background: #2a2500; }
    .action-btn-generate:disabled { opacity: 0.35; cursor: not-allowed; }
    .action-btn-export { background: #0d2010; color: #6dbf6d; border: 1px solid #1a4020; }
    .action-btn-export:hover { background: #143020; }
    .action-btn-cancel {
      background: transparent; color: #666; border: 1px solid #222;
      justify-content: center; margin-top: 4px;
    }
    .action-btn-cancel:hover { color: #aaa; border-color: #444; }
    .btn-select-all {
      padding: 6px 12px; background: transparent; color: #666; border: 1px solid #222;
      border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer;
      min-height: 36px; touch-action: manipulation; -webkit-user-select: none; user-select: none;
      -webkit-tap-highlight-color: transparent;
    }
    .btn-select-all:hover { color: #ccc; border-color: #555; }
    .action-btn-scan { background: #0d1a2e; color: #7b9fd4; border: 1px solid #1a2a50; }
    .action-btn-scan:hover { background: #1a2a40; }

    /* Scan-to-assign modal */
    .scan-modal-overlay {
      position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 400;
      display: none; align-items: flex-end; justify-content: center;
      -webkit-backdrop-filter: blur(4px); backdrop-filter: blur(4px);
      padding-bottom: env(safe-area-inset-bottom, 0px);
    }
    .scan-modal-overlay.open { display: flex; }
    .scan-modal {
      background: #161616; border: 1px solid #2a2a2a; border-radius: 18px 18px 0 0;
      padding: 20px 16px calc(24px + env(safe-area-inset-bottom, 0px));
      width: 100%; max-width: 520px;
    }
    .scan-modal-handle { width: 36px; height: 4px; background: #333; border-radius: 4px; margin: 0 auto 16px; }
    .scan-modal-title { font-size: 13px; font-weight: 700; color: #ccc; margin-bottom: 4px; }
    .scan-modal-product { font-size: 12px; color: #d4af37; margin-bottom: 16px; line-height: 1.4; }
    .scan-modal-input-row { display: flex; gap: 8px; align-items: center; }
    .scan-modal-input {
      flex: 1; padding: 13px 14px; background: #1a1a1a;
      border: 2px solid #2a2a2a; border-radius: 8px; color: #e0e0e0;
      font-size: 16px; outline: none; -webkit-appearance: none; appearance: none;
      min-height: 50px; touch-action: manipulation;
    }
    .scan-modal-input:focus { border-color: #7b9fd4; }
    .btn-modal-camera {
      background: #1a1a1a; color: #888; border: 1px solid #2a2a2a; border-radius: 8px;
      font-size: 22px; cursor: pointer; min-height: 50px; min-width: 50px;
      display: flex; align-items: center; justify-content: center; flex-shrink: 0;
      touch-action: manipulation; -webkit-user-select: none; user-select: none;
      -webkit-tap-highlight-color: transparent;
    }
    .btn-modal-camera.active { background: #0d1a0d; color: #6dbf6d; border-color: #1a3a1a; }
    .scan-modal-camera-wrap {
      margin-top: 10px; display: none; border-radius: 10px; overflow: hidden; background: #000; position: relative;
    }
    .scan-modal-camera-wrap.visible { display: block; }
    .scan-modal-camera-wrap video { width: 100%; max-height: 200px; object-fit: cover; display: block; }
    .scan-modal-scanline {
      position: absolute; left: 8%; right: 8%; height: 2px;
      background: #7b9fd4; box-shadow: 0 0 8px #7b9fd4; opacity: 0.9;
      animation: scanpulse 2s ease-in-out infinite;
    }
    .scan-modal-status { margin-top: 10px; font-size: 13px; color: #555; min-height: 18px; }
    .scan-modal-status.ok { color: #6dbf6d; }
    .scan-modal-status.err { color: #e05c5c; }
    .scan-modal-btns { display: flex; gap: 8px; margin-top: 14px; }
    .btn-modal-assign {
      flex: 2; padding: 13px; background: #7b9fd4; color: #000; border: none;
      border-radius: 8px; font-size: 15px; font-weight: 800; cursor: pointer;
      min-height: 50px; touch-action: manipulation; -webkit-user-select: none; user-select: none;
      -webkit-tap-highlight-color: transparent;
    }
    .btn-modal-assign:disabled { opacity: 0.4; cursor: not-allowed; }
    .btn-modal-cancel {
      flex: 1; padding: 13px; background: transparent; color: #666;
      border: 1px solid #2a2a2a; border-radius: 8px; font-size: 14px; font-weight: 600;
      cursor: pointer; min-height: 50px; touch-action: manipulation;
      -webkit-user-select: none; user-select: none; -webkit-tap-highlight-color: transparent;
    }

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
      header, .container, .action-backdrop, .action-sheet, .toast { display: none !important; }
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
    <div class="stat-box" style="display:flex;align-items:center;justify-content:center;min-width:unset;flex:0;">
      <button class="btn-select-all" onclick="toggleSelectAll()">Select All</button>
    </div>
  </div>

  <input type="text" class="search-bar" id="search-bar" placeholder="Search products… (type to filter by name)" oninput="filterProducts(this.value)" autofocus>

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
        <input type="text" class="scan-input" placeholder="Scan or type barcode…" title="Scan manufacturer barcode" onkeydown="if(event.key==='Enter'){assignManual(this,'<?= htmlspecialchars(addslashes($p['name'])) ?>')}">
        <button class="btn-scan-assign" onclick="assignManual(this.previousElementSibling, '<?= htmlspecialchars(addslashes($p['name'])) ?>')">✔ Assign</button>
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
        <button class="btn-unassign" onclick="unassignBarcode(this, '<?= htmlspecialchars(addslashes($p['name'])) ?>')">✕ Unassign</button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Action sheet backdrop -->
<div class="action-backdrop" id="action-backdrop" onclick="closeActionSheet()"></div>

<!-- Action sheet -->
<div class="action-sheet" id="action-sheet">
  <div class="action-sheet-handle"></div>
  <div class="action-sheet-title" id="action-sheet-title">0 products selected</div>
  <div class="action-btns">
    <button class="action-btn action-btn-scan" id="btn-scan-assign" onclick="openScanModal()">📷 &nbsp;Scan to Assign Barcode</button>
    <button class="action-btn action-btn-print" onclick="printSelected()">🖨 &nbsp;Print Labels</button>
    <button class="action-btn action-btn-generate" id="btn-bulk-generate" onclick="generateSelected()">⚡ &nbsp;Generate Barcodes</button>
    <button class="action-btn action-btn-export" onclick="exportPNG()">📥 &nbsp;Export PNG</button>
    <button class="action-btn action-btn-cancel" onclick="closeActionSheet()">✕ &nbsp;Cancel</button>
  </div>
</div>

<!-- Scan-to-assign modal -->
<div class="scan-modal-overlay" id="scan-modal-overlay">
  <div class="scan-modal">
    <div class="scan-modal-handle"></div>
    <div class="scan-modal-title">Scan Barcode to Assign</div>
    <div class="scan-modal-product" id="scan-modal-product"></div>
    <div class="scan-modal-input-row">
      <input type="text" class="scan-modal-input" id="scan-modal-input"
             placeholder="Scan barcode on packaging…"
             autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" inputmode="text">
      <button class="btn-modal-camera" id="modal-camera-btn" onclick="toggleModalCamera()" title="Use camera">📷</button>
    </div>
    <div class="scan-modal-camera-wrap" id="modal-camera-wrap">
      <video id="modal-camera-video" autoplay playsinline muted></video>
      <div class="scan-modal-scanline"></div>
    </div>
    <div class="scan-modal-status" id="scan-modal-status">Scan or type the barcode from the product packaging</div>
    <div class="scan-modal-btns">
      <button class="btn-modal-assign" id="btn-modal-assign" onclick="doScanAssign()">✔ Assign</button>
      <button class="btn-modal-cancel" onclick="closeScanModal()">Cancel</button>
    </div>
  </div>
</div>

<!-- Hidden print sheet -->
<div id="print-sheet" style="display:none;"></div>

<div class="toast" id="toast"></div>

<!-- JsBarcode library -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<!-- JSZip for PNG export -->
<script src="https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js"></script>

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

// ── Assign manufacturer/scanned barcode manually ────────
async function assignManual(input, productName) {
  const barcode = input.value.trim();
  if (!barcode) { input.focus(); showToast('Scan or enter a barcode first', 'err'); return; }
  input.disabled = true;
  try {
    const res = await fetch('/api/barcode.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'assign', barcode, product_name: productName })
    });
    const data = await res.json();
    if (data.success) {
      showToast('✓ Barcode assigned', 'ok');
      setTimeout(() => location.reload(), 800);
    } else {
      input.disabled = false;
      showToast('Error: ' + (data.error || 'Failed'), 'err');
    }
  } catch {
    input.disabled = false;
    showToast('Network error', 'err');
  }
}

// ── Unassign barcode from product ───────────────────────
async function unassignBarcode(btn, productName) {
  if (!confirm(`Remove barcode from "${productName}"?`)) return;
  btn.disabled = true;
  btn.textContent = '…';
  try {
    const res = await fetch('/api/barcode.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'unassign', product_name: productName })
    });
    const data = await res.json();
    if (data.success) {
      showToast('✓ Barcode unassigned', 'ok');
      setTimeout(() => location.reload(), 800);
    } else {
      btn.disabled = false; btn.textContent = '✕ Unassign';
      showToast('Error: ' + (data.error || 'Failed'), 'err');
    }
  } catch {
    btn.disabled = false; btn.textContent = '✕ Unassign';
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
  let anyUnassigned = false, anyAssigned = false;
  document.querySelectorAll('.product-row').forEach(row => {
    const name = (row.dataset.name || '').toLowerCase();
    const visible = !q || name.startsWith(q);
    row.style.display = visible ? '' : 'none';
    if (visible) {
      if (row.classList.contains('no-barcode')) anyUnassigned = true;
      if (row.classList.contains('has-barcode')) anyAssigned = true;
    }
  });
  // Show/hide section headers based on whether their rows are visible
  const uh = document.getElementById('unassigned-head');
  const ah = document.getElementById('assigned-head');
  if (uh) uh.style.display = anyUnassigned ? '' : 'none';
  if (ah) ah.style.display = anyAssigned ? '' : 'none';
}

// ── Select all toggle ────────────────────────────────────
function toggleSelectAll() {
  selectAllOn = !selectAllOn;
  document.querySelectorAll('.pr-check').forEach(cb => cb.checked = selectAllOn);
  document.querySelector('.btn-select-all').textContent = selectAllOn ? 'Deselect All' : 'Select All';
  updatePrintCount();
}

function updatePrintCount() {
  const checked = [...document.querySelectorAll('.pr-check:checked')];
  const n = checked.length;
  const unassigned = checked.filter(cb => !cb.dataset.barcode);
  const hasUnassigned = unassigned.length > 0;
  const singleUnassigned = unassigned.length === 1 && checked.length === 1;

  const sheet = document.getElementById('action-sheet');
  const backdrop = document.getElementById('action-backdrop');
  const title = document.getElementById('action-sheet-title');
  const genBtn = document.getElementById('btn-bulk-generate');
  const scanBtn = document.getElementById('btn-scan-assign');

  if (n > 0) {
    title.textContent = n + ' product' + (n === 1 ? '' : 's') + ' selected';
    genBtn.disabled = !hasUnassigned;
    genBtn.style.display = hasUnassigned ? '' : 'none';
    // Scan to assign: only shown when exactly 1 unassigned item is checked
    scanBtn.style.display = singleUnassigned ? '' : 'none';
    sheet.classList.add('open');
    backdrop.classList.add('open');
  } else {
    sheet.classList.remove('open');
    backdrop.classList.remove('open');
  }
}

function closeActionSheet() {
  document.querySelectorAll('.pr-check').forEach(cb => cb.checked = false);
  selectAllOn = false;
  document.querySelector('.btn-select-all').textContent = 'Select All';
  document.getElementById('action-sheet').classList.remove('open');
  document.getElementById('action-backdrop').classList.remove('open');
}

// ── Bulk generate barcodes for selected unassigned items ──
async function generateSelected() {
  const checked = [...document.querySelectorAll('.pr-check:checked')].filter(cb => !cb.dataset.barcode);
  if (!checked.length) return;
  closeActionSheet();
  showToast('Generating ' + checked.length + ' barcode(s)…', 'ok');
  for (let idx = 0; idx < checked.length; idx++) {
    const cb = checked[idx];
    const name = cb.dataset.name;
    const barcode = 'AS' + (Date.now() + idx).toString().slice(-8);
    try {
      const res = await fetch('/api/barcode.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'assign', barcode, product_name: name })
      });
      const data = await res.json();
      if (!data.success) showToast('Failed for: ' + name.substring(0,30), 'err');
    } catch { showToast('Network error', 'err'); }
  }
  setTimeout(() => location.reload(), 600);
}

// ── Export selected labels as PNG images in a ZIP ────────
async function exportPNG() {
  const checked = [...document.querySelectorAll('.pr-check:checked')];
  if (!checked.length) return;

  const labels = [];
  checked.forEach(cb => {
    const row = cb.closest('.product-row');
    const qty = parseInt(row.querySelector('.qty-input').value, 10) || 1;
    const name = cb.dataset.name;
    const barcode = cb.dataset.barcode;
    if (!barcode) { showToast('Generate barcodes first for all selected items', 'err'); return; }
    for (let i = 0; i < qty; i++) labels.push({ barcode, name });
  });

  if (!labels.length) return;
  showToast('Generating PNG images…', 'ok');

  // 40×30mm at 203 DPI = 320×240px
  const W = 320, H = 240;
  const zip = new JSZip();

  for (let i = 0; i < labels.length; i++) {
    const { barcode, name } = labels[i];

    // Render barcode SVG
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    document.body.appendChild(svg);
    JsBarcode(svg, barcode, { format: 'CODE128', width: 1.5, height: 80, displayValue: true, fontSize: 14, margin: 4 });
    const svgData = new XMLSerializer().serializeToString(svg);
    document.body.removeChild(svg);

    // Draw on canvas
    const canvas = document.createElement('canvas');
    canvas.width = W; canvas.height = H;
    const ctx = canvas.getContext('2d');
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, W, H);

    // Brand text
    ctx.fillStyle = '#000000';
    ctx.font = 'bold 18px Arial';
    ctx.textAlign = 'center';
    ctx.fillText('AMERICAN SELECT', W / 2, 22);

    // Barcode image from SVG
    await new Promise(resolve => {
      const img = new Image();
      img.onload = () => {
        ctx.drawImage(img, 10, 28, W - 20, 150);
        resolve();
      };
      img.src = 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(svgData)));
    });

    // Product name (wrapped)
    ctx.font = '13px Arial';
    ctx.fillStyle = '#000000';
    ctx.textAlign = 'center';
    const words = name.split(' ');
    let line = '', y = 192;
    for (const word of words) {
      const test = line ? line + ' ' + word : word;
      if (ctx.measureText(test).width > W - 16) {
        ctx.fillText(line, W / 2, y); y += 16; line = word;
      } else { line = test; }
    }
    if (line) ctx.fillText(line, W / 2, y);

    // Add to zip
    const blob = await new Promise(r => canvas.toBlob(r, 'image/png'));
    const filename = name.replace(/[^a-z0-9]/gi, '_').slice(0, 40) + (labels.length > 1 ? `_${i+1}` : '') + '.png';
    zip.file(filename, blob);
  }

  const zipBlob = await zip.generateAsync({ type: 'blob' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(zipBlob);
  a.download = 'barcode-labels.zip';
  a.click();
  showToast('✓ PNG labels exported', 'ok');
}

// ── Scan-to-assign modal ──────────────────────────────────
let modalCameraOn = false, modalCodeReader = null, scanTargetProduct = '';

function openScanModal() {
  const checked = [...document.querySelectorAll('.pr-check:checked')].filter(cb => !cb.dataset.barcode);
  if (!checked.length) return;
  scanTargetProduct = checked[0].dataset.name;
  document.getElementById('scan-modal-product').textContent = scanTargetProduct;
  document.getElementById('scan-modal-input').value = '';
  document.getElementById('scan-modal-status').textContent = 'Scan or type the barcode from the product packaging';
  document.getElementById('scan-modal-status').className = 'scan-modal-status';
  document.getElementById('scan-modal-overlay').classList.add('open');
  closeActionSheet();
  setTimeout(() => document.getElementById('scan-modal-input').focus(), 200);
}

function closeScanModal() {
  stopModalCamera();
  document.getElementById('scan-modal-overlay').classList.remove('open');
  scanTargetProduct = '';
}

document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('scan-modal-input').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); doScanAssign(); }
  });
  // Auto-fill status as user types
  document.getElementById('scan-modal-input').addEventListener('input', function() {
    const v = this.value.trim();
    document.getElementById('scan-modal-status').textContent = v ? 'Press Assign or Enter to save' : 'Scan or type the barcode from the product packaging';
    document.getElementById('scan-modal-status').className = 'scan-modal-status' + (v ? ' ok' : '');
  });
});

async function doScanAssign() {
  const barcode = document.getElementById('scan-modal-input').value.trim();
  if (!barcode) {
    document.getElementById('scan-modal-status').textContent = 'Please scan or enter a barcode first';
    document.getElementById('scan-modal-status').className = 'scan-modal-status err';
    return;
  }
  const btn = document.getElementById('btn-modal-assign');
  btn.disabled = true; btn.textContent = '…';
  try {
    const res = await fetch('/api/barcode.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'assign', barcode, product_name: scanTargetProduct })
    });
    const data = await res.json();
    if (data.success) {
      document.getElementById('scan-modal-status').textContent = '✓ Barcode assigned!';
      document.getElementById('scan-modal-status').className = 'scan-modal-status ok';
      stopModalCamera();
      setTimeout(() => { closeScanModal(); location.reload(); }, 700);
    } else {
      btn.disabled = false; btn.textContent = '✔ Assign';
      document.getElementById('scan-modal-status').textContent = 'Error: ' + (data.error || 'Failed');
      document.getElementById('scan-modal-status').className = 'scan-modal-status err';
    }
  } catch {
    btn.disabled = false; btn.textContent = '✔ Assign';
    document.getElementById('scan-modal-status').textContent = 'Network error';
    document.getElementById('scan-modal-status').className = 'scan-modal-status err';
  }
}

async function toggleModalCamera() {
  modalCameraOn ? stopModalCamera() : startModalCamera();
}

async function startModalCamera() {
  document.getElementById('scan-modal-status').textContent = 'Loading camera…';
  if (typeof ZXing === 'undefined') {
    await new Promise((res, rej) => {
      const s = document.createElement('script');
      s.src = 'https://unpkg.com/@zxing/library@0.18.6/umd/index.min.js';
      s.onload = res; s.onerror = rej;
      document.head.appendChild(s);
    }).catch(() => {
      document.getElementById('scan-modal-status').textContent = 'Failed to load camera';
      document.getElementById('scan-modal-status').className = 'scan-modal-status err';
      return;
    });
  }
  try {
    modalCodeReader = new ZXing.BrowserMultiFormatReader();
    await modalCodeReader.decodeFromConstraints(
      { video: { facingMode: 'environment' } }, 'modal-camera-video',
      (result) => {
        if (result) {
          stopModalCamera();
          document.getElementById('scan-modal-input').value = result.getText();
          document.getElementById('scan-modal-status').textContent = 'Press Assign or Enter to save';
          document.getElementById('scan-modal-status').className = 'scan-modal-status ok';
          doScanAssign();
        }
      }
    );
    modalCameraOn = true;
    document.getElementById('modal-camera-wrap').classList.add('visible');
    document.getElementById('modal-camera-btn').classList.add('active');
    document.getElementById('scan-modal-status').textContent = 'Camera active — point at barcode';
  } catch(e) {
    document.getElementById('scan-modal-status').textContent = 'Camera error: ' + e.message;
    document.getElementById('scan-modal-status').className = 'scan-modal-status err';
  }
}

function stopModalCamera() {
  if (modalCodeReader) { try { modalCodeReader.reset(); } catch {} modalCodeReader = null; }
  modalCameraOn = false;
  document.getElementById('modal-camera-wrap').classList.remove('visible');
  document.getElementById('modal-camera-btn').classList.remove('active');
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
