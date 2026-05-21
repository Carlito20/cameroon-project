/**
 * American Select — Cash Drawer Relay
 * Standalone — no Node.js required (built with pkg/SEA)
 */
const { createServer } = require('http');
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

function qzCall(ws, call, params) {
  return new Promise((resolve, reject) => {
    const uid = 'uid-' + Date.now();
    const handler = (data) => {
      try {
        const msg = JSON.parse(data);
        if (msg.uid === uid) {
          ws.off('message', handler);
          if (msg.error) reject(new Error(msg.error));
          else resolve(msg.result);
        }
      } catch {}
    };
    ws.on('message', handler);
    ws.send(JSON.stringify({ call, params, timestamp: Date.now(), uid }));
    setTimeout(() => { ws.off('message', handler); reject(new Error('QZ Tray timeout')); }, 8000);
  });
}

async function openDrawer() {
  return new Promise((resolve, reject) => {
    const ws = new WebSocket('wss://localhost:8181', { rejectUnauthorized: false });

    ws.on('open', async () => {
      try {
        // Get all printers
        const printers = await qzCall(ws, 'printers.find', {});
        const list = Array.isArray(printers) ? printers : [printers];
        console.log('Printers:', list);

        const printer = list.find(p =>
          /munbyn|volcora|thermal|receipt|pos|epson|star|citizen|bixolon/i.test(String(p))
        ) || list[0];

        if (!printer) throw new Error('No printer found. Available: ' + list.join(', '));

        console.log('Sending drawer command to:', printer);

        // Send drawer command
        await qzCall(ws, 'print', {
          printer: { name: printer },
          options: { raw: true },
          data: [{ type: 'raw', format: 'command', data: ESC_POS_DRAWER }]
        });

        ws.close();
        resolve({ ok: true, printer });
      } catch (e) {
        ws.close();
        reject(e);
      }
    });

    ws.on('error', (e) => reject(new Error('QZ Tray not running: ' + e.message)));
    setTimeout(() => { ws.close(); reject(new Error('QZ Tray connection timeout')); }, 10000);
  });
}

const server = createServer(async (req, res) => {
  addCors(res);
  if (req.method === 'OPTIONS') { res.writeHead(200); res.end(); return; }

  if (req.method === 'POST' && req.url === '/drawer') {
    try {
      const result = await openDrawer();
      console.log('Drawer opened:', result.printer);
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
  console.log(' American Select - Cash Drawer Relay');
  console.log(' Running on http://localhost:' + PORT);
  console.log(' Keep this window open (or minimised)');
  console.log('========================================');
});
