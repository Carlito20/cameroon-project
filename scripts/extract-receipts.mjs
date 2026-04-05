/**
 * extract-receipts.mjs
 * Scans image-based PDFs in receipts/ folder, runs OCR, parses vendor/date/amounts,
 * and adds new rows to American_Select_Expenses.xlsx.
 *
 * Usage:
 *   node scripts/extract-receipts.mjs              → process all new PDFs in receipts/
 *   node scripts/extract-receipts.mjs --dry-run    → show parsed data, don't write
 *   node scripts/extract-receipts.mjs "Order Details.pdf"  → single file
 */

import { pdf as pdfToImg } from 'pdf-to-img';
import Tesseract from 'tesseract.js';
import { createRequire } from 'module';
import path from 'path';
import fs from 'fs';
import { fileURLToPath } from 'url';

const require  = createRequire(import.meta.url);
const XLSX     = require('xlsx');

const __dirname  = path.dirname(fileURLToPath(import.meta.url));
const ROOT       = path.join(__dirname, '..');
const RECEIPTS   = path.join(ROOT, 'receipts');
const XLSX_FILE  = path.join(ROOT, 'American_Select_Expenses.xlsx');

const DRY_RUN = process.argv.includes('--dry-run');
const SINGLE  = process.argv.find(a => a.endsWith('.pdf') && !a.includes('node_modules'));

