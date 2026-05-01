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
  ["Images/Dr Teals/Dr Teal's Body Lotion, Moisture + Nourishing with Coconut Oil & Essential Oils.png", 'dr-teals-lotion-coconut3.webp'],
  ["Images/Dr Teals/Dr Teal's Body Lotion, Moisture + Nourishing with Coconut Oil & Essential Oils1.png", 'dr-teals-lotion-coconut4.webp'],

  // Dr Teal's Lotion - Shea
  ["Images/Dr Teals/Dr Teal's Body Lotion, Shea Butter & Almond.png", 'dr-teals-lotion-shea1.webp'],
  ["Images/Dr Teals/Dr Teal's Body Lotion, Shea Butter & Almond1.png", 'dr-teals-lotion-shea2.webp'],
  ["Images/Dr Teals/Dr Teal's Body Lotion, Shea Butter & Almond2.png", 'dr-teals-lotion-shea3.webp'],

  // Dr Teal's Lotion - Lavender
  ["Images/Dr Teals/Dr Teal's 24 Hour Moisture Body Lotion with Lavender Essential Oil.png", 'dr-teals-lotion-lavender1.webp'],
  ["Images/Dr Teals/Dr Teal's 24 Hour Moisture Body Lotion with Lavender Essential Oil1.png", 'dr-teals-lotion-lavender2.webp'],
  ["Images/Dr Teals/Dr Teal's 24 Hour Moisture Body Lotion with Lavender Essential Oil2.png", 'dr-teals-lotion-lavender3.webp'],

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

  // Lady Speed Stick 72HR Women's Deodorant
  ["Images/Today/Antiperspirant Deodorant for Women, 72 HR Sweat & Odor Protection, Invisible Dry, Shower Fresh Scent, 2.3 oz Stick.png",   'lady-speed-stick-72hr-1.webp'],
  ["Images/Today/Antiperspirant Deodorant for Women, 72 HR Sweat & Odor Protection, Invisible Dry, Shower Fresh Scent, 2.3 oz Stick.2.png", 'lady-speed-stick-72hr-2.webp'],
  ["Images/Today/Antiperspirant Deodorant for Women, 72 HR Sweat & Odor Protection, Invisible Dry, Shower Fresh Scent, 2.3 oz Stick1.png",  'lady-speed-stick-72hr-3.webp'],
  ["Images/Today/Antiperspirant Deodorant for Women, 72 HR Sweat & Odor Protection, Invisible Dry, Shower Fresh Scent, 2.3 oz Stick4.png",  'lady-speed-stick-72hr-4.webp'],

  // Right Guard Sport Men's Deodorant
  ["Images/Today/Right Guard Sport Fresh Scent Antiperspirant & Deodorant Invisible Solid - 4-in-1 Deodorant For Men, Blocks Sweat, 48-Hour Odor Protection, Quick-Drying, & Long-Lasting.png",   'right-guard-sport-men-1.webp'],
  ["Images/Today/Right Guard Sport Fresh Scent Antiperspirant & Deodorant Invisible Solid - 4-in-1 Deodorant For Men, Blocks Sweat, 48-Hour Odor Protection, Quick-Drying, & Long-Lasting.1.png", 'right-guard-sport-men-2.webp'],

  // Cetaphil Baby Daily Lotion
  ["Images/Cetaphil/Cetaphil Baby Daily Lotion with Organic Calendula Vitamin E  Sweet Almond & Sunflower Oils.png",   'cetaphil-baby-daily-lotion-1.webp'],
  ["Images/Cetaphil/Cetaphil Baby Daily Lotion with Organic Calendula Vitamin E  Sweet Almond & Sunflower Oils.1.png", 'cetaphil-baby-daily-lotion-2.webp'],
  ["Images/Cetaphil/Cetaphil Baby Daily Lotion with Organic Calendula Vitamin E  Sweet Almond & Sunflower Oils.2.png", 'cetaphil-baby-daily-lotion-3.webp'],
  ["Images/Cetaphil/Cetaphil Baby Daily Lotion with Organic Calendula Vitamin E  Sweet Almond & Sunflower Oils.3.png", 'cetaphil-baby-daily-lotion-4.webp'],

  // CeraVe Baby Lotion
  ["Images/CeraVe/CeraVe Baby Lotion, Gentle Baby Skin Care with Ceramides, Niacinamide & Vitamin E, Fragrance, Paraben, Dye & Phthalates Free.png",   'cerave-baby-lotion-1.webp'],
  ["Images/CeraVe/CeraVe Baby Lotion, Gentle Baby Skin Care with Ceramides, Niacinamide & Vitamin E, Fragrance, Paraben, Dye & Phthalates Free 1.png", 'cerave-baby-lotion-2.webp'],
  ["Images/CeraVe/CeraVe Baby Lotion, Gentle Baby Skin Care with Ceramides, Niacinamide & Vitamin E, Fragrance, Paraben, Dye & Phthalates Free 2.png", 'cerave-baby-lotion-3.webp'],
  ["Images/CeraVe/CeraVe Baby Lotion, Gentle Baby Skin Care with Ceramides, Niacinamide & Vitamin E, Fragrance, Paraben, Dye & Phthalates Free 3.png", 'cerave-baby-lotion-4.webp'],
  ["Images/CeraVe/CeraVe Baby Lotion, Gentle Baby Skin Care with Ceramides, Niacinamide & Vitamin E, Fragrance, Paraben, Dye & Phthalates Free 4.png", 'cerave-baby-lotion-5.webp'],

  // Nair Hair Removal Creams
  ["Images/Nair/Nair Body Cream Hair Remover, Soothing Aloe & Water Lily, Body Hair Removal Cream.png",                    'nair-aloe-water-lily-1.webp'],
  ["Images/Nair/Nair Body Cream, Hair Removal Cream with Rich Cocoa Butter and Vitamin E for Body.png",   'nair-cocoa-butter-1.webp'],
  ["Images/Nair/Nair Body Cream, Hair Removal Cream with Rich Cocoa Butter and Vitamin E for Body 1.png", 'nair-cocoa-butter-2.webp'],
  ["Images/Nair/Nair Body Cream, Hair Removal Cream with Rich Cocoa Butter and Vitamin E for Body 2.png", 'nair-cocoa-butter-3.webp'],
  ["Images/Nair/Nair Body Cream, Hair Removal Cream with Rich Cocoa Butter and Vitamin E for Body 3.png", 'nair-cocoa-butter-4.webp'],
  ["Images/Nair/Nair Body Cream, Hair Removal Cream with Rich Cocoa Butter and Vitamin E for Body 4.png", 'nair-cocoa-butter-5.webp'],
  ["Images/Nair/Nair Body Cream, Hair Removal Cream with Rich Cocoa Butter and Vitamin E for Body 5.png", 'nair-cocoa-butter-6.webp'],

  // Degree Deodorants
  ["Images/Degree/Degree Original Antiperspirant Deodorant Sheer Powder.png",      'degree-sheer-powder-deodorant-1.webp'],
  ["Images/Degree/Degree Original Antiperspirant Deodorant Sheer Powder1.png",     'degree-sheer-powder-deodorant-2.webp'],
  ["Images/Degree/Degree Original Antiperspirant Deodorant Sheer Powder2.png",     'degree-sheer-powder-deodorant-3.webp'],
  ["Images/Degree/Degree Original Antiperspirant Deodorant Sheer Powder2.png2.png",'degree-sheer-powder-deodorant-4.webp'],

  // Suave Women's Deodorants
  ["Images/Suave/Suave Antiperspirant Deodorant For Women, 48hr Protection, Fresh.png",   'suave-women-fresh-deodorant-1.webp'],
  ["Images/Suave/Suave Antiperspirant Deodorant For Women, 48hr Protection, Fresh.1.png", 'suave-women-fresh-deodorant-2.webp'],
  ["Images/Suave/Suave Antiperspirant Deodorant For Women, 48hr Protection, Fresh.2.png", 'suave-women-fresh-deodorant-3.webp'],
  ["Images/Suave/Suave Antiperspirant Deodorant For Women, 48hr Protection, Fresh2.png",  'suave-women-fresh-deodorant-4.webp'],
  ["Images/Suave/Suave Antiperspirant Deodorant For Women, 48hr Protection, Powder.png",   'suave-women-powder-deodorant-1.webp'],
  ["Images/Suave/Suave Antiperspirant Deodorant For Women, 48hr Protection, Powder.1.png", 'suave-women-powder-deodorant-2.webp'],
  ["Images/Suave/Suave Antiperspirant Deodorant For Women, 48hr Protection, Powder.2.png", 'suave-women-powder-deodorant-3.webp'],
  ["Images/Suave/Suave Antiperspirant Deodorant For Women, 48hr Protection, Powder.3.png", 'suave-women-powder-deodorant-4.webp'],
  ["Images/Suave/Suave Antiperspirant Deodorant For Women, Wild Cherry Blossom.png",   'suave-women-cherry-blossom-deodorant-1.webp'],
  ["Images/Suave/Suave Antiperspirant Deodorant For Women, Wild Cherry Blossom 1.png", 'suave-women-cherry-blossom-deodorant-2.webp'],
  ["Images/Suave/Suave Antiperspirant Deodorant For Women, Wild Cherry Blossom 2.png", 'suave-women-cherry-blossom-deodorant-3.webp'],
  ["Images/Suave/Suave Antiperspirant Female Deodorant, 48hr Protection, Tropical Paradise.png",   'suave-women-tropical-deodorant-1.webp'],
  ["Images/Suave/Suave Antiperspirant Female Deodorant, 48hr Protection, Tropical Paradise.1.png", 'suave-women-tropical-deodorant-2.webp'],
  ["Images/Suave/Suave Antiperspirant Female Deodorant, 48hr Protection, Tropical Paradise.2.png", 'suave-women-tropical-deodorant-3.webp'],
  ["Images/Suave/Suave Antiperspirant Female Deodorant, 48hr Protection, Tropical Paradise.3.png", 'suave-women-tropical-deodorant-4.webp'],

  // Suave Kids
  ["Images/Suave/Suave Kids Paw Patrol 3-in-1 Shampoo, Conditioner & Body Wash, Adventure Bay Breeze.png",     'suave-kids-paw-patrol-wash-1.webp'],
  ["Images/Suave/Suave Kids Paw Patrol 3-in-1 Shampoo, Conditioner & Body Wash, Adventure Bay Breeze.1.png",   'suave-kids-paw-patrol-wash-2.webp'],
  ["Images/Suave/Suave Kids Paw Patrol 3-in-1 Shampoo, Conditioner & Body Wash, Adventure Bay Breeze.2.png",   'suave-kids-paw-patrol-wash-3.webp'],
  ["Images/Suave/Suave Kids Paw Patrol 3-in-1 Shampoo, Conditioner & Body Wash, Adventure Bay Breeze.3.png",   'suave-kids-paw-patrol-wash-4.webp'],

  // AXE Men's Deodorants
  ["Images/AXE/AXE Antiperspirant Deodorant for Men Essence 4 Count 48H Sweat & Odor Protection for Long Lasting Freshness, Black Pepper & Cedarwood 48 Hour.png",  'axe-essence-deodorant-1.webp'],
  ["Images/AXE/AXE Antiperspirant Deodorant for Men Essence 4 Count 48H Sweat & Odor Protection for Long Lasting Freshness, Black Pepper & Cedarwood 48 Hour2.png", 'axe-essence-deodorant-2.webp'],
  ["Images/AXE/AXE Antiperspirant Deodorant for Men Essence 4 Count 48H Sweat & Odor Protection for Long Lasting Freshness, Black Pepper & Cedarwood 48 Hour3.png", 'axe-essence-deodorant-3.webp'],
  ["Images/AXE/AXE Antiperspirant Deodorant for Men Essence 4 Count 48H Sweat & Odor Protection for Long Lasting Freshness, Black Pepper & Cedarwood 48 Hour4.png", 'axe-essence-deodorant-4.webp'],
  ["Images/AXE/AXE Apollo Antiperspirant Deodorant Stick For Men Sage & Cedarwood 48 Hr Anti Sweat Mens Deodorant.png",  'axe-apollo-deodorant-1.webp'],
  ["Images/AXE/AXE Apollo Antiperspirant Deodorant Stick For Men Sage & Cedarwood 48 Hr Anti Sweat Mens Deodorant1.png", 'axe-apollo-deodorant-2.webp'],
  ["Images/AXE/AXE Apollo Antiperspirant Deodorant Stick For Men Sage & Cedarwood 48 Hr Anti Sweat Mens Deodorant3.png", 'axe-apollo-deodorant-3.webp'],
  ["Images/AXE/AXE Apollo Antiperspirant Deodorant Stick For Men Sage & Cedarwood 48 Hr Anti Sweat Mens Deodorant2.png", 'axe-apollo-deodorant-4.webp'],

  // Suave Body Washes (new)
  ["Images/Suave/Suave Wild Cherry Blossom Moisturizing Body Wash with Glycerin & Vitamin E.png",   'suave-wild-cherry-blossom-wash-1.webp'],
  ["Images/Suave/Suave Wild Cherry Blossom Moisturizing Body Wash with Glycerin & Vitamin E.2.png", 'suave-wild-cherry-blossom-wash-2.webp'],
  ["Images/Suave/Suave Wild Cherry Blossom Moisturizing Body Wash with Glycerin & Vitamin 1.png",   'suave-wild-cherry-blossom-wash-3.webp'],
  ["Images/Suave/Suave Strawberry Delight.png",       'suave-strawberry-delight-wash-1.webp'],
  ["Images/Suave/Suave Strawberry Delight.png1.png",  'suave-strawberry-delight-wash-2.webp'],
  ["Images/Suave/Suave Strawberry Delight.png3.png",  'suave-strawberry-delight-wash-3.webp'],
  ["Images/Suave/Suave Ocean Breeze Moisturizing Body Wash with Glycerin & Vitamin E.png",  'suave-ocean-breeze-wash-1.webp'],
  ["Images/Suave/Suave Ocean Breeze Moisturizing Body Wash with Glycerin & Vitamin E2.png", 'suave-ocean-breeze-wash-2.webp'],
  ["Images/Suave/Suave Ocean Breeze Moisturizing Body Wash with Glycerin & Vitamin E1.png", 'suave-ocean-breeze-wash-3.webp'],
  ["Images/Suave/Suave Milk & Honey Moisturizing Body Wash with Glycerin & Vitamin E.png",  'suave-milk-honey-wash-1.webp'],
  ["Images/Suave/Suave Milk & Honey Moisturizing Body Wash with Glycerin & Vitamin E2.png", 'suave-milk-honey-wash-2.webp'],
  ["Images/Suave/Suave Milk & Honey Moisturizing Body Wash with Glycerin & Vitamin E1.png", 'suave-milk-honey-wash-3.webp'],

  // St. Ives Body Washes
  ["Images/St Ives/St. Ives Body Wash Pink Lemon and Mandarin Orange.png",       'st-ives-pink-lemon-wash-1.webp'],
  ["Images/St Ives/St. Ives Body Wash Pink Lemon and Mandarin Orange.1.png",     'st-ives-pink-lemon-wash-2.webp'],
  ["Images/St Ives/St. Ives Body Wash Pink Lemon and Mandarin Orange.2.png",     'st-ives-pink-lemon-wash-3.webp'],
  ["Images/St Ives/St. Ives Body Wash Pink Lemon and Mandarin Orange.1.png3.png",'st-ives-pink-lemon-wash-4.webp'],
  ["Images/St Ives/St. Ives Sea Salt & Pacific Kelp Exfoliating Body Wash.png",  'st-ives-sea-salt-wash-1.webp'],
  ["Images/St Ives/St. Ives Sea Salt & Pacific Kelp Exfoliating Body Wash.png1.png", 'st-ives-sea-salt-wash-2.webp'],
  ["Images/St Ives/St. Ives Sea Salt & Pacific Kelp Exfoliating Body Wash.png2.png", 'st-ives-sea-salt-wash-3.webp'],
  ["Images/St Ives/St. Ives Soothing Body Wash , Oatmeal & Shea Butter, 22 fl oz.png", 'st-ives-oatmeal-shea-wash-1.webp'],
  ["Images/St Ives/St. Ives Soothing Body Wash , Oatmeal & Shea Butter 2.png",   'st-ives-oatmeal-shea-wash-2.webp'],
  ["Images/St Ives/St. Ives Soothing Body Wash , Oatmeal & Shea Butter 3.png",   'st-ives-oatmeal-shea-wash-3.webp'],

  // St. Ives Lotions
  ["Images/St Ives/St. Ives all lotions.png", 'st-ives-all-lotions.webp'],
  ["Images/St Ives/St. Ives Coconut & Orchid Hand & Body Lotion, Softening Body Care for Dry Skin.png",     'st-ives-coconut-orchid-lotion-1.webp'],
  ["Images/St Ives/St. Ives Coconut & Orchid Hand & Body Lotion, Softening Body Care for Dry Skin.png1.png",'st-ives-coconut-orchid-lotion-2.webp'],
  ["Images/St Ives/St. Ives Coconut & Orchid Hand & Body Lotion, Softening Body Care for Dry Skin.png3.png",'st-ives-coconut-orchid-lotion-3.webp'],
  ["Images/St Ives/St. Ives Hand & Body Lotion, Sweet Almond Oil, Restoring Body Care for Dry Skin.png",    'st-ives-sweet-almond-lotion-1.webp'],
  ["Images/St Ives/St. Ives Oatmeal & Shea Butter Hand & Body Lotion, Moisturizing Body Care for Dry Skin.png",  'st-ives-oatmeal-shea-lotion-1.webp'],
  ["Images/St Ives/St. Ives Oatmeal & Shea Butter Hand & Body Lotion, Moisturizing Body Care for Dry Skin1.png", 'st-ives-oatmeal-shea-lotion-2.webp'],
  ["Images/St Ives/St. Ives Oatmeal & Shea Butter Hand & Body Lotion, Moisturizing Body Care for Dry Skin2.png", 'st-ives-oatmeal-shea-lotion-3.webp'],
  ["Images/St Ives/St. Ives Renewing Hand & Body Lotion with Pump, Daily Moisturizer Collagen Elastin for Dry Skin, Made with 100% Natural Moisturizers.png",     'st-ives-renewing-collagen-lotion-1.webp'],
  ["Images/St Ives/St. Ives Renewing Hand & Body Lotion with Pump, Daily Moisturizer Collagen Elastin for Dry Skin, Made with 100% Natural Moisturizers.png1.png",'st-ives-renewing-collagen-lotion-2.webp'],
  ["Images/St Ives/St. Ives Renewing Hand & Body Lotion with Pump, Daily Moisturizer Collagen Elastin for Dry Skin, Made with 100% Natural Moisturizers.2.png",   'st-ives-renewing-collagen-lotion-3.webp'],
  ["Images/St Ives/St. Ives Renewing Hand & Body Lotion with Pump, Daily Moisturizer Collagen Elastin for Dry Skin, Made with 100% Natural Moisturizers.png3.png",'st-ives-renewing-collagen-lotion-4.webp'],
  ["Images/St Ives/St. Ives Rose & Argan Oil Hand & Body Lotion, Smoothing Body Care for Dry Skin.png",     'st-ives-rose-argan-lotion-1.webp'],
  ["Images/St Ives/St. Ives Rose & Argan Oil Hand & Body Lotion, Smoothing Body Care for Dry Skin.png1.png",'st-ives-rose-argan-lotion-2.webp'],
  ["Images/St Ives/St. Ives Rose & Argan Oil Hand & Body Lotion, Smoothing Body Care for Dry Skin2.png",    'st-ives-rose-argan-lotion-3.webp'],
  ["Images/St Ives/St. Ives Vitamin E & Avocado Oil Hand & Body Lotion, Hydrating Body Care for Dry Skin.png",     'st-ives-vitamin-e-avocado-lotion-1.webp'],
  ["Images/St Ives/St. Ives Vitamin E & Avocado Oil Hand & Body Lotion, Hydrating Body Care for Dry Skin.png1.png",'st-ives-vitamin-e-avocado-lotion-2.webp'],
  ["Images/St Ives/St. Ives Vitamin E & Avocado Oil Hand & Body Lotion, Hydrating Body Care for Dry Skin.png3.png",'st-ives-vitamin-e-avocado-lotion-3.webp'],
  ["Images/St Ives/St. Ives Vitamin E & Avocado Oil Hand & Body Lotion, Hydrating Body Care for Dry Skin.png2.png",'st-ives-vitamin-e-avocado-lotion-4.webp'],

  // Cetaphil Face & Body Moisturizer Lotion
  ["Images/Cetaphil/Cetaphil Face & Body Moisturizer, Hydrating Moisturizing Lotion for All Skin Types, Suitable for Sensitive Skin.png",   'cetaphil-face-body-moisturizer-1.webp'],
  ["Images/Cetaphil/Cetaphil Face & Body Moisturizer, Hydrating Moisturizing Lotion for All Skin Types, Suitable for Sensitive Ski4.png",   'cetaphil-face-body-moisturizer-2.webp'],
  ["Images/Cetaphil/Cetaphil Face & Body Moisturizer, Hydrating Moisturizing Lotion for All Skin Types, Suitable for Sensitive Skin,1.png", 'cetaphil-face-body-moisturizer-3.webp'],
  ["Images/Cetaphil/Cetaphil Face & Body Moisturizer, Hydrating Moisturizing Lotion for All Skin Types, Suitable for Sensitive Skin2.png",  'cetaphil-face-body-moisturizer-4.webp'],
  ["Images/Cetaphil/Cetaphil Face & Body Moisturizer, Hydrating Moisturizing Lotion for All Skin Types, Suitable for Sensitive Skin3.png",  'cetaphil-face-body-moisturizer-5.webp'],

  // CeraVe Daily Moisturizing Face & Body Lotion
  ["Images/CeraVe/CeraVe Daily Moisturizing Face & Body Lotion for Normal to Dry Skin.png",       'cerave-daily-moisturizing-lotion-1.webp'],
  ["Images/CeraVe/CeraVe Daily Moisturizing Face & Body Lotion for Normal to Dry Skin1.png",      'cerave-daily-moisturizing-lotion-2.webp'],
  ["Images/CeraVe/CeraVe Daily Moisturizing Face & Body Lotion for Normal to Dry Skin1.png1.png", 'cerave-daily-moisturizing-lotion-3.webp'],

  // CeraVe Moisturizing Cream for Face & Body
  ["Images/CeraVe/CeraVe Moisturizing Cream for Face & Body Normal to Very Dry Skin .png",              'cerave-moisturizing-cream-1.webp'],
  ["Images/CeraVe/CeraVe Moisturizing Cream for Face & Body Normal to Very Dry Skin with Pump .png1.png", 'cerave-moisturizing-cream-2.webp'],
  ["Images/CeraVe/CeraVe Moisturizing Cream for Face & Body Normal to Very Dry Skin.png",               'cerave-moisturizing-cream-3.webp'],
  ["Images/CeraVe/CeraVe Moisturizing Cream for Face & Body Normal to Very Dry Skin2.png",              'cerave-moisturizing-cream-4.webp'],
  ["Images/CeraVe/CeraVe Moisturizing Cream for Face & Body Normal to Very Dry Skin4.png",              'cerave-moisturizing-cream-5.webp'],
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
