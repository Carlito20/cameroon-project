import ExcelJS from 'exceljs';

const wb = new ExcelJS.Workbook();
wb.creator = 'American Select';

const GOLD   = 'FFD4AF37';
const BLACK  = 'FF1A1A1A';
const WHITE  = 'FFFFFFFF';
const GREEN  = 'FF2E7D32';
const RED    = 'FFCC0000';
const ORANGE = 'FFE65100';
const LGOLD  = 'FFFFF8E1';
const LGREEN = 'FFF1F8F1';
const LRED   = 'FFFFF3F3';
const LGRAY  = 'FFF5F5F5';

const hdr = (ws, row, cols, bg = BLACK, fg = GOLD) => {
  const r = ws.getRow(row);
  cols.forEach((v, i) => {
    const c = r.getCell(i + 1);
    c.value = v;
    c.font = { bold: true, color: { argb: fg }, size: 11 };
    c.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: bg } };
    c.alignment = { vertical: 'middle', horizontal: 'center', wrapText: true };
    c.border = { bottom: { style: 'medium', color: { argb: GOLD } } };
  });
  r.height = 24;
};

const row = (ws, rowNum, vals, opts = {}) => {
  const r = ws.getRow(rowNum);
  vals.forEach((v, i) => {
    const c = r.getCell(i + 1);
    c.value = v;
    if (opts.bg)     c.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: opts.bg } };
    if (opts.bold)   c.font = { ...c.font, bold: true };
    if (opts.color)  c.font = { ...c.font, color: { argb: opts.color } };
    if (opts.size)   c.font = { ...c.font, size: opts.size };
    if (opts.align)  c.alignment = { horizontal: opts.align, vertical: 'middle' };
    if (opts.numFmt) c.numFmt = opts.numFmt;
    c.border = { bottom: { style: 'hair', color: { argb: 'FFDDDDDD' } } };
  });
  r.height = opts.height || 18;
  return r;
};

const title = (ws, rowNum, text, span, bg = BLACK) => {
  ws.mergeCells(rowNum, 1, rowNum, span);
  const c = ws.getCell(rowNum, 1);
  c.value = text;
  c.font = { bold: true, color: { argb: GOLD }, size: 13 };
  c.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: bg } };
  c.alignment = { horizontal: 'left', vertical: 'middle' };
  c.border = { left: { style: 'thick', color: { argb: GOLD } } };
  ws.getRow(rowNum).height = 28;
};

const blank = (ws, rowNum) => { ws.getRow(rowNum).height = 8; };

// ════════════════════════════════════════════════════════════
// SHEET 1 — SUMMARY
// ════════════════════════════════════════════════════════════
const s1 = wb.addWorksheet('Summary');
s1.views = [{ state: 'frozen', ySplit: 3 }];
s1.columns = [{ width: 30 }, { width: 20 }, { width: 20 }, { width: 20 }, { width: 20 }];

// Masthead
s1.mergeCells('A1:E1');
const mast = s1.getCell('A1');
mast.value = 'AMERICAN SELECT — PROFITABILITY ANALYSIS REPORT   |   May 2026';
mast.font = { bold: true, color: { argb: GOLD }, size: 14 };
mast.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: BLACK } };
mast.alignment = { horizontal: 'center', vertical: 'middle' };
s1.getRow(1).height = 36;

s1.mergeCells('A2:E2');
const sub = s1.getCell('A2');
sub.value = 'Exchange rate: 1 USD = 600 FCFA   |   Confidential — Internal Use Only';
sub.font = { italic: true, color: { argb: 'FF888888' }, size: 11 };
sub.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF111111' } };
sub.alignment = { horizontal: 'center', vertical: 'middle' };
s1.getRow(2).height = 20;

blank(s1, 3);

// KPI cards
title(s1, 4, '1. FINANCIAL SUMMARY', 5);
hdr(s1, 5, ['Metric', 'USD', 'FCFA', 'Notes', ''], 'FF222222', GOLD);

