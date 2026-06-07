import XLSX from 'xlsx';
import fs from 'fs';

const EXCHANGE_RATE = 600; // 1 USD = 600 FCFA

// Manual category overrides — for products not in the spreadsheet or miscategorized
const CATEGORY_OVERRIDES = {
  'Aveeno Daily Moisture Lotion, 24 fl oz':                                                                        'BODY LOTION',
  'Cetaphil Face & Body Moisturizer, Hydrating Moisturizing Cream for Dry to Very Dry, Sensitive Skin, 16 oz, Fragrance Free': 'BODY LOTION',
  'Cetaphil Face & Body Moisturizer, Hydrating Moisturizing Cream for Dry to Very Dry, Sensitive Skin, 20 oz, Fragrance Free': 'BODY LOTION',
  'Crest Complete Toothpaste':                                                                                      'PERSONAL CARE',
  'Revlon ColorSilk Permanent Hair Color with Bond Repair Complex':                                                 'HAIR',
};

// --- 1. Parse products from categories.ts ---
const content = fs.readFileSync('src/data/categories.ts', 'utf8');
const lines = content.split(/\r?\n/);
const products = [];

lines.forEach((line, i) => {
  // Inline format: { name: '...', ..., price: XXXX, quantity: X }
  const inlineMatch = line.match(/name:\s*["']([^"']+)["'].*?price:\s*(\d+).*?quantity:\s*(\d+)/);
  if (inlineMatch) {
    products.push({ name: inlineMatch[1].trim(), price: parseInt(inlineMatch[2]), quantity: parseInt(inlineMatch[3]) });
    return;
  }

  // Multi-line: look back from price: line for name
  const priceMatch = line.match(/^\s*price:\s*(\d+)/);
  if (!priceMatch) return;
  const price = parseInt(priceMatch[1]);

  let name = null, quantity = null;
  for (let j = i - 1; j >= Math.max(0, i - 20); j--) {
    const dq = lines[j].match(/name:\s*"([^"]+)"/);
    const sq = lines[j].match(/name:\s*'([^']+)'/);
    if (dq || sq) { name = (dq || sq)[1].trim(); break; }
  }
  for (let j = i - 5; j <= i + 5; j++) {
    if (j < 0 || j >= lines.length) continue;
    const qm = lines[j].match(/quantity:\s*(\d+)/);
    if (qm) { quantity = parseInt(qm[1]); break; }
  }
  if (name) products.push({ name, price, quantity: quantity ?? 0 });
});

console.log('Products parsed:', products.length);

// --- 2. US Retail prices + category map from spreadsheets ---
const usRetailMap = {};
const categoryMap = {};

const origWb = XLSX.readFile('c:/Users/Administrator/OneDrive/Documents/Original AmericanSelect_PricingAnalysis.xlsx');
const origData = XLSX.utils.sheet_to_json(origWb.Sheets['Pricing Analysis'], { defval: '' });
origData.forEach(r => {
  if (!r['Product']) return;
  const name = r['Product'].trim();
  if (r['US Retail (USD)']) usRetailMap[name] = parseFloat(r['US Retail (USD)']);
  if (r['Category']) categoryMap[name] = r['Category'];
});

const currWb = XLSX.readFile('AmericanSelect_PricingAnalysis.xlsx');
const currData = XLSX.utils.sheet_to_json(currWb.Sheets['Pricing Analysis'], { defval: '' });
currData.forEach(r => {
  if (!r['Product']) return;
  const name = r['Product'].trim();
  if (r['US Retail (USD)'] && !usRetailMap[name]) usRetailMap[name] = parseFloat(r['US Retail (USD)']);
  if (r['Category'] && !categoryMap[name]) categoryMap[name] = r['Category'];
});

// Normalize lookup (handle apostrophe variants)
function norm(s) { return s.replace(/[‘’]/g, "'").toLowerCase().trim(); }

function findUSRetail(name) {
  if (usRetailMap[name]) return usRetailMap[name];
  const n = norm(name);
  const key = Object.keys(usRetailMap).find(k => norm(k) === n);
  return key ? usRetailMap[key] : null;
}

function findCategory(name) {
  if (categoryMap[name]) return categoryMap[name];
  const n = norm(name);
  const key = Object.keys(categoryMap).find(k => norm(k) === n);
  return key ? categoryMap[key] : 'OTHER';
}

// --- 3. Assign categories — spreadsheet is primary source of truth ---
// The existing spreadsheet has manually-correct categories for all known products.
// We only need file-walking as a fallback for brand-new products not yet in the spreadsheet.
// Top-level category id → label, and subcategory name → label
const topCatLabels = {
  'home-kitchen':       'KITCHEN',
  'body-bath':          'BODY WASH',       // default for body-bath, overridden by subcategory
  'kids':               'KIDS & BABY',
  'household-cleaning': 'HOUSEHOLD',
  'electronics':        'ELECTRONICS',
  'health-wellness':    'HEALTH',
  'home-essentials':    'HOME ESSENTIALS',
  'food-pantry':        'FOOD & PANTRY',
};

