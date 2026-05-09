import ExcelJS from 'exceljs';

const newRows = [
  // ── MEN'S DEODORANTS ───────────────────────────────────────────────────
  ["DEODORANTS (MEN)", "Degree Men Black + White Ultraclear Deodorant",            4.50, 3000, 3000, "Acceptable"],
  ["DEODORANTS (MEN)", "AXE Antiperspirant Deodorant for Men Essence, 48H Sweat & Odor Protection, Black Pepper & Cedarwood", 5.00, 3000, 3000, "Acceptable"],
  ["DEODORANTS (MEN)", "AXE Apollo Antiperspirant Deodorant Stick For Men, Sage & Cedarwood, 48 Hr Anti Sweat", 5.00, 3000, 3000, "Acceptable"],
  ["DEODORANTS (MEN)", "Right Guard Sport Fresh Scent Antiperspirant & Deodorant Invisible Solid 4-in-1 For Men, 48-Hour Odor Protection", 4.00, 2000, 2000, "Acceptable"],

  // ── WOMEN'S DEODORANTS ─────────────────────────────────────────────────
  ["DEODORANTS (WOMEN)", "Lady Speed Stick 72HR Antiperspirant Deodorant for Women, Invisible Dry, Shower Fresh Scent, 2.3 oz", 3.50, 1500, 1500, "Acceptable"],
  ["DEODORANTS (WOMEN)", "Suave Antiperspirant Deodorant For Women, 48hr Protection, Fresh",          3.00, 2300, 2300, "Acceptable"],
  ["DEODORANTS (WOMEN)", "Suave Antiperspirant Deodorant For Women, 48hr Protection, Powder",         3.00, 2300, 2300, "Acceptable"],
  ["DEODORANTS (WOMEN)", "Suave Antiperspirant Deodorant For Women, Wild Cherry Blossom",             3.00, 2300, 2300, "Acceptable"],
  ["DEODORANTS (WOMEN)", "Suave Antiperspirant Deodorant For Women, 48hr Protection, Tropical Paradise", 3.00, 2300, 2300, "Acceptable"],
  ["DEODORANTS (WOMEN)", "Degree Original Antiperspirant Deodorant Sheer Powder",                    4.50, 3200, 3200, "Acceptable"],

  // ── MEN'S COLOGNES & PERFUMES ──────────────────────────────────────────
  ["PERFUMES (MEN)", "Taj Max Aqua Sport 3.4 oz Long-Lasting Perfume",                               15.00, 12000, 12000, "Good margin"],
  ["PERFUMES (MEN)", "Taj Max Exotic Bliss 3.4 oz Long-Lasting Perfume",                             15.00, 12000, 12000, "Good margin"],
  ["PERFUMES (MEN)", "Loveryblack Affinity For Him Pheromone Cologne",                               12.00, 10000, 10000, "Good margin"],
  ["PERFUMES (MEN)", "Loveryblack Affinity At Midnight Pheromone Perfume (Unisex)",                  12.00, 10000, 10000, "Good margin"],
  ["PERFUMES (MEN)", "Loveryblack Affinity Pure Passion Pheromone Perfume (Unisex)",                 12.00, 10000, 10000, "Good margin"],
  ["PERFUMES (MEN)", "Mens Cologne Bross 3.4oz Eau De Parfum Spray, Masculine Mist",                 12.00, 10000, 10000, "Good margin"],
  ["PERFUMES (MEN)", "Men'S Extreme 3.4oz Eau De Parfum - Men Perfume Spray",                        12.00, 10000, 10000, "Good margin"],
  ["PERFUMES (MEN)", "Victorious Heroes Spray Cologne Eau De Toilette For Men",                      10.00,  6000,  6000, "Good margin"],
  ["PERFUMES (MEN)", "Adventure Club Perfume For Men",                                               10.00,  7000,  7000, "Good margin"],
  ["PERFUMES (MEN)", "Azure Vantage Aqua Spray Cologne Eau De Parfum For Men",                        8.00,  5000,  5000, "Good margin"],
  ["PERFUMES (MEN)", "Investor Gold Spray Cologne Eau De Parfum For Men",                             8.00,  5000,  5000, "Good margin"],
  ["PERFUMES (MEN)", "Invincible Black Spray Cologne For Men EDP",                                    8.00,  5000,  5000, "Good margin"],
  ["PERFUMES (MEN)", "Invincible Platinum Spray Cologne Eau De Toilette For Men",                     8.00,  5000,  5000, "Good margin"],
  ["PERFUMES (MEN)", "Magic Code Spray Cologne Eau De Toilette For Men",                              8.00,  5000,  5000, "Good margin"],
  ["PERFUMES (MEN)", "Prism Cologne Eau De Toilette For Men",                                         8.00,  5000,  5000, "Good margin"],
  ["PERFUMES (MEN)", "Daspar De Homme Men Perfume with Pheromones",                                   5.00,  4000,  4000, "Good margin"],

  // ── WOMEN'S PERFUMES ───────────────────────────────────────────────────
  ["PERFUMES (WOMEN)", "Charm Spray Perfume Eau De Parfum For Women",                                12.00, 10000, 10000, "Good margin"],
  ["PERFUMES (WOMEN)", "Daicy Blue Spray Perfume Eau De Parfum",                                     12.00, 10000, 10000, "Good margin"],
  ["PERFUMES (WOMEN)", "Flower Pink Spray Perfume Eau De Parfum",                                    12.00, 10000, 10000, "Good margin"],
  ["PERFUMES (WOMEN)", "Gorgeous Flower Spray Perfume Eau De Parfum",                                12.00, 10000, 10000, "Good margin"],
  ["PERFUMES (WOMEN)", "Honey Bear Pink Spray Perfume Eau De Parfum",                                12.00, 10000, 10000, "Good margin"],
  ["PERFUMES (WOMEN)", "Love Is Forever Spray Perfume Eau De Parfum",                                12.00, 10000, 10000, "Good margin"],
  ["PERFUMES (WOMEN)", "Nice Girl Spray Perfume Eau De Parfum For Women",                            12.00, 10000, 10000, "Good margin"],
  ["PERFUMES (WOMEN)", "Princess High Heels Pink Spray Perfume Eau De Parfum",                       12.00, 10000, 10000, "Good margin"],
  ["PERFUMES (WOMEN)", "Sexy Rose Pink Spray Perfume Eau De Parfum",                                 12.00, 10000, 10000, "Good margin"],
  ["PERFUMES (WOMEN)", "365 Day Spray Perfume Eau De Parfum For Women",                               8.00,  6500,  6500, "Good margin"],
];