const kpis = [
  ['Total Invested (all expenses)',     '$5,483',    '3,289,842 FCFA', 'Inventory + equipment + operations', ''],
  ['Revenue Potential (all stock sold)','$10,259',   '6,155,600 FCFA', 'At current selling prices',          ''],
  ['Gross Profit Potential',            '$4,776',    '2,865,758 FCFA', 'If all 967 units sell',               ''],
  ['Estimated Net Profit',              '$3,000–3,667', '1,800,000–2,200,000 FCFA', 'After ops costs (bags, hosting, transport)', ''],
  ['Return on Investment (ROI)',        '87.1%',     '87.1%',          'You nearly double your money',        ''],
  ['Break-even Point',                  '~52% sold', '~503 units',     'Need to sell 52% of 967 units',       ''],
];
kpis.forEach(([a, b, c, d], i) => {
  const bg = i % 2 === 0 ? WHITE : LGRAY;
  const isBold = i === 2 || i === 4;
  row(s1, 6 + i, [a, b, c, d, ''], { bg, bold: isBold });
  if (i === 4) s1.getRow(6 + i).getCell(2).font = { bold: true, color: { argb: GREEN }, size: 12 };
});

blank(s1, 13);

// Revenue by category
title(s1, 14, '2. REVENUE POTENTIAL BY CATEGORY', 5);
hdr(s1, 15, ['Category', 'Units in Stock', 'Revenue (FCFA)', 'Revenue (USD)', 'Notes']);
const cats = [
  ['Electronics',             289, 1759000, '$2,932', 'Largest revenue driver — Temu sourced'],
  ['Perfumes & Deodorants',   206, 1225600, '$2,043', 'Strong margins on premium lines'],
  ['Body Lotion',             130, 1040500, '$1,734', 'Dr Teal\'s, Vaseline, Olay best performers'],
  ['Body Wash',               152,  736500, '$1,228', 'Suave, Dr Teal\'s, St. Ives'],
  ['Hair Care',                45,  357000,   '$595', 'TRESemmé sets — 136% margin'],
  ['Personal Care',            42,  321500,   '$536', 'Toothpaste, mouthwash, shavers'],
  ['Kitchen Gadgets',          47,  290500,   '$484', 'Blender cups — 229% margin'],
  ['Household',                19,  108000,   '$180', 'Febreze, Spin Mop'],
  ['Kids & Baby',               7,   59500,    '$99', 'Low stock — reorder opportunity'],
  ['TOTAL',                   967, 6155600, '$10,259', ''],
];
cats.forEach(([a, b, c, d, e], i) => {
  const isTotal = i === cats.length - 1;
  const bg = isTotal ? BLACK : (i % 2 === 0 ? WHITE : LGRAY);
  const fg = isTotal ? GOLD : (i < 4 ? GREEN : BLACK);
  const r = s1.getRow(16 + i);
  [a, b, c, d, e].forEach((v, ci) => {
    const cell = r.getCell(ci + 1);
    cell.value = v;
    cell.font = { bold: isTotal, color: { argb: isTotal ? GOLD : (ci === 2 ? ''+fg : BLACK) } };
    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: bg } };
    cell.alignment = { vertical: 'middle', horizontal: ci > 0 ? 'center' : 'left' };
    cell.border = { bottom: { style: isTotal ? 'medium' : 'hair', color: { argb: isTotal ? GOLD : 'FFDDDDDD' } } };
    if (ci === 2 && !isTotal) cell.numFmt = '#,##0';
  });
  r.height = 18;
});

// ════════════════════════════════════════════════════════════
// SHEET 2 — BEST PERFORMERS
// ════════════════════════════════════════════════════════════
const s2 = wb.addWorksheet('Best Performers');
s2.columns = [{ width: 38 }, { width: 16 }, { width: 18 }, { width: 12 }, { width: 28 }];

s2.mergeCells('A1:E1');
const m2 = s2.getCell('A1');
m2.value = 'BEST PERFORMING PRODUCTS — Highest Margins';
m2.font = { bold: true, color: { argb: GOLD }, size: 13 };
m2.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: BLACK } };
m2.alignment = { horizontal: 'center', vertical: 'middle' };
s2.getRow(1).height = 30;
blank(s2, 2);

