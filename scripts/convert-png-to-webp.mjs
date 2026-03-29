import sharp from 'sharp';
import fs from 'fs';
import path from 'path';

const OUT = 'public/images/products';

// Map: [sourcePng, destinationWebpName]
const mappings = [
  // Dr Teal's Body Wash - Lemon Balm
  ["Images/Dr Teals/Dr Teal's Body Wash with Pure Epsom Salt, with Prebiotic Lemon Balm & Sage.png", 'dr-teals-lemon-balm1.webp'],
  ["Images/Dr Teals/Dr Teal's Body Wash with Pure Epsom Salt, with Prebiotic Lemon Balm & Sage 1.png", 'dr-teals-lemon-balm2.webp'],

  // Dr Teal's Body Wash - Eucalyptus
  ["Images/Dr Teals/Dr Teal's Body Wash with Pure Epsom Salt, Relax & Relief with Eucalyptus & Spearmint.png", 'dr-teals-eucalyptus1.webp'],
  ["Images/Dr Teals/Dr Teal's Body Wash with Pure Epsom Salt, Relax & Relief with Eucalyptus & Spearmint, 24 fl oz.png", 'dr-teals-eucalyptus2.webp'],

  // Olay Body Wash
  ["Images/Olay/Olay Essential Botanicals Body Wash, Spiced Chai & Pear.png", 'olay-spiced-chai.webp'],
  ["Images/Olay/Olay Essential Botanicals Body Wash, White Tea & Cucumber.png", 'olay-white-tea.webp'],
  ["Images/Olay/Olay Essential Botanicals Body Wash, Lavender Milk & Sandalwood.png", 'olay-lavender.webp'],
  ["Images/Olay/Olay1.png", 'olay-back1.webp'],
  ["Images/Olay/Olay edited.png", 'olay-back2.webp'],

  // CeraVe
  ["Images/CeraVe/Cerave Lotion 120z.png", 'cerave-lotion.webp'],

  // Cetaphil
  ["Images/Cetaphil/Cetaphil Body Moisturinzer Hydrating sensitive skin.png", 'cetaphil-moisturizer1.webp'],
  ["Images/Cetaphil/Cetaphil Body Moisturinzer Hydrating sensitive skin1.png", 'cetaphil-moisturizer2.webp'],

  // Aveeno
  ["Images/Aveeno Lotion 18oz.png", 'aveeno-lotion.webp'],

  // Jergens Coconut
  ["Images/Jergens/Jergens Hydrating Coconut Body Lotion, Hand and Body Moisturizer, Infused with Coconut Oil, Hydrates Dry Skin Instantly.png", 'jergens-coconut1.webp'],
  ["Images/Jergens/Jergens Hydrating Coconut Body Lotion, Hand and Body Moisturizer, Infused with Coconut Oil, Hydrates Dry Skin Instantly edited.png", 'jergens-coconut2.webp'],
  ["Images/Jergens/Jergens Hydrating Coconut Body Lotion, Hand and Body Moisturizer, Infused with Coconut Oil, Hydrates Dry Skin Instantly1.png", 'jergens-coconut3.webp'],
  ["Images/Jergens/Jergens image.png", 'jergens-coconut4.webp'],

  // Jergens Cherry
  ["Images/Jergens/Jergens Original Scent Dry Skin Body Lotion, Hand and Body Moisturizer, Cherry Almond Essence.png", 'jergens-cherry1.webp'],
  ["Images/Jergens/Jergens Original Scent Dry Skin Body Lotion, Hand and Body Moisturizer, Cherry Almond Essence  edited.png", 'jergens-cherry3.webp'],

  // Jergens Shea
  ["Images/Jergens/Jergens Shea Butter Hand and Body Lotion, Deep Conditioning Moisturizer, 3X More Radiant Skin, with Pure Shea Butter.png", 'jergens-shea1.webp'],
  ["Images/Jergens/Jergens Shea Butter Hand and Body Lotion, Deep Conditioning Moisturizer, 3X More Radiant Skin, with Pure Shea Butter edited.png", 'jergens-shea2.webp'],
  ["Images/Jergens/Jergens Original Scent Dry Skin Body Lotion, Hand and Body Moisturizer, Cherry Almond Essence1.png", 'jergens-common.webp'],

  // Jergens Aloe
  ["Images/Jergens/Jergens Soothing Aloe Body Lotion, Aloe Vera Body and Hand Moisturizer, Illuminating Hydralucence Blend, with Cucumber.png", 'jergens-aloe1.webp'],
  ["Images/Jergens/Jergens Soothing Aloe Body Lotion, Aloe Vera Body and Hand Moisturizer, Illuminating Hydralucence Blend, with Cucumber edited.png", 'jergens-aloe2.webp'],
  ["Images/Jergens/Jergens Soothing Aloe Body Lotion, Aloe Vera Body and Hand Moisturizer, Illuminating Hydralucence Blend, with Cucumber1.png", 'jergens-aloe3.webp'],

  // Jergens Ultra
  ["Images/Jergens/Jergens Ultra Healing Dry Skin Lotion, Hand and Body Moisturizer for Quick Absorption into Extra Dry Skin with Hydralucence Blend, Vitamins C, E and B5, White.png", 'jergens-ultra1.webp'],
  ["Images/Jergens/Jergens Ultra Healing Dry Skin Lotion, Hand and Body Moisturizer for Quick Absorption into Extra Dry Skin with Hydralucence Blend, Vitamins C, E and B5, White edited.png", 'jergens-ultra2.webp'],
  ["Images/Jergens/Jergens Ultra Healing Dry Skin Moisturizer, White.png", 'jergens-ultra3.webp'],

  // Dr Teal's Lotion - Lemon
  ["Images/Dr Teals/Dr Teal's Body Lotion, Lemon Prebiotic Lemon Balm & Essential Oils.png", 'dr-teals-lotion-lemon1.webp'],
  ["Images/Dr Teals/Dr Teal's Body Lotion, Lemon Prebiotic Lemon Balm & Essential Oils1.png", 'dr-teals-lotion-lemon2.webp'],
  ["Images/Dr Teals/Dr Teal's Body Lotion, Lemon Prebiotic Lemon Balm & Essential Oils2.png", 'dr-teals-lotion-lemon3.webp'],

  // Dr Teal's Lotion - Eucalyptus
  ["Images/Dr Teals/Dr Teal's 24 Hour Moisture+ Body Lotion, Eucalyptus & Spearmint & Essential Oils.png", 'dr-teals-lotion-eucalyptus1.webp'],
  ["Images/Dr Teals/Dr Teal's 24 Hour Moisture+ Body Lotion, Eucalyptus & Spearmint & Essential Oils1.png", 'dr-teals-lotion-eucalyptus2.webp'],

  // Dr Teal's Lotion - Coconut
  ["Images/Dr Teals/Dr Teal's 24 Hour Moisture+ Body Lotion, Coconut Oil & Essential Oils.png", 'dr-teals-lotion-coconut1.webp'],
  ["Images/Dr Teals/Dr Teal's 24 Hour Moisture+ Body Lotion, Coconut Oil & Essential Oils1.png", 'dr-teals-lotion-coconut2.webp'],

  // Dr Teal's Lotion - Shea
  ["Images/Dr Teals/Dr Teal's Body Lotion, Shea Butter & Almond.png", 'dr-teals-lotion-shea1.webp'],
  ["Images/Dr Teals/Dr Teal's Body Lotion, Shea Butter & Almond1.png", 'dr-teals-lotion-shea2.webp'],
  ["Images/Dr Teals/Dr Teal's Body Lotion, Shea Butter & Almond2.png", 'dr-teals-lotion-shea3.webp'],

  // Dr Teal's Lotion - Lavender
  ["Images/Dr Teals/Dr Teal's 24 Hour Moisture+ Body Lotion, Lavender Essential Oil.png", 'dr-teals-lotion-lavender1.webp'],
  ["Images/Dr Teals/Dr Teal's 24 Hour Moisture+ Body Lotion, Lavender Essential Oil1.png", 'dr-teals-lotion-lavender2.webp'],

  // Vaseline Men Cooling
  ["Images/Vaceline/Vaseline Men Cooling Hydration 3-in-1 for Dry Skin Face, Hands & Body Lotion for Men with Menthol & Ultra-Hydrating Lipids.png", 'vaseline-men-cooling1.webp'],
  ["Images/Vaceline/Vaseline Men Cooling Hydration 3-in-1 Pack for Dry Skin Face, Hands & Body Lotion for Men with Menthol & Ultra-Hydrating Lipids 1.png", 'vaseline-men-cooling2.webp'],
  ["Images/Vaceline/Vaseline Men Cooling Hydration 3-in-1 4 Pack for Dry Skin Face, Hands & Body Lotion for Men with Menthol & Ultra-Hydrating Lipids edited.png", 'vaseline-men-cooling3.webp'],

  // Vaseline Cocoa
  ["Images/Vaceline/Vaseline Intensive Care Body Lotion Cocoa Radiant 3 count for Dry Skin Lotion Made with Ultra-Hydrating Lipids and Pure Cocoa Butter for a Long-Lasting, Radiant Glow.png", 'vaseline-cocoa1.webp'],
  ["Images/Vaceline/Vaseline Intensive Care Body Lotion Cocoa Radiant 3 count for Dry Skin Lotion Made with Ultra-Hydrating Lipids and Pure Cocoa Butter for a Long-Lasting, Radiant Glow1.png", 'vaseline-cocoa2.webp'],
  ["Images/Vaceline/Vaseline Intensive Care Body Lotion Cocoa Radiant 3 count for Dry Skin Lotion Made with Ultra-Hydrating Lipids and Pure Cocoa Butter for a Long-Lasting, Radiant Glow 2.png", 'vaseline-cocoa3.webp'],

  // Vaseline Aloe
  ["Images/Vaceline/Vaseline Intensive Care Body Lotion for Dry Skin Soothing Hydration Lotion Made with Ultra-Hydrating Lipids + 1% Aloe Vera Extract to Refresh Dehydrated Skin .png", 'vaseline-aloe1.webp'],
  ["Images/Vaceline/Vaseline Intensive Care Body Lotion for Dry Skin Soothing Hydration Lotion Made with Ultra-Hydrating Lipids + 1% Aloe Vera Extract to Refresh Dehydrated Skin1.png", 'vaseline-aloe2.webp'],

  // Vaseline Sensitive
  ["Images/Vaceline/Vaseline Intensive Care Body Lotion  Sensitive Skin Relief For Dry Skin With Colloidal Oatmeal and Ultra-Hydrating Lipids.png", 'vaseline-sensitive1.webp'],
  ["Images/Vaceline/Vaseline Intensive Care Body Lotion  Sensitive Skin Relief For Dry Skin With Colloidal Oatmeal and Ultra-Hydrating Lipids2.png", 'vaseline-sensitive3.webp'],

  // Vaseline Lavender
  ["Images/Vaceline/Vaseline Intensive Care Calm Healing Body Lotion  for Dry Skin Made with Ultra-Hydrating Lipids and Lavender Extract to Heal and Restore Dry Skin.png", 'vaseline-lavender1.webp'],
  ["Images/Vaceline/Vaseline Intensive Care Calm Healing Body Lotion  for Dry Skin Made with Ultra-Hydrating Lipids and Lavender Extract to Heal and Restore Dry Skin 2.png", 'vaseline-lavender2.webp'],

  // Vaseline Oat
  ["Images/Vaceline/Vaseline Intensive Care Nourishing Moisture Body Lotion  Made with Ultra-Hydrating Lipids + Pure Oat Extract for Dry Skin, for Nourished, Healthy-Looking Skin.png", 'vaseline-oat1.webp'],
  ["Images/Vaceline/Vaseline Intensive Care Nourishing Moisture Body Lotion  Made with Ultra-Hydrating Lipids + Pure Oat Extract for Dry Skin, for Nourished, Healthy-Looking Skin1.png", 'vaseline-oat2.webp'],

  // Dr Teal's Kids
  ["Images/Dr Teals/Dr Teals for kids.png", 'dr-teals-kids.webp'],
  ["Images/Dr Teals/Dr Teals for kids1.png", 'dr-teals-kids1.webp'],
  ["Images/Dr Teals/Dr Teals for kids2.png", 'dr-teals-kids2.webp'],

  // Thermometer
  ["Images/Thermometer/Therm.png", 'therm.webp'],
  ["Images/Thermometer/Therm1.png", 'therm1.webp'],
  ["Images/Thermometer/Therm2.png", 'therm2.webp'],

  // Torch
  ["Images/Torch.png", 'torch.webp'],
  ["Images/torch1.png", 'torch1.webp'],
  ["Images/torch2.png", 'torch2.webp'],

  // 2025 Round Watch
  ["Images/Electronics/A 2025 Hot-selling Round Watch with A Metal Frame, Ceramic Base, And A 2.01.png", 'smartwatch-round-1.webp'],
  ["Images/Electronics/A 2025 Hot-selling Round Watch with A Metal Frame, Ceramic Base, And A 2.01 2.png", 'smartwatch-round-2.webp'],
  ["Images/Electronics/A 2025 Hot-selling Round Watch with A Metal Frame, Ceramic Base, And A 2.01 1.png", 'smartwatch-round-3.webp'],

  // PLEIVO Smartwatch
  ["Images/Electronics/New Smart Watch with 2.01-inch Large Screen, LED Flashlight, Outdoor Sports Watch,.png", 'smartwatch-led-1.webp'],
  ["Images/Electronics/New Smart Watch with 2.01-inch Large Screen, LED Flashlight, Outdoor Sports Watch, (2).png", 'smartwatch-led-2.webp'],

  // Versatile Smartwatch
  ["Images/Electronics/A Versatile Smartwatch Featuring a Full Touchscreen, Wireless Calling, Over 100 Sports Modes, Weather Updates, Stopwatch, Timer, Alarm, SMS Notifications.png", 'smartwatch-versatile-1.webp'],
  ["Images/Electronics/A Versatile Smartwatch Featuring a Full Touchscreen, Wireless Calling, Over 100 Sports Modes, Weather Updates, Stopwatch, Timer, Alarm, SMS Notifications 2.png", 'smartwatch-versatile-2.webp'],
  ["Images/Electronics/A Versatile Smartwatch Featuring a Full Touchscreen, Wireless Calling, Over 100 Sports Modes, Weather Updates, Stopwatch, Timer, Alarm, SMS Notifications 1.png", 'smartwatch-versatile-3.webp'],

  // Z68 Smartwatch
  ["Images/Electronics/Z68 Smartwatch Featuring Wireless Calling, Message Alerts, Various Sports Modes, Information Notifications, Multifunctional Phone AnsweringDialing, Remote Photography, Music Playback, Spor.png", 'smartwatch-multi-1.webp'],
  ["Images/Electronics/Z68 Smartwatch Featuring Wireless Calling, Message Alerts, Various Sports Modes, Information Notifications, Multifunctional Phone AnsweringDialing, Remote Photography, Music Playback, Sp.png", 'smartwatch-multi-2.webp'],
  ["Images/Electronics/Z68 Smartwatch Featuring Wireless Calling, Message Alerts, Various Sports Modes, Information Notifications, Multifunctional Phone AnsweringDialing, Remote Photography, Music Playback, Sp (2).png", 'smartwatch-multi-3.webp'],
  ["Images/Electronics/Z68.png", 'smartwatch-multi-4.webp'],

  // Suave Keratin Shampoo & Conditioner
  ["Images/Shampoo and Conditioners/Suave Keratin Infusion Smoothing Shampoo & Conditioner Set For Frizzy Hair.png",      'suave-keratin-shampoo-conditioner1.webp'],
  ["Images/Shampoo and Conditioners/Suave Keratin Infusion Smoothing Shampoo & Conditioner Set For Frizzy Hair1 (2).png", 'suave-keratin-shampoo-conditioner2.webp'],
  ["Images/Shampoo and Conditioners/Suave Keratin Infusion Smoothing Shampoo & Conditioner Set For Frizzy Hair2.png",     'suave-keratin-shampoo-conditioner3.webp'],
  ["Images/Shampoo and Conditioners/Suave Keratin Infusion Smoothing Shampoo & Conditioner Set For Frizzy Hair3.png",     'suave-keratin-shampoo-conditioner4.webp'],

  // Suave Almond & Shea Shampoo & Conditioner
  ["Images/Shampoo and Conditioners/Suave Moisturizing Shampoo & Conditioner With Almond & Shea Butter.png",              'suave-almond-shea-shampoo-conditioner1.webp'],
  ["Images/Shampoo and Conditioners/Suave Moisturizing Shampoo & Conditioner With Almond & Shea Butter1.png",             'suave-almond-shea-shampoo-conditioner2.webp'],
  ["Images/Shampoo and Conditioners/Suave Moisturizing Shampoo & Conditioner With Almond & Shea Butter2.png",             'suave-almond-shea-shampoo-conditioner3.webp'],
  ["Images/Shampoo and Conditioners/Suave Moisturizing Shampoo & Conditioner With Almond & Shea Butter3 (2).png",         'suave-almond-shea-shampoo-conditioner4.webp'],

  // TRESemmé Rich Moisture
  ["Images/Shampoo and Conditioners/Tresemme Rich Moisture Shampoo and Conditioner Set.png",                              'tresemme-rich-moisture-shampoo-conditioner1.webp'],
  ["Images/Shampoo and Conditioners/Tresemme Rich Moisture Shampoo and Conditioner Set1.png",                             'tresemme-rich-moisture-shampoo-conditioner2.webp'],
  ["Images/Shampoo and Conditioners/Tresemme Rich Moisture Shampoo and Conditioner Set2.png",                             'tresemme-rich-moisture-shampoo-conditioner3.webp'],
  ["Images/Shampoo and Conditioners/Tresemme Rich Moisture Shampoo and Conditioner Set3 (2).png",                         'tresemme-rich-moisture-shampoo-conditioner4.webp'],

  // TRESemmé Keratin Smooth
  ["Images/Shampoo and Conditioners/Tresemme Shampoo & Conditioner Keratin Smooth.png",                                   'tresemme-keratin-smooth-shampoo-conditioner1.webp'],
  ["Images/Shampoo and Conditioners/Tresemme Shampoo & Conditioner Keratin Smooth1.png",                                  'tresemme-keratin-smooth-shampoo-conditioner2.webp'],
  ["Images/Shampoo and Conditioners/Tresemme Shampoo & Conditioner Keratin Smooth2.png",                                  'tresemme-keratin-smooth-shampoo-conditioner3.webp'],
  ["Images/Shampoo and Conditioners/Tresemme Shampoo & Conditioner Keratin Smooth3 (2).png",                              'tresemme-keratin-smooth-shampoo-conditioner4.webp'],

  // TRESemmé Anti-Breakage
  ["Images/Shampoo and Conditioners/TRESemmé Shampoo And Conditioner Anti-Breakage.png",                                  'tresemme-anti-breakage-shampoo-conditioner1.webp'],
  ["Images/Shampoo and Conditioners/TRESemmé Shampoo And Conditioner Anti-Breakage1.png",                                 'tresemme-anti-breakage-shampoo-conditioner2.webp'],
  ["Images/Shampoo and Conditioners/TRESemmé Shampoo And Conditioner Anti-Breakage2.png",                                 'tresemme-anti-breakage-shampoo-conditioner3.webp'],

  // TRESemmé Amplified Volume
  ["Images/Shampoo and Conditioners/Tresemme Shampoo and Conditioner for Women Amplified Volume Twin Pack.png",           'tresemme-amplified-volume-shampoo-conditioner1.webp'],
  ["Images/Shampoo and Conditioners/Tresemme Shampoo and Conditioner for Women Amplified Volume Twin Pack1.png",          'tresemme-amplified-volume-shampoo-conditioner2.webp'],
  ["Images/Shampoo and Conditioners/Tresemme Shampoo and Conditioner for Women Amplified Volume Twin Pack2.png",          'tresemme-amplified-volume-shampoo-conditioner3.webp'],

  // TRESemmé Silky & Smooth
  ["Images/Shampoo and Conditioners/Tresemme Silky & Smooth Anti-Frizz Shampoo & Conditioner Frizzy Hair.png",           'tresemme-silky-smooth-shampoo-conditioner1.webp'],
  ["Images/Shampoo and Conditioners/Tresemme Silky & Smooth Anti-Frizz Shampoo & Conditioner Frizzy Hair1.png",          'tresemme-silky-smooth-shampoo-conditioner2.webp'],
  ["Images/Shampoo and Conditioners/Tresemme Silky & Smooth Anti-Frizz Shampoo & Conditioner Frizzy Hair2.png",          'tresemme-silky-smooth-shampoo-conditioner3.webp'],

  // Y01 Wireless Headphones
  ["Images/Today/Y01 Over the Ear Wireless headphones.png",       'y01-headphones1.webp'],
  ["Images/Today/Y01 Over the Ear Wireless headphones.png1.png",  'y01-headphones2.webp'],
  ["Images/Today/Y01 Over the Ear Wireless headphones.png13.png", 'y01-headphones3.webp'],

  // TOZO T6 Earbuds
  ["Images/Today/TOZO T6 Wireless Earbuds IPX8 Waterproof.png",          'tozo-t6-earbuds1.webp'],
  ["Images/Today/TOZO T6 Wireless Earbuds IPX8 Waterproof.png1.png",     'tozo-t6-earbuds2.webp'],
  ["Images/Today/TOZO T6 Wireless Earbuds IPX8 Waterproof.png2 (2).png", 'tozo-t6-earbuds3.webp'],
];

let converted = 0;
let skipped = 0;
let failed = 0;

console.log(`Checking ${mappings.length} PNG → WebP mappings...\n`);

await Promise.all(mappings.map(async ([src, dest]) => {
  const destPath = path.join(OUT, dest);

  if (!fs.existsSync(src)) {
    console.warn(`  MISSING source: ${src}`);
    skipped++;
    return;
  }

  // Skip if WebP already exists and is newer than the source PNG
  if (fs.existsSync(destPath)) {
    const srcMtime = fs.statSync(src).mtimeMs;
    const destMtime = fs.statSync(destPath).mtimeMs;
    if (destMtime >= srcMtime) {
      skipped++;
      return;
    }
  }

  try {
    await sharp(src)
      .webp({ quality: 90, lossless: false, nearLossless: false })
      .toFile(destPath);
    console.log(`  ✓ ${dest}`);
    converted++;
  } catch (err) {
    console.error(`  ✗ ${dest}: ${err.message}`);
    failed++;
  }
}));

console.log(`Done: ${converted} converted, ${skipped} up-to-date, ${failed} failed`);
