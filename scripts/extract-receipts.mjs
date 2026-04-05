/**
 * extract-receipts.mjs
 * • OCR-scans image-based PDFs in receipts/
 * • Parses vendor / date / amounts
 * • Adds new rows to American_Select_Expenses.xlsx with color coding
 * • Moves processed PDFs to receipts/processed/
 *
 * Usage:
 *   node scripts/extract-receipts.mjs              → process all new PDFs
 *   node scripts/extract-receipts.mjs --dry-run    → preview, no changes
 *   node scripts/extract-receipts.mjs "file.pdf"   → single file
 */

import { pdf as pdfToImg }  from 'pdf-to-img';
import Tesseract             from 'tesseract.js';
import { createRequire }     from 'module';
import path                  from 'path';
import fs                    from 'fs';
import { fileURLToPath }     from 'url';

const require   = createRequire(import.meta.url);
const ExcelJS   = require('exceljs');

const __dirname  = path.dirname(fileURLToPath(import.meta.url));
const ROOT       = path.join(__dirname, '..');
const RECEIPTS   = path.join(ROOT, 'receipts');
const PROCESSED  = path.join(RECEIPTS, 'processed');
const XLSX_FILE  = path.join(ROOT, 'American_Select_Expenses.xlsx');

const DRY_RUN = process.argv.includes('--dry-run');
const SINGLE  = process.argv.find(a => a.endsWith('.pdf') && !a.includes('node_modules'));

// ─── Vendor colour palette ─────────────────────────────────────────────────────
// argb = 'FF' + hex RGB  (ExcelJS uses ARGB)
const VENDOR_COLORS = {
  'Amazon.com':                     'FFDCE8FF',  // soft blue
  'Walmart':                        'FFFFF3CD',  // soft yellow
  'Temu':                           'FFFFEBD6',  // soft orange
  'TEMU':                           'FFFFEBD6',
  'Costco':                         'FFFFE0E0',  // soft red
  'Lainy Home':                     'FFF3E0FF',  // soft purple
  'Rivoli Parfums':                 'FFFFCCE8',  // soft pink
  'Daspar':                         'FFE0FFE8',  // soft green
  'Deluxe Import Trading':          'FFE0FFF8',  // soft teal
  'MYS Wholesale Inc':              'FFE0E8FF',  // soft indigo
  'Apparel Candy':                  'FFFFE8F0',  // soft rose
  'Funteze':                        'FFEAFFD6',  // soft lime
  'Trio Trading':                   'FFD6F5FF',  // soft sky
  'Lovery':                         'FFEDE0FF',  // soft lavender
  'So Fresh Perfumes':              'FFD6FFEE',  // soft mint
  'Alibaba':                        'FFFFE8CC',  // soft amber
  'AliExpress':                     'FFFFE8CC',
  'Mia Fan Alibaba.com Singapore E-Commerce Private Limited': 'FFFFE8CC',
  'Shenzhen Boln Electronic Technology Co., Ltd.':           'FFF0F4FF',  // steel blue
  'Pingyang County Xuanbang Non woven Bag Business Department': 'FFF0F4FF',
  'Costco':                         'FFFFD6D6',
  'Target':                         'FFFFEBE8',  // target red-ish
  'eBay':                           'FFFFFADE',  // eBay yellow
  'Etsy':                           'FFFFE4D6',  // etsy orange
  'Shein':                          'FFFFE0F5',  // shein pink
};
const DEFAULT_COLOR   = 'FFFAFAFA';  // near-white for unknown
const HEADER_FILL     = 'FF1A1A2E';  // dark navy
const HEADER_FONT     = 'FFD4AF37';  // gold
const TOTAL_FILL      = 'FF2D2D2D';  // dark grey
const TOTAL_FONT      = 'FFFFD700';  // gold

function vendorColor(vendor) {
  if (!vendor) return DEFAULT_COLOR;
  for (const [key, color] of Object.entries(VENDOR_COLORS)) {
    if (vendor.toLowerCase().includes(key.toLowerCase())) return color;
  }
  return DEFAULT_COLOR;
}

function applyRowStyle(row, fillArgb, bold = false, fontArgb = 'FF111111') {
  row.eachCell({ includeEmpty: true }, cell => {
    cell.fill   = { type: 'pattern', pattern: 'solid', fgColor: { argb: fillArgb } };
    cell.font   = { bold, color: { argb: fontArgb }, name: 'Calibri', size: 11 };
    cell.border = {
      top:    { style: 'thin', color: { argb: 'FFD0D0D0' } },
      bottom: { style: 'thin', color: { argb: 'FFD0D0D0' } },
      left:   { style: 'thin', color: { argb: 'FFD0D0D0' } },
      right:  { style: 'thin', color: { argb: 'FFD0D0D0' } },
    };
  });
}

