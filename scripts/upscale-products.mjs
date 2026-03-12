import sharp from 'sharp';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const productsDir = path.join(__dirname, '..', 'public', 'images', 'products');

const files = fs.readdirSync(productsDir).filter(f => f.endsWith('.webp'));

console.log(`Upscaling ${files.length} product images to 1400×1400...`);

let done = 0;
let skipped = 0;

for (const file of files) {
  const filePath = path.join(productsDir, file);
  try {
    const inputBuffer = fs.readFileSync(filePath);
    const meta = await sharp(inputBuffer).metadata();
    // Skip if already at or above target size
    if (meta.width >= 1380 && meta.height >= 1380) {
      skipped++;
      continue;
    }
    const buffer = await sharp(inputBuffer)
      .resize(1400, 1400, { fit: 'inside', kernel: 'lanczos3' })
      .modulate({ brightness: 1.02, saturation: 1.1 })
      .linear(1.06, -3)
      .sharpen({ sigma: 0.7, m1: 1.2, m2: 0.8 })
      .webp({ quality: 95 })
      .toBuffer();
    fs.writeFileSync(filePath, buffer);
    done++;
    if (done % 20 === 0) console.log(`  ${done}/${files.length - skipped} upscaled...`);
  } catch (e) {
    console.error(`  Error on ${file}:`, e.message);
  }
}

console.log(`Done. ${done} upscaled, ${skipped} already large enough.`);
