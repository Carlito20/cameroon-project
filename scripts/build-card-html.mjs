import { readFileSync, writeFileSync } from 'fs';

const logo = readFileSync('Images/Pages/AS Logo new.png').toString('base64');
const qr   = readFileSync('Images/QR/americanselect-qr.png').toString('base64');

const html = `<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }

  @page { width: 3.5in; height: 2in; margin: 0; }

  body {
    width: 3.5in;
    height: 2in;
    font-family: Arial, sans-serif;
    overflow: hidden;
  }

  /* ===== FRONT ===== */
  .front {
    width: 3.5in;
    height: 2in;
    background: radial-gradient(ellipse at 30% 50%, #2a2a2a 0%, #0e0e0e 60%);
    border: 3px solid #c8922a;
    border-radius: 10px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    page-break-after: always;
    position: relative;
    overflow: hidden;
  }
  .front::before {
    content: '';
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 4px;
    background: linear-gradient(90deg, #c8922a, #f0c050, #c8922a);
  }
  .front .logo-img { width: 60px; height: 60px; border-radius: 50%; }
  .front .brand-name {
    font-size: 20px;
    font-weight: 900;
    letter-spacing: 3px;
    color: #fff;
    text-transform: uppercase;
  }
  .front .brand-name span { color: #f0a500; }
  .front .divider { width: 36px; height: 2px; background: #f0a500; }
  .front .tagline {
    font-size: 6.5px;
    letter-spacing: 2.5px;
    color: #aaa;
    text-transform: uppercase;
  }

  /* ===== BACK ===== */
  .back {
    width: 3.5in;
    height: 2in;
    display: flex;
    border: 3px solid #c8922a;
    border-radius: 10px;
    overflow: hidden;
    position: relative;
  }
  .back::before {
    content: '';
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 4px;
    background: linear-gradient(180deg, #c8922a, #f0c050, #c8922a);
    z-index: 10;
  }
  .back::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 4px;
    background: linear-gradient(90deg, #c8922a, #f0c050, #c8922a);
    z-index: 10;
  }

  /* Left dark half */
  .back-left {
    width: 44%;
    background: radial-gradient(ellipse at 40% 50%, #2a2a2a 0%, #0e0e0e 70%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px;
  }
  .back-left .logo-img { width: 52px; height: 52px; border-radius: 50%; }
  .back-left .brand-name {
    font-size: 11px;
    font-weight: 900;
    letter-spacing: 2px;
    color: #fff;
    text-transform: uppercase;
    text-align: center;
  }
  .back-left .brand-name span { color: #f0a500; }
  .back-left .tagline {
    font-size: 5px;
    letter-spacing: 1.5px;
    color: #888;
    text-transform: uppercase;
    text-align: center;
  }

  /* Right white half */
  .back-right {
    width: 56%;
    background: #fff;
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    padding: 14px 10px 14px 16px;
  }

  .contact-list { display: flex; flex-direction: column; gap: 10px; }
  .contact-item { display: flex; align-items: center; gap: 7px; }
  .contact-icon {
    width: 20px; height: 20px;
    border-radius: 50%;
    background: #f0a500;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .contact-icon svg { width: 11px; height: 11px; fill: #1a1a1a; }
  .contact-text { display: flex; flex-direction: column; }
  .contact-label {
    font-size: 5px;
    letter-spacing: 1.5px;
    color: #f0a500;
    text-transform: uppercase;
    font-weight: 700;
  }
  .contact-value { font-size: 7px; color: #1a1a1a; font-weight: 600; }

  .qr-section { display: flex; flex-direction: column; align-items: center; gap: 4px; }
  .qr-box {
    width: 60px; height: 60px;
    border: 2.5px solid #f0a500;
    border-radius: 4px;
    padding: 3px;
    background: #fff;
  }
  .qr-box img { width: 100%; height: 100%; display: block; }
  .scan-label { font-size: 5px; letter-spacing: 1.5px; color: #888; text-transform: uppercase; }
</style>
</head>
<body>

<!-- FRONT -->
<div class="front">
  <img class="logo-img" src="data:image/png;base64,${logo}" alt="AS Logo">
  <div class="brand-name">AMERICAN <span>SELECT</span></div>
  <div class="divider"></div>
  <div class="tagline">Quality Goods from USA &amp; Canada</div>
</div>

<!-- BACK -->
<div class="back">
  <div class="back-left">
    <img class="logo-img" src="data:image/png;base64,${logo}" alt="AS Logo">
    <div class="brand-name">AMERICAN <span>SELECT</span></div>
    <div class="tagline">IMPORTED. TRUSTED. DELIVERED.</div>
  </div>
  <div class="back-right">
    <div class="contact-list">

      <!-- WhatsApp -->
      <div class="contact-item">
        <div class="contact-icon">
          <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        </div>
        <div class="contact-text">
          <span class="contact-label">WhatsApp</span>
          <span class="contact-value">+237 670 35 85 51</span>
        </div>
      </div>

      <!-- Orange Money -->
      <div class="contact-item">
        <div class="contact-icon" style="background:#ff6600;">
          <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        </div>
        <div class="contact-text">
          <span class="contact-label" style="color:#ff6600;">Call / WhatsApp Us / Orange MoMo</span>
          <span class="contact-value">686 271 567</span>
        </div>
      </div>

      <!-- Website -->
      <div class="contact-item">
        <div class="contact-icon">
          <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
        </div>
        <div class="contact-text">
          <span class="contact-label">Website</span>
          <span class="contact-value">americanselect.net</span>
        </div>
      </div>

      <!-- Location -->
      <div class="contact-item">
        <div class="contact-icon">
          <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
        </div>
        <div class="contact-text">
          <span class="contact-label">Location</span>
          <span class="contact-value">Yaound&eacute;, Cameroon</span>
        </div>
      </div>

    </div>

    <!-- QR Code -->
    <div class="qr-section">
      <div class="qr-box">
        <img src="data:image/png;base64,${qr}" alt="QR Code">
      </div>
      <div class="scan-label">SCAN TO SHOP</div>
    </div>
  </div>
</div>

</body>
</html>`;

writeFileSync('business-card-new.html', html);
console.log('HTML written: business-card-new.html');