// ─── PDF → OCR text ───────────────────────────────────────────────────────────
async function extractText(pdfPath) {
  const pages  = await pdfToImg(pdfPath, { scale: 2.5 });
  const worker = await Tesseract.createWorker('eng', 1, { logger: () => {} });
  let fullText = '';
  for await (const pageImg of pages) {
    const { data: { text } } = await worker.recognize(pageImg);
    fullText += text + '\n';
  }
  await worker.terminate();
  return fullText;
}

// ─── Parse extracted text ─────────────────────────────────────────────────────
function parseReceipt(text, filename) {
  const vendorHints = [
    [/walmart/i,                     'Walmart'],
    [/amazon\.com|amazon/i,          'Amazon.com'],
    [/\btemu\b/i,                    'Temu'],
    [/costco/i,                      'Costco'],
    [/target/i,                      'Target'],
    [/lainy\s*home/i,                'Lainy Home'],
    [/rivoli/i,                      'Rivoli Parfums'],
    [/daspar/i,                      'Daspar'],
    [/deluxe\s*import/i,             'Deluxe Import Trading'],
    [/funteze/i,                     'Funteze'],
    [/apparel\s*candy/i,             'Apparel Candy'],
    [/trio\s*trading/i,              'Trio Trading'],
    [/lovery/i,                      'Lovery'],
    [/mys\s*wholesale/i,             'MYS Wholesale Inc'],
    [/so\s*fr[ea]sh\s*perfumes?/i,   'So Fresh Perfumes'],
    [/alibaba/i,                     'Alibaba'],
    [/aliexpress/i,                  'AliExpress'],
    [/shein/i,                       'Shein'],
    [/ebay/i,                        'eBay'],
    [/etsy/i,                        'Etsy'],
  ];

  let vendor = 'Unknown';
  for (const [re, name] of vendorHints) {
    if (re.test(text)) { vendor = name; break; }
  }
  if (vendor === 'Unknown') {
    const m = path.basename(filename, '.pdf').match(/^[A-Z0-9]+ - (.+?) - \w+ \d{4}$/i);
    if (m) vendor = m[1].trim();
  }

  // Date
  let date = null;
  for (const re of [
    /Order\s+(?:placed|date)[:\s]+(\w+ \d{1,2},?\s*\d{4})/i,
    /(?:placed|date)[:\s]+(\w+ \d{1,2},?\s*\d{4})/i,
    /(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/,
    /(\w+ \d{1,2},?\s*\d{4})/,
  ]) {
    const m = text.match(re);
    if (m) {
      const d = new Date(m[1]);
      if (!isNaN(d)) { date = d.toISOString().split('T')[0]; break; }
    }
  }

  function findAmount(patterns) {
    for (const re of patterns) {
      const hits = [...text.matchAll(re)];
      if (hits.length) {
        const val = parseFloat(hits[hits.length - 1][1].replace(/,/g, ''));
        if (!isNaN(val) && val > 0) return val;
      }
    }
    return 0;
  }

  const total    = findAmount([
    /(?:grand\s+total|order\s+total|total\s+charged|amount\s+charged|amount\s+billed|you\s+paid|total\s+payment)[^\n$]*\$\s?([\d,]+\.?\d*)/gi,
    /^total[:\s]+\$\s?([\d,]+\.?\d*)/gim,
  ]) || findAmount([/\$\s?([\d,]+\.\d{2})/g]);

  const subtotal = findAmount([/(?:item[s]?\s+subtotal|subtotal)[^\n$]*\$\s?([\d,]+\.?\d*)/gi]);
  const tax      = findAmount([/(?:estimated\s+tax|sales\s+tax|tax\s+collected|tax)[^\n$]*\$\s?([\d,]+\.?\d*)/gi]);
  const discount = findAmount([/(?:savings?|discount|coupon|promotion|promo)[^\n$:\-]*[-]?\$\s?([\d,]+\.?\d*)/gi]);

  const skip  = /^(order|date|ship|billing|payment|address|total|subtotal|tax|savings|item|qty|price|receipt|invoice|thank|dear|hello|your|we |from|to:|po |ref|#)/i;
  const items = text.split('\n')
    .map(l => l.trim())
    .filter(l => l.length > 8 && !skip.test(l) && !/^\$|^\d+$|^[-=*]+$/.test(l))
    .slice(0, 3).join('; ').substring(0, 120);

  return { vendor, date, subtotal, tax, discount, total, items };
}

// ─── Get already-recorded source files ───────────────────────────────────────
async function getRecordedFiles() {
  const wb = new ExcelJS.Workbook();
  await wb.xlsx.readFile(XLSX_FILE);
  const ws   = wb.worksheets[0];
  const seen = new Set();
  ws.eachRow((row, i) => {
    if (i > 1) { const v = row.getCell(12).value; if (v) seen.add(path.basename(String(v))); }
  });
  return seen;
}

// ─── Write spreadsheet with full colour coding ───────────────────────────────
async function writeXlsx(newRows) {
  const wb = new ExcelJS.Workbook();
  await wb.xlsx.readFile(XLSX_FILE);
  const ws = wb.worksheets[0];

  // Append new data rows before TOTAL row
  let totalRowNum = null;
  ws.eachRow((row, i) => {
    if (String(row.getCell(1).value) === 'TOTAL') totalRowNum = i;
  });

  const insertAt = totalRowNum ?? ws.rowCount + 1;

  // Insert new rows (ExcelJS spliceRows: position, deleteCount, ...rows)
  if (newRows.length > 0) {
    ws.spliceRows(insertAt, 0, ...newRows.map(r => [
      r.date || '', r.vendor, 'Inventory', 'USD',
      r.subtotal || '', r.tax || '', r.discount || '', r.total || '',
      '', r.items || '', '', r.sourceFile,
    ]));
  }

  // ── Re-colour every row ──
  ws.eachRow((row, i) => {
    if (i === 1) {
      // Header
      applyRowStyle(row, HEADER_FILL, true, HEADER_FONT);
      row.height = 22;
    } else {
      const vendor   = String(row.getCell(2).value || '');
      const isTotal  = String(row.getCell(1).value) === 'TOTAL';
      if (isTotal) {
        applyRowStyle(row, TOTAL_FILL, true, TOTAL_FONT);
      } else {
        applyRowStyle(row, vendorColor(vendor));
      }
    }
  });

  // ── Column widths ──
  const widths = [14, 38, 12, 10, 11, 8, 10, 11, 16, 50, 20, 36];
  widths.forEach((w, i) => { ws.getColumn(i + 1).width = w; });

  // Freeze header row
  ws.views = [{ state: 'frozen', ySplit: 1 }];

  await wb.xlsx.writeFile(XLSX_FILE);
}

// ─── Main ─────────────────────────────────────────────────────────────────────
async function main() {
  const recorded = await getRecordedFiles();

  let toProcess = [];
  if (SINGLE) {
    const full = path.isAbsolute(SINGLE) ? SINGLE : path.join(RECEIPTS, path.basename(SINGLE));
    if (!fs.existsSync(full)) { console.error('File not found:', full); process.exit(1); }
    toProcess = [full];
  } else {
    toProcess = fs.readdirSync(RECEIPTS)
      .filter(f => f.toLowerCase().endsWith('.pdf') && !recorded.has(f))
      .map(f => path.join(RECEIPTS, f));
  }

  const RECOLOR = process.argv.includes('--recolor');

  if (!toProcess.length && !RECOLOR) { console.log('\n✓ No new receipts to process.\n'); return; }
  if (RECOLOR && !toProcess.length) {
    if (!DRY_RUN) { await writeXlsx([]); console.log('\n✅ Spreadsheet recoloured.\n'); }
    return;
  }

  console.log(`\n📄 Processing ${toProcess.length} receipt(s)...\n`);

  const newRows = [];

  for (const filePath of toProcess) {
    const filename = path.basename(filePath);
    process.stdout.write(`  ⏳ ${filename} ...\n`);
    try {
      const text   = await extractText(filePath);
      const parsed = parseReceipt(text, filename);

      console.log(`  ✓ Vendor:    ${parsed.vendor}`);
      console.log(`    Date:      ${parsed.date || '(not found)'}`);
      console.log(`    Subtotal:  $${parsed.subtotal || 0}   Tax: $${parsed.tax || 0}   Discount: $${parsed.discount || 0}`);
      console.log(`    TOTAL:     $${parsed.total || 0}`);
      console.log(`    Items:     ${parsed.items || '(not found)'}`);
      console.log();

      newRows.push({ ...parsed, sourceFile: filename, _filePath: filePath });
    } catch (e) {
      console.log(`  ✗ Failed: ${e.message}\n`);
    }
  }

  if (!newRows.length) { console.log('No rows to add.\n'); return; }

  if (DRY_RUN) {
    console.log('──── DRY RUN: no changes made ────\n');
    return;
  }

  // Write spreadsheet
  await writeXlsx(newRows);
  console.log(`✅ Added ${newRows.length} row(s) to American_Select_Expenses.xlsx`);

  // Move PDFs to processed/
  if (!fs.existsSync(PROCESSED)) fs.mkdirSync(PROCESSED, { recursive: true });
  for (const row of newRows) {
    const dest = path.join(PROCESSED, path.basename(row._filePath));
    fs.renameSync(row._filePath, dest);
    console.log(`  📁 Moved → processed/${path.basename(row._filePath)}`);
  }

  console.log('\n✅ Done. Open American_Select_Expenses.xlsx to review.\n');
}

main().catch(e => { console.error(e); process.exit(1); });