const subCatLabels = {
  'Bath Soaps & Shower Gels':        'BODY WASH',
  'Body Lotion & Moisturizers':      'BODY LOTION',
  'Body Lotion':                     'BODY LOTION',
  'Deodorants':                      'DEODORANTS',
  'Deodorants (Men)':                'DEODORANTS (MEN)',
  'Deodorants (Women)':              'DEODORANTS (WOMEN)',
  'Hair Care':                       'HAIR',
  'Personal Care':                   'PERSONAL CARE',
  'Oral Care':                       'PERSONAL CARE',
  'Perfumes & Fragrances':           'PERFUMES',
  'Perfumes (Men)':                  'PERFUMES (MEN)',
  'Perfumes (Women)':                'PERFUMES (WOMEN)',
  'Body Mist':                       'BODY MIST',
  'Cables & Chargers':               'CABLES & CHARGERS',
  'Smart Watches':                   'ELECTRONICS',
  'Speakers':                        'ELECTRONICS',
  'Headphones & Earbuds':            'ELECTRONICS',
  'Fans & Cooling':                  'ELECTRONICS',
  'Cookware & Bakeware':             'KITCHEN',
  'Food Storage & Containers':       'KITCHEN',
  'Kitchen Appliances':              'KITCHEN',
  'Cleaning Supplies':               'HOUSEHOLD',
  'Air Fresheners':                  'HOUSEHOLD',
};

// Walk lines to build productName → category
// Detection: subcategory name lines have ~8 spaces indent, product name lines ~12+
const productCatMap = {};
let currentTopCat = 'OTHER';
let currentSubCat = null;

lines.forEach((line, i) => {
  // Top-level category id
  const idMatch = line.match(/^\s{4}id:\s*'([^']+)'/);
  if (idMatch && topCatLabels[idMatch[1]]) {
    currentTopCat = topCatLabels[idMatch[1]];
    currentSubCat = null;
    return;
  }

  const nameMatch = line.match(/^(\s*)name:\s*['"]([^'"]+)['"]/);
  if (!nameMatch) return;

  const indent = nameMatch[1].length;
  const name   = nameMatch[2].trim();

  if (indent <= 8) {
    // Top-level category name (e.g. "Kitchen & Dining") — skip
    return;
  }

  if (indent <= 10) {
    // Subcategory name (8–10 spaces) — look ahead to confirm next non-empty line is "items:"
    for (let j = i + 1; j < Math.min(i + 4, lines.length); j++) {
      const next = lines[j].trim();
      if (!next) continue;
      if (next.startsWith('items:')) {
        currentSubCat = subCatLabels[name] || null;
      } else {
        // Not a subcategory, it's a product
        productCatMap[name] = currentSubCat || currentTopCat;
      }
      break;
    }
    return;
  }

  // Product name (12+ spaces indent)
  productCatMap[name] = currentSubCat || currentTopCat;
});

function getCategory(name) {
  // 1. Manual overrides take highest priority
  if (CATEGORY_OVERRIDES[name]) return CATEGORY_OVERRIDES[name];
  const n = name.replace(/['']/g, "'");
  const overrideKey = Object.keys(CATEGORY_OVERRIDES).find(k => k.replace(/['']/g, "'") === n);
  if (overrideKey) return CATEGORY_OVERRIDES[overrideKey];
  // 2. Spreadsheet category (manually curated)
  const fromSheet = findCategory(name);
  if (fromSheet && fromSheet !== 'OTHER') return fromSheet;
  // 3. File-parsed fallback for new products
  if (productCatMap[name]) return productCatMap[name];
  const key = Object.keys(productCatMap).find(k => k.replace(/['']/g, "'") === n);
  if (key) return productCatMap[key];
  return 'OTHER';
}

// --- 4. Status calculation ---
function calcStatus(price, breakEven) {
  if (breakEven === null) return 'No US price data';
  const pct = ((price - breakEven) / breakEven) * 100;
  if (pct < 0)   return 'SELLING AT A LOSS';
  if (pct < 20)  return 'Slight raise needed';
  if (pct < 50)  return 'Acceptable';
  if (pct < 100) return 'Could go higher';
  return 'Good margin';
}

// --- 5. Build rows ---
const rows = [];
const seen = new Set();

products.forEach(p => {
  if (!p.name || seen.has(p.name)) return;
  seen.add(p.name);
  if (p.price === 0) return; // skip unpriced

  const usRetail = findUSRetail(p.name);
  const breakEven = usRetail ? Math.round(usRetail * EXCHANGE_RATE) : null;
  const marginPct = breakEven ? Math.round(((p.price - breakEven) / breakEven) * 100) : null;
  const status = calcStatus(p.price, breakEven);

  rows.push({
    'Category':            getCategory(p.name),
    'Product':             p.name,
    'Stock':               p.quantity,
    'Current Price (XAF)': p.price,
    'US Retail (USD)':     usRetail ?? '',
    'Break-Even (XAF)':    breakEven ?? '',
    'Margin %':            marginPct !== null ? marginPct + '%' : 'N/A',
    'Status':              status,
  });
});

