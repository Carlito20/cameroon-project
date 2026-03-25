/**
 * Converts business-card-new.html to business-card-new.pdf using Puppeteer.
 * Card size: 3.5in × 2in (standard business card)
 */
import puppeteer from 'puppeteer';
import { resolve, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const ROOT = resolve(__dirname, '..');

const inputPath  = resolve(ROOT, 'business-card-new.html');
const outputPath = resolve(ROOT, 'business-card-new.pdf');

const browser = await puppeteer.launch({ headless: true });
const page = await browser.newPage();

// Load the HTML file
await page.goto('file:///' + inputPath.replace(/\\/g, '/'), { waitUntil: 'networkidle0' });
await new Promise(r => setTimeout(r, 1000));

// Generate PDF at exact business card size: 3.5in × 2in
await page.pdf({
  path: outputPath,
  width:  '3.5in',
  height: '2in',
  printBackground: true,
  margin: { top: 0, right: 0, bottom: 0, left: 0 },
});

await browser.close();
console.log(`✓ PDF saved → ${outputPath}`);
