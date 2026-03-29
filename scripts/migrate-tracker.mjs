/**
 * One-time migration: Cameroon_Shop_Purchase_Tracker.xlsx → American_Select_Expenses.xlsx
 * Run once: node scripts/migrate-tracker.mjs
 */

import pkg from 'xlsx';
const { readFile, writeFile, utils } = pkg;
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname  = path.dirname(fileURLToPath(import.meta.url));
const ROOT       = path.resolve(__dirname, '..');
const SOURCE     = path.join(ROOT, 'Cameroon_Shop_Purchase_Tracker.xlsx');
const OUTPUT     = path.join(ROOT, 'American_Select_Expenses.xlsx');

// ---------- helpers ----------
function excelDateToString(val) {
  if (!val) return '';
  if (typeof val === 'string') {
    // already a date string like "01/01/2026"
    const parts = val.split('/');
    if (parts.length === 3) return `${parts[2]}-${parts[0].padStart(2,'0')}-${parts[1].padStart(2,'0')}`;
    return val;
  }
  if (typeof val === 'number') {
    // Excel serial number → JS Date (Excel epoch: Dec 30, 1899)
    const date = new Date((val - 25569) * 86400 * 1000);
    return date.toISOString().slice(0, 10);
  }
  return '';
}

function cleanNum(val) {
  if (val === null || val === undefined || val === '') return 0;
  if (typeof val === 'number') return val;
  // strip $, spaces, commas
  const n = parseFloat(String(val).replace(/[$,\s]/g, ''));
  return isNaN(n) ? 0 : n;
}

function cleanStr(val) {
  if (!val) return '';
  return String(val).trim();
}

// ---------- read source ----------
console.log('\n📂  Reading: Cameroon_Shop_Purchase_Tracker.xlsx');
const wb  = readFile(SOURCE);
const ws  = wb.Sheets['Americanselect shop tracker'];
const raw = utils.sheet_to_json(ws, { header: 1 });

// Header is at row index 5
// Columns: Date(0) | Item Name(1) | Category(2) | Supplier(3) | Qty(4) | Unit Cost(5) | Shipping/Fees(6) | Total Cost(7) | Unit Sell Price(8) | Total Revenue(9) | Profit(10) | Status(11)

const DATA_START = 6;

// ---------- group rows into orders ----------
// Strategy: carry forward Date & Supplier from the last non-null values.
// A "subtotal" row has no Item Name and has Shipping or Total Cost filled in.

const orders = [];   // { date, supplier, category, shipping, total, items[] }
let currentOrder = null;

for (let i = DATA_START; i < raw.length; i++) {
  const row = raw[i];
  if (!row || row.length === 0) continue;

  const date      = excelDateToString(row[0]);
  const itemName  = cleanStr(row[1]);
  const category  = cleanStr(row[2]);
  const supplier  = cleanStr(row[3]);
  const qty       = cleanNum(row[4]);
  const unitCost  = cleanNum(row[5]);
  const shipping  = cleanNum(row[6]);
  const totalCost = cleanNum(row[7]);

  // detect subtotal / summary row (no item name, has shipping or total)
  const isSummaryRow = !itemName && (shipping > 0 || totalCost > 0);

  if (isSummaryRow && currentOrder) {
    if (shipping  > 0) currentOrder.shipping  = shipping;
    if (totalCost > 0) currentOrder.total      = totalCost;
    continue;
  }

  // new order: new date or new supplier
  const newDate     = date     || (currentOrder ? currentOrder.date     : '');
  const newSupplier = supplier || (currentOrder ? currentOrder.supplier : 'Unknown');
  const newCategory = category || (currentOrder ? currentOrder.category : 'Inventory');

  if (!currentOrder || (date && date !== currentOrder.date) || (supplier && supplier !== currentOrder.supplier)) {
    currentOrder = {
      date:     newDate,
      supplier: newSupplier,
      category: newCategory || 'Inventory',
      shipping: 0,
      total:    0,
      items:    []
    };
    orders.push(currentOrder);
  } else {
    // inherit from current
    if (!currentOrder.date     && newDate)     currentOrder.date     = newDate;
    if (!currentOrder.supplier && newSupplier) currentOrder.supplier = newSupplier;
    if (!currentOrder.category && newCategory) currentOrder.category = newCategory;
  }

  if (itemName) {
    currentOrder.items.push({
      description: itemName,
      quantity:    qty       || 1,
      unit_price:  unitCost  || 0,
      total:       totalCost || (qty && unitCost ? qty * unitCost : 0)
    });
    // accumulate total if not already set by a summary row
    if (totalCost > 0) currentOrder.total += totalCost;
  }
}

// ---------- build new workbook ----------
console.log(`  Found ${orders.length} orders / purchase groups`);
console.log(`  Total line items: ${orders.reduce((s, o) => s + o.items.length, 0)}`);

const newWb = utils.book_new();

// ---- Expenses sheet ----
const expRows = [
  ['Date','Vendor','Category','Currency','Subtotal','Shipping & Fees','TOTAL',
   'Payment Method','Items Count','Notes','Source File']
];

// ---- Line Items sheet ----
const itemRows = [
  ['Date','Vendor','Category','Item Description','Qty','Unit Price','Line Total','Source File']
];

for (const order of orders) {
  const itemsTotal = order.items.reduce((s, it) => s + it.total, 0);
  const subtotal   = itemsTotal > 0 ? itemsTotal : order.total;
  const grandTotal = order.total > 0 ? order.total : subtotal + order.shipping;

  expRows.push([
    order.date      || '',
    order.supplier  || 'Unknown',
    order.category  || 'Inventory',
    'USD',
    subtotal,
    order.shipping,
    grandTotal,
    'Unknown',
    order.items.length,
    '',
    'Cameroon_Shop_Purchase_Tracker.xlsx'
  ]);

  for (const item of order.items) {
    itemRows.push([
      order.date     || '',
      order.supplier || 'Unknown',
      order.category || 'Inventory',
      item.description,
      item.quantity,
      item.unit_price,
      item.total,
      'Cameroon_Shop_Purchase_Tracker.xlsx'
    ]);
  }
}

const expWS = utils.aoa_to_sheet(expRows);
expWS['!cols'] = [
  {wch:12},{wch:28},{wch:22},{wch:10},{wch:10},{wch:16},{wch:10},
  {wch:16},{wch:12},{wch:30},{wch:36}
];
utils.book_append_sheet(newWb, expWS, 'Expenses');

const itemsWS = utils.aoa_to_sheet(itemRows);
itemsWS['!cols'] = [
  {wch:12},{wch:28},{wch:22},{wch:50},{wch:6},{wch:12},{wch:12},{wch:36}
];
utils.book_append_sheet(newWb, itemsWS, 'Line Items');

// ---------- save ----------
writeFile(newWb, OUTPUT);

console.log(`\n✅  Migration complete!`);
console.log(`   Orders migrated:     ${orders.length}`);
console.log(`   Line items migrated: ${orders.reduce((s, o) => s + o.items.length, 0)}`);
console.log(`   Saved to: American_Select_Expenses.xlsx`);
console.log(`\n   Future receipts scanned will be added to this same file.\n`);
