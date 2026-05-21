import sharp from 'sharp';

const src = 'C:/Users/Administrator/cameroon-project/Images/Today';
const dest = './public/images/products';

const files = [
  ['airtight-container-550ml-1.webp', 'Air Tight Storage container and Lid.550ml.png'],
  ['airtight-container-550ml-2.webp', 'Air Tight Storage container and Lid.550ml.1.png'],
];

for (const [out, srcFile] of files) {
  const meta = await sharp(`${src}/${srcFile}`).metadata();
  console.log(`${srcFile} — channels: ${meta.channels}, alpha: ${meta.hasAlpha}`);

  await sharp(`${src}/${srcFile}`)
    .flatten({ background: { r: 255, g: 255, b: 255 } })
    .resize(1200, 1200, { fit: 'inside', withoutEnlargement: true, kernel: 'lanczos3' })
    .webp({ quality: 92 })
    .toFile(`${dest}/${out}`);

  console.log(`Fixed: ${out}`);
}

console.log('\nDone.');
