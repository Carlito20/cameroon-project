/**
 * Receipt Scanner — American Select Business Expense Tracker
 *
 * Usage:
 *   node scripts/scan-receipts.mjs                  ← scan all images in receipts/
 *   node scripts/scan-receipts.mjs receipt.jpg       ← scan a specific file
 *
 * Requires: ANTHROPIC_API_KEY environment variable
 * Output:   American_Select_Expenses.xlsx (created or updated automatically)
 */

import Anthropic from '@anthropic-ai/sdk';
import XLSX from 'xlsx';
import ExcelJS from 'exceljs';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const PROJECT_ROOT = path.resolve(__dirname, '..');

// Auto-load .env file
const envPath = path.join(PROJECT_ROOT, '.env');
if (fs.existsSync(envPath)) {
  const envLines = fs.readFileSync(envPath, 'utf8').split('\n');
  for (const line of envLines) {
    const trimmed = line.trim();
    if (!trimmed || trimmed.startsWith('#')) continue;
    const [key, ...rest] = trimmed.split('=');
    if (key && rest.length && !process.env[key.trim()]) {
      process.env[key.trim()] = rest.join('=').trim();
    }
  }
}

const RECEIPTS_DIR    = path.join(PROJECT_ROOT, 'receipts');
const PROCESSED_DIR   = path.join(PROJECT_ROOT, 'receipts', 'processed');
const OUTPUT_XLSX     = path.join(PROJECT_ROOT, 'American_Select_Expenses.xlsx');

const SUPPORTED_EXTS  = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.pdf'];

// ---------- Claude client ----------
const client = new Anthropic({ apiKey: process.env.ANTHROPIC_API_KEY });

// ---------- helpers ----------
function getMimeType(filePath) {
  const ext = path.extname(filePath).toLowerCase();
  const map = { '.jpg': 'image/jpeg', '.jpeg': 'image/jpeg', '.png': 'image/png',
                 '.gif': 'image/gif', '.webp': 'image/webp' };
  return map[ext] || 'image/jpeg';
}

function todayStr() {
  return new Date().toISOString().slice(0, 10);
}

// ---------- extract data from one receipt ----------
async function extractReceiptData(imagePath) {
  const fileData = fs.readFileSync(imagePath);
  const base64   = fileData.toString('base64');
  const ext      = path.extname(imagePath).toLowerCase();
  const filename = path.basename(imagePath);
  const isPDF    = ext === '.pdf';

  console.log(`  → Sending to Claude: ${filename} (${isPDF ? 'PDF' : 'image'})`);

  // PDFs use the "document" content block; images use "image"
  const fileBlock = isPDF
    ? { type: 'document', source: { type: 'base64', media_type: 'application/pdf', data: base64 } }
    : { type: 'image',    source: { type: 'base64', media_type: getMimeType(imagePath), data: base64 } };

  const response = await client.messages.create({
    model: 'claude-haiku-4-5-20251001',
    max_tokens: 2048,
    messages: [{
      role: 'user',
      content: [
        fileBlock,
        {
          type: 'text',
          text: `You are a receipt data extractor for a small import business called American Select.

Analyze this receipt image and extract ALL of the following information.
Respond ONLY with valid JSON — no explanation, no markdown fences.

{
  "date": "YYYY-MM-DD or empty string if unclear",
  "vendor": "store or supplier name",
  "vendor_address": "address if visible, else empty string",
  "items": [
    { "description": "item name", "quantity": 1, "unit_price": 0.00, "total": 0.00 }
  ],
  "subtotal": 0.00,
  "tax": 0.00,
  "discount": 0.00,
  "total": 0.00,
  "currency": "USD or the currency symbol on receipt",
  "payment_method": "Cash / Credit / Debit / Check / Unknown",
  "category": "one of: Inventory / Shipping & Freight / Office Supplies / Marketing / Utilities / Meals & Entertainment / Travel / Other",
  "notes": "any other useful info (receipt number, order number, etc.)"
}

Rules:
- Use numbers (not strings) for all prices.
- If a field is not visible, use empty string or 0.
- For date, use today's date ${todayStr()} only if you truly cannot read it.
- Pick the most appropriate category for this business.`
        }
      ]
    }]
  });

  const rawText = response.content.find(b => b.type === 'text')?.text || '{}';

  // strip any accidental markdown fences
  const jsonText = rawText.replace(/^```json?\s*/i, '').replace(/```\s*$/i, '').trim();

  try {
    return JSON.parse(jsonText);
  } catch {
    console.warn(`  ⚠ Could not parse JSON for ${filename}. Raw response saved in notes.`);
    return {
      date: todayStr(), vendor: filename, items: [],
      subtotal: 0, tax: 0, discount: 0, total: 0,
      currency: 'USD', payment_method: 'Unknown',
      category: 'Other', notes: rawText.slice(0, 300)
    };
  }
}

