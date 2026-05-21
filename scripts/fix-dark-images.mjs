import sharp from 'sharp';

const src = 'C:/Users/Administrator/cameroon-project/Images/Today';
const dest = './public/images/products';

// All these PNGs have transparent backgrounds → transparent pixels render dark
// on the shop's dark background. Fix: flatten alpha onto white.
const toFix = [
  ['slider-bags-1gal-1.webp',   '1-Gallon Slider Storage Bags.png'],
  ['slider-bags-1gal-2.webp',   '1-Gallon Slider Storage Bags 1.png'],
  ['slider-bags-1gal-3.webp',   '1-Gallon Slider Storage Bags 2.png'],
  ['slider-bags-quart-1.webp',  'Quart Slider storage  bags.png'],
  ['slider-bags-quart-2.webp',  'Quart Slider storage  bags.png1.png'],
  ['slider-bags-quart-3.webp',  'Quart Slider storage  bags2.png'],
];

for (const [out, srcFile] of toFix) {
  const srcPath = `${src}/${srcFile}`;
  const destPath = `${dest}/${out}`;

  await sharp(srcPath)
    .flatten({ background: { r: 255, g: 255, b: 255 } }) // transparent → white background
    .resize(1200, 1200, { fit: 'inside', withoutEnlargement: true, kernel: 'lanczos3' })
    .webp({ quality: 92 })
    .toFile(destPath);

  console.log(`Fixed: ${out}`);
}

console.log('\nDone.');
