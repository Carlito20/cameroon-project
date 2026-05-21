/**
 * American Select — Cash Drawer Relay
 * Runs on http://localhost:3099
 * Keep this running on any machine that has the cash drawer connected.
 */
import { createServer } from 'http';
import { createRequire } from 'module';
const require = createRequire(import.meta.url);
const WebSocket = require('ws');

const PORT = 3099;
const ORIGIN = 'https://americanselect.net';
const ESC_POS_DRAWER = '\x1B\x70\x00\x19\xFA';

function addCors(res) {
  res.setHeader('Access-Control-Allow-Origin', ORIGIN);
  res.setHeader('Access-Control-Allow-Private-Network', 'true');
  res.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');
}

async function getPrinterName() {
  return new Promise((resolve, reject) => {
    const ws = new WebSocket('wss://localhost:8181', { rejectUnauthorized: false });
    ws.on('open', () => ws.send(JSON.stringify({ type: 'printers', call: 'find', uid: 'p1' })));
    ws.on('message', (data) => {
      try {
        const msg = JSON.parse(data);
        if (msg.uid === 'p1') {
          ws.close();
          const printers = msg.result || msg.message || [];
          const list = Array.isArray(printers) ? printers : [printers];
          const thermal = list.find(p => /munbyn|volcora|thermal|receipt|pos|epson|star|citizen|bixolon/i.test(String(p))) || list[0] || null;
          resolve(thermal);
        }
      } catch {}
    });
    ws.on('error', reject);
    setTimeout(() => { ws.close(); reject(new Error('QZ Tray timeout — is it running?')); }, 5000);
  });
}

async function openDrawer() {
  return new Promise(async (resolve, reject) => {
    let printer;
    try { printer = await getPrinterName(); } catch (e) { return reject(e); }
    if (!printer) return reject(new Error('No printer found in QZ Tray'));
    const ws = new WebSocket('wss://localhost:8181', { rejectUnauthorized: false });
    ws.on('open', () => ws.send(JSON.stringify({
      type: 'print', uid: 'd1', call: 'print',
      params: { printer: { name: printer }, data: [{ type: 'raw', format: 'command', data: ESC_POS_DRAWER }] }
    })));
    ws.on('message', (data) => {
      try { const msg = JSON.parse(data); if (msg.uid === 'd1') { ws.close(); resolve(msg); } } catch {}
    });
    ws.on('error', (e) => { ws.close(); reject(e); });
    setTimeout(() => { ws.close(); reject(new Error('Print timeout')); }, 8000);
  });
}

const server = createServer(async (req, res) => {
  addCors(res);
  if (req.method === 'OPTIONS') { res.writeHead(200); res.end(); return; }

  if (req.method === 'POST' && req.url === '/drawer') {
    try {
      await openDrawer();
      res.writeHead(200, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({ ok: true }));
    } catch (e) {
      console.error('Drawer error:', e.message);
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
  console.log(' American Select — Cash Drawer Relay');
  console.log(' Listening on http://localhost:' + PORT);
  console.log(' Keep this window open (or minimised)');
  console.log('========================================');
});
