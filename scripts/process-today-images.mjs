import sharp from 'sharp';
import fs from 'fs';
import path from 'path';

const src = 'C:/Users/Administrator/cameroon-project/Images/Today';
const dest = './public/images/products';

const mappings = [
  // [outputSlug, [sourceFilenames...]]
  ['slider-bags-1gal', [
    '1-Gallon Slider Storage Bags.png',
    '1-Gallon Slider Storage Bags 1.png',
    '1-Gallon Slider Storage Bags 2.png',
  ]],
  ['slider-bags-2_5gal', [
    '2.5-Gallon Slider Storage Bags (6).jpeg',
    '2.5-Gallon Slider Storage Bags (6)1.jpeg',
  ]],
  ['slider-bags-quart', [
    'Quart Slider storage  bags.png',
    'Quart Slider storage  bags.png1.png',
    'Quart Slider storage  bags2.png',
    'Quart Slider Storage Bags3.jpeg',
  ]],
  ['airtight-container-550ml', [
    'Air Tight Storage container and Lid.550ml.png',
    'Air Tight Storage container and Lid.550ml.1.png',
  ]],
  ['airtight-container-650ml', [
    'Air Tight Storage Container and Lid.650ml.jpeg',
    'Air Tight Storage Container and Lid.650ml 1.jpeg',
  ]],
  ['grabber-reacher-tool', [
    'Grabber, Reacher Tool- Foldable Trash Picker With 360° Rotating Jaw & Magnet, Litter Picker, Mobility Aid.png',
    'Grabber, Reacher Tool- Foldable Trash Picker With 360° Rotating Jaw & Magnet, Litter Picker, Mobility Aid.png1.png',
    'Grabber, Reacher Tool- Foldable Trash Picker With 360° Rotating Jaw & Magnet, Litter Picker, Mobility Aid.png2.png',
  ]],
  ['yissvic-fly-swatter', [
    'YISSVIC Electric Fly Swatter Bug Zapper Racket Dual Modes Mosquito Killer with Purple Mosquito Light Rechargeable for Home.png',
    'YISSVIC Electric Fly Swatter Bug Zapper Racket Dual Modes Mosquito Killer with Purple Mosquito Light Rechargeable for Home.png1.png',
    'YISSVIC Electric Fly Swatter Bug Zapper Racket Dual Modes Mosquito Killer with Purple Mosquito Light Rechargeable for Home.png1.png4.png',
    'YISSVIC Electric Fly Swatter Bug Zapper Racket Dual Modes Mosquito Killer with Purple Mosquito Light Rechargeable for Home.png1.png4.png5.png',
    'YISSVIC Electric Fly Swatter Bug Zapper Racket Dual Modes Mosquito Killer with Purple Mosquito Light Rechargeable for Home.png2.png',
  ]],
  ['ocedar-spin-mop', [
    'O-Cedar EasyWring Microfiber Spin Mop, Bucket Floor Cleaning System.png',
    'O-Cedar EasyWring Microfiber Spin Mop, Bucket Floor Cleaning System1.png',
    'O-Cedar EasyWring Microfiber Spin Mop, Bucket Floor Cleaning System2.png',
    'O-Cedar EasyWring Microfiber Spin Mop, Bucket Floor Cleaning System4.png',
    'Mop Heads Replacements for O-Ceda Easy Wrin Spin System, Microfiber Refills, Easy Cleaning Mop Repalce Head.png1.png',
    'Mop Heads Replacements for O-Ceda Easy Wrin Spin System, Microfiber Refills, Easy Cleaning Mop Repalce Head.png2.png',
  ]],
  ['ocedar-mop-head', [
    'Mop Heads Replacements for O-Ceda Easy Wrin Spin System, Microfiber Refills, Easy Cleaning Mop Repalce Head.png',
    'Mop Heads Replacements for O-Ceda Easy Wrin Spin System, Microfiber Refills, Easy Cleaning Mop Repalce Head.1.png',
  ]],
  ['crest-complete-toothpaste', [
    'Crest Complete Toothpaste.jpeg',
    'Crest Complete Toothpaste.1.jpeg',
  ]],
];

let ok = 0, skip = 0, err = 0;

for (const [slug, files] of mappings) {
  let idx = 1;
  for (const file of files) {
    const srcPath = path.join(src, file);
    const destName = `${slug}-${idx}.webp`;
    const destPath = path.join(dest, destName);

    if (!fs.existsSync(srcPath)) {
      console.warn(`  MISSING: ${file}`);
      skip++;
      idx++;
      continue;
    }
    if (fs.existsSync(destPath)) {
      console.log(`  EXISTS:  ${destName}`);
      skip++;
      idx++;
      continue;
    }

    try {
      await sharp(srcPath)
        .resize(1200, 1200, { fit: 'inside', withoutEnlargement: true, kernel: 'lanczos3' })
        .webp({ quality: 90 })
        .toFile(destPath);
      console.log(`  OK:      ${destName}`);
      ok++;
    } catch (e) {
      console.error(`  ERROR:   ${destName} — ${e.message}`);
      err++;
    }
    idx++;
  }
}

console.log(`\nDone: ${ok} converted, ${skip} skipped, ${err} errors`);
