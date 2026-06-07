import ExcelJS from 'exceljs';

const STATUS_STYLES = {
  'Good margin':          { bg: '1E8449', font: 'FFFFFF' }, // dark green
  'Could go higher':      { bg: '2E86C1', font: 'FFFFFF' }, // blue
  'Acceptable':           { bg: 'F39C12', font: 'FFFFFF' }, // orange
  'Slight raise needed':  { bg: 'E67E22', font: 'FFFFFF' }, // dark orange
  'SELLING AT A LOSS':    { bg: 'C0392B', font: 'FFFFFF' }, // red
  'No US price data':     { bg: 'BDC3C7', font: '555555' }, // grey
};

const HEADER_BG   = '1C2833'; // near-black
const HEADER_FONT = 'F0A500'; // gold (matches site brand)
const ALT_ROW_BG  = 'F2F3F4'; // very light grey for alternating rows

function applyHeader(row) {
  row.eachCell(cell => {
    cell.fill   = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF' + HEADER_BG } };
    cell.font   = { bold: true, color: { argb: 'FF' + HEADER_FONT }, size: 11 };
    cell.border = {
      bottom: { style: 'medium', color: { argb: 'FFF0A500' } },
    };
    cell.alignment = { vertical: 'middle', horizontal: 'center', wrapText: true };
  });
  row.height = 32;
}

function styleStatusCell(cell, status) {
  const s = STATUS_STYLES[status];
  if (!s) return;
  cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF' + s.bg } };
  cell.font = { bold: true, color: { argb: 'FF' + s.font }, size: 10 };
}

async function styleSheet() {
  const wb = new ExcelJS.Workbook();
  await wb.xlsx.readFile('AmericanSelect_PricingAnalysis_June2026.xlsx');

  // ── PRICING ANALYSIS SHEET ────────────────────────────────
  const pa = wb.getWorksheet('Pricing Analysis');
  pa.views = [{ state: 'frozen', ySplit: 1 }]; // freeze header row
  pa.autoFilter = { from: 'A1', to: 'H1' };   // enable sort/filter dropdowns on all columns

  // Column widths
  const colWidths = [18, 60, 8, 18, 16, 16, 10, 20];
  pa.columns.forEach((col, i) => { col.width = colWidths[i] || 15; });

  pa.eachRow((row, rowNum) => {
    if (rowNum === 1) { applyHeader(row); return; }

    const statusCell = row.getCell(8); // Status column
    const status = statusCell.value;

    row.eachCell({ includeEmpty: true }, (cell, colNum) => {
      // Alternating row background (overridden by status on status col)
      if (rowNum % 2 === 0) {
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF' + ALT_ROW_BG } };
      }
      cell.alignment = { vertical: 'middle', wrapText: colNum === 2 };
      cell.font = { ...cell.font, size: 10 };
      // Light border
      cell.border = {
        top:    { style: 'hair', color: { argb: 'FFDDDDDD' } },
        bottom: { style: 'hair', color: { argb: 'FFDDDDDD' } },
        left:   { style: 'hair', color: { argb: 'FFDDDDDD' } },
        right:  { style: 'hair', color: { argb: 'FFDDDDDD' } },
      };
    });

    row.height = 20;

    // Colour the Status cell
    styleStatusCell(statusCell, status);

    // Colour the Margin % cell based on status too
    const marginCell = row.getCell(7);
    styleStatusCell(marginCell, status);

    // Bold the price column
    row.getCell(4).font = { bold: true, size: 10 };
  });

  // ── SUMMARY SHEET ─────────────────────────────────────────
  const sum = wb.getWorksheet('Summary by Category');
  sum.views = [{ state: 'frozen', ySplit: 1 }];
  sum.autoFilter = { from: 'A1', to: 'J1' };
  sum.columns = [
    { width: 22 }, { width: 10 }, { width: 16 }, { width: 22 },
    { width: 14 }, { width: 16 }, { width: 12 }, { width: 13 },
    { width: 16 }, { width: 14 },
  ];

  sum.eachRow((row, rowNum) => {
    if (rowNum === 1) { applyHeader(row); return; }

    const isTotals = row.getCell(1).value === 'TOTAL';

    row.eachCell({ includeEmpty: true }, cell => {
      cell.alignment = { vertical: 'middle', horizontal: 'center' };
      cell.font = { size: 10, bold: isTotals };
      cell.border = {
        top:    { style: 'hair', color: { argb: 'FFDDDDDD' } },
        bottom: { style: isTotals ? 'medium' : 'hair', color: { argb: 'FFAAAAAA' } },
        left:   { style: 'hair', color: { argb: 'FFDDDDDD' } },
        right:  { style: 'hair', color: { argb: 'FFDDDDDD' } },
      };
    });

    if (isTotals) {
      row.eachCell(cell => {
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF' + HEADER_BG } };
        cell.font = { bold: true, color: { argb: 'FF' + HEADER_FONT }, size: 10 };
      });
    } else if (rowNum % 2 === 0) {
      row.eachCell(cell => {
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF' + ALT_ROW_BG } };
      });
    }

    // Color-code the status count cells
    const colMap = { 5: 'Good margin', 6: 'Could go higher', 7: 'Acceptable', 8: 'Slight raise needed', 9: 'SELLING AT A LOSS' };
    Object.entries(colMap).forEach(([col, status]) => {
      const cell = row.getCell(parseInt(col));
      const val = cell.value;
      if (!isTotals && typeof val === 'number' && val > 0 && STATUS_STYLES[status]) {
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF' + STATUS_STYLES[status].bg } };
        cell.font = { bold: true, color: { argb: 'FF' + STATUS_STYLES[status].font }, size: 10 };
      }
    });

    row.height = 22;
  });

  // ── LEGEND SHEET ──────────────────────────────────────────
  const leg = wb.getWorksheet('Legend');
  leg.columns = [{ width: 22 }, { width: 65 }];

  leg.eachRow((row, rowNum) => {
    if (rowNum === 1) { applyHeader(row); return; }
    const status = row.getCell(1).value;
    row.height = 20;
    row.eachCell(cell => {
      cell.alignment = { vertical: 'middle' };
      cell.font = { size: 10 };
    });
    if (STATUS_STYLES[status]) {
      styleStatusCell(row.getCell(1), status);
      row.getCell(2).font = { size: 10, italic: true };
    }
  });

  await wb.xlsx.writeFile('AmericanSelect_PricingAnalysis_June2026.xlsx');
  console.log('Styled and saved: AmericanSelect_PricingAnalysis_June2026.xlsx');
}

styleSheet().catch(console.error);
