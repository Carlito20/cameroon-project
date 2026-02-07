import sharp from 'sharp';
import fs from 'fs';
import path from 'path';

const productsDir = './public/images/products';
const logoPath = 'c:/Cameroon Project/Images/Pages/AS Logo new.jpeg';
const watermarkSize = 40; // Size of watermark in pixels
const tempDir = './public/images/products/temp';

async function addWatermark() {
  try {
    // Create temp directory
    if (!fs.existsSync(tempDir)) {
      fs.mkdirSync(tempDir, { recursive: true });
    }

    // Resize logo for watermark (small size)
    const watermark = await sharp(logoPath)
      .resize(watermarkSize, watermarkSize, { fit: 'contain', background: { r: 0, g: 0, b: 0, alpha: 0 } })
      .png()
      .toBuffer();

    // Get all image files
    const files = fs.readdirSync(productsDir).filter(file =>
      /\.(jpg|jpeg|png)$/i.test(file) && file !== 'temp'
    );

    console.log(`Found ${files.length} images to process...`);

    for (const file of files) {
      const filePath = path.join(productsDir, file);
      const tempPath = path.join(tempDir, file);
      console.log(`Processing: ${file}`);

      try {
        // Get image dimensions
        const metadata = await sharp(filePath).metadata();

        // Add watermark to bottom-right corner with padding
        const padding = 10;
        const left = metadata.width - watermarkSize - padding;
        const top = metadata.height - watermarkSize - padding;

        // Write to temp file
        await sharp(filePath)
          .composite([{
            input: watermark,
            left: Math.max(0, left),
            top: Math.max(0, top),
            blend: 'over'
          }])
          .toFile(tempPath);

        console.log(`  ✓ Watermark added to ${file}`);
      } catch (err) {
        console.error(`  ✗ Error processing ${file}:`, err.message);
      }
    }

    // Now copy temp files back
    console.log('\nCopying watermarked files back...');
    const tempFiles = fs.readdirSync(tempDir);
    for (const file of tempFiles) {
      const tempPath = path.join(tempDir, file);
      const destPath = path.join(productsDir, file);
      try {
        fs.copyFileSync(tempPath, destPath);
        fs.unlinkSync(tempPath);
        console.log(`  ✓ ${file} updated`);
      } catch (err) {
        console.log(`  ! Could not update ${file}, kept in temp folder`);
      }
    }

    // Clean up temp directory
    try {
      fs.rmdirSync(tempDir);
    } catch (e) {
      console.log('Temp directory not empty, some files may need manual copying');
    }

    console.log('\nDone!');
  } catch (error) {
    console.error('Error:', error.message);
  }
}

addWatermark();
