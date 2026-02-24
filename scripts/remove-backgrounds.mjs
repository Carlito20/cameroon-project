import { removeBackground } from '@imgly/background-removal-node';
import { readdir, readFile, writeFile, unlink, access } from 'fs/promises';
import { join, extname, basename } from 'path';
import { fileURLToPath, pathToFileURL } from 'url';
import { dirname } from 'path';

const __dirname = dirname(fileURLToPath(import.meta.url));
const PRODUCTS_DIR = join(__dirname, '..', 'public', 'images', 'products');
const DONE_FILE = join(__dirname, '..', 'public', 'images', '.bg-removed');

// Load already-processed list
async function loadDoneList() {
  try {
    const content = await readFile(DONE_FILE, 'utf-8');
    return new Set(content.split('\n').filter(Boolean));
  } catch {
    return new Set();
  }
}

async function saveDoneList(done) {
  await writeFile(DONE_FILE, [...done].join('\n') + '\n');
}

async function fileExists(path) {
  try { await access(path); return true; } catch { return false; }
}

async function main() {
  const files = await readdir(PRODUCTS_DIR);
  const images = files.filter(f => /\.(jpe?g|png|webp)$/i.test(f));
  const done = await loadDoneList();

  const toProcess = images.filter(f => {
    const base = basename(f, extname(f));
    const outName = base + '.png';
    return !done.has(outName);
  });

  if (toProcess.length === 0) {
    console.log('✅ All product images already have transparent backgrounds.');
    return;
  }

  console.log(`🎨 Processing ${toProcess.length} image(s) (${images.length - toProcess.length} already done)...\n`);

  let successCount = 0;
  let failCount = 0;

  for (let i = 0; i < toProcess.length; i++) {
    const file = toProcess[i];
    const base = basename(file, extname(file));
    const inputPath = join(PRODUCTS_DIR, file);
    const outName = base + '.png';
    const outputPath = join(PRODUCTS_DIR, outName);

    process.stdout.write(`[${i + 1}/${toProcess.length}] ${file} → ${outName} ... `);

    try {
      const fileUrl = pathToFileURL(inputPath).href;
      const blob = await removeBackground(fileUrl, {
        model: 'medium',
        output: { format: 'image/png', quality: 1 },
      });

      const arrayBuffer = await blob.arrayBuffer();
      const pngBuffer = Buffer.from(arrayBuffer);
      await writeFile(outputPath, pngBuffer);

      // Remove original if it had a different extension
      if (extname(file).toLowerCase() !== '.png') {
        await unlink(inputPath);
      }

      done.add(outName);
      await saveDoneList(done);
      successCount++;
      console.log('✅');
    } catch (err) {
      failCount++;
      console.log(`❌ ${err.message}`);
    }
  }

  console.log(`\n✅ Done: ${successCount} converted, ${failCount} failed.`);
  if (successCount > 0) {
    console.log('\n⚠️  Image file extensions changed to .png — run the update-image-refs script next.');
  }
}

main();
