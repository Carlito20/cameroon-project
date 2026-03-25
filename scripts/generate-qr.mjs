/**
 * Generates a branded QR code with AS logo in center.
 * Brand colors: black #111111 (dots) on white, gold #f0a500 (border/frame)
 * Output: Images/QR/americanselect-qr.png (high-res, print + social ready)
 */

import QRCode from 'qrcode';
import sharp from 'sharp';
import { mkdirSync } from 'fs';
import { resolve, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const ROOT = resolve(__dirname, '..');

const URL        = 'https://americanselect.net';
const QR_SIZE    = 1200;   // px — high-res for print (300dpi @ ~1 inch = 300px, so 1200 = ~4 inch)
const LOGO_RATIO = 0.22;   // logo takes 22% of QR width (max ~30% for error correction level H)
const PADDING    = 60;     // white padding around QR inside gold frame
const BORDER     = 18;     // gold border thickness
const RADIUS     = 40;     // corner radius of outer frame
const GOLD       = { r: 240, g: 165, b: 0 };
const BLACK      = '#111111';

const logoPath   = resolve(ROOT, 'Images/Pages/AS Logo new.png');
const outputDir  = resolve(ROOT, 'Images/QR');
const outputPath = resolve(outputDir, 'americanselect-qr.png');

mkdirSync(outputDir, { recursive: true });

console.log('Generating QR code...');

// 1. Generate QR code as PNG buffer (dark = brand black, light = white)
const qrBuffer = await QRCode.toBuffer(URL, {
  errorCorrectionLevel: 'H',  // highest — allows 30% obstruction for logo
  type: 'png',
  width: QR_SIZE,
  margin: 2,
  color: {
    dark: BLACK,
    light: '#ffffff',
  },
});

// 2. Prepare logo — resize to fit in center, circular mask
const logoSize = Math.round(QR_SIZE * LOGO_RATIO);
const logoWhitePad = Math.round(logoSize * 0.12); // white halo around logo

// Circular mask for logo
const circleMask = Buffer.from(
  `<svg width="${logoSize}" height="${logoSize}">
    <circle cx="${logoSize / 2}" cy="${logoSize / 2}" r="${logoSize / 2}" fill="white"/>
  </svg>`
);

const logoComposite = await sharp(logoPath)
  .resize(logoSize - logoWhitePad * 2, logoSize - logoWhitePad * 2, { fit: 'contain', background: { r: 255, g: 255, b: 255, alpha: 1 } })
  .extend({ top: logoWhitePad, bottom: logoWhitePad, left: logoWhitePad, right: logoWhitePad, background: { r: 255, g: 255, b: 255, alpha: 1 } })
  .composite([{ input: circleMask, blend: 'dest-in' }])
  .png()
  .toBuffer();

// 3. Composite logo onto center of QR
const logoX = Math.round((QR_SIZE - logoSize) / 2);
const logoY = Math.round((QR_SIZE - logoSize) / 2);

const qrWithLogo = await sharp(qrBuffer)
  .composite([{ input: logoComposite, left: logoX, top: logoY }])
  .png()
  .toBuffer();

// 4. Add gold border frame with rounded corners
const totalSize = QR_SIZE + PADDING * 2 + BORDER * 2;

// Gold rounded-rect background
const goldFrame = Buffer.from(
  `<svg width="${totalSize}" height="${totalSize}">
    <rect x="0" y="0" width="${totalSize}" height="${totalSize}" rx="${RADIUS}" ry="${RADIUS}" fill="#f0a500"/>
    <rect x="${BORDER}" y="${BORDER}" width="${totalSize - BORDER * 2}" height="${totalSize - BORDER * 2}" rx="${RADIUS - 6}" ry="${RADIUS - 6}" fill="white"/>
  </svg>`
);

const final = await sharp({
  create: { width: totalSize, height: totalSize, channels: 4, background: { r: 255, g: 255, b: 255, alpha: 0 } }
})
  .composite([
    { input: goldFrame, left: 0, top: 0 },
    { input: qrWithLogo, left: PADDING + BORDER, top: PADDING + BORDER },
  ])
  .png({ compressionLevel: 9 })
  .toBuffer();

await sharp(final).toFile(outputPath);

console.log(`✓ QR code saved → ${outputPath}`);
console.log(`  Size: ${totalSize}×${totalSize}px — print-ready (300dpi ~${(totalSize/300).toFixed(1)}" × ${(totalSize/300).toFixed(1)}")`);
console.log(`  Also suitable for social media profiles and posts`);
