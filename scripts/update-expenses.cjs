/**
 * Reads all PDFs in receipts/processed/, extracts items + quantities,
 * matches each to a spreadsheet row, and writes Items + Total Qty columns.
 */
const { PDFParse } = require('pdf-parse');
const path = require('path');
const fs = require('fs');

const RECEIPTS_DIR = path.resolve(__dirname, '../receipts/processed');
const XLSX_PATH = path.resolve(__dirname, '../receipts/American_Select_Expenses.xlsx');

// ── PDF text extraction ──────────────────────────────────────────────────────

async function extractText(filePath) {
  const url = 'file:///' + filePath.split(path.sep).join('/');
  const p = new PDFParse({ verbosity: 0, url });
  await p.load();
  const result = await p.getText();
  return typeof result === 'string' ? result : result.text || '';
}

// ── Format parsers ───────────────────────────────────────────────────────────

// Shopify/direct vendor: "Item SKU GTIN Price Qty Item Subtotal" table
function parseShopify(text) {
  const items = [];
  const headerIdx = text.indexOf('Item SKU GTIN Price Qty Item Subtotal');
  if (headerIdx === -1) return null;

  const body = text.slice(headerIdx + 'Item SKU GTIN Price Qty Item Subtotal'.length);
  // Each item ends with: optional_sku optional_gtin $price qty $subtotal
  // Pattern: line(s) of item name, then a line matching /(\$[\d.]+)\s+(\d+)\s+\$[\d.]+/
  const lines = body.split('\n').map(l => l.trim()).filter(Boolean);

  let nameParts = [];
  for (const line of lines) {
    // Stop at summary lines
    if (/^(Subtotal|Shipping|Tax|Estimated|Total|PRO FORMA|Charges)/i.test(line)) break;

    // Line that ends an item entry: contains "$price qty $subtotal"
    const m = line.match(/\$?([\d.]+)\s+(\d+)\s+\$[\d.]+\s*$/);
    if (m) {
      const price = parseFloat(m[1]);
      const qty = parseInt(m[2]);
      // Remove trailing SKU/GTIN/price from the line to get item fragment
      const itemFrag = line.replace(/\S+\s+\$?[\d.]+\s+\d+\s+\$[\d.]+\s*$/, '').trim();
      // Also strip leading SKU (alphanumeric code) at start
      const cleanFrag = itemFrag.replace(/^[A-Z0-9]{4,12}\s+/, '').trim();
      const fullName = [...nameParts, cleanFrag].filter(Boolean).join(' ')
        .replace(/\s*\|\s*default\s*-?\s*#\S+/gi, '')
        .replace(/\s*\|\s*default\s*$/gi, '')
        .replace(/\s+/g, ' ')
        .trim();
      if (fullName) items.push({ name: fullName, qty, price });
      nameParts = [];
    } else {
      // Accumulate name lines; strip inline SKU/GTIN codes
      const cleaned = line
        .replace(/^[A-Z0-9]{4,12}\s+\d{12,13}\s*/, '')  // remove leading SKU GTIN
        .replace(/\|\s*default\s*-?\s*#\S+/gi, '')
        .replace(/\|\s*default\s*$/gi, '')
        .trim();
      if (cleaned) nameParts.push(cleaned);
    }
  }

  // Extract vendor + total
  const orderMatch = text.match(/Invoice issued for and on behalf of:.*?\n([^\n]+)/s);
  const vendor = orderMatch ? orderMatch[1].trim() : null;

  const totalMatch = text.match(/(?:Grand Total|Total paid|Estimated total[^$]*|Paid with[^$]*)\$?([\d.]+)/i);
  const total = totalMatch ? parseFloat(totalMatch[1]) : null;

  const dateMatch = text.match(/placed on ([A-Za-z]+ \d+, \d{4})/);
  const date = dateMatch ? dateMatch[1] : null;

  return { format: 'shopify', vendor, date, total, items };
}

// Walmart: "Item name\tQty N $amount" lines
function parseWalmart(text) {
  if (!/walmart/i.test(text) && !/Order#\s*[\d-]{10,}/i.test(text)) return null;
  if (!text.includes('Qty')) return null;

  const items = [];
  // Match: name (optional status) Qty N $line_total  — tab-separated variants
  const re = /^(.+?)\s+(?:Return to Walmart store\s+|Unavailable\s+)?Qty (\d+)(?:\s+\t|\t|\s+)\$([\d.]+)/gm;
  let m;
  while ((m = re.exec(text)) !== null) {
    const name = m[1].replace(/\t/g, ' ').trim();
    const qty = parseInt(m[2]);
    const lineTotal = parseFloat(m[3]);
    const price = Math.round((lineTotal / qty) * 100) / 100;
    if (name && !name.startsWith('More from')) items.push({ name, qty, price });
  }

  const totalMatch = text.match(/^Total\s+\$?([\d.]+)/m);
  const total = totalMatch ? parseFloat(totalMatch[1]) : null;

  const dateMatch = text.match(/([A-Za-z]+ \d+, \d{4}) order/);
  const date = dateMatch ? dateMatch[1] : null;

  const orderMatch = text.match(/Order#\s*(\S+)/);
  const orderId = orderMatch ? orderMatch[1] : null;

  return { format: 'walmart', vendor: 'Walmart', date, total, orderId, items };
}

// Temu: "Item details (N)" section with "name\t$price\nBy seller\nvariant\t×qty"
function parseTemu(text) {
  if (!/temu/i.test(text)) return null;

  const items = [];
  const detailsIdx = text.indexOf('Item details');
  if (detailsIdx === -1) return null;

  const body = text.slice(detailsIdx);
  // Each item: name\t$price\nBy seller\nvariant\t×qty
  const re = /^(.+?)\t\$([\d.]+)\nBy\s+.+\n.+\t×(\d+)/gm;
  let m;
  while ((m = re.exec(body)) !== null) {
    const name = m[1].replace(/…\s*$/, '…').trim();
    const price = parseFloat(m[2]);
    const qty = parseInt(m[3]);
    items.push({ name, qty, price });
  }

  const subtotalMatch = text.match(/Subtotal:\s+\$?([\d.]+)/);
  const subtotal = subtotalMatch ? parseFloat(subtotalMatch[1]) : null;

  const totalMatch = text.match(/Order total:\s+\$?([\d.]+)/);
  const total = totalMatch ? parseFloat(totalMatch[1]) : null;

  const dateMatch = text.match(/Order time:\s+([A-Za-z]+ \d+, \d{4})/);
  const date = dateMatch ? dateMatch[1] : null;

  const orderIdMatch = text.match(/Order ID:\s+(\S+)/);
  const orderId = orderIdMatch ? orderIdMatch[1] : null;

  return { format: 'temu', vendor: 'Temu', date, subtotal, total, orderId, items };
}

// Amazon: item listed before "Sold by: Amazon.com" with $price
function parseAmazon(text) {
  if (!/amazon/i.test(text) && !/Order # \d{3}-\d{7}-\d{7}/i.test(text)) return null;
  if (!text.includes('Sold by: Amazon')) return null;

  const items = [];
  // Item appears between "Your package..." delivery line and "Sold by: Amazon"
  // Price appears after "Return or replace items:" line
  const re = /Your package[^\n]+\n([\s\S]*?)Sold by: Amazon[\s\S]*?Return or replace items:[^\n]+\n\$([\d.]+)/g;
  let m;
  while ((m = re.exec(text)) !== null) {
    const name = m[1].replace(/\n/g, ' ').trim();
    if (!name || name.length > 400) continue; // skip if captured garbage
    const itemTotal = parseFloat(m[2]);
    // qty is usually 1 unless item name says "Pack of N" or "N Count"
    const packMatch = name.match(/[\(\s](\d+)[\s\-](?:pack|count|pk)\b/i);
    const qty = packMatch ? parseInt(packMatch[1]) : 1;
    const price = Math.round((itemTotal / qty) * 100) / 100;
    items.push({ name, qty, price });
  }

  const totalMatch = text.match(/Grand Total:\s+\$?([\d.]+)/);
  const total = totalMatch ? parseFloat(totalMatch[1]) : null;

  const subtotalMatch = text.match(/Item\(s\) Subtotal:\s+\$?([\d.]+)/);
  const subtotal = subtotalMatch ? parseFloat(subtotalMatch[1]) : null;

  const dateMatch = text.match(/Order placed ([A-Za-z]+ \d+, \d{4})/);
  const date = dateMatch ? dateMatch[1] : null;

  const orderMatch = text.match(/Order # (\S+)/);
  const orderId = orderMatch ? orderMatch[1] : null;

  return { format: 'amazon', vendor: 'Amazon.com', date, subtotal, total, orderId, items };
}

// Alibaba Tax Invoice: "Sold By: Vendor Alibaba.com..."
// Item lines end with: qty unit_price discount% tax_rate tax_amount amount amount
function parseAlibaba(text) {
  if (!/alibaba/i.test(text)) return null;
  if (!text.includes('Tax Invoice')) return null;

  const items = [];

  // Find order items section (after Order Number line)
  const orderIdx = text.search(/Order Number:\s*\d+/);
  if (orderIdx === -1) return null;
  const body = text.slice(orderIdx);

  const lines = body.split('\n').map(l => l.trim()).filter(Boolean);
  let nameParts = [];
  for (const line of lines) {
    if (/^Order Number:/i.test(line)) continue; // skip order number line
    if (/^(Shipping Fee|Grand Total|Credit|Amount paid|Sales Tax|Total|Note:)/i.test(line)) break;

    // Item data line ends with: qty(N.00) unitPrice(N.NNNNNN) taxRate% taxAmt amount amount
    // The item name may appear before the numbers on the same line
    const m = line.match(/^(.*?)(\d+\.\d{2})\s+(\d+\.\d+)\s+[\d.]+%\s+[\d.]+\s+([\d.]+)\s+[\d.]+\s*$/);
    if (m) {
      const qty = Math.round(parseFloat(m[2]));
      const lineTotal = parseFloat(m[4]);
      const price = Math.round((lineTotal / qty) * 100) / 100;
      const nameOnLine = m[1].trim();
      const fullName = [...nameParts, nameOnLine].filter(Boolean).join(' ').replace(/\s+/g, ' ').trim();
      if (fullName) items.push({ name: fullName, qty, price });
      nameParts = [];
    } else {
      nameParts.push(line);
    }
  }

  const vendorMatch = text.match(/Sold By:\s*([^\n]+?)(?:\s+Alibaba\.com)?(?:\s+Singapore)?/i);
  const vendor = vendorMatch ? vendorMatch[1].trim() : null;

  const totalMatch = text.match(/Grand Total\s+([\d.]+)/);
  const total = totalMatch ? parseFloat(totalMatch[1]) : null;

  const dateMatch = text.match(/Invoice Date\s*:\s*(\d{4}-\d{2}-\d{2})/);
  const date = dateMatch ? dateMatch[1] : null;

  return { format: 'alibaba', vendor, date, total, items };
}

function parseReceipt(text, filename) {
  return parseTemu(text)
    || parseAmazon(text)
    || parseWalmart(text)
    || parseShopify(text)
    || parseAlibaba(text)
    || null;
}

// ── Load all PDFs ────────────────────────────────────────────────────────────

async function loadAllReceipts() {
  const files = fs.readdirSync(RECEIPTS_DIR)
    .filter(f => f.toLowerCase().endsWith('.pdf'));

  const receipts = [];
  for (const file of files) {
    try {
      const text = await extractText(path.join(RECEIPTS_DIR, file));
      const parsed = parseReceipt(text, file);
      if (parsed) {
        parsed.file = file;
        receipts.push(parsed);
      } else {
        console.warn(`  UNRECOGNIZED: ${file}`);
      }
    } catch (e) {
      console.warn(`  ERROR reading ${file}: ${e.message}`);
    }
  }
  return receipts;
}

// ── Match receipts to spreadsheet rows ──────────────────────────────────────

function matchReceipts(rows, receipts) {
  // rows: array of {rowNum, date, vendor, subtotal, total}
  // Returns: Map<rowNum, receipt>

  const used = new Set();
  const result = new Map();

  // Normalize vendor name for comparison
  const norm = s => (s || '').toLowerCase().replace(/[^a-z0-9]/g, '');

  for (const row of rows) {
    const rowTotal = row.total;
    const rowVendor = norm(row.vendor);
    const rowSubtotal = row.subtotal;

    // Find candidate receipts: match by total (within $0.05) or subtotal
    let candidates = receipts.filter(r => !used.has(r.file)).filter(r => {
      const totalMatch = r.total != null && Math.abs(r.total - rowTotal) < 0.06;
      const subtotalMatch = r.subtotal != null && Math.abs(r.subtotal - rowSubtotal) < 0.06;
      return totalMatch || subtotalMatch;
    });

    if (candidates.length === 0) continue;

    // Prefer vendor match
    const vendorMatch = candidates.filter(r => norm(r.vendor).includes(rowVendor) || rowVendor.includes(norm(r.vendor)));
    if (vendorMatch.length > 0) candidates = vendorMatch;

    // Take first candidate
    const match = candidates[0];
    result.set(row.rowNum, match);
    used.add(match.file);
  }

  return result;
}

// ── Excel update ─────────────────────────────────────────────────────────────

function formatItems(items) {
  if (!items || items.length === 0) return '';
  return items.map(i => {
    const priceStr = i.price != null ? ` @ $${i.price.toFixed(2)}/unit` : '';
    return `${i.name} ×${i.qty}${priceStr}`;
  }).join('\n');
}

function totalQty(items) {
  if (!items || items.length === 0) return 0;
  return items.reduce((s, i) => s + i.qty, 0);
}

async function updateExcel(matchMap) {
  const { execSync } = require('child_process');

  // Build a JSON data file for the PowerShell script
  const data = {};
  for (const [rowNum, receipt] of matchMap.entries()) {
    data[rowNum] = {
      items: formatItems(receipt.items),
      totalQty: String(totalQty(receipt.items)),
      file: receipt.file,
    };
  }

  const dataFile = path.resolve(__dirname, '../receipts/receipt-data.json');
  // Write UTF-8 with BOM so PowerShell 5.1 reads it correctly
  fs.writeFileSync(dataFile, '﻿' + JSON.stringify(data), 'utf8');
  console.log(`\nWriting ${Object.keys(data).length} rows to Excel...`);

  const psScript = `
$data = Get-Content '${dataFile.replace(/\\/g, '\\\\')}' -Encoding UTF8 | ConvertFrom-Json
$excel = New-Object -ComObject Excel.Application
$excel.Visible = $false
$excel.DisplayAlerts = $false
$wb = $excel.Workbooks.Open('${XLSX_PATH.replace(/\\/g, '\\\\')}')
$ws = $wb.Worksheets(1)

# Add headers in col 9 and 10 if not already there
if ($ws.Cells(1,9).Value2 -ne 'Items') { $ws.Cells(1,9).Value2 = 'Items' }
if ($ws.Cells(1,10).Value2 -ne 'Total Qty') { $ws.Cells(1,10).Value2 = 'Total Qty' }

$props = $data.PSObject.Properties
foreach ($prop in $props) {
  $rowNum = [int]$prop.Name
  $val = $prop.Value
  $ws.Cells($rowNum, 9).Value2 = $val.items
  $ws.Cells($rowNum, 9).WrapText = $true
  $ws.Cells($rowNum, 10).Value2 = $val.totalQty
}

# Auto-fit column 9 width (cap at 80)
$ws.Columns(9).ColumnWidth = 60
$ws.Columns(10).ColumnWidth = 12

$wb.Save()
$wb.Close()
$excel.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($excel) | Out-Null
Write-Host "Done"
`;

  const psFile = path.resolve(__dirname, '../receipts/update-excel.ps1');
  fs.writeFileSync(psFile, psScript);

  const out = execSync(`powershell -ExecutionPolicy Bypass -File "${psFile}"`, { encoding: 'utf8' });
  console.log(out.trim());

  fs.unlinkSync(dataFile);
  fs.unlinkSync(psFile);
}

// ── Main ─────────────────────────────────────────────────────────────────────

async function main() {
  console.log('Reading spreadsheet rows...');

  // Read existing rows from Excel via PowerShell
  const psRead = `
$excel = New-Object -ComObject Excel.Application
$excel.Visible = $false
$wb = $excel.Workbooks.Open('${XLSX_PATH.replace(/\\/g, '\\\\')}')
$ws = $wb.Worksheets(1)
$rows = @()
for ($r = 2; $r -le 88; $r++) {
  $vendor = $ws.Cells($r,2).Value2
  if ($vendor -eq $null) { continue }
  $sub = $ws.Cells($r,4).Value2; if ($sub -eq $null) { $sub = 0 }
  $tot = $ws.Cells($r,8).Value2; if ($tot -eq $null) { $tot = 0 }
  $rows += [PSCustomObject]@{
    rowNum   = $r
    vendor   = [string]$vendor
    subtotal = [double]$sub
    total    = [double]$tot
  }
}
$wb.Close($false)
$excel.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($excel) | Out-Null
$rows | ConvertTo-Json
`;

  const { execSync } = require('child_process');
  const psFile = path.resolve(__dirname, '../receipts/read-rows.ps1');
  fs.writeFileSync(psFile, psRead);
  const rawRows = execSync(`powershell -ExecutionPolicy Bypass -File "${psFile}"`, { encoding: 'utf8' });
  fs.unlinkSync(psFile);

  const rows = JSON.parse(rawRows);
  console.log(`  Found ${rows.length} data rows`);

  console.log('\nParsing PDF receipts...');
  const receipts = await loadAllReceipts();
  console.log(`  Parsed ${receipts.length} receipts`);

  console.log('\nMatching receipts to rows...');
  const matchMap = matchReceipts(rows, receipts);
  console.log(`  Matched ${matchMap.size} of ${rows.length} rows`);

  // Report unmatched
  const matched = new Set(matchMap.keys());
  const unmatched = rows.filter(r => !matched.has(r.rowNum));
  if (unmatched.length) {
    console.log('\nUnmatched rows:');
    unmatched.forEach(r => console.log(`  Row ${r.rowNum}: ${r.vendor} total=$${r.total}`));
  }

  // Show match summary
  console.log('\nMatched:');
  for (const [rowNum, receipt] of matchMap.entries()) {
    const row = rows.find(r => r.rowNum === rowNum);
    console.log(`  Row ${rowNum} ${row.vendor} → ${receipt.file} (${receipt.items.length} item types, ${totalQty(receipt.items)} total qty)`);
  }

  console.log('\nUpdating Excel...');
  await updateExcel(matchMap);
  console.log('Done!');
}

main().catch(err => { console.error(err); process.exit(1); });