hdr(s2, 3, ['Product', 'Cost (per unit)', 'Selling Price (FCFA)', 'Margin %', 'Source'], 'FF222222', GOLD);
const best = [
  ['7-in-1 USB Hub',                    '~$3.45',  '8,000',  '+287%', 'Temu'],
  ['Portable Blender Cup 380mL',         '~$4.05',  '8,000',  '+229%', 'Temu'],
  ['Suave Shampoo + Conditioner Set',    '~$6.00',  '13,000', '+261%', 'Walmart'],
  ['Memory Cards 256GB',                 '~$4.04',  '6,000',  '+148%', 'Temu'],
  ['Miss Coco EDP For Women',            '~$3.41',  '5,000',  '+144%', 'Daspar'],
  ['Women\'s Spray Perfumes (Funteze)',  '~$6.95',  '10,000', '+140%', 'Funteze'],
  ['Dr Teal\'s Body Lotion',            '~$5.75',  '8,000',  '+132%', 'Walmart'],
  ['TRESemmé Shampoo + Conditioner Set', '~$11.00', '15,000', '+136%', 'Walmart'],
  ['Memory Card Reader 4-in-1',          '~$3.08',  '3,500',  '+89%',  'Temu'],
  ['Power Bank 20,000mAh',              '~$6.28',  '7,000',  '+86%',  'Temu'],
  ['Rechargeable Flashlight',           '~$3.89',  '5,000',  '+114%', 'Alibaba'],
  ['High-end EDP (Rivoli Parfums)',      '~$15.00', '15,000', '+67%',  'Rivoli Parfums'],
  ['Nokia Go Earbuds+ TWS',             '~$10.60', '10,000', '+57%',  'Temu'],
  ['Airpod Pro Clone',                  '~$10.00', '15,000', '+50%',  'Temu'],
];
best.forEach(([a, b, c, d, e], i) => {
  const bg = i % 2 === 0 ? LGREEN : WHITE;
  const r = s2.getRow(4 + i);
  [a, b, c, d, e].forEach((v, ci) => {
    const cell = r.getCell(ci + 1);
    cell.value = v;
    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: bg } };
    cell.alignment = { vertical: 'middle', horizontal: ci === 0 ? 'left' : 'center' };
    cell.border = { bottom: { style: 'hair', color: { argb: 'FFDDDDDD' } } };
    if (ci === 3) cell.font = { bold: true, color: { argb: GREEN } };
  });
  r.height = 18;
});

// ════════════════════════════════════════════════════════════
// SHEET 3 — LOSS MAKERS & FIXES
// ════════════════════════════════════════════════════════════
const s3 = wb.addWorksheet('Loss-Makers & Fixes');
s3.columns = [{ width: 34 }, { width: 16 }, { width: 18 }, { width: 18 }, { width: 16 }, { width: 22 }];

s3.mergeCells('A1:F1');
const m3 = s3.getCell('A1');
m3.value = 'PRODUCTS REQUIRING IMMEDIATE PRICE CORRECTION';
m3.font = { bold: true, color: { argb: WHITE }, size: 13 };
m3.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFCC0000' } };
m3.alignment = { horizontal: 'center', vertical: 'middle' };
s3.getRow(1).height = 30;

s3.mergeCells('A2:F2');
const m3b = s3.getCell('A2');
m3b.value = '⚠ Every sale on these products currently loses money. Reprice immediately and do not reorder.';
m3b.font = { italic: true, color: { argb: 'FFCC0000' } };
m3b.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: LRED } };
m3b.alignment = { horizontal: 'center', vertical: 'middle' };
s3.getRow(2).height = 20;

blank(s3, 3);
hdr(s3, 4, ['Product', 'Actual Cost', 'Current Price', 'Recommended Price', 'Loss/Unit', 'Action'], 'FF8B0000', WHITE);

const losers = [
  ['Rechargeable Fans (all colours)',  '~$11–14',    '3,500 FCFA',  '7,000 FCFA',  '~$7/unit',    'RAISE — do not reorder'],
  ['JS59 Wireless Headphones',         '~$12–15',    '2,500 FCFA',  '6,500 FCFA',  '~$10/unit',   'RAISE — do not reorder'],
  ['Monster Wireless Earbuds',         '~$12–15',    '4,000 FCFA',  '6,500 FCFA',  '~$8/unit',    'RAISE — do not reorder'],
  ['Acer / Ace Headsets',              '~$10–15',    '5,500 FCFA',  '9,000 FCFA',  '~$5/unit',    'RAISE — do not reorder'],
  ['Spin Mop + Bucket Set',            '~$40 retail','20,000 FCFA', '35,000 FCFA', '~$7/unit',    'RAISE immediately'],
  ['CeraVe Moisturizing Cream',        '~$20 retail','12,000 FCFA', '18,000 FCFA', 'Selling at cost','RAISE immediately'],
  ['Lovery Pheromone Perfumes',        '~$16.52',    '10,000 FCFA', '13,000 FCFA', '$0.15 profit','Raise or discontinue'],
  ['TG537 Speakers',                   '~$15.85',    '10,000 FCFA', '13,000 FCFA', '$0.82 profit','RAISE — barely breaking even'],
  ['USB Cables (all types)',           '~$1.70',     '1,500 FCFA',  '2,500 FCFA',  '$0.80 profit','RAISE — too thin'],
  ['45W Type-C Charger',              '~$4.96',     '3,500 FCFA',  '6,000 FCFA',  '$0.87 profit','RAISE — too thin'],
];
losers.forEach(([a, b, c, d, e, f], i) => {
  const isCritical = i < 5;
  const bg = isCritical ? LRED : LGOLD;
  const r = s3.getRow(5 + i);
  [a, b, c, d, e, f].forEach((v, ci) => {
    const cell = r.getCell(ci + 1);
    cell.value = v;
    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: bg } };
    cell.alignment = { vertical: 'middle', horizontal: ci === 0 ? 'left' : 'center' };
    cell.border = { bottom: { style: 'hair', color: { argb: 'FFDDDDDD' } } };
    if (ci === 4) cell.font = { bold: true, color: { argb: RED } };
    if (ci === 3) cell.font = { bold: true, color: { argb: GREEN } };
    if (ci === 5) cell.font = { bold: true, color: { argb: isCritical ? RED : ORANGE } };
  });
  r.height = 20;
});

