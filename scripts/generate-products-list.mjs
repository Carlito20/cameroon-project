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
  '#ff69b4': 'Pink',        '#2c2c2c': 'Black',         '#d4af37': 'Gold',
  '#808080': 'Gray',        '#e74c3c': 'Red',            '#2980b9': 'Blue',
  '#800080': 'Purple',      '#ffffff': 'White',          '#ff8c00': 'Orange',
  '#f5f5dc': 'Beige',       '#7b1fa2': 'Deep Purple',    '#2e7d32': 'Green',
  '#1565c0': 'Blue',        '#c62828': 'Dark Red',       '#4a5240': 'Army Green',
  '#1a237e': 'Indigo',      '#4a148c': 'Deep Purple',    '#b71c1c': 'Crimson',
  '#e65100': 'Dark Orange', '#33691e': 'Olive Green',    '#00bcd4': 'Cyan',
  '#c0c0c0': 'Silver',      '#008000': 'Green',          '#00008b': 'Dark Blue',
  '#8b0000': 'Dark Red',    '#ffd700': 'Yellow',
  // Revlon ColorSilk hair colors
  '#c0b89a': 'Silver Blonde',         '#b8860b': 'Dark Blonde',
  '#c2185b': 'Radiant Raspberry',     '#8b4513': 'Auburn Brown',
  '#800020': 'Burgundy',              '#c0392b': 'Bright Auburn',
  '#6a0dad': 'Vibrant Violet',        '#5c0029': 'Deep Burgundy',
  '#3d1c02': 'Brown Black',           '#e8dcb0': 'Ultra Light Ash Blonde',
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
let currentCategory = null;   // top-level category name (used for category discounts)
let expectCategory  = false;  // the next name: line is a category's name
let imgPending      = false;  // images: [ opened — first path is on a following line
let inColorImages   = false;  // inside colorImages: { '#hex': [ ... ] }
let colorImgHex     = null;   // colour whose first image is still to be read
let inNested        = false;  // inside another multi-line { ... } field
const firstPath = s => s.match(/['"](\/images\/[^'"]+)['"]/)?.[1];

for (const raw of lines) {
  const line = raw.trim();

  // one-line product: { name: '...', images: [...], price: 4500, quantity: 3 },
  const inl = line.match(/^\{\s*name:\s*(['"])((?:\\.|(?!\1).)*)\1(.*)$/);
  if (inl) {
    if (cur?.name && cur.quantity !== undefined) products.push(cur);
    const rest = inl[3];
    const q  = rest.match(/\bquantity:\s*(\d+)/);
    const pr = rest.match(/\bprice:\s*(\d+)/);
    const c  = rest.match(/\bcolors:\s*\[[^\]]*\]/);
    const im = rest.match(/\bimages?:\s*\[?\s*['"]([^'"]+)['"]/);
    cur = {
      name:      inl[2].replace(/\\(.)/g, '$1'),
      quantity:  q ? parseInt(q[1]) : undefined,
      price:     pr ? parseInt(pr[1]) : undefined,
      colorsRaw: c ? c[0] : null,
      category:  currentCategory,
      image:     im ? im[1] : undefined,
    };
    // closes on the same line → done; otherwise keep parsing the following lines
    if (/\}\s*,?$/.test(rest)) {
      if (cur.quantity !== undefined) products.push(cur);
      cur = null;
    }
    continue;
  }

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

  // images: / image: — keep the first photo (shown on the checkout customer display)
  if (/^images:\s*\[/.test(line) || /^image:\s*['"]/.test(line)) {
    const p = firstPath(line);
    if (cur && !cur.image && p) cur.image = p;
    imgPending = !p && !line.includes(']');
    continue;
  }
  if (imgPending) {
    const p = firstPath(line);
    if (p) { if (cur && !cur.image) cur.image = p; imgPending = false; }
    if (line.includes(']')) imgPending = false;
    continue;
  }

  // colorImages: { '#hex': [ '/images/...', ... ], ... } — first photo per colour
  if (/^colorImages:\s*\{/.test(line)) { inColorImages = true; if (cur) cur.colorImages = {}; continue; }
  if (inColorImages) {
    const hx = line.match(/^['"](#[0-9a-fA-F]{6})['"]\s*:/);
    if (hx) colorImgHex = hx[1].toLowerCase();
    const p = firstPath(line);
    if (p && colorImgHex && cur?.colorImages && !cur.colorImages[colorImgHex]) {
      cur.colorImages[colorImgHex] = p;
      colorImgHex = null;
    }
    if (/^\}\s*,?$/.test(line)) { inColorImages = false; colorImgHex = null; }
    continue;
  }

  // other nested multi-line objects (e.g. colorQuantities: {) — skip, so their
  // closing "}," isn't taken for the end of the product
  if (/^\w+:\s*\{$/.test(line)) { inNested = true; continue; }
  if (inNested) { if (/^\}\s*,?$/.test(line)) inNested = false; continue; }

  // id: — only top-level categories have one
  if (/^id:\s*['"]/.test(line)) { expectCategory = true; continue; }

  // name:
  const nm = line.match(/^name:\s*['"](.+)['"]/);
  if (nm) {
    if (cur?.name && cur.quantity !== undefined) products.push(cur);
    if (expectCategory) {
      currentCategory = nm[1];
      expectCategory  = false;
      cur = null;
      continue;
    }
    cur = { name: nm[1], quantity: undefined, price: undefined, colorsRaw: null, category: currentCategory };
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
const add = (name, qty, price, category, base, image) => {
  if (!seen.has(name)) {
    seen.add(name);
    const ex = existingMap[name];
    result.push({
      name,
      quantity: ex?.quantity !== undefined ? ex.quantity : qty,
      price:    ex?.price ?? price ?? undefined,
      category: category ?? undefined,
      base:     base ?? undefined,     // colour variants: the product they belong to
      image:    image ?? undefined,    // first photo — shown on the checkout customer display
    });
  }
};

for (const p of products) {
  if (!p.name || p.quantity == null) continue;
  const colors = parseColors(p.colorsRaw);
  const imgFor = hex => p.colorImages?.[hex.toLowerCase()] ?? p.image;

  if (colors.length > 1) {
    const perColor = Math.ceil(p.quantity / colors.length);
    for (const hex of colors) add(`${p.name} (${getColorName(hex)})`, perColor, p.price, p.category, p.name, imgFor(hex));
  } else if (colors.length === 1) {
    add(p.name, p.quantity, p.price, p.category, undefined, p.image);
    add(`${p.name} (${getColorName(colors[0])})`, p.quantity, p.price, p.category, p.name, imgFor(colors[0]));
  } else {
    add(p.name, p.quantity, p.price, p.category, undefined, p.image);
  }
}

writeFileSync(outputPath, JSON.stringify(result, null, 2));
console.log(`✓ products-list.json — ${result.length} entries`);