rows.sort((a, b) => a['Category'].localeCompare(b['Category']) || a['Product'].localeCompare(b['Product']));
console.log('Analysis rows:', rows.length);

// --- 6. Summary by category ---
const catSummary = {};
rows.forEach(r => {
  const c = r['Category'];
  if (!catSummary[c]) catSummary[c] = { count:0, good:0, higher:0, acceptable:0, slight:0, loss:0, noData:0, stock:0, value:0 };
  catSummary[c].count++;
  catSummary[c].stock += r['Stock'] || 0;
  catSummary[c].value += (r['Stock'] || 0) * r['Current Price (XAF)'];
  const s = r['Status'];
  if (s === 'Good margin')         catSummary[c].good++;
  else if (s === 'Could go higher') catSummary[c].higher++;
  else if (s === 'Acceptable')     catSummary[c].acceptable++;
  else if (s === 'Slight raise needed') catSummary[c].slight++;
  else if (s === 'SELLING AT A LOSS')   catSummary[c].loss++;
  else catSummary[c].noData++;
});

const summaryRows = Object.entries(catSummary)
  .sort((a, b) => b[1].value - a[1].value)
  .map(([cat, v]) => ({
    'Category':              cat,
    'Products':              v.count,
    'Total Stock Units':     v.stock,
    'Total Stock Value (XAF)': v.value.toLocaleString(),
    'Good Margin':           v.good,
    'Could Go Higher':       v.higher,
    'Acceptable':            v.acceptable,
    'Slight Raise':          v.slight,
    'Selling at a Loss':     v.loss,
    'No Price Data':         v.noData,
  }));

// Totals row
const totals = {
  'Category': 'TOTAL',
  'Products': rows.length,
  'Total Stock Units': rows.reduce((s,r) => s + (r['Stock']||0), 0),
  'Total Stock Value (XAF)': rows.reduce((s,r) => s + (r['Stock']||0)*r['Current Price (XAF)'], 0).toLocaleString(),
  'Good Margin': rows.filter(r => r['Status']==='Good margin').length,
  'Could Go Higher': rows.filter(r => r['Status']==='Could go higher').length,
  'Acceptable': rows.filter(r => r['Status']==='Acceptable').length,
  'Slight Raise': rows.filter(r => r['Status']==='Slight raise needed').length,
  'Selling at a Loss': rows.filter(r => r['Status']==='SELLING AT A LOSS').length,
  'No Price Data': rows.filter(r => r['Status']==='No US price data').length,
};
summaryRows.push(totals);

// --- 7. Legend ---
const legendRows = [
  { 'Status': 'Good margin',         'Meaning': 'Margin ≥ 100% above break-even. Strong profit — keep price.' },
  { 'Status': 'Could go higher',     'Meaning': 'Margin 50–99%. Healthy profit with room to increase.' },
  { 'Status': 'Acceptable',          'Meaning': 'Margin 20–49%. Covering costs with modest profit.' },
  { 'Status': 'Slight raise needed', 'Meaning': 'Margin 0–19%. Barely profitable — consider raising price.' },
  { 'Status': 'SELLING AT A LOSS',   'Meaning': 'Price is below break-even. Every sale loses money. Fix immediately.' },
  { 'Status': 'No US price data',    'Meaning': 'US Retail price not available — margin cannot be calculated.' },
  { 'Status': '',                    'Meaning': '' },
  { 'Status': 'Exchange Rate',       'Meaning': '1 USD = 600 FCFA' },
  { 'Status': 'Break-Even formula',  'Meaning': 'US Retail (USD) × 600 — minimum price to recover product cost' },
  { 'Status': 'Margin % formula',    'Meaning': '(Current Price − Break-Even) ÷ Break-Even × 100' },
  { 'Status': 'Date',                'Meaning': 'June 2026' },
];

// --- 8. Write file ---
const wb = XLSX.utils.book_new();
XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(legendRows), 'Legend');
XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(rows), 'Pricing Analysis');
XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(summaryRows), 'Summary by Category');

const filename = 'AmericanSelect_PricingAnalysis_June2026.xlsx';
XLSX.writeFile(wb, filename);
console.log('\nGenerated:', filename);
console.log('Total products:', rows.length);
console.log('Good margin:',    rows.filter(r => r['Status'] === 'Good margin').length);
console.log('Could go higher:', rows.filter(r => r['Status'] === 'Could go higher').length);
console.log('Acceptable:',      rows.filter(r => r['Status'] === 'Acceptable').length);
console.log('Slight raise:',    rows.filter(r => r['Status'] === 'Slight raise needed').length);
console.log('At a loss:',       rows.filter(r => r['Status'] === 'SELLING AT A LOSS').length);
console.log('No data:',         rows.filter(r => r['Status'] === 'No US price data').length);