const statusColors = {
  "Good margin":       { bg: "FF2E7D32", font: "FFFFFFFF" },
  "Acceptable":        { bg: "FF4CAF50", font: "FFFFFFFF" },
  "Could go higher":   { bg: "FF8BC34A", font: "FF000000" },
  "Slight raise":      { bg: "FFFFF9C4", font: "FF000000" },
  "Underpriced":       { bg: "FFFFC107", font: "FF000000" },
  "WAY UNDERPRICED":   { bg: "FFFF5722", font: "FFFFFFFF" },
  "VERY UNDERPRICED":  { bg: "FFE53935", font: "FFFFFFFF" },
  "SELLING AT A LOSS": { bg: "FF7B1FA2", font: "FFFFFFFF" },
};

const wb = new ExcelJS.Workbook();
await wb.xlsx.readFile('AmericanSelect_PricingAnalysis.xlsx');
const ws = wb.getWorksheet('Pricing Analysis');

const catColors = {
  'DEODORANTS (MEN)':   'FFFFE0B2',
  'DEODORANTS (WOMEN)': 'FFFFF9C4',
  'PERFUMES (MEN)':     'FFE1BEE7',
  'PERFUMES (WOMEN)':   'FFFCE4EC',
};

let lastCat = null;
let shade = false;