// ─── PDF → OCR text ───────────────────────────────────────────────────────────
async function extractText(pdfPath) {
  const pages = await pdfToImg(pdfPath, { scale: 2.5 });
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
  // ── Vendor ──
  let vendor = 'Unknown';
  const vendorHints = [
    [/walmart\.com|walmart/i,        'Walmart'],
    [/amazon\.com|amazon/i,          'Amazon.com'],
    [/\btemu\b/i,                    'Temu'],
    [/costco/i,                      'Costco'],
    [/target\.com|target/i,          'Target'],
    [/lainy\s*home/i,                'Lainy Home'],
    [/rivoli/i,                      'Rivoli Parfums'],
    [/daspar/i,                      'Daspar'],
    [/deluxe\s*import/i,             'Deluxe Import Trading'],
    [/funteze/i,                     'Funteze'],
    [/apparel\s*candy/i,             'Apparel Candy'],
    [/trio\s*trading/i,              'Trio Trading'],
    [/lovery/i,                      'Lovery'],
    [/so\s*fr[ea]sh\s*perfumes?/i,   'So Fresh Perfumes'],
    [/mys\s*wholesale/i,             'MYS Wholesale Inc'],
    [/alibaba/i,                     'Alibaba'],
    [/aliexpress/i,                  'AliExpress'],
    [/shein/i,                       'Shein'],
    [/ebay/i,                        'eBay'],
    [/shopify/i,                     'Shopify'],
    [/etsy/i,                        'Etsy'],
  ];
  for (const [re, name] of vendorHints) {
    if (re.test(text)) { vendor = name; break; }
  }
  // Fallback: extract from filename pattern "CODE - Vendor Name - Mon YYYY.pdf"
  if (vendor === 'Unknown') {
    const m = path.basename(filename, '.pdf').match(/^[A-Z0-9]+ - (.+?) - \w+ \d{4}$/i);
    if (m) vendor = m[1].trim();
  }

  // ── Date ──
  let date = null;
  const datePatterns = [
    /Order\s+(?:placed|date)[:\s]+(\w+ \d{1,2},?\s*\d{4})/i,
    /(?:placed|date)[:\s]+(\w+ \d{1,2},?\s*\d{4})/i,
    /(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/,
    /(\w+ \d{1,2},?\s*\d{4})/,
  ];
  for (const re of datePatterns) {
    const m = text.match(re);
    if (m) {
      const parsed = new Date(m[1]);
      if (!isNaN(parsed.getTime())) {
        date = parsed.toISOString().split('T')[0];
        break;
      }
    }
  }

  // ── Extract labelled dollar amount ──
  function findAmount(patterns) {
    for (const re of patterns) {
      const matches = [...text.matchAll(re)];
      if (matches.length) {
        const val = parseFloat(matches[matches.length - 1][1].replace(/,/g, ''));
        if (!isNaN(val) && val > 0) return val;
      }
    }
    return 0;
  }

  const total = findAmount([
    /(?:grand\s+total|order\s+total|total\s+charged|amount\s+charged|amount\s+billed|you\s+paid|total\s+payment)[^\n$]*\$\s?([\d,]+\.?\d*)/gi,
    /^total[:\s]+\$\s?([\d,]+\.?\d*)/gim,
  ]) || findAmount([/\$\s?([\d,]+\.\d{2})/g]); // fallback: largest dollar amount

  const subtotal = findAmount([
    /(?:item[s]?\s+subtotal|subtotal)[^\n$]*\$\s?([\d,]+\.?\d*)/gi,
  ]);

  const tax = findAmount([
    /(?:estimated\s+tax|sales\s+tax|tax\s+collected|tax)[^\n$]*\$\s?([\d,]+\.?\d*)/gi,
  ]);

  const discount = findAmount([
    /(?:savings?|discount|coupon|promotion|promo)[^\n$:\-]*[-]?\$\s?([\d,]+\.?\d*)/gi,
  ]);

  // ── Items (first meaningful content lines) ──
  const skip = /^(order|date|ship|billing|payment|address|total|subtotal|tax|savings|item|qty|price|receipt|invoice|thank|dear|hello|your|we |from|to:|po |ref|#)/i;
  const items = text.split('\n')
    .map(l => l.trim())
    .filter(l => l.length > 8 && !skip.test(l) && !/^\$|^\d+$|^[-=*]+$/.test(l))
    .slice(0, 3)
    .join('; ')
    .substring(0, 120);

  return { vendor, date, subtotal, tax, discount, total, items };
}

// ─── Get already-recorded source files ───────────────────────────────────────
function getRecordedFiles() {
  const wb   = XLSX.readFile(XLSX_FILE);
  const ws   = wb.Sheets[wb.SheetNames[0]];
  const data = XLSX.utils.sheet_to_json(ws, { header: 1 });
  return new Set(data.slice(1).map(r => r[11]).filter(Boolean).map(f => path.basename(String(f))));
}

// ─── Append rows to spreadsheet ───────────────────────────────────────────────
function appendToXlsx(newRows) {
  const wb     = XLSX.readFile(XLSX_FILE);
  const wsName = wb.SheetNames[0];
  const ws     = wb.Sheets[wsName];
  const data   = XLSX.utils.sheet_to_json(ws, { header: 1 });

  const totalIdx = data.findIndex(r => r[0] === 'TOTAL');
  const insertAt = totalIdx >= 0 ? totalIdx : data.length;

  data.splice(insertAt, 0, ...newRows.map(r => [
    r.date || '',
    r.vendor,
    'Inventory',
    'USD',
    r.subtotal || '',
    r.tax      || '',
    r.discount || '',
    r.total    || '',
    '',        // Payment Method (fill manually)
    r.items    || '',
    '',        // Notes
    r.sourceFile,
  ]));

  const newWs = XLSX.utils.aoa_to_sheet(data);
  wb.Sheets[wsName] = newWs;
  XLSX.writeFile(wb, XLSX_FILE);
}

// ─── Main ─────────────────────────────────────────────────────────────────────
async function main() {
  const recorded = getRecordedFiles();

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

  if (!toProcess.length) {
    console.log('\n✓ No new receipts to process.\n');
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

      newRows.push({ ...parsed, sourceFile: filename });
    } catch (e) {
      console.log(`  ✗ Failed: ${e.message}\n`);
    }
  }

  if (!newRows.length) { console.log('No rows to add.\n'); return; }

  if (DRY_RUN) {
    console.log('──── DRY RUN: spreadsheet not modified ────\n');
    return;
  }

  appendToXlsx(newRows);
  console.log(`✅ Added ${newRows.length} row(s) to American_Select_Expenses.xlsx`);
  console.log('   Tip: review amounts, then move PDFs to receipts/processed/\n');
}

main().catch(e => { console.error(e); process.exit(1); });
