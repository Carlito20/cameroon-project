/**
 * sync-color-stock.mjs
 *
 * Reads categories.ts to find all products with color arrays,
 * then ensures products-list.json has a per-color entry for every variant.
 *
 * For missing entries, stock is divided evenly across colors.
 * Existing entries (including manually set unequal stock) are NEVER overwritten.
 *
 * Run: node scripts/sync-color-stock.mjs
 */

import { readFileSync, writeFileSync } from 'fs';
import { resolve, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dir = dirname(fileURLToPath(import.meta.url));
const root = resolve(__dir, '..');

const colorNames = {
  '#ff69b4': 'Pink',
  '#2c2c2c': 'Black',
  '#d4af37': 'Gold',
  '#808080': 'Gray',
  '#e74c3c': 'Red',
  '#2980b9': 'Blue',
  '#800080': 'Purple',
  '#ffffff': 'White',
  '#ff8c00': 'Orange',
  '#f5f5dc': 'Beige',
  '#c0c0c0': 'Silver',
  '#008000': 'Green',
  '#00008b': 'Dark Blue',
  '#8b0000': 'Dark Red',
  '#ffd700': 'Yellow',
  '#2e7d32': 'Green',
  '#1565c0': 'Blue',
  '#c62828': 'Dark Red',
  '#4a5240': 'Army Green',
  '#1a237e': 'Indigo',
  '#4a148c': 'Deep Purple',
  '#b71c1c': 'Crimson',
  '#e65100': 'Dark Orange',
  '#33691e': 'Olive Green',
  '#00bcd4': 'Cyan',
  '#7b1fa2': 'Deep Purple',
};

function getColorName(hex) {
  return colorNames[hex.toLowerCase()] || hex;
}

// Parse categories.ts text to extract { name, quantity, colors[] } for products with colors
function parseCategories(src) {
  const products = [];
  // Match object blocks that contain a `colors:` array
  const blockRe = /\{[^{}]*?name\s*:\s*'([^']+)'[^{}]*?quantity\s*:\s*(\d+)[^{}]*?colors\s*:\s*\[([^\]]+)\][^{}]*?\}/gs;
  let m;
  while ((m = blockRe.exec(src)) !== null) {
    const name = m[1];
    const quantity = parseInt(m[2], 10);
    const colorsRaw = m[3];
    const colors = [...colorsRaw.matchAll(/'(#[0-9a-fA-F]+)'/g)].map(c => c[1].toLowerCase());
    if (colors.length > 0) {
      products.push({ name, quantity, colors });
    }
  }
  return products;
}

// --- Main ---
const categoriesSrc = readFileSync(resolve(root, 'src/data/categories.ts'), 'utf8');
const productsPath  = resolve(root, 'public/api/products-list.json');
const productsList  = JSON.parse(readFileSync(productsPath, 'utf8'));

const existingNames = new Set(productsList.map(p => p.name));
const colorProducts = parseCategories(categoriesSrc);

let added = 0;

for (const { name, quantity, colors } of colorProducts) {
  // Find the base entry to get its current quantity (may differ from categories.ts if manually updated)
  const baseEntry = productsList.find(p => p.name === name);
  const baseQty = baseEntry ? baseEntry.quantity : quantity;
  const perColor = Math.ceil(baseQty / colors.length);

  for (const hex of colors) {
    const colorName = getColorName(hex);
    const variantName = `${name} (${colorName})`;
    if (!existingNames.has(variantName)) {
      productsList.push({ name: variantName, quantity: perColor });
      existingNames.add(variantName);
      console.log(`  + Added: ${variantName} (qty: ${perColor})`);
      added++;
    }
  }
}

if (added === 0) {
  console.log('All per-color entries already exist. Nothing to add.');
} else {
  writeFileSync(productsPath, JSON.stringify(productsList, null, 2));
  console.log(`\nDone. Added ${added} per-color entries to products-list.json`);
}
