<?php
require_once __DIR__ . '/../api/db.php';
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Barcode Scanner — American Select</title>
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
    }
    header h1 { color: #d4af37; font-size: 18px; font-weight: 700; letter-spacing: 1px; }
    header span { color: #666; font-size: 13px; }
    .back-btn {
      background: transparent;
      color: #888;
      border: 1px solid #333;
      border-radius: 6px;
      padding: 8px 16px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      min-height: 44px;
      min-width: 44px;
      touch-action: manipulation;
      -webkit-user-select: none;
      user-select: none;
      -webkit-tap-highlight-color: transparent;
    }
    .back-btn:hover { color: #ccc; border-color: #555; }
    .container {
      max-width: 520px;
      margin: 0 auto;
      padding: 24px 16px;
      padding-bottom: calc(40px + env(safe-area-inset-bottom, 0px));
      padding-left: calc(16px + env(safe-area-inset-left, 0px));
      padding-right: calc(16px + env(safe-area-inset-right, 0px));
    }

    /* Camera */
    .camera-wrap {
      position: relative;
      width: 100%;
      aspect-ratio: 1 / 1;
      background: #111;
      border-radius: 16px;
      overflow: hidden;
      border: 2px solid #222;
    }
    #video {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    .scan-line {
      position: absolute;
      left: 10%;
      right: 10%;
      height: 2px;
      background: #d4af37;
      box-shadow: 0 0 8px #d4af37;
      animation: scanMove 2s linear infinite;
      border-radius: 2px;
    }
    @keyframes scanMove {
      0%   { top: 15%; }
      50%  { top: 80%; }
      100% { top: 15%; }
    }
    .scan-corner {
      position: absolute;
      width: 28px;
      height: 28px;
      border-color: #d4af37;
      border-style: solid;
    }
    .scan-corner.tl { top: 12%; left: 8%; border-width: 3px 0 0 3px; border-radius: 4px 0 0 0; }
    .scan-corner.tr { top: 12%; right: 8%; border-width: 3px 3px 0 0; border-radius: 0 4px 0 0; }
    .scan-corner.bl { bottom: 12%; left: 8%; border-width: 0 0 3px 3px; border-radius: 0 0 0 4px; }
    .scan-corner.br { bottom: 12%; right: 8%; border-width: 0 3px 3px 0; border-radius: 0 0 4px 0; }
    .camera-placeholder {
      position: absolute;
      inset: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 12px;
      color: #444;
      font-size: 14px;
    }
    .camera-placeholder svg { opacity: 0.4; }
    .start-btn {
      background: #d4af37;
      color: #000;
      border: none;
      border-radius: 10px;
      padding: 14px 32px;
      font-size: 15px;
      font-weight: 700;
      cursor: pointer;
      width: 100%;
      margin-top: 16px;
      min-height: 52px;
      touch-action: manipulation;
      -webkit-user-select: none;
      user-select: none;
      -webkit-tap-highlight-color: transparent;
      letter-spacing: 0.3px;
    }
    .start-btn:hover { background: #e8c547; }
    .start-btn:disabled { opacity: 0.5; cursor: not-allowed; }

    /* Status bar */
    .status-bar {
      margin-top: 16px;
      padding: 12px 16px;
      border-radius: 10px;
      font-size: 14px;
      display: none;
    }
    .status-bar.scanning { background: #0d1a2a; border: 1px solid #1a3a5c; color: #5b9bd5; display: block; }
    .status-bar.found    { background: #0d1a0d; border: 1px solid #1a3a1a; color: #6dbf6d; display: block; }
    .status-bar.unknown  { background: #1a1a0d; border: 1px solid #3a3a1a; color: #c8b84a; display: block; }
    .status-bar.error    { background: #2a0a0a; border: 1px solid #5c1a1a; color: #ff6b6b; display: block; }

    /* Result card */
    .result-card {
      margin-top: 20px;
      background: #111;
      border: 1px solid #222;
      border-radius: 14px;
      padding: 20px;
      display: none;
    }
    .result-card.visible { display: block; }
    .result-label { font-size: 11px; color: #555; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
    .result-product-name { font-size: 16px; font-weight: 600; color: #e0e0e0; line-height: 1.4; margin-bottom: 16px; }
    .result-barcode { font-size: 12px; color: #444; margin-bottom: 16px; font-family: monospace; }
    .qty-row { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
    .qty-label { font-size: 13px; color: #888; white-space: nowrap; }
    .qty-input {
      width: 90px;
      padding: 10px 12px;
      background: #1a1a1a;
      border: 1px solid #2a2a2a;
      border-radius: 8px;
      color: #e0e0e0;
      font-size: 18px;
      font-weight: 700;
      text-align: center;
      outline: none;
      -webkit-appearance: none;
      appearance: none;
      touch-action: manipulation;
      min-height: 44px;
    }
    .qty-input:focus { border-color: #d4af37; }
    .save-stock-btn {
      background: #1a2a1a;
      color: #6dbf6d;
      border: 1px solid #2a4a2a;
      border-radius: 8px;
      padding: 10px 20px;
      font-size: 14px;
      font-weight: 700;
      cursor: pointer;
      min-height: 44px;
      touch-action: manipulation;
      -webkit-user-select: none;
      user-select: none;
      -webkit-tap-highlight-color: transparent;
    }
    .save-stock-btn:hover { background: #1e361e; }
    .save-stock-btn:disabled { opacity: 0.5; cursor: not-allowed; }
    .divider { border: none; border-top: 1px solid #1a1a1a; margin: 16px 0; }

    /* Assign form */
    .assign-label { font-size: 13px; color: #888; margin-bottom: 8px; display: block; }
    .assign-select {
      width: 100%;
      padding: 10px 12px;
      background: #1a1a1a;
      border: 1px solid #2a2a2a;
      border-radius: 8px;
      color: #e0e0e0;
      font-size: 15px;
      outline: none;
      -webkit-appearance: none;
      appearance: none;
      touch-action: manipulation;
      min-height: 44px;
      margin-bottom: 12px;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23888' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 12px center;
      padding-right: 36px;
    }
    .assign-select:focus { border-color: #d4af37; }
    .assign-btn {
      background: #d4af37;
      color: #000;
      border: none;
      border-radius: 8px;
      padding: 10px 24px;
      font-size: 14px;
      font-weight: 700;
      cursor: pointer;
      width: 100%;
      min-height: 44px;
      touch-action: manipulation;
      -webkit-user-select: none;
      user-select: none;
      -webkit-tap-highlight-color: transparent;
    }
    .assign-btn:hover { background: #e8c547; }
    .assign-btn:disabled { opacity: 0.5; cursor: not-allowed; }

    .scan-again-btn {
      background: transparent;
      color: #888;
      border: 1px solid #333;
      border-radius: 8px;
      padding: 10px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      width: 100%;
      margin-top: 12px;
      min-height: 44px;
      touch-action: manipulation;
      -webkit-user-select: none;
      user-select: none;
      -webkit-tap-highlight-color: transparent;
    }
    .scan-again-btn:hover { border-color: #555; color: #ccc; }
  </style>
</head>
<body>
<header>
  <div>
    <h1>AMERICAN SELECT</h1>
    <span>Barcode Scanner</span>
  </div>
  <a href="dashboard.php" class="back-btn">← Dashboard</a>
</header>

<div class="container">
  <div class="camera-wrap">
    <video id="video" autoplay playsinline muted></video>
    <div class="scan-line" id="scan-line" style="display:none;"></div>
    <div class="scan-corner tl"></div>
    <div class="scan-corner tr"></div>
    <div class="scan-corner bl"></div>
    <div class="scan-corner br"></div>
    <div class="camera-placeholder" id="placeholder">
      <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M3 9V6a1 1 0 011-1h3M3 15v3a1 1 0 001 1h3M15 4h3a1 1 0 011 1v3M15 20h3a1 1 0 001-1v-3M7 12h.01M12 12h.01M17 12h.01"/>
      </svg>
      Tap Start to activate camera
    </div>
  </div>

  <button class="start-btn" id="start-btn" onclick="startScanner()">Start Scanner</button>

  <div class="status-bar" id="status-bar"></div>

  <!-- Found product card -->
  <div class="result-card" id="result-card">
    <div class="result-label">Product Found</div>
    <div class="result-product-name" id="result-name"></div>
    <div class="result-barcode" id="result-barcode"></div>
    <div class="qty-row">
      <span class="qty-label">Stock:</span>
      <input type="number" class="qty-input" id="qty-input" min="0" inputmode="numeric">
      <button class="save-stock-btn" id="save-stock-btn" onclick="saveStock()">Save</button>
    </div>
    <button class="scan-again-btn" onclick="resetScanner()">📷 Scan Another</button>
  </div>

  <!-- Unknown barcode card -->
  <div class="result-card" id="assign-card">
    <div class="result-label">Unknown Barcode</div>
    <div class="result-barcode" id="assign-barcode"></div>
    <hr class="divider">
    <label class="assign-label">Assign this barcode to a product:</label>
    <select class="assign-select" id="assign-select">
      <option value="">— Select product —</option>
      <?php foreach ($products as $p): ?>
        <option value="<?= htmlspecialchars($p['name']) ?>"><?= htmlspecialchars($p['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="assign-btn" onclick="assignBarcode()">Assign &amp; Save Stock</button>
    <button class="scan-again-btn" onclick="resetScanner()">📷 Scan Another</button>
  </div>
</div>

<script src="https://unpkg.com/@zxing/library@0.18.6/umd/index.min.js"></script>
<script>
  let codeReader = null;
  let scanning = false;
  let lastBarcode = '';
  let debounceTimer = null;
  let currentProductName = '';

  function setStatus(msg, type) {
    const el = document.getElementById('status-bar');
    el.textContent = msg;
    el.className = 'status-bar ' + type;
  }

  function startScanner() {
    const btn = document.getElementById('start-btn');
    btn.disabled = true;
    btn.textContent = 'Starting...';

    const placeholder = document.getElementById('placeholder');
    placeholder.style.display = 'none';

    codeReader = new ZXing.BrowserMultiFormatReader();
    codeReader.decodeFromConstraints(
      { video: { facingMode: 'environment' } },
      'video',
      (result, err) => {
        if (result && !scanning) {
          const code = result.getText();
          if (code === lastBarcode) return;
          clearTimeout(debounceTimer);
          debounceTimer = setTimeout(() => {
            lastBarcode = code;
            handleBarcode(code);
          }, 300);
        }
      }
    ).then(() => {
      scanning = false;
      document.getElementById('scan-line').style.display = 'block';
      btn.textContent = 'Scanning...';
      setStatus('Point camera at a barcode', 'scanning');
    }).catch(err => {
      btn.disabled = false;
      btn.textContent = 'Start Scanner';
      placeholder.style.display = 'flex';
      setStatus('Camera error: ' + (err.message || 'Could not access camera'), 'error');
    });
  }

  function handleBarcode(barcode) {
    scanning = true;
    document.getElementById('scan-line').style.display = 'none';
    setStatus('Barcode detected: ' + barcode, 'found');

    fetch('/api/barcode.php?barcode=' + encodeURIComponent(barcode))
      .then(r => r.json())
      .then(data => {
        if (data.error) {
          setStatus('Error: ' + data.error, 'error');
          scanning = false;
          return;
        }
        if (data.found) {
          showProductCard(barcode, data.product_name, data.quantity);
        } else {
          showAssignCard(barcode);
        }
      })
      .catch(() => {
        setStatus('Network error — check connection', 'error');
        scanning = false;
      });
  }

  function showProductCard(barcode, productName, quantity) {
    currentProductName = productName;
    document.getElementById('result-name').textContent = productName;
    document.getElementById('result-barcode').textContent = 'Barcode: ' + barcode;
    document.getElementById('qty-input').value = quantity;
    document.getElementById('result-card').classList.add('visible');
    document.getElementById('assign-card').classList.remove('visible');
    setStatus('✓ Product matched — update stock below', 'found');
    if (codeReader) codeReader.reset();
    document.getElementById('start-btn').textContent = 'Scanning Stopped';
  }

  function showAssignCard(barcode) {
    document.getElementById('assign-barcode').textContent = 'Barcode: ' + barcode;
    document.getElementById('assign-select').value = '';
    document.getElementById('assign-card').classList.add('visible');
    document.getElementById('result-card').classList.remove('visible');
    setStatus('Unknown barcode — assign it to a product below', 'unknown');
    if (codeReader) codeReader.reset();
    document.getElementById('start-btn').textContent = 'Scanning Stopped';

    // Store barcode for assign action
    document.getElementById('assign-card').dataset.barcode = barcode;
  }

  function saveStock() {
    const qty = parseInt(document.getElementById('qty-input').value, 10);
    if (isNaN(qty) || qty < 0) { setStatus('Enter a valid quantity', 'error'); return; }
    const btn = document.getElementById('save-stock-btn');
    btn.disabled = true;
    btn.textContent = '...';

    fetch('/api/barcode.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'stock', product_name: currentProductName, quantity: qty })
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        setStatus('✓ Stock updated to ' + qty, 'found');
      } else {
        setStatus('Error: ' + (data.error || 'Failed'), 'error');
      }
    })
    .catch(() => setStatus('Network error', 'error'))
    .finally(() => { btn.disabled = false; btn.textContent = 'Save'; });
  }

  function assignBarcode() {
    const barcode = document.getElementById('assign-card').dataset.barcode;
    const productName = document.getElementById('assign-select').value;
    if (!productName) { setStatus('Please select a product first', 'error'); return; }

    const btn = document.querySelector('.assign-btn');
    btn.disabled = true;
    btn.textContent = '...';

    fetch('/api/barcode.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'assign', barcode, product_name: productName })
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        showProductCard(barcode, productName, data.quantity);
      } else {
        setStatus('Error: ' + (data.error || 'Failed'), 'error');
        btn.disabled = false;
        btn.textContent = 'Assign & Save Stock';
      }
    })
    .catch(() => {
      setStatus('Network error', 'error');
      btn.disabled = false;
      btn.textContent = 'Assign & Save Stock';
    });
  }

  function resetScanner() {
    lastBarcode = '';
    scanning = false;
    currentProductName = '';
    document.getElementById('result-card').classList.remove('visible');
    document.getElementById('assign-card').classList.remove('visible');
    document.getElementById('status-bar').className = 'status-bar';
    document.getElementById('start-btn').disabled = false;
    document.getElementById('start-btn').textContent = 'Start Scanner';
    document.getElementById('scan-line').style.display = 'none';
    document.getElementById('placeholder').style.display = 'flex';
    if (codeReader) { codeReader.reset(); codeReader = null; }
  }
</script>
</body>
</html>