// ---------- load or create workbook ----------
function loadOrCreateWorkbook() {
  if (fs.existsSync(OUTPUT_XLSX)) {
    console.log(`  Loading existing: ${path.basename(OUTPUT_XLSX)}`);
    return XLSX.readFile(OUTPUT_XLSX);
  }

  console.log(`  Creating new: ${path.basename(OUTPUT_XLSX)}`);
  const wb = XLSX.utils.book_new();

  // Summary sheet
  const summaryHeaders = [
    ['Date', 'Vendor', 'Category', 'Currency', 'Subtotal', 'Tax', 'Discount',
     'TOTAL', 'Payment Method', 'Items Purchased', 'Notes', 'Source File']
  ];
  const summaryWS = XLSX.utils.aoa_to_sheet(summaryHeaders);
  summaryWS['!cols'] = [
    {wch:12},{wch:28},{wch:22},{wch:10},{wch:10},{wch:8},{wch:10},
    {wch:10},{wch:16},{wch:60},{wch:40},{wch:30}
  ];
  XLSX.utils.book_append_sheet(wb, summaryWS, 'Expenses');

  // Line items sheet
  const itemsHeaders = [
    ['Date', 'Vendor', 'Category', 'Item Description', 'Qty', 'Unit Price', 'Line Total', 'Source File']
  ];
  const itemsWS = XLSX.utils.aoa_to_sheet(itemsHeaders);
  itemsWS['!cols'] = [
    {wch:12},{wch:28},{wch:22},{wch:40},{wch:6},{wch:12},{wch:12},{wch:30}
  ];
  XLSX.utils.book_append_sheet(wb, itemsWS, 'Line Items');

  return wb;
}

// ---------- append extracted data to workbook ----------
function appendToWorkbook(wb, data, sourceFile) {
  const date     = data.date     || todayStr();
  const vendor   = data.vendor   || 'Unknown';
  const category = data.category || 'Other';

  // ---- Expenses sheet ----
  const expWS    = wb.Sheets['Expenses'];
  const expData  = XLSX.utils.sheet_to_json(expWS, { header: 1 });
  expData.push([
    date,
    vendor,
    category,
    data.currency        || 'USD',
    data.subtotal        || 0,
    data.tax             || 0,
    data.discount        || 0,
    data.total           || 0,
    data.payment_method  || 'Unknown',
    (data.items || []).map(i => i.description).filter(Boolean).join(', ') || '',
    data.notes           || '',
    sourceFile
  ]);
  const newExpWS = XLSX.utils.aoa_to_sheet(expData);
  newExpWS['!cols'] = expWS['!cols'];
  wb.Sheets['Expenses'] = newExpWS;

  // ---- Line Items sheet ----
  if (data.items && data.items.length > 0) {
    const itemsWS   = wb.Sheets['Line Items'];
    const itemsData = XLSX.utils.sheet_to_json(itemsWS, { header: 1 });
    for (const item of data.items) {
      itemsData.push([
        date,
        vendor,
        category,
        item.description || '',
        item.quantity    || 1,
        item.unit_price  || 0,
        item.total       || 0,
        sourceFile
      ]);
    }
    const newItemsWS = XLSX.utils.aoa_to_sheet(itemsData);
    newItemsWS['!cols'] = itemsWS['!cols'];
    wb.Sheets['Line Items'] = newItemsWS;
  }

  return wb;
}

// ---------- apply colors ----------
async function applyColors(filePath) {
  const colColors = [
    'FF4472C4','FFED7D31','FFA9D18E','FFFFC000','FF5B9BD5','FF70AD47',
    'FFFF0000','FF7030A0','FF00B0F0','FFFF00FF','FF00B050','FFBFBFBF'
  ];
  const tints = [
    'FFD9E1F2','FFFCE4D6','FFE2EFDA','FFFFF2CC','FFDEEAF1','FFE2EFDA',
    'FFFFD7D7','FFE8D5F5','FFDDEEFD','FFFFD7FF','FFD6F0E3','FFF2F2F2'
  ];
  const ewb = new ExcelJS.Workbook();
  await ewb.xlsx.readFile(filePath);
  for (const sheetName of ['Expenses', 'Line Items']) {
    const ws = ewb.getWorksheet(sheetName);
    if (!ws) continue;
    const colCount = sheetName === 'Expenses' ? 12 : 8;
    ws.eachRow((row, rowNumber) => {
      for (let c = 1; c <= colCount; c++) {
        const cell = row.getCell(c);
        const isTotal = row.getCell(1).value === 'TOTAL';
        if (isTotal) {
          cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF1F3864' } };
          cell.font = { bold: true, color: { argb: 'FFFFFFFF' }, size: 11 };
        } else if (rowNumber === 1) {
          cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: colColors[c - 1] } };
          cell.font = { bold: true, color: { argb: 'FFFFFFFF' }, size: 11 };
          cell.alignment = { vertical: 'middle', horizontal: 'center', wrapText: true };
        } else {
          cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: tints[c - 1] } };
          cell.font = { size: 10 };
          cell.alignment = { vertical: 'middle' };
        }
        cell.border = {
          top:    { style: 'thin', color: { argb: 'FFBFBFBF' } },
          left:   { style: 'thin', color: { argb: 'FFBFBFBF' } },
          bottom: { style: 'thin', color: { argb: 'FFBFBFBF' } },
          right:  { style: 'thin', color: { argb: 'FFBFBFBF' } },
        };
      }
    });
    ws.getRow(1).height = 30;
  }
  await ewb.xlsx.writeFile(filePath);
}

