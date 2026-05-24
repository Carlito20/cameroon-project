/**
 * Downloads products-list.json from the live site before building
 * so that prices updated via the admin panel are preserved in the build.
 */

import { writeFileSync } from 'fs';
import { resolve, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const outputPath = resolve(__dirname, '../public/api/products-list.json');
const url = 'https://americanselect.net/api/products-list.json';

try {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), 8000);
  const res = await fetch(url, { signal: controller.signal });
  clearTimeout(timer);
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  const data = await res.text();
  JSON.parse(data); // validate it's real JSON before overwriting
  writeFileSync(outputPath, data);
  console.log('✓ Synced products-list.json from live site');
} catch (e) {
  console.warn(`⚠ Could not sync from live site (${e.message}) — using local file`);
}
