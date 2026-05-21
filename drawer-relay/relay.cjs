/**
 * American Select — Cash Drawer Relay
 * Sends ESC/POS drawer command directly via Windows print spooler.
 * No QZ Tray required.
 */
const { createServer } = require('http');
const { execFile, exec } = require('child_process');
const path = require('path');

const PORT = 3099;
const ORIGIN = 'https://americanselect.net';
const PS_SCRIPT = path.join(__dirname, 'rawprint.ps1');

function addCors(res) {
  res.setHeader('Access-Control-Allow-Origin', ORIGIN);
  res.setHeader('Access-Control-Allow-Private-Network', 'true');
  res.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');
}

function openDrawer() {
  return new Promise((resolve, reject) => {
    const cmd = `powershell -NoProfile -NonInteractive -File "${PS_SCRIPT}"`;

    exec(cmd, { timeout: 10000 }, (err, stdout, stderr) => {
      if (err) {
        reject(new Error(stderr || err.message));
      } else if (stdout.includes('OK:')) {
        resolve(stdout.trim());
      } else {
        reject(new Error(stdout || 'Unknown error'));
      }
    });
  });
}

const server = createServer(async (req, res) => {
  addCors(res);
  if (req.method === 'OPTIONS') { res.writeHead(200); res.end(); return; }

  if (req.method === 'POST' && req.url === '/drawer') {
    try {
      const result = await openDrawer();
      console.log('Drawer opened:', result);
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
  console.log(' Printer: auto-detect');
  console.log(' Running on http://localhost:' + PORT);
  console.log(' Keep this window open (or minimised)');
  console.log('========================================');
});
