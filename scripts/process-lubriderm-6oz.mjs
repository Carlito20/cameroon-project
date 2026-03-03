import sharp from 'sharp';

const src1 = 'Images/Lubriderm Daily Moisture Lotion + Pro-Ceramide, Shea Butter & Glycerin, Hydrating Face, Hand & Body Lotion, 24-hour Moisturizer for Dry Skin 06 oz.png';
const src2 = 'Images/Lubriderm Daily Moisture Lotion + Pro-Ceramide, Shea Butter & Glycerin, Hydrating Face, Hand & Body Lotion, 24-hour Moisturizer for Dry Skin 06 oz1.png';

async function process(src, dest) {
  await sharp(src)
    .trim({ threshold: 20 })
    .resize(1010, 1200, { fit: 'contain', background: { r:255, g:255, b:255, alpha:1 }, kernel: 'lanczos3' })
    .modulate({ brightness: 1.03, saturation: 1.1 })
    .sharpen({ sigma: 0.8 })
    .webp({ quality: 95 })
    .toFile(dest);
  const m = await sharp(dest).metadata();
  console.log(dest, m.width, m.height);
}

await Promise.all([
  process(src1, 'public/images/products/lubriderm-daily-6oz-1.webp'),
  process(src2, 'public/images/products/lubriderm-daily-6oz-2.webp'),
]);
console.log('Done');
