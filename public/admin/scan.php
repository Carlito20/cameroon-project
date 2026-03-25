<?php
require_once __DIR__ . '/../api/db.php';
session_start();
if (empty($_SESSION['admin_logged_in'])) { header('Location: index.php'); exit; }

$jsonPath = __DIR__ . '/../api/products-list.json';
$products = [];
if (file_exists($jsonPath)) $products = json_decode(file_get_contents($jsonPath), true) ?? [];
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
      background: #0a0a0a; color: #e0e0e0;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      min-height: 100vh; min-height: -webkit-fill-available;
      -webkit-overflow-scrolling: touch; overflow-x: hidden;
    }
    header {
      background: #111; border-bottom: 1px solid #222;
      padding: 16px 24px;
      padding-top: calc(16px + env(safe-area-inset-top, 0px));
      padding-left: calc(24px + env(safe-area-inset-left, 0px));
      padding-right: calc(24px + env(safe-area-inset-right, 0px));
      display: flex; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 100;
      -webkit-transform: translateZ(0); transform: translateZ(0); will-change: transform;
    }
    header h1 { color: #d4af37; font-size: 18px; font-weight: 700; letter-spacing: 1px; }
    header span { color: #666; font-size: 13px; }
    .back-btn {
      background: transparent; color: #888; border: 1px solid #333; border-radius: 6px;
      padding: 8px 16px; font-size: 13px; font-weight: 600; cursor: pointer;
      text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
      min-height: 44px; touch-action: manipulation;
      -webkit-user-select: none; user-select: none; -webkit-tap-highlight-color: transparent;
    }
    .back-btn:hover { color: #ccc; border-color: #555; }
    .container {
      max-width: 520px; margin: 0 auto;
      padding: 20px 16px;
      padding-bottom: calc(40px + env(safe-area-inset-bottom, 0px));
      padding-left: calc(16px + env(safe-area-inset-left, 0px));
      padding-right: calc(16px + env(safe-area-inset-right, 0px));
    }

    /* Camera */
    .camera-wrap {
      position: relative; width: 100%; aspect-ratio: 1/1;
      background: #111; border-radius: 16px; overflow: hidden; border: 2px solid #222;
    }
    #video { width: 100%; height: 100%; object-fit: cover; display: block; }
    .scan-line {
      position: absolute; left: 10%; right: 10%; height: 2px;
      background: #d4af37; box-shadow: 0 0 8px #d4af37;
      animation: scanMove 2s linear infinite; border-radius: 2px; display: none;
    }
    @keyframes scanMove { 0%{top:15%} 50%{top:80%} 100%{top:15%} }
    .scan-corner { position: absolute; width: 28px; height: 28px; border-color: #d4af37; border-style: solid; }
    .scan-corner.tl { top:12%;left:8%;border-width:3px 0 0 3px;border-radius:4px 0 0 0; }
    .scan-corner.tr { top:12%;right:8%;border-width:3px 3px 0 0;border-radius:0 4px 0 0; }
    .scan-corner.bl { bottom:12%;left:8%;border-width:0 0 3px 3px;border-radius:0 0 0 4px; }
    .scan-corner.br { bottom:12%;right:8%;border-width:0 3px 3px 0;border-radius:0 0 4px 0; }
    .camera-placeholder {
      position: absolute; inset: 0; display: flex; flex-direction: column;
      align-items: center; justify-content: center; gap: 12px; color: #444; font-size: 14px;
    }
    .camera-placeholder svg { opacity: 0.4; }

    .start-btn {
      background: #d4af37; color: #000; border: none; border-radius: 10px;
      padding: 14px; font-size: 15px; font-weight: 700; cursor: pointer;
      width: 100%; margin-top: 14px; min-height: 52px;
      touch-action: manipulation; -webkit-user-select: none; user-select: none;
      -webkit-tap-highlight-color: transparent;
    }
    .start-btn:hover { background: #e8c547; }
    .start-btn:disabled { opacity: 0.5; cursor: not-allowed; }

    .status-bar {
      margin-top: 14px; padding: 12px 16px; border-radius: 10px;
      font-size: 14px; display: none;
    }
    .status-bar.scanning { background:#0d1a2a;border:1px solid #1a3a5c;color:#5b9bd5;display:block; }
    .status-bar.found    { background:#0d1a0d;border:1px solid #1a3a1a;color:#6dbf6d;display:block; }
    .status-bar.unknown  { background:#1a1a0d;border:1px solid #3a3a1a;color:#c8b84a;display:block; }
    .status-bar.error    { background:#2a0a0a;border:1px solid #5c1a1a;color:#ff6b6b;display:block; }

    /* Result card */
    .result-card {
      margin-top: 18px; background: #111; border: 1px solid #222;
      border-radius: 14px; padding: 20px; display: none;
    }
    .result-card.visible { display: block; }
    .result-label { font-size: 11px; color: #555; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
    .result-product-name { font-size: 15px; font-weight: 600; color: #e0e0e0; line-height: 1.4; margin-bottom: 6px; }
    .result-stock { font-size: 13px; color: #888; margin-bottom: 4px; }
    .result-stock span { color: #d4af37; font-weight: 700; font-size: 16px; }
    .result-price { font-size: 13px; color: #888; margin-bottom: 16px; }
    .result-price span { color: #6dbf6d; font-weight: 700; font-size: 16px; }
    .result-barcode { font-size: 11px; color: #444; margin-bottom: 16px; font-family: monospace; }

    /* Action buttons */
    .action-grid {
      display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px;
    }
    .action-btn {
      padding: 12px 8px; border-radius: 10px; border: 2px solid transparent;
      font-size: 14px; font-weight: 700; cursor: pointer; text-align: center;
      transition: all 0.2s; min-height: 52px;
      touch-action: manipulation; -webkit-user-select: none; user-select: none;
      -webkit-tap-highlight-color: transparent;
    }
    .action-btn .action-icon { font-size: 18px; display: block; margin-bottom: 2px; }
    .action-btn.received  { background:#0d1a0d; color:#6dbf6d; border-color:#1a3a1a; }
    .action-btn.sold      { background:#0d1020; color:#7b9fd4; border-color:#1a2a40; }
    .action-btn.damaged   { background:#2a1a0a; color:#d4884a; border-color:#3a2a1a; }
    .action-btn.returned  { background:#1a0d1a; color:#b47bd4; border-color:#2a1a3a; }
    .action-btn.selected.received { background:#1e361e; border-color:#6dbf6d; }
    .action-btn.selected.sold     { background:#1a2a40; border-color:#7b9fd4; }
    .action-btn.selected.damaged  { background:#3a2a1a; border-color:#d4884a; }
    .action-btn.selected.returned { background:#2a1a3a; border-color:#b47bd4; }

    .qty-row { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
    .qty-label { font-size: 13px; color: #888; white-space: nowrap; }
    .qty-input {
      width: 90px; padding: 10px 12px; background: #1a1a1a; border: 1px solid #2a2a2a;
      border-radius: 8px; color: #e0e0e0; font-size: 18px; font-weight: 700;
      text-align: center; outline: none; -webkit-appearance: none; appearance: none;
      touch-action: manipulation; min-height: 44px;
    }
    .qty-input:focus { border-color: #d4af37; }
    .note-input {
      width: 100%; padding: 10px 12px; background: #1a1a1a; border: 1px solid #2a2a2a;
      border-radius: 8px; color: #e0e0e0; font-size: 14px; outline: none;
      -webkit-appearance: none; appearance: none; margin-bottom: 12px; min-height: 44px;
    }
    .note-input:focus { border-color: #d4af37; }
    .note-input::placeholder { color: #444; }

    .confirm-btn {
      background: #d4af37; color: #000; border: none; border-radius: 8px;
      padding: 12px; font-size: 15px; font-weight: 700; cursor: pointer;
      width: 100%; min-height: 48px;
      touch-action: manipulation; -webkit-user-select: none; user-select: none;
      -webkit-tap-highlight-color: transparent;
    }
    .confirm-btn:hover { background: #e8c547; }
    .confirm-btn:disabled { opacity: 0.5; cursor: not-allowed; }
    .divider { border:none; border-top:1px solid #1a1a1a; margin:16px 0; }

    /* Assign form */
    .assign-label { font-size: 13px; color: #888; margin-bottom: 8px; display: block; }
    .assign-select {
      width: 100%; padding: 10px 12px; background: #1a1a1a; border: 1px solid #2a2a2a;
      border-radius: 8px; color: #e0e0e0; font-size: 15px; outline: none;
      -webkit-appearance: none; appearance: none; touch-action: manipulation;
      min-height: 44px; margin-bottom: 12px;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23888' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
      background-repeat: no-repeat; background-position: right 12px center; padding-right: 36px;
    }
    .assign-select:focus { border-color: #d4af37; }
    .assign-btn {
      background: #d4af37; color: #000; border: none; border-radius: 8px;
      padding: 12px; font-size: 14px; font-weight: 700; cursor: pointer;
      width: 100%; min-height: 44px;
      touch-action: manipulation; -webkit-user-select: none; user-select: none;
      -webkit-tap-highlight-color: transparent;
    }
    .assign-btn:hover { background: #e8c547; }
    .assign-btn:disabled { opacity: 0.5; cursor: not-allowed; }
    .scan-again-btn {
      background: transparent; color: #666; border: 1px solid #2a2a2a; border-radius: 8px;
      padding: 10px; font-size: 13px; font-weight: 600; cursor: pointer;
      width: 100%; margin-top: 10px; min-height: 44px;
      touch-action: manipulation; -webkit-user-select: none; user-select: none;
      -webkit-tap-highlight-color: transparent;
    }
    .scan-again-btn:hover { border-color: #555; color: #ccc; }
  </style>
</head>
<body>
<header>
  <div><h1>AMERICAN SELECT</h1><span>Barcode Scanner</span></div>
  <a href="dashboard.php" class="back-btn">← Dashboard</a>
</header>

<div class="container">
  <div class="camera-wrap">
    <video id="video" autoplay playsinline muted></video>
    <div class="scan-line" id="scan-line"></div>
    <div class="scan-corner tl"></div><div class="scan-corner tr"></div>
    <div class="scan-corner bl"></div><div class="scan-corner br"></div>
    <div class="camera-placeholder" id="placeholder">
      <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M3 9V6a1 1 0 011-1h3M3 15v3a1 1 0 001 1h3M15 4h3a1 1 0 011 1v3M15 20h3a1 1 0 001-1v-3"/>
      </svg>
      Tap Start to activate camera
    </div>
  </div>

  <!-- Physical/USB scanner input -->
  <div style="display:flex;gap:8px;margin-top:14px;align-items:center;">
    <input type="text" id="scanner-input"
           placeholder="Scan barcode with physical scanner…"
           autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" inputmode="text"
           style="flex:1;padding:13px 14px;background:#1a1a1a;border:2px solid #2a2a2a;border-radius:8px;
                  color:#e0e0e0;font-size:16px;outline:none;-webkit-appearance:none;appearance:none;
                  min-height:50px;touch-action:manipulation;"
           onfocus="this.style.borderColor='#d4af37'" onblur="this.style.borderColor='#2a2a2a'">
    <button onclick="startScanner()" id="start-btn"
            style="padding:0 16px;background:#d4af37;color:#000;border:none;border-radius:8px;
                   font-size:14px;font-weight:800;cursor:pointer;min-height:50px;white-space:nowrap;
                   touch-action:manipulation;-webkit-user-select:none;user-select:none;">
      📷 Camera
    </button>
  </div>
  <div class="status-bar" id="status-bar"></div>

  <!-- Found product card -->
  <div class="result-card" id="result-card">
    <div class="result-label">Product</div>
    <div class="result-product-name" id="result-name"></div>
    <div class="result-stock">Stock: <span id="result-stock">0</span></div>
    <div class="result-price">Price: <span id="result-price">—</span></div>
    <div class="result-barcode" id="result-barcode"></div>

    <div class="action-grid">
      <button class="action-btn received" onclick="selectAction('received')">
        <span class="action-icon">📦</span>Received
      </button>
      <button class="action-btn sold" onclick="selectAction('sold')">
        <span class="action-icon">✅</span>Sold
      </button>
      <button class="action-btn damaged" onclick="selectAction('damaged')">
        <span class="action-icon">⚠️</span>Damaged
      </button>
      <button class="action-btn returned" onclick="selectAction('returned')">
        <span class="action-icon">↩️</span>Returned
      </button>
    </div>

    <div class="qty-row">
      <span class="qty-label">Qty:</span>
      <input type="number" class="qty-input" id="qty-input" value="1" min="1" inputmode="numeric">
    </div>
    <input type="text" class="note-input" id="note-input" placeholder="Note (optional)">
    <button class="confirm-btn" id="confirm-btn" onclick="confirmTransaction()" disabled>Select an action above</button>
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
    <button class="assign-btn" onclick="assignBarcode()">Assign Product</button>
    <button class="scan-again-btn" onclick="resetScanner()">📷 Scan Another</button>
  </div>
</div>

<script src="https://unpkg.com/@zxing/library@0.18.6/umd/index.min.js"></script>
<script>
  let codeReader = null, scanning = false, lastBarcode = '', debounceTimer = null;
  let currentProductName = '', selectedAction = '';

  // Physical/USB scanner input
  const scannerInput = document.getElementById('scanner-input');
  scannerInput.focus();
  scannerInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      const v = this.value.trim();
      if (v && !scanning) { this.value = ''; lastBarcode = v; handleBarcode(v); }
    }
  });

  function setStatus(msg, type) {
    const el = document.getElementById('status-bar');
    el.textContent = msg; el.className = 'status-bar ' + type;
  }

  function startScanner() {
    const btn = document.getElementById('start-btn');
    btn.disabled = true; btn.textContent = 'Starting...';
    document.getElementById('placeholder').style.display = 'none';
    codeReader = new ZXing.BrowserMultiFormatReader();
    codeReader.decodeFromConstraints(
      { video: { facingMode: 'environment' } }, 'video',
      (result, err) => {
        if (result && !scanning) {
          const code = result.getText();
          if (code === lastBarcode) return;
          clearTimeout(debounceTimer);
          debounceTimer = setTimeout(() => { lastBarcode = code; handleBarcode(code); }, 300);
        }
      }
    ).then(() => {
      document.getElementById('scan-line').style.display = 'block';
      btn.textContent = 'Scanning...';
      setStatus('Point camera at a barcode', 'scanning');
    }).catch(err => {
      btn.disabled = false; btn.textContent = 'Start Scanner';
      document.getElementById('placeholder').style.display = 'flex';
      setStatus('Camera error: ' + (err.message || 'Could not access camera'), 'error');
    });
  }

  function handleBarcode(barcode) {
    scanning = true;
    document.getElementById('scan-line').style.display = 'none';
    setStatus('Reading barcode...', 'scanning');
    fetch('/api/barcode.php?barcode=' + encodeURIComponent(barcode))
      .then(r => r.json())
      .then(data => {
        if (data.error) { setStatus('Error: ' + data.error, 'error'); scanning = false; return; }
        if (data.found) showProductCard(barcode, data.product_name, data.quantity, data.price);
        else showAssignCard(barcode);
      })
      .catch(() => { setStatus('Network error', 'error'); scanning = false; });
  }

  function showProductCard(barcode, productName, quantity, price) {
    currentProductName = productName; selectedAction = '';
    document.getElementById('result-name').textContent = productName;
    document.getElementById('result-stock').textContent = quantity;
    document.getElementById('result-price').textContent = price ? price.toLocaleString() + ' FCFA' : '—';
    document.getElementById('result-barcode').textContent = 'Barcode: ' + barcode;
    document.getElementById('qty-input').value = 1;
    document.getElementById('note-input').value = '';
    document.querySelectorAll('.action-btn').forEach(b => b.classList.remove('selected'));
    document.getElementById('confirm-btn').disabled = true;
    document.getElementById('confirm-btn').textContent = 'Select an action above';
    document.getElementById('result-card').classList.add('visible');
    document.getElementById('assign-card').classList.remove('visible');
    setStatus('✓ Product found — select action below', 'found');
    if (codeReader) codeReader.reset();
    document.getElementById('start-btn').textContent = 'Scanning Stopped';
  }

  function showAssignCard(barcode) {
    document.getElementById('assign-barcode').textContent = 'Barcode: ' + barcode;
    document.getElementById('assign-select').value = '';
    document.getElementById('assign-card').dataset.barcode = barcode;
    document.getElementById('assign-card').classList.add('visible');
    document.getElementById('result-card').classList.remove('visible');
    setStatus('Unknown barcode — assign to a product below', 'unknown');
    if (codeReader) codeReader.reset();
    document.getElementById('start-btn').textContent = 'Scanning Stopped';
  }

  function selectAction(action) {
    selectedAction = action;
    document.querySelectorAll('.action-btn').forEach(b => b.classList.remove('selected'));
    document.querySelector('.action-btn.' + action).classList.add('selected');
    const labels = { received:'Confirm Received', sold:'Confirm Sold', damaged:'Confirm Damaged', returned:'Confirm Returned' };
    const btn = document.getElementById('confirm-btn');
    btn.disabled = false; btn.textContent = labels[action];
  }

  function confirmTransaction() {
    if (!selectedAction || !currentProductName) return;
    const qty = parseInt(document.getElementById('qty-input').value, 10);
    if (isNaN(qty) || qty <= 0) { setStatus('Enter a valid quantity', 'error'); return; }
    const note = document.getElementById('note-input').value;
    const btn = document.getElementById('confirm-btn');
    btn.disabled = true; btn.textContent = 'Saving...';

    fetch('/api/barcode.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action:'transaction', product_name:currentProductName, tx_action:selectedAction, quantity:qty, note })
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        document.getElementById('result-stock').textContent = data.stock_after;
        const actionLabel = { received:'Received', sold:'Sold', damaged:'Damaged', returned:'Returned' }[selectedAction];
        setStatus('✓ ' + actionLabel + ' ' + qty + ' — stock now ' + data.stock_after, 'found');
        btn.textContent = '✓ Done';
        selectedAction = '';
        document.querySelectorAll('.action-btn').forEach(b => b.classList.remove('selected'));
      } else {
        setStatus('Error: ' + (data.error || 'Failed'), 'error');
        btn.disabled = false; btn.textContent = 'Try Again';
      }
    })
    .catch(() => { setStatus('Network error', 'error'); btn.disabled = false; btn.textContent = 'Try Again'; });
  }

  function assignBarcode() {
    const barcode = document.getElementById('assign-card').dataset.barcode;
    const productName = document.getElementById('assign-select').value;
    if (!productName) { setStatus('Please select a product first', 'error'); return; }
    const btn = document.querySelector('.assign-btn');
    btn.disabled = true; btn.textContent = 'Assigning...';
    fetch('/api/barcode.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action:'assign', barcode, product_name:productName })
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) showProductCard(barcode, productName, data.quantity);
      else { setStatus('Error: ' + (data.error||'Failed'), 'error'); btn.disabled=false; btn.textContent='Assign Product'; }
    })
    .catch(() => { setStatus('Network error', 'error'); btn.disabled=false; btn.textContent='Assign Product'; });
  }

  function resetScanner() {
    lastBarcode = ''; scanning = false; currentProductName = ''; selectedAction = '';
    document.getElementById('result-card').classList.remove('visible');
    document.getElementById('assign-card').classList.remove('visible');
    document.getElementById('status-bar').className = 'status-bar';
    document.getElementById('scan-line').style.display = 'none';
    document.getElementById('placeholder').style.display = 'flex';
    if (codeReader) { codeReader.reset(); codeReader = null; }
    document.getElementById('scanner-input').value = '';
    document.getElementById('scanner-input').focus();
  }
</script>
</body>
</html>
