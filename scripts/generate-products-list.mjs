/**
 * Generates public/api/products-list.json from src/data/categories.ts
 * Used to seed product_stock table on first order per product.
 * Run automatically as part of npm run build.
 */

import { readFileSync, writeFileSync } from 'fs';
import { resolve, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const categoriesPath = resolve(__dirname, '../src/data/categories.ts');
const outputPath    = resolve(__dirname, '../public/api/products-list.json');

const colorNames = {
  '#ff69b4': 'Pink',   '#2c2c2c': 'Black',  '#d4af37': 'Gold',
  '#808080': 'Gray',   '#e74c3c': 'Red',     '#2980b9': 'Blue',
  '#800080': 'Purple', '#ffffff': 'White',   '#ff8c00': 'Orange',
  '#f5f5dc': 'Beige',  '#7b1fa2': 'Deep Purple', '#2e7d32': 'Green',
  '#1565c0': 'Blue',   '#c62828': 'Dark Red', '#4a5240': 'Army Green',
  '#1a237e': 'Indigo', '#4a148c': 'Deep Purple', '#b71c1c': 'Crimson',
  '#e65100': 'Dark Orange', '#33691e': 'Olive Green', '#00bcd4': 'Cyan',
  '#c0c0c0': 'Silver', '#008000': 'Green', '#00008b': 'Dark Blue',
  '#8b0000': 'Dark Red', '#ffd700': 'Yellow',
};
const getColorName = hex => colorNames[hex.toLowerCase()] || hex;

// Load existing products-list.json to preserve manually set per-color quantities
let existingMap = {};
try {
  const existing = JSON.parse(readFileSync(outputPath, 'utf-8'));
  for (const e of existing) existingMap[e.name] = { quantity: e.quantity, price: e.price };
} catch { /* file may not exist yet */ }

const content = readFileSync(categoriesPath, 'utf-8');
const lines   = content.split('\n');

const products = [];
let cur = null;
let colorsStr = '';
let inColors  = false;

for (const raw of lines) {
  const line = raw.trim();

  // colors: array (may span multiple lines)
  if (/^colors:\s*\[/.test(line)) {
    inColors  = true;
    colorsStr = line;
    if (line.includes(']')) { inColors = false; if (cur) cur.colorsRaw = colorsStr; }
    continue;
  }
  if (inColors) {
    colorsStr += line;
    if (line.includes(']')) { inColors = false; if (cur) cur.colorsRaw = colorsStr; }
    continue;
  }

  // name:
  const nm = line.match(/^name:\s*['"](.+)['"]/);
  if (nm) {
    if (cur?.name && cur.quantity !== undefined) products.push(cur);
    cur = { name: nm[1], quantity: undefined, price: undefined, colorsRaw: null };
    continue;
  }

  // quantity:
  const qm = line.match(/^quantity:\s*(\d+)/);
  if (qm && cur) { cur.quantity = parseInt(qm[1]); continue; }

  // price:
  const pm = line.match(/^price:\s*(\d+)/);
  if (pm && cur) { cur.price = parseInt(pm[1]); continue; }

  // end of product block
  if ((line === '},' || line === '}') && cur?.name && cur.quantity !== undefined) {
    products.push(cur);
    cur = null;
  }
}
if (cur?.name && cur.quantity !== undefined) products.push(cur);

// Parse hex colors from raw string
const parseColors = raw => raw ? (raw.match(/#[0-9a-fA-F]{6}/g) || []) : [];

// Build final list, deduplicated
const result = [];
const seen   = new Set();
// Use existing quantity if present (preserves manual per-color stock edits)
const add = (name, qty, price) => {
  if (!seen.has(name)) {
    seen.add(name);
    const ex = existingMap[name];
    result.push({
      name,
      quantity: ex?.quantity !== undefined ? ex.quantity : qty,
      price:    price ?? ex?.price ?? undefined,
    });
  }
};

for (const p of products) {
  if (!p.name || p.quantity == null) continue;
  const colors = parseColors(p.colorsRaw);

  if (colors.length > 1) {
    const perColor = Math.ceil(p.quantity / colors.length);
    for (const hex of colors) add(`${p.name} (${getColorName(hex)})`, perColor, p.price);
    add(p.name, p.quantity, p.price); // base entry too
  } else if (colors.length === 1) {
    add(p.name, p.quantity, p.price);
    add(`${p.name} (${getColorName(colors[0])})`, p.quantity, p.price);
  } else {
    add(p.name, p.quantity, p.price);
  }
}

writeFileSync(outputPath, JSON.stringify(result, null, 2));
console.log(`✓ products-list.json — ${result.length} entries`);
