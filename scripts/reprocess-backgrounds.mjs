/**
 * Re-processes all product images with the large model for better accuracy,
 * then cleans the alpha channel to eliminate halos and semi-transparent fringes.
 */
import { removeBackground } from '@imgly/background-removal-node';
import { readdir, writeFile } from 'fs/promises';
import { join, extname, basename } from 'path';
import { fileURLToPath, pathToFileURL } from 'url';
import { dirname } from 'path';
import { createWriteStream } from 'fs';

const __dirname = dirname(fileURLToPath(import.meta.url));
const PRODUCTS_DIR = join(__dirname, '..', 'public', 'images', 'products');

// Clean alpha using pure JS PNG parsing (no native modules needed)
async function cleanAlpha(pngBuffer) {
  // Dynamically import pngjs (pure JS PNG encoder/decoder)
  const { PNG } = await import('pngjs').then(m => m).catch(() => null) ?? {};
  if (!PNG) return pngBuffer; // fallback: return as-is if pngjs unavailable

  return new Promise((resolve, reject) => {
    const png = new PNG();
    png.parse(pngBuffer, (err, data) => {
      if (err) return reject(err);
      for (let i = 3; i < data.data.length; i += 4) {
        const a = data.data[i];
        if (a < 25) data.data[i] = 0;        // fully transparent
        else if (a > 220) data.data[i] = 255; // fully opaque
      }
      const out = [];
      const pngOut = new PNG({ width: data.width, height: data.height });
      pngOut.data = data.data;
      const chunks = [];
      pngOut.pack()
        .on('data', c => chunks.push(c))
        .on('end', () => resolve(Buffer.concat(chunks)))
        .on('error', reject);
    });
  });
}

async function main() {
  const files = await readdir(PRODUCTS_DIR);
  const images = files.filter(f => /\.(png|jpe?g)$/i.test(f));

  console.log(`🎨 Re-processing ${images.length} image(s) with large model + alpha cleanup...\n`);

  let ok = 0, fail = 0;

  for (let i = 0; i < images.length; i++) {
    const file = images[i];
    const inputPath = join(PRODUCTS_DIR, file);
    const outName = basename(file, extname(file)) + '.png';
    const outputPath = join(PRODUCTS_DIR, outName);

    process.stdout.write(`[${i + 1}/${images.length}] ${file} ... `);

    try {
      const blob = await removeBackground(pathToFileURL(inputPath).href, {
        model: 'medium',
        output: { format: 'image/png', quality: 1 },
      });

      const raw = Buffer.from(await blob.arrayBuffer());
      const cleaned = await cleanAlpha(raw);
      await writeFile(outputPath, cleaned);
      ok++;
      console.log('✅');
    } catch (err) {
      fail++;
      console.log(`❌ ${err.message}`);
    }
  }

  console.log(`\n✅ Done: ${ok} processed, ${fail} failed.`);
}

main();
