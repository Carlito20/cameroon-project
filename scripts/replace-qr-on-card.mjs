/**
 * Replaces the QR code on the business card PDF (page 2, back side)
 * with the new branded QR code.
 */

import { PDFDocument } from 'pdf-lib';
import { readFileSync, writeFileSync } from 'fs';
import { resolve, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const ROOT = resolve(__dirname, '..');

const pdfPath    = resolve(ROOT, 'business-card.pdf');
const qrPath     = resolve(ROOT, 'Images/QR/americanselect-qr.png');
const outputPath = resolve(ROOT, 'business-card-updated.pdf');

// Page 2 (back): 816 x 1035.12 pts
// QR code box position on page (estimated from visual inspection):
// x from left: 642, y from bottom: 742, width: 114, height: 114
const QR_X = 642;
const QR_Y = 742;
const QR_W = 114;
const QR_H = 114;

const pdfBytes = readFileSync(pdfPath);
const qrBytes  = readFileSync(qrPath);

const pdf = await PDFDocument.load(pdfBytes);
const qrImage = await pdf.embedPng(qrBytes);

// Work on page 2 (index 1) — the back of the card
const page = pdf.getPages()[1];

// Draw white rectangle to cover existing QR code
page.drawRectangle({
  x: QR_X - 2,
  y: QR_Y - 2,
  width: QR_W + 4,
  height: QR_H + 4,
  color: { type: 'RGB', red: 1, green: 1, blue: 1 },
});

// Draw new QR code
page.drawImage(qrImage, {
  x: QR_X,
  y: QR_Y,
  width: QR_W,
  height: QR_H,
});

writeFileSync(outputPath, await pdf.save());
console.log(`✓ Saved → ${outputPath}`);
