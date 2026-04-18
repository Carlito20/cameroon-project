<?php
require_once __DIR__ . '/../api/db.php';
session_start();
if (empty($_SESSION['admin_logged_in'])) { header('Location: index.php'); exit; }

$filter = $_GET['filter'] ?? 'pending';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Orders — American Select</title>
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
    .back-btn {
      background: transparent; color: #888; border: 1px solid #333; border-radius: 6px;
      padding: 7px 13px; font-size: 13px; font-weight: 600; cursor: pointer;
      text-decoration: none; display: inline-flex; align-items: center; gap: 5px;
      min-height: 44px; touch-action: manipulation; -webkit-user-select: none; user-select: none;
      -webkit-tap-highlight-color: transparent;
    }
    .back-btn:hover { color: #ccc; border-color: #555; }
    .container {
      max-width: 780px; margin: 0 auto; padding: 16px;
      padding-bottom: calc(40px + env(safe-area-inset-bottom, 0px));
      padding-left: calc(16px + env(safe-area-inset-left, 0px));
      padding-right: calc(16px + env(safe-area-inset-right, 0px));
    }

    /* Filter tabs */
    .filter-tabs { display: flex; gap: 6px; margin-bottom: 20px; flex-wrap: wrap; }
    .tab {
      padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600;
      cursor: pointer; text-decoration: none; border: 1px solid #2a2a2a; color: #666;
      background: transparent; min-height: 44px; display: flex; align-items: center;
      touch-action: manipulation; -webkit-tap-highlight-color: transparent;
      -webkit-user-select: none; user-select: none;
    }
    .tab.active-pending { background: #d4af37; color: #000; border-color: #d4af37; }
    .tab.active-completed { background: #0d1a0d; color: #6dbf6d; border-color: #1a3a1a; }
    .tab.active-cancelled { background: #1a0d0d; color: #e05c5c; border-color: #3a1a1a; }
    .tab:hover:not([class*="active"]) { color: #ccc; border-color: #555; }

    /* Loading / empty */
    .loading { text-align: center; padding: 60px 20px; color: #444; }
    .empty-state { text-align: center; padding: 60px 20px; color: #333; font-size: 15px; }
    .empty-icon { font-size: 48px; margin-bottom: 12px; opacity: 0.3; }

    /* Order card */
    .order-card {
      background: #111; border: 1px solid #1e1e1e; border-radius: 12px;
      margin-bottom: 14px; overflow: hidden;
    }
    .order-card.pending { border-left: 3px solid #d4af37; }
    .order-card.completed { border-left: 3px solid #6dbf6d; }
    .order-card.cancelled { border-left: 3px solid #555; opacity: 0.7; }

    .order-head {
      padding: 14px 16px 10px;
      display: flex; align-items: flex-start; justify-content: space-between; gap: 10px;
    }
    .order-ref { font-size: 13px; font-weight: 800; color: #d4af37; letter-spacing: 0.5px; }
    .order-customer { font-size: 14px; font-weight: 700; color: #e0e0e0; margin-top: 4px; }
    .order-phone { font-size: 13px; color: #7b9fd4; margin-top: 2px; }
    .order-time { font-size: 12px; color: #888; margin-top: 3px; }
    .order-pay { font-size: 12px; color: #888; margin-top: 3px; }
    .status-badge {
      font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px;
      border: 1px solid; white-space: nowrap; flex-shrink: 0;
    }
    .status-badge.pending { background:#1a1500;color:#d4af37;border-color:#3a3010; }
    .status-badge.completed { background:#0d1a0d;color:#6dbf6d;border-color:#1a3a1a; }
    .status-badge.cancelled { background:#1a1a1a;color:#555;border-color:#2a2a2a; }

    /* Items list */
    .order-items { padding: 0 16px 10px; }
    .order-item {
      display: flex; justify-content: space-between; align-items: center;
      padding: 6px 0; border-bottom: 1px solid #161616; gap: 10px;
    }
    .order-item:last-child { border-bottom: none; }
    .oi-name { font-size: 13px; color: #ccc; flex: 1; line-height: 1.4; }
    .oi-qty { font-size: 12px; color: #555; white-space: nowrap; }
    .oi-price { font-size: 13px; color: #d4af37; font-weight: 600; white-space: nowrap; }

    /* Total row */
    .order-total {
      display: flex; justify-content: space-between; align-items: center;
      padding: 10px 16px; background: #0d0d0d; border-top: 1px solid #1a1a1a;
    }
    .ot-label { font-size: 12px; color: #555; text-transform: uppercase; letter-spacing: 0.5px; }
    .ot-amount { font-size: 18px; font-weight: 800; color: #d4af37; }

    /* Order note */
    .order-note { padding: 6px 16px 10px; font-size: 12px; color: #555; font-style: italic; }

    /* Action buttons */
    .order-actions {
      display: flex; gap: 8px; padding: 12px 16px;
      border-top: 1px solid #1a1a1a; flex-wrap: wrap;
    }
    .btn-complete {
      flex: 1; padding: 10px 12px; background: #0d1a0d; color: #6dbf6d;
      border: 1px solid #1a3a1a; border-radius: 8px; font-size: 13px; font-weight: 700;
      cursor: pointer; min-height: 44px; touch-action: manipulation;
      -webkit-user-select: none; user-select: none; -webkit-tap-highlight-color: transparent;
    }
    .btn-complete:hover { background: #1a2e1a; }
    .btn-scan {
      flex: 1; padding: 10px 12px; background: #0d1020; color: #7b9fd4;
      border: 1px solid #1a2a40; border-radius: 8px; font-size: 13px; font-weight: 700;
      cursor: pointer; min-height: 44px; touch-action: manipulation;
      -webkit-user-select: none; user-select: none; -webkit-tap-highlight-color: transparent;
    }
    .btn-scan:hover { background: #1a2040; }
    .btn-cancel {
      flex: 1; padding: 10px 12px; background: transparent; color: #664444;
      border: 1px solid #2a1a1a; border-radius: 8px; font-size: 13px; font-weight: 700;
      cursor: pointer; min-height: 44px; touch-action: manipulation;
      -webkit-user-select: none; user-select: none; -webkit-tap-highlight-color: transparent;
    }
    .btn-print-receipt {
      flex: 1; padding: 10px 12px; background: #1a1520; color: #a98fd4;
      border: 1px solid #2a1a40; border-radius: 8px; font-size: 13px; font-weight: 700;
      cursor: pointer; min-height: 44px; touch-action: manipulation;
      -webkit-user-select: none; user-select: none; -webkit-tap-highlight-color: transparent;
    }
    .btn-print-receipt:hover { background: #221a2e; }
    .btn-wa-confirm {
      flex: 1; padding: 10px 12px; background: #0d2010; color: #25d366;
      border: 1px solid #1a4a20; border-radius: 8px; font-size: 13px; font-weight: 700;
      cursor: pointer; min-height: 44px; touch-action: manipulation;
      -webkit-user-select: none; user-select: none; -webkit-tap-highlight-color: transparent;
      text-decoration: none; display: flex; align-items: center; justify-content: center;
    }
    .btn-wa-confirm:hover { background: #112a18; }

    @media print {
      body > *:not(#print-area) { display: none !important; }
      #print-area { display: block !important; padding: 10mm 15mm; color: #000; background: #fff; }
      @page { size: A5; margin: 10mm; }
    }
    .btn-cancel:hover { background: #1a0d0d; color: #e05c5c; border-color: #5a2a2a; }

    /* Complete order modal */
    .complete-overlay {
      position: fixed; inset: 0; background: rgba(0,0,0,0.85);
      -webkit-backdrop-filter: blur(4px); backdrop-filter: blur(4px);
      z-index: 200; display: none; align-items: center; justify-content: center;
      padding: 20px;
      padding-top: calc(20px + env(safe-area-inset-top, 0px));
      padding-bottom: calc(20px + env(safe-area-inset-bottom, 0px));
    }
    .complete-overlay.open { display: flex; }
    .complete-modal {
      background: #111; border: 1px solid #2a2a2a; border-radius: 14px;
      padding: 24px 20px; max-width: 400px; width: 100%;
    }
    .complete-modal h3 { font-size: 16px; color: #e0e0e0; margin-bottom: 10px; }
    .complete-modal p { font-size: 13px; color: #888; margin-bottom: 16px; line-height: 1.5; }
    .payment-ref-input {
      width: 100%; padding: 10px 14px; background: #1a1a1a;
      border: 1px solid #2a2a2a; border-radius: 8px; color: #e0e0e0;
      font-size: 14px; outline: none; -webkit-appearance: none; appearance: none;
      min-height: 44px; touch-action: manipulation; margin-bottom: 14px;
    }
    .payment-ref-input:focus { border-color: #555; }
    .complete-modal-btns { display: flex; gap: 10px; }
    .btn-complete-confirm {
      flex: 1; padding: 11px; background: #0d2010; color: #4caf50;
      border: 1px solid #1a4a20; border-radius: 8px; font-size: 14px; font-weight: 700;
      cursor: pointer; min-height: 44px; touch-action: manipulation;
      -webkit-user-select: none; user-select: none; -webkit-tap-highlight-color: transparent;
    }
    .btn-complete-cancel {
      flex: 1; padding: 11px; background: transparent; color: #888;
      border: 1px solid #2a2a2a; border-radius: 8px; font-size: 14px; font-weight: 600;
      cursor: pointer; min-height: 44px; touch-action: manipulation;
      -webkit-user-select: none; user-select: none; -webkit-tap-highlight-color: transparent;
    }

    /* Cancel modal */
    .cancel-overlay {
      position: fixed; inset: 0; background: rgba(0,0,0,0.85);
      -webkit-backdrop-filter: blur(4px); backdrop-filter: blur(4px);
      z-index: 200; display: none; align-items: center; justify-content: center;
      padding: 20px;
      padding-top: calc(20px + env(safe-area-inset-top, 0px));
      padding-bottom: calc(20px + env(safe-area-inset-bottom, 0px));
    }
    .cancel-overlay.open { display: flex; }
    .cancel-modal {
      background: #111; border: 1px solid #2a2a2a; border-radius: 14px;
      padding: 24px 20px; max-width: 400px; width: 100%;
    }
    .cancel-modal h3 { font-size: 16px; color: #e0e0e0; margin-bottom: 10px; }
    .cancel-modal p { font-size: 13px; color: #666; margin-bottom: 16px; line-height: 1.5; }
    .cancel-reason {
      width: 100%; padding: 10px 14px; background: #1a1a1a;
      border: 1px solid #2a2a2a; border-radius: 8px; color: #e0e0e0;
      font-size: 14px; outline: none; -webkit-appearance: none; appearance: none;
      min-height: 44px; touch-action: manipulation; margin-bottom: 14px;
    }
    .cancel-reason:focus { border-color: #555; }
    .cancel-modal-btns { display: flex; gap: 10px; }
    .btn-cancel-confirm {
      flex: 1; padding: 11px; background: #3a1010; color: #e05c5c;
      border: 1px solid #5a2020; border-radius: 8px; font-size: 14px; font-weight: 700;
      cursor: pointer; min-height: 44px; touch-action: manipulation;
      -webkit-user-select: none; user-select: none; -webkit-tap-highlight-color: transparent;
    }
    .btn-keep {
      flex: 1; padding: 11px; background: transparent; color: #888;
      border: 1px solid #2a2a2a; border-radius: 8px; font-size: 14px; font-weight: 600;
      cursor: pointer; min-height: 44px; touch-action: manipulation;
      -webkit-user-select: none; user-select: none; -webkit-tap-highlight-color: transparent;
    }

    /* Search & date bar */
    .search-bar {
      display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap; align-items: center;
    }
    .search-input {
      flex: 1; min-width: 180px; padding: 10px 14px; background: #1a1a1a;
      border: 1px solid #2a2a2a; border-radius: 8px; color: #e0e0e0;
      font-size: 14px; outline: none; -webkit-appearance: none; appearance: none;
      min-height: 44px; touch-action: manipulation;
    }
    .search-input:focus { border-color: #d4af37; }
    .search-input::placeholder { color: #444; }
    .date-input {
      padding: 10px 12px; background: #1a1a1a; border: 1px solid #2a2a2a;
      border-radius: 8px; color: #e0e0e0; font-size: 13px; outline: none;
      min-height: 44px; touch-action: manipulation; cursor: pointer;
      color-scheme: dark;
    }
    .date-input::-webkit-datetime-edit { color: #e0e0e0; }
    .date-input::-webkit-datetime-edit-fields-wrapper { color: #e0e0e0; }
    .date-input::-webkit-datetime-edit-text { color: #666; }
    .date-input::-webkit-datetime-edit-month-field { color: #e0e0e0; }
    .date-input::-webkit-datetime-edit-day-field   { color: #e0e0e0; }
    .date-input::-webkit-datetime-edit-year-field  { color: #e0e0e0; }
    .date-input::-webkit-calendar-picker-indicator { filter: invert(1); opacity: 0.6; cursor: pointer; }
    .date-input:focus { border-color: #d4af37; }
    .search-clear {
      padding: 10px 14px; background: transparent; color: #666;
      border: 1px solid #2a2a2a; border-radius: 8px; font-size: 13px;
      font-weight: 600; cursor: pointer; min-height: 44px; white-space: nowrap;
      touch-action: manipulation; -webkit-user-select: none; user-select: none;
      -webkit-tap-highlight-color: transparent;
    }
    .search-clear:hover { color: #ccc; border-color: #555; }
    .results-count { font-size: 12px; color: #444; padding: 4px 0; width: 100%; }

    /* Toast */
    .toast {
      position: fixed; bottom: calc(24px + env(safe-area-inset-bottom, 0px)); left: 50%;
      transform: translateX(-50%); background: #1a1a1a; border: 1px solid #333;
      color: #e0e0e0; padding: 10px 20px; border-radius: 8px; font-size: 13px;
      z-index: 300; white-space: nowrap; display: none;
      -webkit-transform: translateX(-50%) translateZ(0);
    }
    .toast.show { display: block; }
    .toast.ok { border-color: #1a3a1a; color: #6dbf6d; }
    .toast.err { border-color: #3a1a1a; color: #e05c5c; }
  </style>
</head>
<body>
<header>
  <div><h1>AMERICAN SELECT</h1><span>Orders</span></div>
  <a href="dashboard.php" class="back-btn">← Dashboard</a>
</header>

<div class="container">
  <div class="filter-tabs">
    <a href="?filter=pending"   class="tab <?= $filter==='pending'   ? 'active-pending'   : '' ?>">⏳ Pending</a>
    <a href="?filter=completed" class="tab <?= $filter==='completed' ? 'active-completed' : '' ?>">✅ Completed</a>
    <a href="?filter=cancelled" class="tab <?= $filter==='cancelled' ? 'active-cancelled' : '' ?>">✗ Cancelled</a>
    <a href="?filter=all"       class="tab <?= $filter==='all'       ? 'active-pending'   : '' ?>">All</a>
  </div>

  <div class="search-bar">
    <input type="search" class="search-input" id="search-input"
      placeholder="Search by name, phone, or order ref…" oninput="applyFilters()" autocomplete="off">
    <input type="date" class="date-input" id="date-from" onchange="applyFilters()" title="From date">
    <input type="date" class="date-input" id="date-to"   onchange="applyFilters()" title="To date">
    <button class="search-clear" onclick="clearFilters()">Clear</button>
  </div>
  <div class="results-count" id="results-count"></div>

  <div id="orders-list">
    <div class="loading">Loading orders…</div>
  </div>
</div>

<!-- Complete order modal -->
<div class="complete-overlay" id="complete-overlay">
  <div class="complete-modal">
    <h3>Mark as Paid & Complete</h3>
    <p>Enter the MoMo transaction ID the customer sent you. This will be saved to the receipt.<br>
    <span style="color:#555;">Leave blank if you don't have it yet.</span></p>
    <input type="text" class="payment-ref-input" id="payment-ref-input" placeholder="e.g. TXN123456789" maxlength="100" autocomplete="off" autocapitalize="characters">
    <div class="complete-modal-btns">
      <button class="btn-complete-cancel" onclick="closeCompleteModal()">Back</button>
      <button class="btn-complete-confirm" id="complete-confirm-btn" onclick="confirmComplete()">✓ Mark Paid</button>
    </div>
  </div>
</div>

<!-- Cancel modal -->
<div class="cancel-overlay" id="cancel-overlay">
  <div class="cancel-modal">
    <h3>Cancel Order?</h3>
    <p>Stock will <strong>not</strong> be changed — items stay in inventory. Add a reason below (optional).</p>
    <input type="text" class="cancel-reason" id="cancel-reason" placeholder="Reason (e.g. Customer didn't pay)" maxlength="255">
    <div class="cancel-modal-btns">
      <button class="btn-keep" onclick="closeCancelModal()">Keep Order</button>
      <button class="btn-cancel-confirm" id="cancel-confirm-btn" onclick="confirmCancel()">Cancel Order</button>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>
<div id="print-area" style="display:none;"></div>

<script>
const FILTER = '<?= htmlspecialchars($filter) ?>';
let cancelTargetId = null;
let completeTargetId = null;
let allOrders = [];


function applyFilters() {
  const q = (document.getElementById('search-input').value || '').toLowerCase().trim();
  const from = document.getElementById('date-from').value;  // 'YYYY-MM-DD' or ''
  const to   = document.getElementById('date-to').value;

  const filtered = allOrders.filter(o => {
    // Text search: name, phone, order ref, item names
    if (q) {
      const items = JSON.parse(o.items || '[]');
      const itemNames = items.map(i => (i.name || '').toLowerCase()).join(' ');
      const haystack = [o.customer_name, o.customer_phone, o.order_ref, itemNames]
        .map(s => (s || '').toLowerCase()).join(' ');
      if (!haystack.includes(q)) return false;
    }
    // Date range (compare YYYY-MM-DD prefix of created_at)
    if (from || to) {
      const d = (o.created_at || '').slice(0, 10);
      if (from && d < from) return false;
      if (to   && d > to)   return false;
    }
    return true;
  });

  renderOrders(filtered);
  const count = document.getElementById('results-count');
  if (q || from || to) {
    count.textContent = `${filtered.length} of ${allOrders.length} order${allOrders.length !== 1 ? 's' : ''} shown`;
  } else {
    count.textContent = '';
  }
}

function clearFilters() {
  document.getElementById('search-input').value = '';
  document.getElementById('date-from').value = '';
  document.getElementById('date-to').value = '';
  applyFilters();
}

function fmt(n) { return Number(n).toLocaleString('en-US'); }

function timeAgo(dateStr) {
  const d = new Date(dateStr + ' UTC');
  const diff = Math.floor((Date.now() - d.getTime()) / 1000);
  const dateLabel = (d.getMonth()+1).toString().padStart(2,'0') + '/' + d.getDate().toString().padStart(2,'0') + '/' + d.getFullYear();
  if (diff < 60) return 'just now · ' + dateLabel;
  if (diff < 3600) return Math.floor(diff/60) + 'm ago · ' + dateLabel;
  if (diff < 86400) return Math.floor(diff/3600) + 'h ago · ' + dateLabel;
  return dateLabel;
}

function payIcon(method) {
  if (!method) return '';
  if (method.includes('MTN')) return '🟡 ';
  if (method.includes('Orange')) return '🟠 ';
  if (method.includes('Cash')) return '💵 ';
  return '💳 ';
}

function renderOrders(orders) {
  const list = document.getElementById('orders-list');
  if (!orders.length) {
    const q = (document.getElementById('search-input')?.value || '').trim();
    const msg = q || document.getElementById('date-from')?.value || document.getElementById('date-to')?.value
      ? 'No orders match your search'
      : `No ${FILTER === 'all' ? '' : FILTER} orders`;
    list.innerHTML = `<div class="empty-state"><div class="empty-icon">📋</div>${msg}</div>`;
    return;
  }

  list.innerHTML = orders.map(o => {
    const items = JSON.parse(o.items || '[]');
    const isPending = o.status === 'pending';
    const itemsHtml = items.map(i => {
      const line = (i.price || 0) * (i.quantity || 1);
      return `<div class="order-item">
        <span class="oi-name">${esc(i.name)}</span>
        <span class="oi-qty">x${i.quantity || 1}</span>
        ${i.price ? `<span class="oi-price">${fmt(line)} FCFA</span>` : ''}
      </div>`;
    }).join('');

    const waReceivedLink  = o.customer_phone ? buildWaOrderReceivedLink(o) : null;
    const waReceiptLink   = o.customer_phone && o.status === 'completed' ? buildWaPaymentReceiptLink(o) : null;
    const actionsHtml = `<div class="order-actions">
      ${isPending ? `
        <button class="btn-complete" onclick="completeOrder(${o.id})">✓ Mark Paid & Complete</button>
        <button class="btn-scan"     onclick="scanOrder(${o.id})">📷 Scan & Process</button>
        <button class="btn-cancel"   onclick="openCancelModal(${o.id})">✗ Cancel</button>
        ${waReceivedLink ? `<a class="btn-wa-confirm" href="${waReceivedLink}" target="_blank" rel="noopener noreferrer">📱 Order Received</a>` : ''}` : ''}
      ${o.status !== 'cancelled' ? `<button class="btn-print-receipt" onclick="printOrderReceipt(${o.id})">🖨 Print Receipt</button>` : ''}
      ${waReceiptLink ? `<a class="btn-wa-confirm" href="${waReceiptLink}" target="_blank" rel="noopener noreferrer">📱 Payment Receipt</a>` : ''}
    </div>`;

    const noteHtml = o.note ? `<div class="order-note">${esc(o.note)}</div>` : '';

    const customerHtml = o.customer_name
      ? `<div class="order-customer">👤 ${esc(o.customer_name)}</div>${o.customer_phone ? `<div class="order-phone">📞 <a href="tel:${esc(o.customer_phone)}" style="color:#7b9fd4;text-decoration:none;">${esc(o.customer_phone)}</a></div>` : ''}`
      : `<div class="order-customer" style="color:#555;font-weight:400;font-style:italic;">No customer info</div>`;

    return `<div class="order-card ${o.status}" id="order-${o.id}">
      <div class="order-head">
        <div>
          <div class="order-ref">${esc(o.order_ref)}</div>
          ${customerHtml}
          <div class="order-time">${timeAgo(o.created_at)}</div>
          <div class="order-pay">${payIcon(o.payment_method)}${esc(o.payment_method || 'Payment not specified')}</div>
        </div>
        <span class="status-badge ${o.status}">${o.status.charAt(0).toUpperCase() + o.status.slice(1)}</span>
      </div>
      <div class="order-items">${itemsHtml}</div>
      ${noteHtml}
      <div class="order-total">
        <span class="ot-label">Total</span>
        <span class="ot-amount">${fmt(o.total)} FCFA</span>
      </div>
      ${actionsHtml}
    </div>`;
  }).join('');
}

async function loadOrders() {
  try {
    const res = await fetch('/api/orders.php?status=' + FILTER);
    const data = await res.json();
    if (data.error) { document.getElementById('orders-list').innerHTML = `<div class="empty-state">Error: ${data.error}</div>`; return; }
    allOrders = data.orders || [];
    applyFilters();
  } catch {
    document.getElementById('orders-list').innerHTML = '<div class="empty-state">Network error — check connection</div>';
  }
}

function completeOrder(id) {
  completeTargetId = id;
  document.getElementById('payment-ref-input').value = '';
  document.getElementById('complete-overlay').classList.add('open');
  setTimeout(() => document.getElementById('payment-ref-input').focus(), 100);
}

function closeCompleteModal() {
  completeTargetId = null;
  document.getElementById('complete-overlay').classList.remove('open');
}

async function confirmComplete() {
  if (!completeTargetId) return;
  const paymentRef = document.getElementById('payment-ref-input').value.trim();
  const btn = document.getElementById('complete-confirm-btn');
  btn.textContent = 'Saving…'; btn.disabled = true;
  try {
    const res = await fetch('/api/orders.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'complete', id: completeTargetId, payment_ref: paymentRef })
    });
    const data = await res.json();
    if (data.success) {
      closeCompleteModal();
      showToast('✓ Order completed — stock updated', 'ok');
      loadOrders();
    } else showToast('Error: ' + (data.error || 'Failed'), 'err');
  } catch { showToast('Network error', 'err'); }
  btn.textContent = '✓ Mark Paid'; btn.disabled = false;
}

function scanOrder(id) {
  // Save order items to localStorage so checkout.php can pre-load them
  const card = document.getElementById('order-' + id);
  if (!card) return;
  // Re-fetch the order data from the rendered card isn't ideal — just pass ID
  localStorage.setItem('checkoutOrderId', id);
  window.location.href = 'checkout.php?from_order=' + id;
}

function openCancelModal(id) {
  cancelTargetId = id;
  document.getElementById('cancel-reason').value = '';
  document.getElementById('cancel-overlay').classList.add('open');
  setTimeout(() => document.getElementById('cancel-reason').focus(), 100);
}

function closeCancelModal() {
  cancelTargetId = null;
  document.getElementById('cancel-overlay').classList.remove('open');
}

async function confirmCancel() {
  if (!cancelTargetId) return;
  const note = document.getElementById('cancel-reason').value.trim();
  document.getElementById('cancel-confirm-btn').textContent = 'Cancelling…';
  try {
    const res = await fetch('/api/orders.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'cancel', id: cancelTargetId, note })
    });
    const data = await res.json();
    closeCancelModal();
    if (data.success) { showToast('Order cancelled — stock unchanged', 'ok'); loadOrders(); }
    else showToast('Error: ' + (data.error || 'Failed'), 'err');
  } catch { showToast('Network error', 'err'); closeCancelModal(); }
}

function showToast(msg, type) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = 'toast show ' + (type || '');
  setTimeout(() => t.className = 'toast', 3000);
}

function esc(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function normalisePhone(raw) {
  let phone = String(raw).replace(/\D/g, '');
  // Already has a country code (10+ digits starting with known codes)
  if (phone.startsWith('237')) return phone;          // Cameroon
  if (phone.startsWith('1') && phone.length === 11) return phone;  // USA/Canada with leading 1
  if (phone.startsWith('44')) return phone;           // UK
  // US/Canada 10-digit number — add country code 1
  if (phone.length === 10 && !phone.startsWith('0')) return '1' + phone;
  // Cameroon 9-digit number — add 237
  if (phone.length === 9) return '237' + phone;
  // Fallback: return as-is
  return phone;
}

// Sent as soon as admin sees the order - lets customer know it's been received
function buildWaOrderReceivedLink(o) {
  const phone = normalisePhone(o.customer_phone);
  const items = JSON.parse(o.items || '[]');
  let lines = '';
  items.forEach(i => {
    const line = (i.price || 0) * (i.quantity || 1);
    lines += `- ${i.name} x${i.quantity || 1}${i.price ? ' - ' + Number(line).toLocaleString() + ' FCFA' : ''}\n`;
  });
  const name = o.customer_name || 'there';
  const msg =
    `✅ *Order Received - American Select*\n` +
    `Hi ${name}! We have received your order.\n` +
    `Bonjour ${name} ! Nous avons bien recu votre commande.\n\n` +
    lines +
    `\nTotal: ${Number(o.total).toLocaleString()} FCFA\n` +
    `💳 Payment: ${o.payment_method || 'N/A'}\n` +
    (o.customer_name ? `👤 Name: ${o.customer_name}\n` : '') +
    (o.customer_phone ? `📞 Phone: ${o.customer_phone}\n` : '') +
    `📋 Order Ref: ${o.order_ref}\n\n` +
    `Please send payment to complete your order:\n` +
    `Veuillez envoyer le paiement pour finaliser votre commande :\n` +
    `MTN MoMo: *679 457 181*\n` +
    `Orange Money: *686 271 567*\n\n` +
    `After paying, please reply with your *MoMo transaction ID* so we can confirm.\n` +
    `Apres le paiement, merci de repondre avec votre *ID de transaction MoMo* pour confirmation.\n\n` +
    `We will confirm once payment is received. Thank you!\n` +
    `Nous confirmerons des reception du paiement. Merci !`;
  return `https://wa.me/${phone}?text=${encodeURIComponent(msg)}`;
}

// Sent after marking the order as paid & complete
function buildWaPaymentReceiptLink(o) {
  const phone = normalisePhone(o.customer_phone);
  const items = JSON.parse(o.items || '[]');
  let lines = '';
  let total = 0;
  items.forEach(i => {
    const line = (i.price || 0) * (i.quantity || 1);
    total += line;
    lines += `- ${i.name} x${i.quantity || 1}${i.price ? ' - ' + Number(line).toLocaleString() + ' FCFA' : ''}\n`;
  });
  const name = o.customer_name || 'there';
  const msg =
    `✅ *Payment Confirmed - American Select*\n` +
    `Hi ${name}! Your payment has been confirmed.\n` +
    `Bonjour ${name} ! Votre paiement a ete confirme.\n\n` +
    lines +
    `\nTotal: ${Number(o.total || total).toLocaleString()} FCFA\n` +
    `💳 Payment: ${o.payment_method || 'N/A'}\n` +
    (o.customer_name ? `👤 Name: ${o.customer_name}\n` : '') +
    (o.customer_phone ? `📞 Phone: ${o.customer_phone}\n` : '') +
    `📋 Order Ref: ${o.order_ref}\n` +
    (o.payment_ref ? `🔖 Transaction ID: ${o.payment_ref}\n` : '') +
    `\nThank you for shopping with American Select!\n` +
    `Merci pour votre achat chez American Select !\n` +
    `Questions? Call/WhatsApp:\n` +
    `MTN: 679 457 181 | Orange: 686 271 567`;
  return `https://wa.me/${phone}?text=${encodeURIComponent(msg)}`;
}

function printOrderReceipt(id) {
  const o = allOrders.find(x => x.id == id);
  if (!o) return;
  const items = JSON.parse(o.items || '[]');
  let total = 0;
  const itemsHtml = items.map(i => {
    const line = (i.price || 0) * (i.quantity || 1);
    total += line;
    return `<div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:13px;">
      <div>
        <div>${esc(i.name)}</div>
        <div style="color:#888;">x${i.quantity || 1}${i.price ? ' @ ' + Number(i.price).toLocaleString() + ' FCFA' : ''}</div>
      </div>
      <div style="font-weight:bold;">${i.price ? line.toLocaleString() + ' FCFA' : '—'}</div>
    </div>`;
  }).join('');

  const payIcon = o.payment_method?.includes('MTN') ? '🟡' : o.payment_method?.includes('Orange') ? '🟠' : '💵';
  const date = new Date(o.created_at + ' UTC').toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' });

  document.getElementById('print-area').innerHTML = `
    <div style="text-align:center;margin-bottom:12px;">
      <img src="/images/as-logo.jpeg" alt="American Select" style="height:72px;object-fit:contain;display:block;margin:0 auto 8px;">
      <h2 style="font-size:17px;letter-spacing:1px;margin-bottom:2px;">AMERICAN SELECT</h2>
      <p style="font-size:11px;color:#555;margin:0;">Quality Imports from the USA &amp; Canada</p>
      <p style="font-size:11px;color:#555;margin:4px 0 0;">Yaoundé, Cameroon</p>
      <p style="font-size:11px;color:#555;margin:2px 0 0;">MTN: 679 457 181 &nbsp;|&nbsp; Orange: 686 271 567</p>
      <p style="font-size:11px;color:#555;margin:2px 0 0;">americanselect.net</p>
    </div>
    <hr style="border:none;border-top:1px dashed #aaa;margin:10px 0;">
    <div style="display:flex;justify-content:space-between;font-size:12px;color:#555;margin-bottom:10px;">
      <span>Order: <strong style="color:#222;">${esc(o.order_ref)}</strong></span>
      <span>${date}</span>
    </div>
    ${o.customer_name ? `<p style="font-size:12px;color:#555;margin-bottom:8px;">Customer: <strong style="color:#222;">${esc(o.customer_name)}${o.customer_phone ? ' · ' + esc(o.customer_phone) : ''}</strong></p>` : ''}
    ${itemsHtml}
    <hr style="border:none;border-top:1px dashed #aaa;margin:10px 0;">
    <div style="display:flex;justify-content:space-between;font-size:18px;font-weight:bold;">
      <span>TOTAL</span><span>${(o.total || total).toLocaleString()} FCFA</span>
    </div>
    <p style="margin-top:6px;font-size:12px;color:#888;">${payIcon} Paid via / Paye par: ${esc(o.payment_method || 'N/A')}</p>
    ${o.payment_ref ? `<p style="font-size:12px;color:#888;">🔖 Transaction ID: <strong style="color:#222;">${esc(o.payment_ref)}</strong></p>` : ''}
    <hr style="border:none;border-top:1px dashed #aaa;margin:14px 0 8px;">
    <p style="text-align:center;font-size:11px;color:#888;">Thank you for shopping with American Select!</p>
    <p style="text-align:center;font-size:11px;color:#888;">Merci pour votre achat chez American Select !</p>`;

  const printArea = document.getElementById('print-area');
  printArea.style.display = 'block';
  const hidePrint = () => { printArea.style.display = 'none'; window.removeEventListener('afterprint', hidePrint); };
  window.addEventListener('afterprint', hidePrint);
  setTimeout(() => window.print(), 100);
}

loadOrders();
// Auto-refresh pending orders every 30 seconds
if (FILTER === 'pending' || FILTER === 'all') {
  setInterval(loadOrders, 30000);
}
</script>
</body>
</html>
