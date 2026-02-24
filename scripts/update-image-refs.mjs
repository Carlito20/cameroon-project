import { readFile, writeFile } from 'fs/promises';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const CATEGORIES_FILE = join(__dirname, '..', 'src', 'data', 'categories.ts');

async function main() {
  let content = await readFile(CATEGORIES_FILE, 'utf-8');
  const original = content;

  // Replace /images/products/xxx.jpg and .jpeg with .png
  content = content.replace(/\/images\/products\/([^'"]+)\.(jpe?g)/g, '/images/products/$1.png');

  if (content === original) {
    console.log('✅ No .jpg/.jpeg product image references found — already up to date.');
    return;
  }

  await writeFile(CATEGORIES_FILE, content, 'utf-8');
  const count = (original.match(/\/images\/products\/[^'"]+\.jpe?g/g) || []).length;
  console.log(`✅ Updated ${count} image reference(s) in categories.ts from .jpg/.jpeg → .png`);
}

main();
