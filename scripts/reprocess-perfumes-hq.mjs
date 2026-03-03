import sharp from 'sharp';
import fs from 'fs';

const TARGET_W = 944;
const TARGET_H = 1200;
const QUALITY = 95;

const jobs = [
  { src: 'Images/Perfumes/Paris Night Just for Men.png',                    dest: 'public/images/products/paris-night-just-for-men.webp' },
  { src: 'Images/Perfumes/Infinity 3.3 oz EDT For Men.png',                 dest: 'public/images/products/infinity-edt-men.webp' },
  { src: 'Images/Perfumes/Platinum 3.4 oz EDP For Men.png',                 dest: 'public/images/products/platinum-edp-men.webp' },
  { src: 'Images/Perfumes/Savage 3.4 oz EDP For Men.png',                   dest: 'public/images/products/savage-edp-men.webp' },
  { src: 'Images/Perfumes/Taj Max Aqua Sport 3.4 oz Long-Lasting Perfume.png', dest: 'public/images/products/taj-max-aqua-sport.webp' },
  { src: 'Images/Perfumes/Taj Max Exotic Bliss 3.4 oz Long-Lasting Perfume.png', dest: 'public/images/products/taj-max-exotic-bliss.webp' },
  { src: 'Images/Perfumes/Victory 3.3 oz EDT For Men.png',                  dest: 'public/images/products/victory-edt-men.webp',   twoStep: true },
  { src: 'Images/Perfumes/Hercules Paris 3.4 oz EDP For Men.png',           dest: 'public/images/products/hercules-paris-edp-men.webp', twoStep: true },
  { src: 'Images/Degree Men Black + White Ultraclear Deodorant.png',        dest: 'public/images/products/degree-men-black-white-ultraclear.webp' },
  { src: 'Images/Perfumes/Fantastic Pink 3.4 oz EDP For Women.png',         dest: 'public/images/products/fantastic-pink-edp-women.webp' },
  { src: 'Images/Perfumes/Fleur De Paris 3.4 oz EDP For Women.png',         dest: 'public/images/products/fleur-de-paris-edp-women.webp' },
  { src: 'Images/Perfumes/Lazell Night Bloom For Women EDP 3.4 oz.png',     dest: 'public/images/products/lazell-night-bloom-edp-women.webp' },
  { src: 'Images/Perfumes/Lazell Spring For Women EDP 3.4 oz.png',          dest: 'public/images/products/lazell-spring-edp-women.webp' },
  { src: 'Images/Perfumes/Miss Coco 3.4 oz EDP For Women.png',              dest: 'public/images/products/miss-coco-edp-women.webp' },
];

async function process({ src, dest, twoStep }) {
  const meta = await sharp(src).metadata();

  let pipeline;

  if (twoStep) {
    // Two-step upscale for very small source images
    const buf = await sharp(src)
      .resize(meta.width * 2, meta.height * 2, { kernel: 'lanczos3' })
      .toBuffer();
    pipeline = sharp(buf);
  } else {
    pipeline = sharp(src);
  }

  await pipeline
    .trim({ threshold: 20 })
    .resize(TARGET_W, TARGET_H, {
      fit: 'contain',
      background: { r: 255, g: 255, b: 255, alpha: 1 },
      kernel: 'lanczos3',
    })
    .modulate({ brightness: 1.03, saturation: 1.15 })
    .linear(1.08, -5)
    .sharpen({ sigma: 0.6, m1: 1.0, m2: 0.5 })
    .webp({ quality: QUALITY })
    .toFile(dest);

  const out = await sharp(dest).metadata();
  const size = fs.statSync(dest).size;
  console.log(`✓ ${dest.split('/').pop().padEnd(45)} ${out.width}x${out.height}  ${Math.round(size / 1024)}KB`);
}

console.log(`Processing ${jobs.length} images at ${TARGET_W}x${TARGET_H} quality ${QUALITY}...\n`);

for (const job of jobs) {
  try {
    await process(job);
  } catch (e) {
    console.error(`✗ ${job.dest}:`, e.message);
  }
}

console.log('\nDone!');
