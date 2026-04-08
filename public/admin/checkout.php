<?php
require_once __DIR__ . '/../api/db.php';
session_start();
if (empty($_SESSION['admin_logged_in'])) { header('Location: index.php'); exit; }

$jsonPath = __DIR__ . '/../api/products-list.json';
$products = [];
if (file_exists($jsonPath)) {
    $products = json_decode(file_get_contents($jsonPath), true) ?? [];
    usort($products, fn($a, $b) => strcmp($a['name'], $b['name']));
}

// Pre-load order from pending_orders if from_order param given
$preloadOrder = null;
$fromOrderId  = (int)($_GET['from_order'] ?? 0);
if ($fromOrderId) {
    try {
        $pdo  = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $stmt = $pdo->prepare('SELECT * FROM pending_orders WHERE id = ? AND status = "pending"');
        $stmt->execute([$fromOrderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $preloadOrder = [
                'id'             => $row['id'],
                'order_ref'      => $row['order_ref'],
                'payment_method' => $row['payment_method'],
                'items'          => json_decode($row['items'], true) ?? [],
                'total'          => $row['total']
            ];
        }
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Checkout — American Select</title>
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
      padding: 14px 20px;
      padding-top: calc(14px + env(safe-area-inset-top, 0px));
      padding-left: calc(20px + env(safe-area-inset-left, 0px));
      padding-right: calc(20px + env(safe-area-inset-right, 0px));
      display: flex; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 100;
      -webkit-transform: translateZ(0); transform: translateZ(0); will-change: transform;
    }
    header h1 { color: #d4af37; font-size: 17px; font-weight: 800; letter-spacing: 1px; }
    header span { color: #555; font-size: 12px; }
    .header-btns { display: flex; gap: 8px; align-items: center; }
    .back-btn {
      background: transparent; color: #888; border: 1px solid #333; border-radius: 6px;
      padding: 7px 13px; font-size: 13px; font-weight: 600; cursor: pointer;
      text-decoration: none; display: inline-flex; align-items: center; gap: 5px;
      min-height: 44px; touch-action: manipulation; -webkit-user-select: none; user-select: none;
      -webkit-tap-highlight-color: transparent;
    }
    .back-btn:hover { color: #ccc; border-color: #555; }
    .btn-new {
      background: transparent; color: #d4af37; border: 1px solid #3a3010; border-radius: 6px;
      padding: 7px 13px; font-size: 13px; font-weight: 700; cursor: pointer;
      min-height: 44px; touch-action: manipulation; -webkit-user-select: none; user-select: none;
      -webkit-tap-highlight-color: transparent; display: none;
    }
    .btn-new:hover { background: #1a1500; }

    .container {
      max-width: 680px; margin: 0 auto;
      padding: 16px;
      padding-bottom: calc(40px + env(safe-area-inset-bottom, 0px));
      padding-left: calc(16px + env(safe-area-inset-left, 0px));
      padding-right: calc(16px + env(safe-area-inset-right, 0px));
    }

    /* ── Scanner section ── */
    .scan-section {
      background: #111; border: 1px solid #1e1e1e; border-radius: 12px;
      padding: 14px; margin-bottom: 16px;
    }
    .scan-row { display: flex; gap: 8px; align-items: center; }
    .barcode-input {
      flex: 1; padding: 12px 14px; background: #1a1a1a;
      border: 2px solid #2a2a2a; border-radius: 8px; color: #e0e0e0;
      font-size: 16px; outline: none; -webkit-appearance: none; appearance: none;
      min-height: 50px; touch-action: manipulation;
    }
    .barcode-input:focus { border-color: #d4af37; }
    .btn-camera {
      background: #1a1a1a; color: #888; border: 1px solid #2a2a2a; border-radius: 8px;
      padding: 0; font-size: 22px; cursor: pointer; min-height: 50px; min-width: 50px;
      display: flex; align-items: center; justify-content: center;
      touch-action: manipulation; -webkit-user-select: none; user-select: none;
      -webkit-tap-highlight-color: transparent; flex-shrink: 0;
    }
    .btn-camera.active { background: #0d1a0d; color: #6dbf6d; border-color: #1a3a1a; }
    .camera-wrap {
      margin-top: 12px; display: none; position: relative;
      border-radius: 10px; overflow: hidden; background: #000;
    }
    .camera-wrap.visible { display: block; }
    .camera-wrap video { width: 100%; max-height: 240px; object-fit: cover; display: block; }
    .scan-line-anim {
      position: absolute; left: 8%; right: 8%; height: 2px;
      background: #d4af37; box-shadow: 0 0 8px #d4af37; opacity: 0.9;
      animation: scanpulse 2s ease-in-out infinite;
    }
    @keyframes scanpulse { 0%,100%{top:25%} 50%{top:75%} }
    .scan-status { margin-top: 10px; font-size: 13px; color: #555; min-height: 18px; }
    .scan-status.ok { color: #6dbf6d; }
    .scan-status.err { color: #e05c5c; }
    .scan-status.warn { color: #d4884a; }

    /* Manual add */
    .manual-toggle {
      display: inline-block; margin-top: 10px; font-size: 12px; color: #444;
      cursor: pointer; text-decoration: underline; touch-action: manipulation;
      -webkit-tap-highlight-color: transparent; -webkit-user-select: none; user-select: none;
    }
    .manual-toggle:hover { color: #777; }
    .manual-wrap { display: none; margin-top: 10px; }
    .manual-wrap.open { display: block; }
    .manual-input {
      width: 100%; padding: 10px 14px; background: #1a1a1a;
      border: 1px solid #2a2a2a; border-radius: 8px; color: #e0e0e0;
      font-size: 15px; outline: none; -webkit-appearance: none; appearance: none;
      min-height: 44px; touch-action: manipulation;
    }
    .manual-input:focus { border-color: #555; }
    .manual-results {
      background: #161616; border: 1px solid #222; border-radius: 8px;
      margin-top: 6px; max-height: 220px; overflow-y: auto;
      -webkit-overflow-scrolling: touch; overscroll-behavior: contain; display: none;
    }
    .manual-item {
      padding: 10px 14px; cursor: pointer; border-bottom: 1px solid #1e1e1e;
      touch-action: manipulation; -webkit-tap-highlight-color: transparent;
      -webkit-user-select: none; user-select: none;
    }
    .manual-item:last-child { border-bottom: none; }
    .manual-item:active { background: #222; }
    .manual-item-name { font-size: 13px; color: #ccc; line-height: 1.4; }
    .manual-item-price { font-size: 12px; color: #6dbf6d; margin-top: 2px; }

    /* ── Cart ── */
    .cart-box {
      background: #111; border: 1px solid #1e1e1e; border-radius: 12px; overflow: hidden;
    }
    .cart-head {
      padding: 12px 16px; border-bottom: 1px solid #1a1a1a;
      display: flex; align-items: center; justify-content: space-between;
    }
    .cart-title { font-size: 14px; font-weight: 700; color: #e0e0e0; }
    .cart-badge { font-size: 12px; color: #555; }
    .cart-empty {
      padding: 50px 20px; text-align: center; color: #2a2a2a; font-size: 15px;
    }
    .empty-icon { font-size: 48px; margin-bottom: 12px; filter: grayscale(1) opacity(0.3); }
    .cart-item {
      display: grid; grid-template-columns: 1fr auto auto auto;
      gap: 10px; align-items: center; padding: 12px 16px;
      border-bottom: 1px solid #161616;
    }
    .cart-item:last-child { border-bottom: none; }
    .ci-name { font-size: 13px; color: #ddd; line-height: 1.4; }
    .ci-unit { font-size: 11px; color: #555; margin-top: 2px; }
    .ci-linetotal { font-size: 13px; color: #6dbf6d; margin-top: 2px; font-weight: 600; }
    .ci-stock-warn { font-size: 11px; color: #d4884a; margin-top: 2px; }
    .qty-ctrl { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
    .qty-btn {
      width: 30px; height: 30px; min-width: 30px; min-height: 30px;
      border-radius: 50%; background: #1a1a1a; border: 1px solid #2a2a2a;
      color: #ccc; font-size: 17px; font-weight: 700; cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      touch-action: manipulation; -webkit-tap-highlight-color: transparent;
      -webkit-user-select: none; user-select: none;
    }
    .qty-btn:active { background: #2a2a2a; }
    .qty-num { font-size: 14px; font-weight: 700; min-width: 22px; text-align: center; }
    .remove-btn {
      width: 30px; height: 30px; min-width: 30px; min-height: 30px;
      border-radius: 50%; background: transparent; border: 1px solid #2a1a1a;
      color: #553333; font-size: 16px; cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      touch-action: manipulation; -webkit-tap-highlight-color: transparent;
      -webkit-user-select: none; user-select: none; flex-shrink: 0;
    }
    .remove-btn:active { background: #2a1212; color: #e05c5c; }

    /* ── Footer / Total ── */
    .cart-foot {
      background: #0d0d0d; border-top: 2px solid #1e1e1e; padding: 16px; display: none;
    }
    .total-row { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 14px; }
    .total-lbl { font-size: 12px; color: #555; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
    .total-units { font-size: 12px; color: #444; }
    .total-price { font-size: 28px; font-weight: 800; color: #d4af37; line-height: 1; }
    .btn-checkout {
      width: 100%; padding: 16px; background: #d4af37; color: #000;
      border: none; border-radius: 10px; font-size: 16px; font-weight: 800;
      cursor: pointer; letter-spacing: 0.5px; min-height: 54px;
      touch-action: manipulation; -webkit-user-select: none; user-select: none;
      -webkit-tap-highlight-color: transparent; transition: background 0.15s;
    }
    .btn-checkout:hover:not(:disabled) { background: #c8a428; }
    .btn-checkout:disabled { background: #1e1e1e; color: #333; cursor: not-allowed; }
    .checkout-msg { font-size: 12px; text-align: center; margin-top: 10px; min-height: 16px; color: #555; }
    .checkout-msg.err { color: #e05c5c; }
    .checkout-msg.warn { color: #d4884a; }
    .phone-label { font-size: 12px; color: #555; text-transform: uppercase; letter-spacing: 0.5px; margin: 14px 0 6px; }
    .phone-input {
      width: 100%; padding: 10px 14px; background: #1a1a1a;
      border: 1px solid #2a2a2a; border-radius: 8px; color: #e0e0e0;
      font-size: 15px; outline: none; -webkit-appearance: none; appearance: none;
      min-height: 44px; touch-action: manipulation; margin-bottom: 12px;
    }
    .phone-input:focus { border-color: #555; }
    .btn-wa-confirm-receipt {
      flex: 1; min-width: 100%; padding: 12px; background: #0d2010; color: #25d366;
      border: 1px solid #1a4a20; border-radius: 8px; font-size: 14px; font-weight: 700;
      cursor: pointer; min-height: 44px; touch-action: manipulation;
      -webkit-user-select: none; user-select: none; -webkit-tap-highlight-color: transparent;
      text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 6px;
    }
    .btn-wa-confirm-receipt:hover { background: #112a18; }
    .pay-label { font-size: 12px; color: #555; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
    .pay-methods { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 14px; }
    .pay-btn {
      flex: 1; min-width: calc(50% - 4px); padding: 10px 8px; border-radius: 8px;
      border: 1px solid #2a2a2a; background: #1a1a1a; color: #888;
      font-size: 13px; font-weight: 600; cursor: pointer; text-align: center;
      touch-action: manipulation; -webkit-user-select: none; user-select: none;
      -webkit-tap-highlight-color: transparent; min-height: 44px;
    }
    .pay-btn.selected { border-color: #d4af37; background: #1a1500; color: #d4af37; }
    .pay-btn:active { background: #222; }

    /* ── Receipt overlay ── */
    .receipt-overlay {
      position: fixed; inset: 0; z-index: 300;
      background: rgba(0,0,0,0.93);
      -webkit-backdrop-filter: blur(6px); backdrop-filter: blur(6px);
      display: none; align-items: center; justify-content: center;
      padding: 20px;
      padding-top: calc(20px + env(safe-area-inset-top, 0px));
      padding-bottom: calc(20px + env(safe-area-inset-bottom, 0px));
      padding-left: calc(20px + env(safe-area-inset-left, 0px));
      padding-right: calc(20px + env(safe-area-inset-right, 0px));
    }
    .receipt-overlay.open { display: flex; }
    .receipt-card {
      background: #111; border: 1px solid #2a2a2a; border-radius: 16px;
      padding: 28px 22px; width: 100%; max-width: 420px;
      max-height: 85vh; overflow-y: auto;
      -webkit-overflow-scrolling: touch; overscroll-behavior: contain;
    }
    .receipt-logo { color: #d4af37; font-size: 18px; font-weight: 800; letter-spacing: 2px; text-align: center; }
    .receipt-tagline { color: #444; font-size: 11px; text-align: center; margin-top: 3px; margin-bottom: 6px; }
    .receipt-date { color: #555; font-size: 12px; text-align: center; margin-bottom: 18px; }
    .receipt-divider { border: none; border-top: 1px dashed #2a2a2a; margin: 14px 0; }
    .receipt-row {
      display: flex; justify-content: space-between; gap: 10px; margin-bottom: 10px;
    }
    .receipt-item-name { font-size: 13px; color: #ccc; flex: 1; line-height: 1.4; }
    .receipt-item-meta { font-size: 11px; color: #555; margin-top: 2px; }
    .receipt-item-amt { font-size: 13px; font-weight: 700; color: #e0e0e0; white-space: nowrap; align-self: flex-start; }
    .receipt-total-row { display: flex; justify-content: space-between; align-items: center; }
    .receipt-total-lbl { font-size: 14px; font-weight: 700; color: #888; }
    .receipt-total-amt { font-size: 24px; font-weight: 800; color: #d4af37; }
    .receipt-btns { display: flex; gap: 10px; margin-top: 22px; flex-wrap: wrap; }
    .btn-print {
      flex: 1; padding: 12px; background: #1a1a1a; color: #ccc;
      border: 1px solid #2a2a2a; border-radius: 8px; font-size: 14px; font-weight: 600;
      cursor: pointer; min-height: 44px; touch-action: manipulation;
      -webkit-user-select: none; user-select: none; -webkit-tap-highlight-color: transparent;
    }
    .btn-whatsapp {
      flex: 1; padding: 12px; background: #1a2e1a; color: #25d366;
      border: 1px solid #1e4a1e; border-radius: 8px; font-size: 14px; font-weight: 700;
      cursor: pointer; min-height: 44px; touch-action: manipulation;
      -webkit-user-select: none; user-select: none; -webkit-tap-highlight-color: transparent;
      text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 6px;
    }
    .btn-whatsapp:hover { background: #1e3a1e; }
    .btn-newsale {
      flex: 1; min-width: 100%; padding: 12px; background: #d4af37; color: #000;
      border: none; border-radius: 8px; font-size: 14px; font-weight: 800;
      cursor: pointer; min-height: 44px; touch-action: manipulation;
      -webkit-user-select: none; user-select: none; -webkit-tap-highlight-color: transparent;
    }

    @media print {
      body > *:not(.print-area) { display: none !important; }
      .print-area { display: block !important; color: #000; background: #fff; padding: 20px; }
    }
  </style>
</head>
<body>
<header>
  <div><h1>AMERICAN SELECT</h1><span>POS Checkout</span></div>
  <div class="header-btns">
    <button class="btn-new" id="btn-new" onclick="newSale()">＋ New Sale</button>
    <a href="dashboard.php" class="back-btn">← Dashboard</a>
  </div>
</header>

<div class="container">

  <!-- Pending order banner -->
  <div id="order-banner" style="display:none;background:#0d1020;border:1px solid #1a2a40;border-radius:10px;padding:12px 16px;margin-bottom:14px;display:none;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
    <div>
      <div style="font-size:11px;color:#555;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:3px;">Processing Order</div>
      <div style="font-size:15px;font-weight:800;color:#7b9fd4;" id="ob-ref"></div>
      <div style="font-size:12px;color:#555;margin-top:2px;" id="ob-pay"></div>
    </div>
    <a href="orders.php" style="font-size:12px;color:#555;text-decoration:none;border:1px solid #2a2a2a;padding:6px 12px;border-radius:6px;touch-action:manipulation;">← Back to Orders</a>
  </div>

  <!-- Scanner -->
  <div class="scan-section">
    <div class="scan-row">
      <input type="text" class="barcode-input" id="barcode-input"
             placeholder="Scan barcode or type &amp; press Enter"
             autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" inputmode="text">
      <button class="btn-camera" id="camera-btn" onclick="toggleCamera()" title="Camera scanner">📷</button>
    </div>
    <div class="camera-wrap" id="camera-wrap">
      <video id="camera-video" autoplay playsinline muted></video>
      <div class="scan-line-anim"></div>
    </div>
    <div class="scan-status" id="scan-status">Ready — scan an item to begin</div>
    <span class="manual-toggle" onclick="toggleManual()">＋ Add product manually</span>
    <div class="manual-wrap" id="manual-wrap">
      <input type="text" class="manual-input" id="manual-input"
             placeholder="Search product name…" oninput="filterProducts(this.value)"
             autocomplete="off">
      <div class="manual-results" id="manual-results"></div>
    </div>
  </div>

  <!-- Cart -->
  <div class="cart-box">
    <div class="cart-head">
      <span class="cart-title">Cart</span>
      <span class="cart-badge" id="cart-badge">Empty</span>
    </div>
    <div id="cart-body">
      <div class="cart-empty">
        <div class="empty-icon">🛒</div>
        Scan items to add them
      </div>
    </div>
    <div class="cart-foot" id="cart-foot">
      <div class="total-row">
        <div>
          <div class="total-lbl">Total</div>
          <div class="total-units" id="total-units"></div>
        </div>
        <div class="total-price" id="total-price">0 FCFA</div>
      </div>
      <div class="pay-label">Payment Method</div>
      <div class="pay-methods">
        <button class="pay-btn" data-method="Cash" onclick="selectPayment(this)">💵 Cash</button>
        <button class="pay-btn" data-method="MTN Mobile Money" onclick="selectPayment(this)">🟡 MTN MoMo</button>
        <button class="pay-btn" data-method="Orange Money" onclick="selectPayment(this)">🟠 Orange Money</button>
        <button class="pay-btn" data-method="Other" onclick="selectPayment(this)">💳 Other</button>
      </div>
      <div id="momo-info" style="display:none;background:#1a1500;border:1px solid #3a3000;border-radius:8px;padding:10px 14px;margin-top:8px;font-size:13px;color:#f0c040;">
        📲 Send to: <strong style="font-size:15px;letter-spacing:1px;">679 457 181</strong>
      </div>
      <div class="phone-label">Customer Phone <span style="color:#444;font-weight:400;">(optional — for WhatsApp confirmation)</span></div>
      <input type="tel" class="phone-input" id="customer-phone" placeholder="e.g. 677 123 456" inputmode="tel" autocomplete="tel" oninput="customerPhone=this.value.trim()">
      <button class="btn-checkout" id="btn-checkout" onclick="doCheckout()" disabled>
        ✓&nbsp; Checkout
      </button>
      <div class="checkout-msg" id="checkout-msg"></div>
    </div>
  </div>

</div>

<!-- Receipt -->
<div class="receipt-overlay" id="receipt-overlay">
  <div class="receipt-card">
    <div class="receipt-logo">AMERICAN SELECT</div>
    <div class="receipt-tagline">americanselect.net</div>
    <div class="receipt-date" id="receipt-date"></div>
    <hr class="receipt-divider">
    <div id="receipt-lines"></div>
    <hr class="receipt-divider">
    <div class="receipt-total-row">
      <span class="receipt-total-lbl">TOTAL</span>
      <span class="receipt-total-amt" id="receipt-total"></span>
    </div>
    <div style="margin-top:10px;font-size:12px;color:#555;">Paid via: <span id="receipt-payment" style="color:#d4af37;font-weight:700;"></span></div>
    <div class="receipt-btns">
      <button class="btn-print" onclick="printReceipt()">🖨 Print</button>
      <a class="btn-whatsapp" id="btn-whatsapp" href="#" target="_blank" rel="noopener">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        Share
      </a>
      <a class="btn-wa-confirm-receipt" id="btn-wa-confirm" href="#" target="_blank" rel="noopener" style="display:none;">
        📱 Send Confirmation
      </a>
      <button class="btn-newsale" onclick="newSale()">＋ New Sale</button>
    </div>
  </div>
</div>

<!-- Hidden print area -->
<div class="print-area" id="print-area" style="display:none;"></div>

<script>
const catalog = <?= json_encode(array_values($products)) ?>;
const catalogMap = {};
catalog.forEach(p => { catalogMap[p.name] = p; });

let cart = []; // [{name, price, qty, stock}]
let busy = false;
let cameraOn = false;
let codeReader = null;
let selectedPayment = '';
let customerPhone = '';
let pendingOrderId = <?= $preloadOrder ? (int)$preloadOrder['id'] : 'null' ?>;

// Pre-load from pending order if coming from orders page
<?php if ($preloadOrder): ?>
(function() {
  const order = <?= json_encode($preloadOrder) ?>;
  // Show order banner
  const banner = document.getElementById('order-banner');
  if (banner) {
    document.getElementById('ob-ref').textContent = order.order_ref;
    document.getElementById('ob-pay').textContent = order.payment_method || '';
    banner.style.display = 'flex';
  }
  // Pre-load cart items
  order.items.forEach(item => {
    cart.push({ name: item.name, price: item.price || 0, qty: item.quantity || 1, stock: 999 });
  });
  // Pre-select payment method
  if (order.payment_method) {
    selectedPayment = order.payment_method;
    document.querySelectorAll('.pay-btn').forEach(b => {
      if (b.dataset.method === order.payment_method) b.classList.add('selected');
    });
  }
  renderCart();
  document.getElementById('btn-new').style.display = 'inline-flex';
})();
<?php endif; ?>

// Auto-focus
document.getElementById('barcode-input').focus();

// Enter key on barcode input
document.getElementById('barcode-input').addEventListener('keydown', function(e) {
  if (e.key === 'Enter') {
    e.preventDefault();
    const v = this.value.trim();
    if (v) { this.value = ''; scanBarcode(v); }
  }
});

// ── Barcode scan ─────────────────────────────────────────
function scanBarcode(barcode) {
  setScanStatus('Looking up…', '');
  fetch('/api/barcode.php?barcode=' + encodeURIComponent(barcode))
    .then(r => r.json())
    .then(d => {
      if (d.error) { setScanStatus('Error: ' + d.error, 'err'); return; }
      if (!d.found) { setScanStatus('Unknown barcode — assign it in the Scan page first, or add manually below', 'err'); return; }
      addToCart(d.product_name, d.price || 0, d.quantity);
    })
    .catch(() => setScanStatus('Network error', 'err'));
}

// ── Cart ops ─────────────────────────────────────────────
function addToCart(name, price, stock) {
  const ex = cart.find(i => i.name === name);
  if (ex) {
    ex.stock = stock;
    if (ex.qty >= stock) {
      setScanStatus('⚠ Out of stock — only ' + stock + ' available for: ' + name.substring(0, 40), 'err');
      return;
    }
    ex.qty++;
  } else {
    if (stock < 1) {
      setScanStatus('⚠ Out of stock — 0 available for: ' + name.substring(0, 40), 'err');
      return;
    }
    cart.push({ name, price, qty: 1, stock });
  }
  renderCart();
  broadcastDisplay();
  setScanStatus('✓ ' + name.substring(0, 50), 'ok');
  document.getElementById('btn-new').style.display = 'inline-flex';
  setTimeout(() => document.getElementById('barcode-input').focus(), 80);
}

function adjustQty(idx, d) {
  const item = cart[idx];
  const newQty = item.qty + d;
  if (d > 0 && newQty > item.stock) return; // block exceeding stock
  cart[idx].qty = Math.max(1, newQty);
  renderCart();
  broadcastDisplay();
}

function removeItem(idx) {
  cart.splice(idx, 1);
  renderCart();
  broadcastDisplay();
  if (!cart.length) document.getElementById('btn-new').style.display = 'none';
}

// ── Broadcast to customer display ────────────────────────
function broadcastDisplay() {
  const total = cart.reduce((s, i) => s + i.price * i.qty, 0);
  fetch('/api/display.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      active: cart.length > 0,
      items: cart.map(i => ({ name: i.name, price: i.price, qty: i.qty })),
      total,
      payment: selectedPayment
    })
  }).catch(() => {});
}

// ── Render cart ──────────────────────────────────────────
function renderCart() {
  const body = document.getElementById('cart-body');
  const foot = document.getElementById('cart-foot');
  const badge = document.getElementById('cart-badge');
  if (!cart.length) {
    body.innerHTML = '<div class="cart-empty"><div class="empty-icon">🛒</div>Scan items to add them</div>';
    foot.style.display = 'none';
    badge.textContent = 'Empty';
    return;
  }

  let totalUnits = 0, totalPrice = 0;
  let html = '';
  cart.forEach((item, i) => {
    const line = item.price * item.qty;
    const overStock = item.qty > item.stock;
    totalUnits += item.qty;
    totalPrice += line;
    html += `<div class="cart-item">
      <div>
        <div class="ci-name">${esc(item.name)}</div>
        <div class="ci-unit">${item.price ? item.price.toLocaleString() + ' FCFA each' : 'No price set'}</div>
        <div class="ci-linetotal">${item.price ? line.toLocaleString() + ' FCFA' : '—'}</div>
        ${overStock ? '<div class="ci-stock-warn">⚠ Only ' + item.stock + ' in stock</div>' : ''}
      </div>
      <div class="qty-ctrl">
        <button class="qty-btn" onclick="adjustQty(${i},-1)">−</button>
        <span class="qty-num">${item.qty}</span>
        <button class="qty-btn" onclick="adjustQty(${i},1)" ${item.qty >= item.stock ? 'disabled style="opacity:0.3;cursor:not-allowed;"' : ''}>+</button>
      </div>
      <div></div>
      <button class="remove-btn" onclick="removeItem(${i})">×</button>
    </div>`;
  });

  body.innerHTML = html;
  foot.style.display = 'block';
  badge.textContent = cart.length + ' item' + (cart.length > 1 ? 's' : '');
  document.getElementById('total-units').textContent = totalUnits + ' unit' + (totalUnits > 1 ? 's' : '');
  document.getElementById('total-price').textContent = totalPrice.toLocaleString() + ' FCFA';

  const hasOverStock = cart.some(i => i.qty > i.stock);
  const btn = document.getElementById('btn-checkout');
  const msg = document.getElementById('checkout-msg');
  btn.disabled = busy || hasOverStock || !selectedPayment;
  if (hasOverStock) {
    msg.textContent = '⚠ Reduce quantity for items exceeding stock';
    msg.className = 'checkout-msg warn';
  } else if (!selectedPayment) {
    msg.textContent = 'Select a payment method above';
    msg.className = 'checkout-msg';
  } else {
    msg.textContent = '';
    msg.className = 'checkout-msg';
  }
}

function selectPayment(el) {
  document.querySelectorAll('.pay-btn').forEach(b => b.classList.remove('selected'));
  el.classList.add('selected');
  selectedPayment = el.dataset.method;
  const momoInfo = document.getElementById('momo-info');
  if (momoInfo) momoInfo.style.display = selectedPayment === 'MTN Mobile Money' ? 'block' : 'none';
  renderCart();
  broadcastDisplay();
}

// ── Checkout ─────────────────────────────────────────────
async function doCheckout() {
  if (!cart.length || busy) return;
  busy = true;
  const btn = document.getElementById('btn-checkout');
  const msg = document.getElementById('checkout-msg');
  btn.disabled = true;
  btn.textContent = 'Processing…';
  msg.textContent = '';

  const snapshot = cart.map(i => ({ ...i }));
  const results = [];
  let allOk = true;

  for (let i = 0; i < snapshot.length; i++) {
    const item = snapshot[i];
    msg.textContent = 'Processing ' + (i + 1) + ' of ' + snapshot.length + '…';
    try {
      const res = await fetch('/api/barcode.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'transaction',
          product_name: item.name,
          tx_action: 'sold',
          quantity: item.qty,
          note: 'POS Checkout — ' + selectedPayment
        })
      });
      const d = await res.json();
      results.push({ item, ok: !!d.success });
      if (!d.success) allOk = false;
    } catch {
      results.push({ item, ok: false });
      allOk = false;
    }
  }

  busy = false;
  if (allOk) {
    // Mark pending order as complete if this came from orders page
    if (pendingOrderId) {
      fetch('/api/orders.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'complete', id: pendingOrderId, note: 'Processed via checkout' })
      }).catch(() => {});
      pendingOrderId = null;
    }
    showReceipt(snapshot);
  } else {
    btn.textContent = '✓  Checkout';
    btn.disabled = false;
    msg.textContent = '⚠ Some items failed — check connection and try again';
    msg.className = 'checkout-msg err';
  }
}

// ── Receipt ──────────────────────────────────────────────
function showReceipt(items) {
  const now = new Date();
  document.getElementById('receipt-date').textContent =
    now.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) +
    '  ·  ' + now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });

  let total = 0, html = '';
  items.forEach(item => {
    const line = item.price * item.qty;
    total += line;
    html += `<div class="receipt-row">
      <div>
        <div class="receipt-item-name">${esc(item.name.length > 55 ? item.name.substring(0,55)+'…' : item.name)}</div>
        <div class="receipt-item-meta">×${item.qty}  ·  ${item.price ? item.price.toLocaleString() + ' FCFA each' : ''}</div>
      </div>
      <div class="receipt-item-amt">${item.price ? line.toLocaleString() + ' FCFA' : '—'}</div>
    </div>`;
  });

  document.getElementById('receipt-lines').innerHTML = html;
  document.getElementById('receipt-total').textContent = total.toLocaleString() + ' FCFA';
  document.getElementById('receipt-payment').textContent = selectedPayment;

  // Build WhatsApp message
  const dateStr = document.getElementById('receipt-date').textContent;
  let waMsg = '🧾 *AMERICAN SELECT*\n' + dateStr + '\n\n';
  items.forEach(item => {
    const line = item.price * item.qty;
    waMsg += '• ' + item.name + '\n';
    waMsg += '  ×' + item.qty + '  ·  ' + (item.price ? line.toLocaleString() + ' FCFA' : '—') + '\n';
  });
  waMsg += '\n*TOTAL: ' + total.toLocaleString() + ' FCFA*\n💳 Paid via: ' + selectedPayment + '\n\nThank you for shopping with American Select!';
  document.getElementById('btn-whatsapp').href = 'https://wa.me/?text=' + encodeURIComponent(waMsg);

  // WhatsApp confirmation button
  const confirmBtn = document.getElementById('btn-wa-confirm');
  if (customerPhone) {
    let phone = customerPhone.replace(/\D/g, '');
    if (!phone.startsWith('237')) phone = '237' + phone;
    const payIcon = selectedPayment.includes('MTN') ? '🟡' : selectedPayment.includes('Orange') ? '🟠' : '💵';
    let msgLines = '';
    items.forEach(item => {
      const line = item.price * item.qty;
      msgLines += `• ${item.name} ×${item.qty}${item.price ? ' — ' + line.toLocaleString() + ' FCFA' : ''}\n`;
    });
    const waMsg =
      `✅ *Payment Received — American Select*\n` +
      `Order Ref: ${document.getElementById('receipt-date').textContent}\n\n` +
      msgLines +
      `\n*Total: ${total.toLocaleString()} FCFA*\n` +
      `${payIcon} Paid via: ${selectedPayment}\n\n` +
      `Thank you for shopping with American Select!\n` +
      `Questions? Call/WhatsApp:\n` +
      `MTN: 679 457 181 | Orange: 686 271 567`;
    confirmBtn.href = `https://wa.me/${phone}?text=${encodeURIComponent(waMsg)}`;
    confirmBtn.style.display = 'flex';
  } else {
    confirmBtn.style.display = 'none';
  }

  document.getElementById('receipt-overlay').classList.add('open');

  // Build print area
  document.getElementById('print-area').innerHTML =
    `<div style="text-align:center;margin-bottom:10px;">
       <img src="/images/as-logo.jpeg" alt="American Select" style="height:72px;object-fit:contain;display:block;margin:0 auto 8px;">
       <h2 style="font-size:17px;letter-spacing:1px;margin-bottom:2px;">AMERICAN SELECT</h2>
       <p style="font-size:11px;color:#555;margin:0;">Quality Imports from the USA &amp; Canada</p>
       <p style="font-size:11px;color:#555;margin:4px 0 0;">Yaoundé, Cameroon</p>
       <p style="font-size:11px;color:#555;margin:2px 0 0;">MTN: 679 457 181 &nbsp;|&nbsp; Orange: 686 271 567</p>
       <p style="font-size:11px;color:#555;margin:2px 0 0;">americanselect.net</p>
     </div>
     <hr style="border:none;border-top:1px dashed #aaa;margin:10px 0;">
     <p style="font-size:11px;color:#666;text-align:center;margin-bottom:12px;">${dateStr}</p>` +
    items.map(item => {
      const line = item.price * item.qty;
      return `<div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:13px;">
        <div><div>${esc(item.name)}</div><div style="color:#888;">×${item.qty} @ ${item.price.toLocaleString()} FCFA</div></div>
        <div style="font-weight:bold;">${line.toLocaleString()} FCFA</div>
      </div>`;
    }).join('') +
    `<hr style="border:none;border-top:1px dashed #aaa;margin:10px 0;">
     <div style="display:flex;justify-content:space-between;font-size:18px;font-weight:bold;">
       <span>TOTAL</span><span>${total.toLocaleString()} FCFA</span>
     </div>
     <p style="margin-top:6px;font-size:12px;color:#888;">Paid via: ${selectedPayment}</p>
     <hr style="border:none;border-top:1px dashed #aaa;margin:14px 0 8px;">
     <p style="text-align:center;font-size:11px;color:#888;margin:0;">Thank you for shopping with American Select!</p>`;
}

function printReceipt() {
  document.getElementById('print-area').style.display = 'block';
  window.print();
  document.getElementById('print-area').style.display = 'none';
}

// ── New Sale ─────────────────────────────────────────────
function newSale() {
  cart = [];
  busy = false;
  selectedPayment = '';
  customerPhone = '';
  document.getElementById('customer-phone').value = '';
  broadcastDisplay();
  document.querySelectorAll('.pay-btn').forEach(b => b.classList.remove('selected'));
  renderCart();
  document.getElementById('receipt-overlay').classList.remove('open');
  document.getElementById('btn-wa-confirm').style.display = 'none';
  document.getElementById('btn-checkout').textContent = '✓  Checkout';
  document.getElementById('checkout-msg').textContent = '';
  document.getElementById('checkout-msg').className = 'checkout-msg';
  document.getElementById('btn-new').style.display = 'none';
  setScanStatus('Ready — scan an item to begin', '');
  setTimeout(() => document.getElementById('barcode-input').focus(), 100);
}

// ── Camera ────────────────────────────────────────────────
function toggleCamera() {
  cameraOn ? stopCamera() : startCamera();
}

async function startCamera() {
  setScanStatus('Loading camera…', '');
  if (typeof ZXing === 'undefined') {
    await new Promise((res, rej) => {
      const s = document.createElement('script');
      s.src = 'https://unpkg.com/@zxing/library@0.18.6/umd/index.min.js';
      s.onload = res; s.onerror = rej;
      document.head.appendChild(s);
    }).catch(() => { setScanStatus('Failed to load camera library', 'err'); return; });
  }
  try {
    codeReader = new ZXing.BrowserMultiFormatReader();
    await codeReader.decodeFromConstraints({ video: { facingMode: 'environment' } }, 'camera-video', (result) => {
      if (result) { stopCamera(); scanBarcode(result.getText()); }
    });
    cameraOn = true;
    document.getElementById('camera-wrap').classList.add('visible');
    document.getElementById('camera-btn').classList.add('active');
    setScanStatus('Camera active — point at a barcode', '');
  } catch (e) {
    setScanStatus('Camera error: ' + e.message, 'err');
  }
}

function stopCamera() {
  if (codeReader) { try { codeReader.reset(); } catch {} codeReader = null; }
  cameraOn = false;
  document.getElementById('camera-wrap').classList.remove('visible');
  document.getElementById('camera-btn').classList.remove('active');
}

// ── Manual add ────────────────────────────────────────────
function toggleManual() {
  const wrap = document.getElementById('manual-wrap');
  wrap.classList.toggle('open');
  if (wrap.classList.contains('open')) document.getElementById('manual-input').focus();
}

function filterProducts(q) {
  const res = document.getElementById('manual-results');
  q = q.trim().toLowerCase();
  if (!q) { res.style.display = 'none'; return; }
  const matches = catalog.filter(p => p.name.toLowerCase().includes(q)).slice(0, 12);
  if (!matches.length) { res.style.display = 'none'; return; }
  res.innerHTML = matches.map((p, i) =>
    `<div class="manual-item" data-idx="${i}" data-name="${esc(p.name)}" data-price="${p.price||0}" onclick="pickProduct(this)">
      <div class="manual-item-name">${esc(p.name)}</div>
      <div class="manual-item-price">${p.price ? p.price.toLocaleString() + ' FCFA' : 'No price'}</div>
    </div>`
  ).join('');
  res.style.display = 'block';
}

function pickProduct(el) {
  const name = el.dataset.name;
  const price = parseInt(el.dataset.price, 10) || 0;
  document.getElementById('manual-input').value = '';
  document.getElementById('manual-results').style.display = 'none';
  document.getElementById('manual-wrap').classList.remove('open');
  // Look up live stock from DB
  fetch('/api/barcode.php?name=' + encodeURIComponent(name))
    .then(r => r.json())
    .then(d => {
      const stock = (d && !d.error) ? d.quantity : (catalogMap[name]?.quantity ?? 99);
      addToCart(name, price, stock);
    })
    .catch(() => addToCart(name, price, catalogMap[name]?.quantity ?? 99));
}

// ── Helpers ───────────────────────────────────────────────
function setScanStatus(msg, type) {
  const el = document.getElementById('scan-status');
  el.textContent = msg;
  el.className = 'scan-status' + (type ? ' ' + type : '');
}

function esc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
</body>
</html>
