/**
 * Replaces watermarked featured product images with clean source images.
 * Converts PNG sources → JPEG, resizes to 800x800 (object-fit: cover, so ratio doesn't matter).
 */
import sharp from 'sharp';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const DEST = path.join(ROOT, 'public/images/products');

// [source (relative to ROOT), destination filename in public/images/products/]
const REPLACEMENTS = [
  // Body Care
  ['Images/Aveeno Lotion 18oz.jpg',                                                                                                                      'aveeno-lotion.jpg'],
  ['Images/Dr Teals/Dr Teal\'s Body Wash Relax and Relief with Eucalyptus Spearmint.png',                                                               'dr-teals-eucalyptus1.jpg'],
  ['Images/Dr Teals/Dr Teal\'s Body Lotion with Prebiotic Lemon Balm, Sage, & Thyme Essential Oil Blend.png',                                           'dr-teals-lemon-balm1.jpg'],
  ["Images/Dr Teals/Dr Teal's 24 Hour Moisture+ Body Lotion, Coconut Oil & Essential Oils1.png",                                                        'dr-teals-lotion-coconut1.jpg'],
  ["Images/Dr Teals/Dr Teal's 24 Hour Moisture+ Body Lotion, Lavender Essential Oil1.png",                                                              'dr-teals-lotion-lavender1.jpg'],
  ["Images/Dr Teals/Dr Teal's Kids 3-in-1 Bubble Bath, Body Wash & Shampoo with Melatonin & Essential Oil.png",                                        'dr-teals-kids.jpg'],
  ['Images/Jergens/Jergens Ultra Healing Dry Skin Moisturizer, White.png',                                                                               'jergens-ultra1.jpg'],
  ['Images/Jergens/Jergens Hydrating Coconut Body Lotion, Hand and Body Moisturizer, Infused with Coconut Oil, Hydrates Dry Skin Instantly.png',         'jergens-coconut1.jpg'],
  ['Images/Jergens/Jergens Shea Butter Hand and Body Lotion, Deep Conditioning Moisturizer, 3X More Radiant Skin, with Pure Shea Butter.png',            'jergens-shea1.jpg'],
  ['Images/Olay/Olay Essential Botanicals Body Wash, Spiced Chai & Pear.png',                                                                            'olay-spiced-chai.jpg'],
  ['Images/Olay/Olay Essential Botanicals Body Wash, White Tea & Cucumber.png',                                                                          'olay-white-tea.jpeg'],

  // Personal Care
  ["Images/Electronics/Men's Electric Shaver 3 in 1 - Portable USB Rechargeable Shaver Featuring 3D Floating Blades and a Digital Display Suitable for Both Wet and Dry Shaving.png",
                                                                                                                                                          'mens-electric-shaver-1.jpeg'],
  ["Images/Electronics/Men's Electric Shaver  Exquisite Packaging Box, USB Charging, Lithium Battery, Matte Texture, Essential for Men, Beard Trimming.png",
                                                                                                                                                          'mens-shaver-matte-1.jpeg'],

  // Electronics
  ['Images/Electronics/Acer OHR544 Wireless Headset black.png',                                                                                          'acer-tws-headset-1.jpeg'],
  ['Images/Electronics/Nokia Go Earbuds+ TWS-201.png',                                                                                                   'nokia-earbuds-1.jpeg'],
  ['Images/Electronics/Hyundai LP5t Wireless Headphones with Surround Sound and Noise Cancellation.png',                                                 'hyundai-lp5t-1.jpeg'],
  ['Images/Electronics/Portable Wireless Speaker, 15W Stereo, RGB Lighting, Suitable for Both Indoor and Outdoor Use.png',                               'portable-speaker-rgb-1.jpeg'],
  ['Images/Electronics/Rechargeable Arm Blood Pressure Monitor with Large LED Screen, Digital Blood Pressure Machine.png',                               'arm-bp-monitor-1.jpeg'],

  // Kitchen
  ['Images/Manual Pasta Maker Machine, 9 Adjustable Thickness Settings.png',                                                                             'manual-pasta-maker-1.jpg'],
  ['Images/Chopper edit.jpeg',                                                                                                                            'manual-food-chopper.jpeg'],
];

import fs from 'fs';

async function run() {
  let ok = 0, fail = 0;
  for (const [relSrc, destFile] of REPLACEMENTS) {
    const src = path.join(ROOT, relSrc);
    const dest = path.join(DEST, destFile);
    try {
      await sharp(src)
        .resize(800, 800, { fit: 'inside', withoutEnlargement: true, kernel: 'lanczos3' })
        .jpeg({ quality: 95, mozjpeg: true })
        .toFile(dest);
      console.log(`  ✓ ${destFile}`);
      ok++;

      // Also regenerate the .webp counterpart if it exists
      const ext = path.extname(destFile);
      const webpFile = destFile.slice(0, -ext.length) + '.webp';
      const webpDest = path.join(DEST, webpFile);
      if (fs.existsSync(webpDest)) {
        await sharp(src)
          .resize(800, 800, { fit: 'inside', withoutEnlargement: true, kernel: 'lanczos3' })
          .webp({ quality: 90 })
          .toFile(webpDest);
        console.log(`  ✓ ${webpFile}`);
        ok++;
      }
    } catch (err) {
      console.error(`  ✗ ${destFile}: ${err.message}`);
      fail++;
    }
  }
  console.log(`\nDone: ${ok} replaced, ${fail} failed.`);
}

run();
