import puppeteer from 'puppeteer';
import { resolve, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const ROOT = resolve(__dirname, '..');
const pdfFile = process.argv[2] || 'business-card-new.pdf';
const outFile = process.argv[3] || 'business-card-preview.png';

const browser = await puppeteer.launch({ headless: true });
const page = await browser.newPage();
await page.setViewport({ width: 1400, height: 900 });
await page.goto('file:///' + resolve(ROOT, pdfFile).replace(/\\/g, '/'), { waitUntil: 'networkidle0' });
await new Promise(r => setTimeout(r, 3000));
await page.screenshot({ path: resolve(ROOT, outFile), fullPage: true });
await browser.close();
console.log('Preview saved:', outFile);