// ════════════════════════════════════════════════════════════
// SHEET 4 — ACTION PLAN
// ════════════════════════════════════════════════════════════
const s4 = wb.addWorksheet('Action Plan');
s4.columns = [{ width: 6 }, { width: 28 }, { width: 55 }, { width: 16 }, { width: 14 }];

s4.mergeCells('A1:E1');
const m4 = s4.getCell('A1');
m4.value = 'ACTION PLAN — Priority Order';
m4.font = { bold: true, color: { argb: GOLD }, size: 14 };
m4.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: BLACK } };
m4.alignment = { horizontal: 'center', vertical: 'middle' };
s4.getRow(1).height = 34;
blank(s4, 2);

hdr(s4, 3, ['#', 'Action', 'Details', 'Priority', 'Status'], 'FF222222', GOLD);

const actions = [
  ['1', 'Raise loss-making prices',
   'Fans→7,000 | JS59→6,500 | Monster Earbuds→6,500 | Acer/Ace→9,000 | Cables→2,500 | Charger→6,000 | Lovery→13,000 | TG537→13,000 | CeraVe Cream→18,000 | Spin Mop→35,000',
   'URGENT', '☐ Done'],
  ['2', 'Start tracking sales daily',
   'Use the Sales Report in the admin panel to log every walk-in sale. This gives real revenue data to compare against total expenses.',
   'HIGH', '☐ Done'],
  ['3', 'Reorder best-performing products',
   'USB Hubs, Blender Cups, Power Banks, Dr Teal\'s Body Lotion, Women\'s Perfumes (Funteze), Memory Cards. Margins: 86–287%.',
   'HIGH', '☐ Done'],
  ['4', 'Do NOT reorder loss-makers',
   'Fans, JS59 Headphones, TG537 Speakers — do not buy more stock until prices corrected and confirmed profitable.',
   'HIGH', '☐ Done'],
  ['5', 'Consider product bundles',
   'Bundle cable + charger (7,000 FCFA), or storage lids + garlic blender (8,000 FCFA) to improve margin per transaction.',
   'MEDIUM', '☐ Done'],
  ['6', 'Renegotiate Lovery vendor pricing',
   'At ~$16.52/unit landed cost, Lovery pheromone perfumes leave almost no margin. Negotiate below $10/unit or find alternative vendor.',
   'MEDIUM', '☐ Done'],
  ['7', 'Review Spin Mop + Aveeno pricing',
   'Spin Mop costs $40 US retail — current 20,000 FCFA ($33) is below purchase price. Raise to 35,000. Aveeno 18oz costs $9 — raise from 7,000 to 9,000.',
   'MEDIUM', '☐ Done'],
  ['8', 'Monthly profitability check',
   'Each month export the Sales Report CSV, compare revenue vs. expenses spreadsheet to track actual vs. projected profit.',
   'LOW', '☐ Done'],
];

const prioColors = { 'URGENT': RED, 'HIGH': ORANGE, 'MEDIUM': 'FF1565C0', 'LOW': GREEN };
actions.forEach(([num, act, det, prio, status], i) => {
  const bg = i % 2 === 0 ? WHITE : LGRAY;
  const r = s4.getRow(4 + i);
  [num, act, det, prio, status].forEach((v, ci) => {
    const cell = r.getCell(ci + 1);
    cell.value = v;
    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: bg } };
    cell.alignment = { vertical: 'middle', horizontal: ci === 0 ? 'center' : 'left', wrapText: true };
    cell.border = { bottom: { style: 'hair', color: { argb: 'FFDDDDDD' } } };
    if (ci === 0) cell.font = { bold: true, size: 12, color: { argb: GOLD } };
    if (ci === 1) cell.font = { bold: true };
    if (ci === 3) cell.font = { bold: true, color: { argb: prioColors[prio] || BLACK } };
  });
  r.height = 40;
});