newRows.forEach(([category, product, us, current, suggested, status]) => {
  if (category !== lastCat) { shade = !shade; lastCat = category; }

  const row = ws.addRow([category, product, us, current, suggested, status]);
  row.height = 18;

  const col = statusColors[status] ?? { bg: 'FFFFFFFF', font: 'FF000000' };
  const catBg = catColors[category] ?? 'FFEEEEEE';
  const baseBg = shade ? 'FFF9F9F9' : 'FFFFFFFF';

  row.getCell(1).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: catBg } };
  row.getCell(1).font = { bold: true, size: 10, color: { argb: 'FF4A0070' } };

  row.getCell(2).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: baseBg } };
  row.getCell(2).font = { size: 10 };

  row.getCell(3).value = us;
  row.getCell(3).numFmt = '"$"#,##0.00';
  row.getCell(3).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: baseBg } };
  row.getCell(3).alignment = { horizontal: 'center' };

  row.getCell(4).value = current;
  row.getCell(4).numFmt = '#,##0 "XAF"';
  row.getCell(4).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: baseBg } };
  row.getCell(4).alignment = { horizontal: 'center' };

  row.getCell(5).value = suggested;
  row.getCell(5).numFmt = '#,##0 "XAF"';
  row.getCell(5).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFFF8E1' } };
  row.getCell(5).font = { bold: true, size: 10 };
  row.getCell(5).alignment = { horizontal: 'center' };

  const sc = row.getCell(6);
  sc.value = status;
  sc.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: col.bg } };
  sc.font = { bold: true, size: 10, color: { argb: col.font } };
  sc.alignment = { horizontal: 'center', vertical: 'middle' };

  row.eachCell(c => {
    c.border = { bottom: { style: 'hair', color: { argb: 'FFDDDDDD' } } };
    c.alignment = { ...c.alignment, vertical: 'middle' };
  });
});

// Update Summary sheet
const summary = wb.getWorksheet('Summary by Category');
const newCats = { 'DEODORANTS (MEN)': {}, 'DEODORANTS (WOMEN)': {}, 'PERFUMES (MEN)': {}, 'PERFUMES (WOMEN)': {} };
newRows.forEach(([cat, , us, cur, sug]) => {
  if (!newCats[cat].count) { newCats[cat] = { count: 0, us: 0, cur: 0, sug: 0 }; }
  newCats[cat].count++; newCats[cat].us += us; newCats[cat].cur += cur; newCats[cat].sug += sug;
});

const sumCatColors = {
  'DEODORANTS (MEN)':   'FFFFE0B2',
  'DEODORANTS (WOMEN)': 'FFFFF9C4',
  'PERFUMES (MEN)':     'FFE1BEE7',
  'PERFUMES (WOMEN)':   'FFFCE4EC',
};

Object.entries(newCats).forEach(([cat, d]) => {
  if (!d.count) return;
  const row = summary.addRow([cat, d.count, +(d.us/d.count).toFixed(2), Math.round(d.cur/d.count), Math.round(d.sug/d.count)]);
  row.height = 22;
  const bg = sumCatColors[cat] ?? 'FFFFFFFF';
  row.getCell(1).font = { bold: true };
  row.getCell(3).numFmt = '"$"#,##0.00';
  row.getCell(4).numFmt = '#,##0 "XAF"';
  row.getCell(5).numFmt = '#,##0 "XAF"';
  row.eachCell(c => {
    c.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: bg } };
    c.alignment = { vertical: 'middle', horizontal: 'center' };
    c.border = { bottom: { style: 'thin', color: { argb: 'FFCCCCCC' } } };
  });
  row.getCell(1).alignment = { horizontal: 'left', vertical: 'middle' };
});

await wb.xlsx.writeFile('AmericanSelect_PricingAnalysis.xlsx');
console.log(`Done — added ${newRows.length} rows to AmericanSelect_PricingAnalysis.xlsx`);