// ---------- main ----------
async function main() {
  if (!process.env.ANTHROPIC_API_KEY) {
    console.error('\n❌  ANTHROPIC_API_KEY is not set.');
    console.error('   Set it by running:  export ANTHROPIC_API_KEY=your-key-here\n');
    process.exit(1);
  }

  // determine which files to scan
  let filesToScan = [];

  const arg = process.argv[2];
  if (arg) {
    // specific file passed as argument
    const full = path.isAbsolute(arg) ? arg : path.resolve(process.cwd(), arg);
    if (!fs.existsSync(full)) {
      console.error(`❌  File not found: ${full}`);
      process.exit(1);
    }
    filesToScan = [full];
  } else {
    // scan receipts/ folder
    if (!fs.existsSync(RECEIPTS_DIR)) {
      console.error(`❌  receipts/ folder not found at: ${RECEIPTS_DIR}`);
      console.error('   Create it and drop your receipt photos inside, then run this script again.');
      process.exit(1);
    }
    filesToScan = fs.readdirSync(RECEIPTS_DIR)
      .filter(f => SUPPORTED_EXTS.includes(path.extname(f).toLowerCase()))
      .map(f => path.join(RECEIPTS_DIR, f));
  }

  if (filesToScan.length === 0) {
    console.log('\n📂  No receipt images found in receipts/');
    console.log('   Supported formats: JPG, JPEG, PNG, GIF, WEBP, PDF');
    console.log('   Drop your receipt photos in the receipts/ folder and run again.\n');
    process.exit(0);
  }

  console.log(`\n🧾  American Select — Receipt Scanner`);
  console.log(`   Found ${filesToScan.length} receipt(s) to process\n`);

  let wb = loadOrCreateWorkbook();
  let successCount = 0;
  let errorCount   = 0;

  for (const filePath of filesToScan) {
    const filename = path.basename(filePath);
    console.log(`📄  Processing: ${filename}`);

    try {
      const data = await extractReceiptData(filePath);

      console.log(`  ✅ Extracted:`);
      console.log(`     Date:     ${data.date || '(not found)'}`);
      console.log(`     Vendor:   ${data.vendor || '(not found)'}`);
      console.log(`     Total:    ${data.currency || '$'} ${data.total ?? 0}`);
      console.log(`     Category: ${data.category || 'Other'}`);
      console.log(`     Items:    ${(data.items || []).length}`);

      wb = appendToWorkbook(wb, data, filename);

      // move to processed/
      const dest = path.join(PROCESSED_DIR, filename);
      fs.renameSync(filePath, dest);
      console.log(`  📦 Moved to receipts/processed/${filename}\n`);

      successCount++;
    } catch (err) {
      console.error(`  ❌ Error processing ${filename}: ${err.message}\n`);
      errorCount++;
    }
  }

  // add totals row to Expenses sheet
  const expWS   = wb.Sheets['Expenses'];
  const expData = XLSX.utils.sheet_to_json(expWS, { header: 1 });
  if (expData.length > 1) {
    // remove any previous totals row
    if (expData[expData.length - 1][0] === 'TOTAL') expData.pop();
    const rows = expData.slice(1);
    const sum  = (col) => rows.reduce((acc, r) => acc + (parseFloat(r[col]) || 0), 0);
    expData.push([
      'TOTAL', '', '', '',
      +sum(4).toFixed(2),
      +sum(5).toFixed(2),
      +sum(6).toFixed(2),
      +sum(7).toFixed(2),
      '', rows.length, '', ''
    ]);
    const newWS = XLSX.utils.aoa_to_sheet(expData);
    newWS['!cols'] = expWS['!cols'];
    wb.Sheets['Expenses'] = newWS;
  }

  // save workbook
  XLSX.writeFile(wb, OUTPUT_XLSX);

  // apply colors
  await applyColors(OUTPUT_XLSX);

  console.log(`\n✅  Done!`);
  console.log(`   Processed:  ${successCount} receipt(s)`);
  if (errorCount > 0) console.log(`   Errors:     ${errorCount} receipt(s) — check output above`);
  console.log(`   Saved to:   ${path.basename(OUTPUT_XLSX)}`);
  console.log(`   Open the file to view your expense data.\n`);
}

main().catch(err => {
  console.error('\n❌  Fatal error:', err.message);
  process.exit(1);
});
