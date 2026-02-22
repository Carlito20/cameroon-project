import sharp from 'sharp';
import fs from 'fs';
import path from 'path';

const imagesDir = './public/images';
const logoPath = 'c:/Cameroon Project/Images/Pages/AS Logo new.jpeg';
const watermarkSize = 60;

// Files/folders to exclude from watermarking
const excludeFiles = ['as-logo.jpeg'];
const markerFile = '.watermarked';

// Get all image files recursively
function getImageFiles(dir) {
  let results = [];
  const entries = fs.readdirSync(dir, { withFileTypes: true });
  for (const entry of entries) {
    const fullPath = path.join(dir, entry.name);
    if (entry.isDirectory() && entry.name !== 'temp') {
      results = results.concat(getImageFiles(fullPath));
    } else if (entry.isFile() && /\.(jpg|jpeg|png)$/i.test(entry.name) && !excludeFiles.includes(entry.name)) {
      results.push(fullPath);
    }
  }
  return results;
}

// Check if image was already watermarked (using a tracking file)
function getWatermarkedSet() {
  const markerPath = path.join(imagesDir, markerFile);
  if (fs.existsSync(markerPath)) {
    return new Set(fs.readFileSync(markerPath, 'utf-8').split('\n').filter(Boolean));
  }
  return new Set();
}

function saveWatermarkedSet(set) {
  const markerPath = path.join(imagesDir, markerFile);
  fs.writeFileSync(markerPath, [...set].join('\n'));
}

async function addWatermark() {
  try {
    const watermarked = getWatermarkedSet();
    const allImages = getImageFiles(imagesDir);

    // Filter to only new images
    const newImages = allImages.filter(f => !watermarked.has(f.replace(/\\/g, '/')));

    if (newImages.length === 0) {
      console.log('No new images to watermark.');
      return;
    }

    console.log(`Found ${newImages.length} new image(s) to watermark...`);

    // Resize logo for watermark
    const watermark = await sharp(logoPath)
      .resize(watermarkSize, watermarkSize, { fit: 'contain', background: { r: 0, g: 0, b: 0, alpha: 0 } })
      .png()
      .toBuffer();

    for (const filePath of newImages) {
      const fileName = path.basename(filePath);
      console.log(`Processing: ${filePath}`);

      try {
        const metadata = await sharp(filePath).metadata();
        const padding = 10;
        const left = metadata.width - watermarkSize - padding;
        const top = metadata.height - watermarkSize - padding;

        const tempPath = filePath + '.tmp';

        await sharp(filePath)
          .composite([{
            input: watermark,
            left: Math.max(0, left),
            top: Math.max(0, top),
            blend: 'over'
          }])
          .toFile(tempPath);

        // Replace original with watermarked version
        fs.unlinkSync(filePath);
        fs.renameSync(tempPath, filePath);

        // Track as watermarked
        watermarked.add(filePath.replace(/\\/g, '/'));
        console.log(`  ✓ Watermark added to ${fileName}`);
      } catch (err) {
        console.error(`  ✗ Error processing ${fileName}:`, err.message);
        // Clean up temp file if it exists
        const tempPath = filePath + '.tmp';
        if (fs.existsSync(tempPath)) fs.unlinkSync(tempPath);
      }
    }

    saveWatermarkedSet(watermarked);
    console.log(`\nDone! ${newImages.length} image(s) watermarked.`);
  } catch (error) {
    console.error('Error:', error.message);
  }
}

addWatermark();
