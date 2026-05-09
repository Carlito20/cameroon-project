/**
 * Reads AmericanSelect_PricingAnalysis.xlsx and pushes the
 * "Suggested Price (XAF)" column (col 5) to the live site's
 * price API as admin-authenticated price overrides.
 *
 * Usage:  node scripts/import-prices-from-excel.mjs
 *
 * The script skips any row where the suggested price is 0 or blank.
 * It prints a summary at the end showing success / failure counts.
 */

import ExcelJS from 'exceljs';
import https from 'https';
import http from 'http';
import { URL } from 'url';

// ── Config ──────────────────────────────────────────────────
const SITE_BASE   = 'https://americanselect.net';
const LOGIN_URL   = `${SITE_BASE}/admin/index.php`;
const PRICE_URL   = `${SITE_BASE}/api/price.php`;
const EXCEL_FILE  = 'AmericanSelect_PricingAnalysis.xlsx';
const SHEET_NAME  = 'Pricing Analysis';

// Admin credentials (same as dashboard login)
const ADMIN_PASS  = 'Stock$Data#2024!';
// ────────────────────────────────────────────────────────────

// Minimal cookie-jar for session handling
let sessionCookie = '';

function request(urlStr, options = {}, body = null) {
  return new Promise((resolve, reject) => {
    const u = new URL(urlStr);
    const lib = u.protocol === 'https:' ? https : http;
    const reqOptions = {
      hostname: u.hostname,
      port: u.port || (u.protocol === 'https:' ? 443 : 80),
      path: u.pathname + u.search,
      method: options.method || 'GET',
      headers: {
        'User-Agent': 'AmericanSelect-PriceImporter/1.0',
        ...(sessionCookie ? { Cookie: sessionCookie } : {}),
        ...options.headers,
      },
      rejectUnauthorized: false, // cPanel self-signed certs
    };

    const req = lib.request(reqOptions, res => {
      // Capture Set-Cookie on login redirect
      if (res.headers['set-cookie']) {
        sessionCookie = res.headers['set-cookie']
          .map(c => c.split(';')[0])
          .join('; ');
      }
      let data = '';
      res.on('data', chunk => (data += chunk));
      res.on('end', () => resolve({ status: res.statusCode, headers: res.headers, body: data }));
    });

    req.on('error', reject);
    if (body) req.write(body);
    req.end();
  });
}

async function login() {
  console.log('Logging in to admin panel…');
  const body = new URLSearchParams({ password: ADMIN_PASS }).toString();
  const res = await request(LOGIN_URL, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'Content-Length': Buffer.byteLength(body),
    },
  }, body);

  // Successful login redirects to dashboard
  if (res.status === 302 || res.status === 200) {
    if (!sessionCookie) throw new Error('Login failed — no session cookie returned.');
    console.log('Login successful.\n');
  } else {
    throw new Error(`Login failed — HTTP ${res.status}`);
  }
}

async function setPrice(name, price) {
  const body = JSON.stringify({ name, price });
  const res = await request(PRICE_URL, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Content-Length': Buffer.byteLength(body),
    },
  }, body);
  const json = JSON.parse(res.body);
  return json.success === true;
}

async function main() {
  // ── 1. Read Excel ────────────────────────────────────────
  const wb = new ExcelJS.Workbook();
  await wb.xlsx.readFile(EXCEL_FILE);
  const ws = wb.getWorksheet(SHEET_NAME);
  if (!ws) throw new Error(`Sheet "${SHEET_NAME}" not found in ${EXCEL_FILE}`);

  const rows = [];
  ws.eachRow((row, i) => {
    if (i === 1) return; // skip header
    const product  = String(row.getCell(2).value ?? '').trim();
    const suggested = Number(row.getCell(5).value ?? 0);
    if (product && suggested > 0) rows.push({ product, price: suggested });
  });

  if (rows.length === 0) {
    console.log('No rows with a suggested price found. Nothing to import.');
    return;
  }

  console.log(`Found ${rows.length} products to update.\n`);

  // ── 2. Authenticate ──────────────────────────────────────
  await login();

  // ── 3. Push prices ───────────────────────────────────────
  let ok = 0, fail = 0;
  const failed = [];

  for (const { product, price } of rows) {
    process.stdout.write(`  Updating: ${product.slice(0, 60).padEnd(62)} ${price.toLocaleString()} XAF … `);
    try {
      const success = await setPrice(product, price);
      if (success) { ok++; console.log('OK'); }
      else          { fail++; failed.push(product); console.log('FAILED'); }
    } catch (e) {
      fail++;
      failed.push(product);
      console.log(`ERROR: ${e.message}`);
    }
  }

  // ── 4. Summary ───────────────────────────────────────────
  console.log('\n──────────────────────────────────────');
  console.log(`Done.  ${ok} updated,  ${fail} failed.`);
  if (failed.length) {
    console.log('\nFailed products:');
    failed.forEach(p => console.log(`  • ${p}`));
  }
}

main().catch(e => { console.error(e.message); process.exit(1); });
