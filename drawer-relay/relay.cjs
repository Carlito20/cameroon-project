/**
 * American Select — Cash Drawer + Label Relay
 * - /drawer  → opens cash drawer via Windows print spooler (POS-80C)
 * - /barcode → prints rendered label image to Munbyn ITPP130
 * - /status  → health check
 */
const { createServer } = require('http');
const { execFile } = require('child_process');
const { writeFileSync, unlinkSync } = require('fs');
const { tmpdir } = require('os');
const path = require('path');

const PORT = 3099;
const ORIGIN = 'https://americanselect.net';
const PS_DRAWER = path.join(__dirname, 'rawprint.ps1');
const PS_LABEL  = path.join(__dirname, 'print-label.ps1');
const PS_RAW    = path.join(__dirname, 'print-raw.ps1');
const DRAWER_PRINTER = 'POS-80C';

// Auto-detect Munbyn printer name at startup (handles ITPP130, IPP139, etc.)
const { execSync } = require('child_process');
let LABEL_PRINTER = '';
try {
  LABEL_PRINTER = execSync(
    'powershell -NoProfile -Command "Get-Printer | Where-Object { $_.Name -match \'munbyn\' } | Select-Object -First 1 -ExpandProperty Name"',
    { timeout: 5000 }
  ).toString().trim();
} catch {}
if (!LABEL_PRINTER) LABEL_PRINTER = 'Munbyn ITPP130';
console.log('Label printer:', LABEL_PRINTER);

function addCors(res) {
  res.setHeader('Access-Control-Allow-Origin', ORIGIN);
  res.setHeader('Access-Control-Allow-Private-Network', 'true');
  res.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');
}

function readBody(req) {
  return new Promise((resolve) => {
    const chunks = [];
    req.on('data', d => chunks.push(d));
    req.on('end', () => {
      try { resolve(JSON.parse(Buffer.concat(chunks).toString())); } catch { resolve({}); }
    });
  });
}

// ── Cash drawer ──────────────────────────────────────────
function openDrawer() {
  return new Promise((resolve, reject) => {
    execFile('powershell', [
      '-NoProfile', '-NonInteractive', '-ExecutionPolicy', 'Bypass',
      '-File', PS_DRAWER, '-printerName', DRAWER_PRINTER
    ], { timeout: 10000 }, (err, stdout, stderr) => {
      if (err) reject(new Error(stderr || err.message));
      else if (stdout.includes('OK:')) resolve();
      else reject(new Error(stderr || stdout || 'Drawer command failed'));
    });
  });
}

// ── Print raw ESC/POS bytes to receipt printer ───────────
function printRaw(filePath, printer) {
  return new Promise((resolve, reject) => {
    execFile('powershell', [
      '-NoProfile', '-NonInteractive', '-ExecutionPolicy', 'Bypass',
      '-File', PS_RAW, '-filePath', filePath, '-printer', printer
    ], { timeout: 15000 }, (err, stdout, stderr) => {
      if (err) reject(new Error(stderr || err.message));
      else if (stdout.includes('OK:')) resolve();
      else reject(new Error(stderr || stdout || 'Print failed'));
    });
  });
}

// ── Print label image to Munbyn ──────────────────────────
// widthIn/heightIn are the physical label size in inches (default 3x2 for
// backward compatibility with older cached frontend code).
function printLabelImage(base64png, widthIn = 3, heightIn = 2) {
  return new Promise((resolve, reject) => {
    const tmpFile = path.join(tmpdir(), 'label_' + Date.now() + '.png');
    const imgData = base64png.replace(/^data:image\/\w+;base64,/, '');
    writeFileSync(tmpFile, Buffer.from(imgData, 'base64'));

    execFile('powershell', [
      '-NoProfile', '-NonInteractive', '-ExecutionPolicy', 'Bypass',
      '-File', PS_LABEL, '-imagePath', tmpFile, '-printer', LABEL_PRINTER,
      '-widthHundredths', String(Math.round(widthIn * 100)),
      '-heightHundredths', String(Math.round(heightIn * 100))
    ], { timeout: 15000 }, (err, stdout, stderr) => {
      try { unlinkSync(tmpFile); } catch {}
      if (err) reject(new Error(stderr || err.message));
      else if (stdout.includes('OK:')) resolve();
      else reject(new Error(stderr || stdout || 'Print failed'));
    });
  });
}

// ── HTTP server ──────────────────────────────────────────
const server = createServer(async (req, res) => {
  addCors(res);
  if (req.method === 'OPTIONS') { res.writeHead(200); res.end(); return; }

  if (req.method === 'POST' && req.url === '/drawer') {
    try {
      await openDrawer();
      console.log('[drawer] opened');
      res.writeHead(200, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({ ok: true }));
    } catch (e) {
      console.error('[drawer] error:', e.message);
      res.writeHead(500, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({ ok: false, error: e.message }));
    }
    return;
  }

  if (req.method === 'POST' && req.url === '/barcode') {
    try {
      const body = await readBody(req);
      if (!body.image) throw new Error('Missing image data');
      await printLabelImage(body.image, body.widthIn, body.heightIn);
      console.log('[barcode] printed');
      res.writeHead(200, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({ ok: true }));
    } catch (e) {
      console.error('[barcode] error:', e.message);
      res.writeHead(500, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({ ok: false, error: e.message }));
    }
    return;
  }

  if (req.method === 'POST' && req.url === '/receipt') {
    try {
      const body = await readBody(req);
      if (!body.data) throw new Error('Missing receipt data');
      const tmpFile = path.join(tmpdir(), 'receipt_' + Date.now() + '.bin');
      writeFileSync(tmpFile, Buffer.from(body.data, 'base64'));
      try { await printRaw(tmpFile, DRAWER_PRINTER); } finally { try { unlinkSync(tmpFile); } catch {} }
      console.log('[receipt] printed');
      res.writeHead(200, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({ ok: true }));
    } catch (e) {
      console.error('[receipt] error:', e.message);
      res.writeHead(500, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({ ok: false, error: e.message }));
    }
    return;
  }

  if (req.url === '/status') {
    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ ok: true, relay: 'running' }));
    return;
  }

  res.writeHead(404); res.end();
});

server.listen(PORT, '127.0.0.1', () => {
  console.log('========================================');
  console.log(' American Select — Drawer + Label Relay');
  console.log(' Running on http://localhost:' + PORT);
  console.log(' /drawer  → POS-80C cash drawer');
  console.log(' /barcode → Munbyn ITPP130 label');
  console.log(' Keep this window open (or minimised)');
  console.log('========================================');
});
