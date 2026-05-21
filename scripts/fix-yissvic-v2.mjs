import sharp from 'sharp';
import fs from 'fs';

const src = 'C:/Users/Administrator/cameroon-project/Images/Today';
const dest = './public/images/products';

const files = [
  ['yissvic-fly-swatter-v2-1.webp', 'YISSVIC Electric Fly Swatter Bug Zapper Racket Dual Modes Mosquito Killer with Purple Mosquito Light Rechargeable for Home.png'],
  ['yissvic-fly-swatter-v2-2.webp', 'YISSVIC Electric Fly Swatter Bug Zapper Racket Dual Modes Mosquito Killer with Purple Mosquito Light Rechargeable for Home.png1.png'],
  ['yissvic-fly-swatter-v2-3.webp', 'YISSVIC Electric Fly Swatter Bug Zapper Racket Dual Modes Mosquito Killer with Purple Mosquito Light Rechargeable for Home.png2.png'],
  ['yissvic-fly-swatter-v2-4.webp', 'YISSVIC Electric Fly Swatter Bug Zapper Racket Dual Modes Mosquito Killer with Purple Mosquito Light Rechargeable for Home.png1.png4.png'],
  ['yissvic-fly-swatter-v2-5.webp', 'YISSVIC Electric Fly Swatter Bug Zapper Racket Dual Modes Mosquito Killer with Purple Mosquito Light Rechargeable for Home.png1.png4.png5.png'],
];

// Delete old images 4 & 5
for (const f of ['yissvic-fly-swatter-4.webp', 'yissvic-fly-swatter-5.webp']) {
  const p = `${dest}/${f}`;
  if (fs.existsSync(p)) { fs.unlinkSync(p); console.log(`Deleted: ${f}`); }
}

// Process new images
for (const [out, srcFile] of files) {
  await sharp(`${src}/${srcFile}`)
    .flatten({ background: { r: 255, g: 255, b: 255 } })
    .resize(1200, 1200, { fit: 'inside', withoutEnlargement: true, kernel: 'lanczos3' })
    .webp({ quality: 92 })
    .toFile(`${dest}/${out}`);
  console.log(`OK: ${out}`);
}

console.log('\nDone.');
