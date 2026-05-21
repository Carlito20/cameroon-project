import { removeBackground } from '@imgly/background-removal-node';
import { PNG } from 'pngjs';
import { writeFile } from 'fs/promises';
import { pathToFileURL } from 'url';

const images = [
  'Images/Today/WhatsApp Image 2026-05-17 at 5.42.37 P.jpeg',
];

// Flatten transparent PNG onto white background, return PNG buffer
function flattenOntoWhite(pngBuffer) {
  const src = PNG.sync.read(pngBuffer);
  const out = new PNG({ width: src.width, height: src.height });

  for (let y = 0; y < src.height; y++) {
    for (let x = 0; x < src.width; x++) {
      const i = (src.width * y + x) * 4;
      const alpha = src.data[i + 3] / 255;
      out.data[i]     = Math.round(src.data[i]     * alpha + 255 * (1 - alpha));
      out.data[i + 1] = Math.round(src.data[i + 1] * alpha + 255 * (1 - alpha));
      out.data[i + 2] = Math.round(src.data[i + 2] * alpha + 255 * (1 - alpha));
      out.data[i + 3] = 255;
    }
  }
  return PNG.sync.write(out);
}

for (const src of images) {
  const outPath = src.replace(/\.jpeg$/i, '-white-bg.png');
  process.stdout.write(`Processing: ${src} ...\n`);
  try {
    const blob = await removeBackground(pathToFileURL(src).href, {
      model: 'medium',
      output: { format: 'image/png', quality: 1 },
    });
    const transparentPng = Buffer.from(await blob.arrayBuffer());
    const whiteBgPng = flattenOntoWhite(transparentPng);
    await writeFile(outPath, whiteBgPng);
    console.log(`✅ Saved: ${outPath}\n`);
  } catch (err) {
    console.log(`❌ ${err.message}\n`);
  }
}
console.log('Done.');
