import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { createRequire } from 'module';
const require = createRequire(import.meta.url);
const pdfParse = require('pdf-parse');

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const folders = [
  path.join(__dirname, '../receipts'),
  path.join(__dirname, '../receipts/processed'),
];

// Match dollar amounts like $1,234.56 or $12.50
const dollarRegex = /\$\s?([\d,]+\.?\d*)/g;
// Match "Total" lines to prioritise
const totalLineRegex = /(?:order\s+total|grand\s+total|total\s+charged|amount\s+charged|amount\s+paid|subtotal)[^\n$]*\$([\d,]+\.?\d*)/gi;

let grandTotal = 0;
let fileCount = 0;
const results = [];

async function processFile(filePath) {
  const filename = path.basename(filePath);
  try {
    const buffer = fs.readFileSync(filePath);
    const data = await pdfParse(buffer);
    const text = data.text;

    // Try to find a labelled total first
    let fileTotal = null;
    const totalMatch = [...text.matchAll(totalLineRegex)];
    if (totalMatch.length > 0) {
      // Take the last labelled total (usually the grand total)
      const raw = totalMatch[totalMatch.length - 1][1].replace(/,/g, '');
      fileTotal = parseFloat(raw);
    }

    // Fall back: take the largest dollar amount on a "Total" line or just largest overall
    if (fileTotal === null) {
      const allAmounts = [...text.matchAll(dollarRegex)]
        .map(m => parseFloat(m[1].replace(/,/g, '')))
        .filter(n => !isNaN(n) && n > 0);
      if (allAmounts.length > 0) {
        fileTotal = Math.max(...allAmounts);
      }
    }

    if (fileTotal !== null && fileTotal > 0) {
      grandTotal += fileTotal;
      fileCount++;
      results.push({ filename, total: fileTotal });
    } else {
      results.push({ filename, total: null });
    }
  } catch (e) {
    results.push({ filename, total: null, error: e.message });
  }
}

for (const folder of folders) {
  if (!fs.existsSync(folder)) continue;
  const files = fs.readdirSync(folder).filter(f => f.toLowerCase().endsWith('.pdf'));
  for (const file of files) {
    await processFile(path.join(folder, file));
  }
}

console.log('\n📄 RECEIPT TOTALS\n' + '─'.repeat(60));
for (const r of results) {
  if (r.total !== null) {
    console.log(`  ✓ ${r.filename.padEnd(48)} $${r.total.toFixed(2)}`);
  } else {
    console.log(`  ? ${r.filename.padEnd(48)} (could not extract)`);
  }
}
console.log('─'.repeat(60));
console.log(`  ${fileCount} receipts parsed`);
console.log(`  GRAND TOTAL: $${grandTotal.toFixed(2)}`);
console.log('─'.repeat(60) + '\n');
