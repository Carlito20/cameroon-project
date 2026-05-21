/**
 * Local cash drawer relay — runs on http://localhost:3099
 * Browser POSTs to /drawer → relay connects to QZ Tray WSS internally → opens drawer
 * Plain HTTP is fine: Chrome exempts localhost from mixed-content blocking
 */
import { createServer } from 'http';
import WebSocket from 'ws';

const PORT = 3099;
const ORIGIN = 'https://americanselect.net';
const ESC_POS_DRAWER = '\x1B\x70\x00\x19\xFA'; // ESC p 0 — open cash drawer pin 2

function addCors(res) {
  res.setHeader('Access-Control-Allow-Origin', ORIGIN);
  res.setHeader('Access-Control-Allow-Private-Network', 'true');
  res.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');
}

async function getPrinterName() {
  return new Promise((resolve, reject) => {
    const ws = new WebSocket('wss://localhost:8181', { rejectUnauthorized: false });
    ws.on('open', () => {
      ws.send(JSON.stringify({ type: 'printers', call: 'find', uid: 'p1' }));
    });
    ws.on('message', (data) => {
      try {
        const msg = JSON.parse(data);
        if (msg.uid === 'p1') {
          ws.close();
          const printers = msg.result || msg.message || [];
          const thermal = (Array.isArray(printers) ? printers : [printers]).find(p =>
            /munbyn|volcora|thermal|receipt|pos|epson|star|citizen|bixolon/i.test(String(p))
          ) || printers[0] || null;
          resolve(thermal);
        }
      } catch {}
    });
    ws.on('error', reject);
    setTimeout(() => { ws.close(); reject(new Error('timeout')); }, 5000);
  });
}

async function openDrawer() {
  return new Promise(async (resolve, reject) => {
    let printer;
    try { printer = await getPrinterName(); } catch (e) { return reject(e); }
    if (!printer) return reject(new Error('No printer found'));

    const ws = new WebSocket('wss://localhost:8181', { rejectUnauthorized: false });
    ws.on('open', () => {
      const payload = JSON.stringify({
        type: 'print', uid: 'd1',
        call: 'print',
        params: {
          printer: { name: printer },
          data: [{ type: 'raw', format: 'command', data: ESC_POS_DRAWER }]
        }
      });
      ws.send(payload);
    });
    ws.on('message', (data) => {
      try {
        const msg = JSON.parse(data);
        if (msg.uid === 'd1') { ws.close(); resolve(msg); }
      } catch {}
    });
    ws.on('error', (e) => { ws.close(); reject(e); });
    setTimeout(() => { ws.close(); reject(new Error('timeout')); }, 8000);
  });
}

const server = createServer(async (req, res) => {
  addCors(res);

  if (req.method === 'OPTIONS') {
    res.writeHead(200); res.end(); return;
  }

  if (req.method === 'POST' && req.url === '/drawer') {
    try {
      await openDrawer();
      res.writeHead(200, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({ ok: true }));
    } catch (e) {
      res.writeHead(500, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({ ok: false, error: e.message }));
    }
    return;
  }

  if (req.method === 'GET' && req.url === '/status') {
    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ ok: true, relay: 'running' }));
    return;
  }

  res.writeHead(404); res.end();
});

server.listen(PORT, '127.0.0.1', () => {
  console.log(`Drawer relay running on http://localhost:${PORT}`);
});
