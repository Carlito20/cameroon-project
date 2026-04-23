<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>American Select — Customer Display</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { height: 100%; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: #f5f5f5; color: #111;
      display: flex; flex-direction: column; min-height: 100vh;
      overflow: hidden;
    }

    /* ── Header ── */
    header {
      background: #111; color: #d4af37;
      padding: 14px 24px;
      display: flex; align-items: center; justify-content: space-between;
      flex-shrink: 0;
    }
    .header-logo { display: flex; align-items: center; gap: 12px; }
    .header-logo img { height: 44px; object-fit: contain; }
    .header-title { font-size: 18px; font-weight: 800; letter-spacing: 1.5px; }
    .header-sub { font-size: 11px; color: #666; margin-top: 2px; }
    .header-time { font-size: 22px; font-weight: 700; color: #d4af37; font-variant-numeric: tabular-nums; }

    /* ── Main layout ── */
    .display-body {
      flex: 1; display: flex; flex-direction: column;
      padding: 20px 24px;
      gap: 16px;
      overflow: hidden;
    }

    /* ── Items list ── */
    .items-panel {
      flex: 1; background: #fff; border-radius: 14px;
      box-shadow: 0 2px 12px rgba(0,0,0,.07);
      display: flex; flex-direction: column;
      overflow: hidden;
    }
    .items-header {
      padding: 14px 20px; border-bottom: 2px solid #f0f0f0;
      font-size: 13px; font-weight: 700; color: #888;
      text-transform: uppercase; letter-spacing: 0.5px;
      display: flex; justify-content: space-between;
    }
    .items-list {
      flex: 1; overflow-y: auto;
      -webkit-overflow-scrolling: touch;
    }
    .item-row {
      display: flex; align-items: center; justify-content: space-between;
      padding: 10px 20px; border-bottom: 1px solid #f5f5f5;
      gap: 14px;
      animation: slideIn 0.25s ease;
    }
    @keyframes slideIn {
      from { opacity: 0; transform: translateY(-6px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .item-img {
      width: 64px; height: 64px; flex-shrink: 0;
      border-radius: 8px; background: #f0f0f0;
      object-fit: contain; padding: 4px;
      display: block;
    }
    .item-img-placeholder {
      width: 64px; height: 64px; flex-shrink: 0;
      border-radius: 8px; background: #f0f0f0;
      display: flex; align-items: center; justify-content: center;
      font-size: 28px; color: #ccc;
    }
    .item-name { font-size: 15px; font-weight: 600; color: #111; flex: 1; line-height: 1.3; }
    .item-qty  { font-size: 14px; color: #888; white-space: nowrap; }
    .item-line { font-size: 16px; font-weight: 700; color: #111; white-space: nowrap; }
    .empty-state {
      flex: 1; display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      color: #bbb; font-size: 16px; padding: 40px;
      text-align: center; gap: 10px;
    }
    .empty-icon { font-size: 56px; opacity: 0.4; }

    /* ── Total bar ── */
    .total-bar {
      background: #fff; border-radius: 14px;
      box-shadow: 0 2px 12px rgba(0,0,0,.07);
      padding: 18px 24px;
      display: flex; align-items: center; justify-content: space-between;
      flex-shrink: 0;
    }
    .total-label { font-size: 15px; font-weight: 700; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }
    .total-amount { font-size: 36px; font-weight: 800; color: #111; font-variant-numeric: tabular-nums; }

    /* ── Payment panel ── */
    .payment-panel {
      border-radius: 14px; padding: 18px 24px;
      flex-shrink: 0; display: none;
      align-items: center; justify-content: space-between; gap: 16px;
    }
    .payment-panel.visible { display: flex; }
    .payment-panel.mtn    { background: #1a1500; }
    .payment-panel.orange { background: #1a0e00; }
    .payment-panel.cash   { background: #0a1a0a; }
    .payment-panel.other  { background: #111; }

    .pay-method-label { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
    .pay-number { font-size: 30px; font-weight: 800; letter-spacing: 2px; font-variant-numeric: tabular-nums; }
    .pay-send   { font-size: 13px; margin-top: 4px; opacity: 0.7; }
    .pay-icon   { font-size: 48px; }

    .mtn    .pay-method-label, .mtn    .pay-number { color: #f0c040; }
    .mtn    .pay-send { color: #f0c040; }
    .orange .pay-method-label, .orange .pay-number { color: #ff8c00; }
    .orange .pay-send { color: #ff8c00; }
    .cash   .pay-method-label, .cash   .pay-number { color: #6dbf6d; }
    .cash   .pay-send { color: #6dbf6d; }
    .other  .pay-method-label, .other  .pay-number { color: #e0e0e0; }
    .other  .pay-send { color: #aaa; }

    /* ── Welcome screen ── */
    .welcome-screen {
      flex: 1; display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      text-align: center; padding: 40px;
      gap: 20px;
    }
    .welcome-screen img { height: 100px; object-fit: contain; opacity: 0.9; }
    .welcome-screen h1 { font-size: 28px; font-weight: 800; color: #111; letter-spacing: 1px; }
    .welcome-screen p  { font-size: 16px; color: #888; max-width: 360px; line-height: 1.6; }
    .welcome-dots { display: flex; gap: 8px; margin-top: 10px; }
    .welcome-dot { width: 8px; height: 8px; border-radius: 50%; background: #d4af37; animation: dotPulse 1.5s ease-in-out infinite; }
    .welcome-dot:nth-child(2) { animation-delay: 0.3s; }
    .welcome-dot:nth-child(3) { animation-delay: 0.6s; }
    @keyframes dotPulse { 0%,100%{opacity:0.3} 50%{opacity:1} }

    /* ── Footer ── */
    footer {
      background: #111; color: #444; font-size: 11px;
      text-align: center; padding: 8px 24px;
      flex-shrink: 0;
    }

    @media (max-width: 600px) {
      .total-amount { font-size: 26px; }
      .pay-number   { font-size: 22px; }
      .header-time  { font-size: 16px; }
    }
  </style>
</head>
<body>

<header>
  <div class="header-logo">
    <img src="/images/as-logo.jpeg" alt="American Select">
    <div>
      <div class="header-title">AMERICAN SELECT</div>
      <div class="header-sub">Quality Imports from USA &amp; Canada</div>
    </div>
  </div>
  <div class="header-time" id="clock"></div>
</header>

<!-- Welcome (shown when no active sale) -->
<div class="welcome-screen" id="welcome-screen">
  <img src="/images/as-logo.jpeg" alt="American Select">
  <h1>Welcome! / Bienvenue !</h1>
  <p>Your items will appear here as they are scanned.<br>
  <span style="opacity:0.7;font-size:0.9em;">Vos articles apparaîtront ici au fur et à mesure du scan.</span></p>
  <div class="welcome-dots">
    <div class="welcome-dot"></div>
    <div class="welcome-dot"></div>
    <div class="welcome-dot"></div>
  </div>
</div>

<!-- Active sale display -->
<div class="display-body" id="display-body" style="display:none;">
  <div class="items-panel">
    <div class="items-header">
      <span>Item</span>
      <span>Amount</span>
    </div>
    <div class="items-list" id="items-list">
      <div class="empty-state">
        <div class="empty-icon">🛒</div>
        <span>Scanning items… / Scan en cours…</span>
      </div>
    </div>
  </div>

  <div class="total-bar">
    <span class="total-label">Total</span>
    <span class="total-amount" id="total-amount">0 FCFA</span>
  </div>

  <div class="payment-panel" id="payment-panel">
    <div>
      <div class="pay-method-label" id="pay-label"></div>
      <div class="pay-number" id="pay-number"></div>
      <div class="pay-send" id="pay-send"></div>
    </div>
    <div class="pay-icon" id="pay-icon"></div>
  </div>
</div>

<footer>americanselect.net &nbsp;|&nbsp; MTN: 679 457 181 &nbsp;|&nbsp; Orange: 686 271 567</footer>

<script>
  // ── Clock ──────────────────────────────────────────────────────────────
  function updateClock() {
    const now = new Date();
    document.getElementById('clock').textContent =
      now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
  }
  updateClock();
  setInterval(updateClock, 10000);

  // ── Format ──────────────────────────────────────────────────────────────
  function fmt(n) { return Number(n).toLocaleString('fr-CM') + ' FCFA'; }

  // ── Render state ────────────────────────────────────────────────────────
  let lastUpdated = 0;

  function renderState(state) {
    const welcome     = document.getElementById('welcome-screen');
    const body        = document.getElementById('display-body');
    const itemsList   = document.getElementById('items-list');
    const totalEl     = document.getElementById('total-amount');
    const payPanel    = document.getElementById('payment-panel');

    if (!state.active || !state.items || state.items.length === 0) {
      welcome.style.display = 'flex';
      body.style.display    = 'none';
      return;
    }

    welcome.style.display = 'none';
    body.style.display    = 'flex';

    // Items
    if (state.items.length === 0) {
      itemsList.innerHTML = `<div class="empty-state"><div class="empty-icon">🛒</div><span>Scanning items…</span></div>`;
    } else {
      itemsList.innerHTML = state.items.map(i => `
        <div class="item-row">
          ${i.image
            ? `<img class="item-img" src="${esc(i.image)}" alt="" loading="lazy">`
            : `<div class="item-img-placeholder">🛍</div>`}
          <span class="item-name">${esc(i.name)}</span>
          <span class="item-qty">×${i.qty}</span>
          <span class="item-line">${i.price ? fmt(i.price * i.qty) : '—'}</span>
        </div>`).join('');
      // Scroll to bottom to show latest item
      itemsList.scrollTop = itemsList.scrollHeight;
    }

    // Total
    totalEl.textContent = fmt(state.total || 0);

    // Payment method
    const method = state.payment || '';
    if (method) {
      payPanel.className = 'payment-panel visible';
      if (method.includes('MTN')) {
        payPanel.classList.add('mtn');
        document.getElementById('pay-label').textContent  = 'MTN Mobile Money';
        document.getElementById('pay-number').textContent = '679 457 181';
        document.getElementById('pay-send').textContent   = 'Send payment to this number';
        document.getElementById('pay-icon').textContent   = '🟡';
      } else if (method.includes('Orange')) {
        payPanel.classList.add('orange');
        document.getElementById('pay-label').textContent  = 'Orange Money';
        document.getElementById('pay-number').textContent = '686 271 567';
        document.getElementById('pay-send').textContent   = 'Send payment to this number';
        document.getElementById('pay-icon').textContent   = '🟠';
      } else if (method.includes('Cash')) {
        payPanel.classList.add('cash');
        document.getElementById('pay-label').textContent  = 'Cash Payment';
        document.getElementById('pay-number').textContent = fmt(state.total || 0);
        document.getElementById('pay-send').textContent   = 'Amount due';
        document.getElementById('pay-icon').textContent   = '💵';
      } else {
        payPanel.classList.add('other');
        document.getElementById('pay-label').textContent  = 'Payment Method';
        document.getElementById('pay-number').textContent = method;
        document.getElementById('pay-send').textContent   = '';
        document.getElementById('pay-icon').textContent   = '💳';
      }
    } else {
      payPanel.className = 'payment-panel';
    }
  }

  // ── Poll API ────────────────────────────────────────────────────────────
  async function poll() {
    try {
      const res = await fetch('/api/display.php?t=' + Date.now());
      const state = await res.json();
      if (state.updated !== lastUpdated) {
        lastUpdated = state.updated;
        renderState(state);
      }
    } catch {}
  }

  poll();
  setInterval(poll, 1500);

  function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }
</script>
</body>
</html>
