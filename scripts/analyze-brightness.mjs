import sharp from 'sharp';

const files = [
  './public/images/products/slider-bags-1gal-1.webp',
  './public/images/products/slider-bags-1gal-2.webp',
  './public/images/products/slider-bags-1gal-3.webp',
  './public/images/products/slider-bags-quart-1.webp',
  './public/images/products/slider-bags-quart-2.webp',
  './public/images/products/slider-bags-quart-3.webp',
  './public/images/products/slider-bags-quart-4.webp', // ← the good one
];

for (const f of files) {
  const stats = await sharp(f).stats();
  const avg = ((stats.channels[0].mean + stats.channels[1].mean + stats.channels[2].mean) / 3).toFixed(1);
  console.log(`${f.split('/').pop().padEnd(35)} avg brightness: ${avg}`);
}