// ════════════════════════════════════════════════════════════
// SHEET 5 — VENDOR BREAKDOWN
// ════════════════════════════════════════════════════════════
const s5 = wb.addWorksheet('Vendor Breakdown');
s5.columns = [{ width: 28 }, { width: 16 }, { width: 16 }, { width: 16 }, { width: 32 }];

s5.mergeCells('A1:E1');
const m5 = s5.getCell('A1');
m5.value = 'EXPENSE BREAKDOWN BY VENDOR';
m5.font = { bold: true, color: { argb: GOLD }, size: 13 };
m5.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: BLACK } };
m5.alignment = { horizontal: 'center', vertical: 'middle' };
s5.getRow(1).height = 30;
blank(s5, 2);

hdr(s5, 3, ['Vendor', 'Category', 'Amount Paid (USD)', 'Amount (FCFA)', 'Products Purchased']);
const vendors = [
  ['Walmart',              'Inventory',        636,   381600,  'Body wash, lotion, hair care, shampoo sets'],
  ['Amazon.com',           'Inventory + Equip',839,   503400,  'Body care, deodorant, baby products, MUNBYN printers'],
  ['Temu',                 'Electronics',      1050,  630000,  'Smartwatches, fans, speakers, cables, earbuds, hubs'],
  ['Shenzhen Boln (Ali.)', 'Chargers',         248,   148800,  '45W Type-C chargers (50 units)'],
  ['Rivoli Parfums',       'Perfumes',         321,   192600,  'High-end EDP — Bleu de Rivoli, Legende, Imperial Oud'],
  ['Lovery',               'Perfumes',         264,   158400,  'Loveryblack Affinity pheromone perfume line'],
  ['Daspar',               'Perfumes',         251,   150600,  'Miss Coco, Lazell, pheromone sprays'],
  ['Funteze',              'Perfumes',         208,   124800,  'Women\'s spray perfumes (Sexy Rose, Honey Bear, etc.)'],
  ['MYS Wholesale Inc',    'Colognes',         143,    85800,  'Men\'s colognes (Prism, Magic Code, Invincible, etc.)'],
  ['Pingyang (Alibaba)',   'Packaging',        137,    82200,  '300 shopping bags'],
  ['Lainy Home',           'Kitchen',           63,    37800,  'Manual food choppers (11 units)'],
  ['Trio Trading',         'Health',            53,    31800,  'Thermometers (10 units)'],
  ['Apparel Candy',        'Perfumes',          70,    42000,  'Paris Night cologne (2 packs)'],
  ['Mia Fan (Alibaba)',    'Electronics',       58,    34800,  'Rechargeable flashlights (15 units)'],
  ['Costco',               'Storage/Misc',     168,   100800,  'Storage and miscellaneous inventory'],
  ['TOTAL',               '',                5483,  3289800,  '967 units across all categories'],
];
vendors.forEach(([a, b, c, d, e], i) => {
  const isTotal = i === vendors.length - 1;
  const bg = isTotal ? BLACK : (i % 2 === 0 ? WHITE : LGRAY);
  const r = s5.getRow(4 + i);
  [a, b, c, d, e].forEach((v, ci) => {
    const cell = r.getCell(ci + 1);
    cell.value = v;
    cell.font = { bold: isTotal, color: { argb: isTotal ? GOLD : BLACK } };
    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: bg } };
    cell.alignment = { vertical: 'middle', horizontal: ci > 1 && ci < 4 ? 'center' : 'left' };
    cell.border = { bottom: { style: isTotal ? 'medium' : 'hair', color: { argb: isTotal ? GOLD : 'FFDDDDDD' } } };
    if (ci === 2 && !isTotal) { cell.numFmt = '"$"#,##0'; }
    if (ci === 3 && !isTotal) { cell.numFmt = '#,##0 "FCFA"'; }
    if (ci === 2 && isTotal)  { cell.value = '$5,483'; }
    if (ci === 3 && isTotal)  { cell.value = '3,289,842 FCFA'; }
  });
  r.height = isTotal ? 22 : 18;
});

await wb.xlsx.writeFile('AmericanSelect_ProfitabilityReport.xlsx');
console.log('Done — AmericanSelect_ProfitabilityReport.xlsx created (5 sheets).');
