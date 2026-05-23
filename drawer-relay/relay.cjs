/**
 * American Select — Cash Drawer + Label Relay
 * - /drawer  → opens cash drawer via Windows print spooler
 * - /barcode → prints barcode label directly to Munbyn via ESC/POS
 * - /status  → health check
 */
const { createServer } = require('http');
const { exec } = require('child_process');
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

function readBody(req) {
  return new Promise((resolve) => {
    let body = '';
    req.on('data', d => body += d);
    req.on('end', () => {
      try { resolve(JSON.parse(body)); } catch { resolve({}); }
    });
  });
}

// ── Cash drawer via PowerShell spooler ──────────────────
function openDrawer() {
  return new Promise((resolve, reject) => {
    const cmd = `powershell -NoProfile -NonInteractive -ExecutionPolicy Bypass -File "${PS_SCRIPT}"`;
    exec(cmd, { timeout: 10000 }, (err, stdout, stderr) => {
      if (err) reject(new Error(stderr || err.message));
      else if (stdout.includes('OK:')) resolve(stdout.trim());
      else reject(new Error(stdout || 'Unknown error'));
    });
  });
}

// ── Barcode label via PowerShell + Windows spooler ──────
function printBarcodeLabel(barcodeValue, productName, printerName) {
  return new Promise((resolve, reject) => {
    const ps = `
Add-Type @"
using System;
using System.Runtime.InteropServices;
public class Spooler {
    [DllImport("winspool.drv", CharSet=CharSet.Ansi)]
    public static extern bool OpenPrinter(string n, out IntPtr h, IntPtr d);
    [DllImport("winspool.drv")]
    public static extern bool ClosePrinter(IntPtr h);
    [DllImport("winspool.drv", CharSet=CharSet.Ansi)]
    public static extern int StartDocPrinter(IntPtr h, int level, ref DOC_INFO_1 info);
    [DllImport("winspool.drv")]
    public static extern bool EndDocPrinter(IntPtr h);
    [DllImport("winspool.drv")]
    public static extern bool StartPagePrinter(IntPtr h);
    [DllImport("winspool.drv")]
    public static extern bool EndPagePrinter(IntPtr h);
    [DllImport("winspool.drv")]
    public static extern bool WritePrinter(IntPtr h, byte[] b, int n, out int w);
    [StructLayout(LayoutKind.Sequential, CharSet=CharSet.Ansi)]
    public struct DOC_INFO_1 {
        public string pDocName;
        public string pOutputFile;
        public string pDataType;
    }
}
"@

# Auto-detect printer if not specified
$printerName = "${printerName}"
if (-not $printerName) {
    $all = Get-Printer | Select-Object -ExpandProperty Name
    $printerName = $all | Where-Object { $_ -match 'munbyn|thermal|label' } | Select-Object -First 1
    if (-not $printerName) { $printerName = $all[0] }
}

# Build ESC/POS label data
$esc = [char]0x1B
$gs  = [char]0x1D

$barcode = "${barcodeValue}"
$name    = "${productName}"
# Truncate name to 2 lines of ~24 chars
$nameLine1 = if ($name.Length -gt 24) { $name.Substring(0,24) } else { $name }
$nameLine2 = if ($name.Length -gt 24) { $name.Substring(24, [Math]::Min($name.Length-24, 24)) } else { "" }

# ESC/POS sequence
$bytes = [System.Collections.Generic.List[byte]]::new()
# Initialize
foreach ($b in [System.Text.Encoding]::ASCII.GetBytes("$esc@")) { $bytes.Add($b) }
# Center align
foreach ($b in [System.Text.Encoding]::ASCII.GetBytes("${esc}a1")) { $bytes.Add($b) }
# Bold on
foreach ($b in [System.Text.Encoding]::ASCII.GetBytes("${esc}E1")) { $bytes.Add($b) }
# "AMERICAN SELECT"
foreach ($b in [System.Text.Encoding]::ASCII.GetBytes("AMERICAN SELECT`n")) { $bytes.Add($b) }
# Bold off, normal size
foreach ($b in [System.Text.Encoding]::ASCII.GetBytes("${esc}E0")) { $bytes.Add($b) }
# Barcode height 80 dots
$bytes.Add([byte]0x1D); $bytes.Add([byte]0x68); $bytes.Add([byte]80)
# Barcode width 3 (medium)
$bytes.Add([byte]0x1D); $bytes.Add([byte]0x77); $bytes.Add([byte]3)
# HRI below barcode
$bytes.Add([byte]0x1D); $bytes.Add([byte]0x48); $bytes.Add([byte]2)
# Print CODE128 barcode: GS k 73 (len) (data)
$barcodeBytes = [System.Text.Encoding]::ASCII.GetBytes($barcode)
$bytes.Add([byte]0x1D); $bytes.Add([byte]0x6B); $bytes.Add([byte]0x49)
$bytes.Add([byte]$barcodeBytes.Length)
foreach ($b in $barcodeBytes) { $bytes.Add($b) }
# New line
$bytes.Add([byte]0x0A)
# Product name
foreach ($b in [System.Text.Encoding]::ASCII.GetBytes("$nameLine1`n")) { $bytes.Add($b) }
if ($nameLine2) { foreach ($b in [System.Text.Encoding]::ASCII.GetBytes("$nameLine2`n")) { $bytes.Add($b) } }
# Feed and cut
$bytes.Add([byte]0x0A); $bytes.Add([byte]0x0A)
$bytes.Add([byte]0x1D); $bytes.Add([byte]0x56); $bytes.Add([byte]0x42); $bytes.Add([byte]0x05)

$data = $bytes.ToArray()

$h = [IntPtr]::Zero
if (-not [Spooler]::OpenPrinter($printerName, [ref]$h, [IntPtr]::Zero)) {
    Write-Error "Cannot open: $printerName"; exit 1
}
$doc = New-Object Spooler+DOC_INFO_1
$doc.pDocName = "Barcode"; $doc.pDataType = "RAW"
[Spooler]::StartDocPrinter($h, 1, [ref]$doc) | Out-Null
[Spooler]::StartPagePrinter($h) | Out-Null
$written = 0
[Spooler]::WritePrinter($h, $data, $data.Length, [ref]$written) | Out-Null
[Spooler]::EndPagePrinter($h) | Out-Null
[Spooler]::EndDocPrinter($h) | Out-Null
[Spooler]::ClosePrinter($h) | Out-Null
Write-Host "OK:$written"
`;

    const tmpFile = path.join(require('os').tmpdir(), 'barcode_print.ps1');
    require('fs').writeFileSync(tmpFile, ps, 'utf8');
    exec(`powershell -NoProfile -NonInteractive -ExecutionPolicy Bypass -File "${tmpFile}"`,
      { timeout: 15000 },
      (err, stdout, stderr) => {
        require('fs').unlinkSync(tmpFile);
        if (err) reject(new Error(stderr || err.message));
        else if (stdout.includes('OK:')) resolve(stdout.trim());
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
      console.log('Drawer opened');
      res.writeHead(200, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({ ok: true }));
    } catch (e) {
      console.error('Drawer error:', e.message);
      res.writeHead(500, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({ ok: false, error: e.message }));
    }
    return;
  }

  if (req.method === 'POST' && req.url === '/barcode') {
    try {
      const body = await readBody(req);
      const { barcode, name, printer } = body;
      if (!barcode) throw new Error('Missing barcode value');
      await printBarcodeLabel(barcode, name || '', printer || '');
      console.log('Barcode printed:', barcode, name?.substring(0, 30));
      res.writeHead(200, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({ ok: true }));
    } catch (e) {
      console.error('Barcode print error:', e.message);
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
  console.log(' /drawer  → open cash drawer');
  console.log(' /barcode → print barcode label');
  console.log(' Keep this window open (or minimised)');
  console.log('========================================');
});
