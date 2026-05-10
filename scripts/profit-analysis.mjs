import { readFileSync } from 'fs';
import ExcelJS from 'exceljs';

const EXCHANGE = 600; // 1 USD = 600 FCFA
const TOTAL_SPENT_USD = 5483.07;
const TOTAL_SPENT_FCFA = TOTAL_SPENT_USD * EXCHANGE;

// Live selling prices (exact product names → FCFA)
const livePrices = {
  "Dr Teal's Body Wash with Prebiotic Lemon Balm and Essential Oil Blend":6000,
  "Dr Teal's Body Wash Relax and Relief with Eucalyptus Spearmint":6000,
  "Dr Teal's Vanilla Comfort Body Wash with Pure Epsom Salt":6000,
  "Dr Teal's Hawaiian Bliss Body Wash with Alaea Red Sea Salt, Hibiscus & Papaya, 24 fl oz":6000,
  "Dr Teal's Body Wash with Pure Epsom Salt, Soothe & Sleep with Lavender":6000,
  "Dr Teal's Body Wash, Nourish & Protect with Coconut Oil":6000,
  "Dr Teal's Body Wash with Pure Epsom Salt, Shea Butter & Almond":6000,
  "Olay Essential Botanicals Body Wash, Spiced Chai & Pear":6500,
  "Olay Essential Botanicals Body Wash, White Tea & Cucumber":6500,
  "Olay Essential Botanicals Body Wash, Lavender Milk & Sandalwood":6500,
  "Suave Men Hydrating 3-in-1 Body, Hair & Face Wash with Glycerin & Vitamin E":4500,
  "Suave Moisturizing Body Wash Cocoa Butter and Shea with Vitamin E":4500,
  "Suave Cocoa Butter + Shea Moisturizing Body Wash with Glycerin & Vitamin E":7000,
  "Suave Moisturizing Body Wash Sweet Pea & Violet with Vitamin E":4500,
  "Suave Ocean Breeze Moisturizing Body Wash with Glycerin & Vitamin E, 18 fl oz":4500,
  "Suave Ocean Breeze Moisturizing Body Wash with Glycerin & Vitamin E, 30 fl oz":7000,
  "Suave Wild Cherry Blossom Moisturizing Body Wash with Glycerin & Vitamin E":7000,
  "Suave Strawberry Delight Moisturizing Body Wash with Glycerin & Vitamin E":7000,
  "Suave Milk & Honey Moisturizing Body Wash with Glycerin & Vitamin E":7000,
  "NIVEA MEN Maximum Hydration 3-in-1 Body Wash with Aloe Vera":4500,
  "Irish Spring Original Clean Deodorant Bar Soap":1500,
  "St. Ives Body Wash Pink Lemon and Mandarin Orange":5000,
  "St. Ives Sea Salt & Pacific Kelp Exfoliating Body Wash":5000,
  "St. Ives Soothing Body Wash, Oatmeal & Shea Butter, 22 fl oz":5000,
  "Olay Body Lotion Age Defying with Niacinamide Serum":12000,
  "Olay Body Lotion Nourishing with Hyaluronic Acid Serum":12000,
  "Olay Body Lotion Smoothing with Retinol Serum":12000,
  "Olay Body Lotion Tone Enhancing with AHA Serum":12000,
  "Aveeno Daily Moisturizing Body Lotion 18oz":7000,
  "Jergens Hydrating Coconut Body Lotion, Hand and Body Moisturizer, Infused with Coconut Oil":6500,
  "Jergens Shea Butter Hand and Body Lotion, Deep Conditioning Moisturizer":6500,
  "Jergens Soothing Aloe Body Lotion, Aloe Vera Body and Hand Moisturizer":6500,
  "Jergens Skin Firming Body Lotion with Collagen and Elastin":6500,
  "Jergens Ultra Healing Dry Skin Lotion, Hand and Body Moisturizer":6500,
  "Dr Teal's 24 Hour Moisture+ Body Lotion, Prebiotic Lemon Balm & Essential Oils":8000,
  "Dr Teal's 24 Hour Moisture+ Body Lotion, Eucalyptus & Spearmint":8000,
  "Dr Teal's 24 Hour Moisture+ Body Lotion, Coconut Oil & Essential Oils":8000,
  "Dr Teal's 24 Hour Moisture+ Body Lotion, Shea Butter & Almond":8000,
  "Dr Teal's 24 Hour Moisture Body Lotion with Lavender Essential Oil":8000,
  "Dr Teal's 24 Hour Moisture Body Lotion, Vanilla Comfort":8000,
  "Vaseline Men Cooling Hydration 3-in-1 Face, Hands & Body Lotion with Menthol":6500,
  "Vaseline Men Fast Absorbing 3-in-1 Face, Hands & Body Lotion For Men, For Dry Skin, Absorbs in Just 15 Seconds for Moisturized Skin":6500,
  "Vaseline Intensive Care Body Lotion Cocoa Radiant with Pure Cocoa Butter":6500,
  "Vaseline Intensive Care Soothing Hydration Body Lotion with Aloe Vera":6500,
  "Vaseline Intensive Care Sensitive Skin Relief Body Lotion with Colloidal Oatmeal":6500,
  "Vaseline Intensive Care Calm Healing Body Lotion with Lavender Extract":6500,
  "Vaseline Intensive Care Unscented Advanced Repair Body Lotion":6500,
  "Vaseline Intensive Care Nourishing Moisture Body Lotion with Pure Oat Extract":6500,
  "St. Ives Coconut & Orchid Hand & Body Lotion, Softening Body Care for Dry Skin":6500,
  "St. Ives Hand & Body Lotion, Sweet Almond Oil, Restoring Body Care for Dry Skin":6500,
  "St. Ives Oatmeal & Shea Butter Hand & Body Lotion, Moisturizing Body Care for Dry Skin":6500,
  "St. Ives Renewing Hand & Body Lotion with Pump, Daily Moisturizer Collagen Elastin for Dry Skin, Made with 100% Natural Moisturizers":6500,
  "St. Ives Rose & Argan Oil Hand & Body Lotion, Smoothing Body Care for Dry Skin":6500,
  "St. Ives Vitamin E & Avocado Oil Hand & Body Lotion, Hydrating Body Care for Dry Skin":6500,
  "Cetaphil Face & Body Moisturizer, Hydrating Moisturizing Lotion for All Skin Types, Suitable for Sensitive Skin":12000,
  "CeraVe Daily Moisturizing Face & Body Lotion for Normal to Dry Skin":12000,
  "CeraVe Moisturizing Cream for Face & Body Normal to Very Dry Skin":12000,
  "Lubriderm Fragrance Free Daily Moisture Lotion + Pro-Ceramide, Shea Butter & Glycerin, Face, Hand & Body Lotion for Sensitive Skin, Hydrating Lotion for Healthier-Looking Skin":11000,
  "Lubriderm Daily Moisture Lotion + Pro-Ceramide, Shea Butter & Glycerin, Hydrating Face, Hand & Body Lotion, 24-hour Moisturizer for Dry Skin, 6 Fl Oz":6000,
  "Colgate Cavity Protection Toothpaste with Fluoride":3500,
  "Crest Pro-Health Advanced Mouthwash":9000,
  "Nair Body Cream, Hair Removal Cream":6500,
  "Men's Electric Shaver 3 in 1 - Portable USB Rechargeable Shaver Featuring 3D Floating Blades and a Digital Display Suitable for Both Wet and Dry Shaving":18000,
  "LQT Men's Electric Shaver | Exquisite Packaging Box, USB Charging, Lithium Battery, Matte Texture, Essential for Men, Beard Trimming":12000,
  "Suave Keratin Infusion Smoothing Shampoo & Conditioner Set For Frizzy Hair":13000,
  "Suave Moisturizing Shampoo & Conditioner With Almond & Shea Butter":13000,
  "TRESemmé Rich Moisture Shampoo and Conditioner Set":15000,
  "TRESemmé Shampoo & Conditioner Keratin Smooth":15000,
  "TRESemmé Shampoo And Conditioner Anti-Breakage":15000,
  "TRESemmé Shampoo and Conditioner for Women Amplified Volume Twin Pack":15000,
  "TRESemmé Silky & Smooth Anti-Frizz Shampoo & Conditioner for Frizzy Hair":15000,
  "Revlon ColorSilk Permanent Hair Color with Bond Repair Complex (Silver Blonde)":5000,
  "Revlon ColorSilk Permanent Hair Color with Bond Repair Complex (Dark Blonde)":5000,
  "Revlon ColorSilk Permanent Hair Color with Bond Repair Complex (Radiant Raspberry)":5000,
  "Revlon ColorSilk Permanent Hair Color with Bond Repair Complex (Auburn Brown)":5000,
  "Revlon ColorSilk Permanent Hair Color with Bond Repair Complex (Burgundy)":5000,
  "Revlon ColorSilk Permanent Hair Color with Bond Repair Complex (Bright Auburn)":5000,
  "Revlon ColorSilk Permanent Hair Color with Bond Repair Complex (Vibrant Violet)":5000,
  "Revlon ColorSilk Permanent Hair Color with Bond Repair Complex (Deep Burgundy)":5000,
  "Revlon ColorSilk Permanent Hair Color with Bond Repair Complex (Brown Black)":5000,
  "Revlon ColorSilk Permanent Hair Color with Bond Repair Complex (Black)":5000,
  "Revlon ColorSilk Permanent Hair Color with Bond Repair Complex (Ultra Light Ash Blonde)":5000,
  "Dr Teal's Kids 3-in-1 Sleep Bath: Bubble Bath, Body Wash & Shampoo with Melatonin & Essential Oil":9000,
  "Dr Teal's Kids 3-in-1 Bubble Bath, Body Wash & Shampoo with Ashwagandha":9000,
  "Suave Kids Paw Patrol 3-in-1 Shampoo, Conditioner & Body Wash, Adventure Bay Breeze":6000,
  "Cetaphil Baby Daily Lotion with Organic Calendula Vitamin E Sweet Almond & Sunflower Oils":8500,
  "CeraVe Baby Lotion, Gentle Baby Skin Care with Ceramides, Niacinamide & Vitamin E, Fragrance, Paraben, Dye & Phthalates Free":8500,
  "Febreze Air Freshener Spray, Odor-Fighting Room Spray, Linen & Sky":3000,
  "Febreze Air Mist Air Freshener Spray, Heavy Duty Crisp Clean":3000,
  "Febreze Air Mist Air Freshener Spray Hawaiian Aloha":3000,
  "Febreze Air Effects Air Freshener - Mountain Scent":3000,
  "Mop and Bucket Set, 360° Spin Mop and Bucket Set and 4 Microfiber Mop Refills":20000,
  "Manual Pasta Maker Machine, 9 Adjustable Thickness Settings":25000,
  "Reusable Stretchable Storage Lids for Round Pots, Bowls, and Cups – Air-Tight, Food-Safe":2000,
  "1000 Reusable Stretchable Storage Lids for Round Pots, Bowls, and Cups – Air-Tight, Food-Safe":3500,
  "Manual Food Chopper, Pull String Vegetable and Meat Mincer with Clear Container, Hand-Powered Kitchen Processor":8000,
  "8.45oz RZSYZH USB Rechargeable Electric mini Garlic  Blender with Stainless Steel Blades for Automatic Chopping":5000,
  "380Ml Rechargeable Portable Blender Cup, Electric USB Juicer Blender, Mini Blender for Shakes And Smoothies, Juice (Green)":8000,
  "380Ml Rechargeable Portable Blender Cup, Electric USB Juicer Blender, Mini Blender for Shakes And Smoothies, Juice (Black)":8000,
  "380Ml Rechargeable Portable Blender Cup, Electric USB Juicer Blender, Mini Blender for Shakes And Smoothies, Juice (Pink)":8000,
  "380Ml Rechargeable Portable Blender Cup, Electric USB Juicer Blender, Mini Blender for Shakes And Smoothies, Juice (Deep Purple)":8000,
  "380Ml Rechargeable Portable Blender Cup, Electric USB Juicer Blender, Mini Blender for Shakes And Smoothies, Juice (Blue)":8000,
  "Manual Chopper. No Need for Electricity (Pink)":10000,
  "Manual Chopper. No Need for Electricity (Gray)":10000,
  "Smart Watch Compatible with iPhone & Android Devices (Pink)":6500,
  "Smart Watch Compatible with iPhone & Android Devices (Black)":6500,
  "Z68 Smartwatch Featuring Wireless Calling, Message Alerts, Various Sports Modes, Information Notifications, Multifunctional Phone Answering/Dialing, Remote Photography, Music Playback, Sports Tracking (Black)":10000,
  "Z68 Smartwatch Featuring Wireless Calling, Message Alerts, Various Sports Modes, Information Notifications, Multifunctional Phone Answering/Dialing, Remote Photography, Music Playback, Sports Tracking (Gold)":10000,
  "Z68 Smartwatch Featuring Wireless Calling, Message Alerts, Various Sports Modes, Information Notifications, Multifunctional Phone Answering/Dialing, Remote Photography, Music Playback, Sports Tracking (Pink)":10000,
  "LAXASFIT Unisex Smartwatch Compatible with Both iPhone and Android":10000,
  "LAXASFIT Unisex Smartwatch Compatible with Both iPhone and Android (Black)":10000,
  "PLEIVO New Smart Watch with 2.01-inch Large Screen, LED Flashlight, Outdoor Sports Watch, Fitness Tracker Compatible with Android and iPhone":10000,
  "PLEIVO New Smart Watch with 2.01-inch Large Screen, LED Flashlight, Outdoor Sports Watch, Fitness Tracker Compatible with Android and iPhone (Black)":10000,
  "Smart Watch with A Metal Frame, Ceramic Base, And A 2.01-inch Large Screen":10500,
  "Smart Watch with A Metal Frame, Ceramic Base, And A 2.01-inch Large Screen (Black)":10500,
  "GT4 PRO Smartwatch Featuring a Full Touchscreen, Wireless Calling, Over 100 Sports Modes, Weather Updates, Stopwatch, Timer, Alarm, SMS Notifications":10000,
  "GT4 PRO Smartwatch Featuring a Full Touchscreen, Wireless Calling, Over 100 Sports Modes, Weather Updates, Stopwatch, Timer, Alarm, SMS Notifications (Black)":10000,
  "Doviico 1.83-inch Touch Screen, Supports Call Function, Incoming Call And Message Notifications, Multifunctional Fitness And Sports Smart Bracelet, Wireless Connection with Android And Phones":6500,
  "Doviico 1.83-inch Touch Screen, Supports Call Function, Incoming Call And Message Notifications, Multifunctional Fitness And Sports Smart Bracelet, Wireless Connection with Android And Phones (Pink)":6500,
  "256 GB Memory Card with Adapter - Enough Storage for your data":6000,
  "20000mAh Large-Capacity Mobile Power Bank":7000,
  "Rechargeable LED Torch/Flashlight With High Power Lamp":5000,
  "Rechargeable Portable Fan (Purple)":3500,
  "Rechargeable Portable Fan (Pink)":3500,
  "Rechargeable Portable Fan (Black)":3500,
  "Dark Grey USB Rechargeable Fan":3500,
  "Dark Grey USB Rechargeable Fan (Black)":3500,
  "YISSVIC Rechargeable Electric Fly Swatter Foldable Bug Zapper":12000,
  "TG192 A Large Capacity 2400mAh Outdoor Wireless Speaker (Green)":12000,
  "TG192 A Large Capacity 2400mAh Outdoor Wireless Speaker (Blue)":12000,
  "TG192 A Large Capacity 2400mAh Outdoor Wireless Speaker (Dark Red)":12000,
  "TG192 A Large Capacity 2400mAh Outdoor Wireless Speaker (Black)":12000,
  "TG192 A Large Capacity 2400mAh Outdoor Wireless Speaker (Army Green)":12000,
  "TG537 Portable Wireless Speaker - TWS Technology (Blue)":10000,
  "TG537 Portable Wireless Speaker - TWS Technology (Red)":10000,
  "TG537 Portable Wireless Speaker - TWS Technology (Black)":10000,
  "TG537 Portable Wireless Speaker - TWS Technology (Cyan)":10000,
  "TG667 Compact and Portable Wireless Speaker. It Supports USB, TF Cards, and FM Radio, and Can Connect to Mobile Phones, Tablets (Red)":8500,
  "TG667 Compact and Portable Wireless Speaker. It Supports USB, TF Cards, and FM Radio, and Can Connect to Mobile Phones, Tablets (Blue)":8500,
  "TG667 Compact and Portable Wireless Speaker. It Supports USB, TF Cards, and FM Radio, and Can Connect to Mobile Phones, Tablets (Black)":8500,
  "TG667 Compact and Portable Wireless Speaker. It Supports USB, TF Cards, and FM Radio, and Can Connect to Mobile Phones, Tablets (Purple)":8500,
  "Hyundai LP5t Wireless Headphones with Surround Sound and Noise Cancellation (Gray)":7200,
  "Hyundai LP5t Wireless Headphones with Surround Sound and Noise Cancellation (White)":7200,
  "JS59 Wireless Headphones, 5.4 Headphones with 4 ENC Noise-Cancelling Microphones, HiFi Stereo, 50 Hours of Playback Time, USB-C":2500,
  "JS59 Wireless Headphones, 5.4 Headphones with 4 ENC Noise-Cancelling Microphones, HiFi Stereo, 50 Hours of Playback Time, USB-C (Black)":2500,
  "YD03 Wireless Earbuds":4500,
  "Airpod Pro":15000,
  "Monster Wireless Earbuds with CVC 8.0 Noise Reduction":4000,
  "Acer OHR544 Wireless Headset with Heavy Bass Stereo + Talking Noise Cancellation (Orange)":6000,
  "Acer OHR544 Wireless Headset with Heavy Bass Stereo + Talking Noise Cancellation (Beige)":6000,
  "Acer OHR544 Wireless Headset with Heavy Bass Stereo + Talking Noise Cancellation (Black)":6000,
  "Ace OHR501 Wireless 5.4 Bass Stereo Headset with Noise Cancelling Microphone, Touch Control (Purple)":5500,
  "Ace OHR501 Wireless 5.4 Bass Stereo Headset with Noise Cancelling Microphone, Touch Control (Black)":5500,
  "Nokia Go Earbuds+ TWS-201 (White)":10000,
  "Nokia Go Earbuds+ TWS-201 (Black)":10000,
  "Wireless Earbuds with Charging Case, USB Type-C Charging, TWS Technology":6500,
  "Wireless Earbuds with Charging Case, USB Type-C Charging, TWS Technology (White)":6500,
  "Y01 Over the Ear Wireless Headphones":13000,
  "TOZO T6 Wireless Earbuds IPX8 Waterproof":9000,
  "Portable Wireless Speaker, 15W Stereo, RGB Lighting, Suitable for Both Indoor and Outdoor Use":6500,
  "4-in-1 USB Mini SD Card Reader with Dual Card Slots":3500,
  "USB 2.0 Type-C Flash Drive 128GB High-Speed Memory Stick":5000,
  "Data Cable, USB C to USB C Charging Cable, Type C to Type C Fast Charging":1500,
  "7-in-1 USB Extender Hub":8000,
  "USB to USB C Charging Cable":1500,
  "USB iPhone Charging Cable":1500,
  "45W High Quality Type-C Charger - Super Fast Charging (Black)":3500,
  "45W High Quality Type-C Charger - Super Fast Charging (White)":3500,
  "Non-Contact Forehead Digital Thermometer":8000,
  "Rechargeable/Battery Powered (Optional) Digital Wrist Blood Pressure Monitor with Large LCD, Voice Broadcast (Optional)":20000,
  "Rechargeable Arm Blood Pressure Monitor with Large LED Screen, Digital Blood Pressure Machine":20000,
  "Rechargeable Digital Scale with USB Charging":15000,
  "Degree Men Black + White Ultraclear Deodorant":3000,
  "AXE Antiperspirant Deodorant for Men Essence, 48H Sweat & Odor Protection, Black Pepper & Cedarwood":3000,
  "AXE Apollo Antiperspirant Deodorant Stick For Men, Sage & Cedarwood, 48 Hr Anti Sweat":3000,
  "Right Guard Sport Fresh Scent Antiperspirant & Deodorant Invisible Solid 4-in-1 For Men, 48-Hour Odor Protection":2000,
  "Lady Speed Stick 72HR Antiperspirant Deodorant for Women, Invisible Dry, Shower Fresh Scent, 2.3 oz":1500,
  "Suave Antiperspirant Deodorant For Women, 48hr Protection, Fresh":2300,
  "Suave Antiperspirant Deodorant For Women, 48hr Protection, Powder":2300,
  "Suave Antiperspirant Deodorant For Women, Wild Cherry Blossom":2300,
  "Suave Antiperspirant Deodorant For Women, 48hr Protection, Tropical Paradise":2300,
  "Degree Original Antiperspirant Deodorant Sheer Powder":3200,
  "Loveryblack Affinity At Midnight Pheromone Perfume (Unisex)":10000,
  "Loveryblack Affinity For Him Pheromone Cologne":10000,
  "Loveryblack Affinity Pure Passion Pheromone Perfume (Unisex)":10000,
  "Mens Cologne Bross 3.4oz Eau De Parfum Spray, Masculine Mist":10000,
  "Men'S Extreme 3.4oz Eau De Parfum - Men Perfume Spray":10000,
  "Daspar De Homme Men Perfume with Pheromones":4000,
  "Adventure Club Perfume For Men":7000,
  "Azure Vantage Aqua Spray Cologne Eau De Parfum For Men":5000,
  "Investor Gold Spray Cologne Eau De Parfum For Men":5000,
  "Invincible Black Spray Cologne For Men EDP":5000,
  "Invincible Platinum Spray Cologne Eau De Toilette For Men":5000,
  "Magic Code Spray Cologne Eau De Toilette For Men":5000,
  "Prism Cologne Eau De Toilette For Men":5000,
  "Victorious Heroes Spray Cologne Eau De Toilette For Men":6000,
  "Taj Max Aqua Sport 3.4 oz Long-Lasting Perfume":12000,
  "Taj Max Exotic Bliss 3.4 oz Long-Lasting Perfume":12000,
  "Paris Night Just for Men":5000,
  "Infinity 3.3 oz EDT For Men":5000,
  "Platinum 3.4 oz EDP For Men":5000,
  "Savage 3.4 oz EDP For Men":5000,
  "Victory 3.3 oz EDT For Men":5000,
  "Hercules Paris 3.4 oz EDP For Men":5000,
  "Men'S Salvang 3.4oz Eau De Parfume":10000,
  "Bleu De Rivoli Eau De Parfum":15000,
  "Blue Intense Eau De Parfum":15000,
  "Eau De Vivre Eau De Parfum":15000,
  "Imperial Oud Eau De Parfum":15000,
  "Legende Intense Eau De Parfum":15000,
  "Reverie Pour Homme Eau De Parfum":15000,
  "Fantastic Pink 3.4 oz EDP For Women":5000,
  "Fleur De Paris 3.4 oz EDP For Women":8000,
  "Lazell Night Bloom For Women EDP 3.4 oz":8000,
  "Lazell Spring For Women EDP 3.4 oz":8000,
  "Miss Coco 3.4 oz EDP For Women":5000,
  "Flower Eau De Parfum For Women":10000,
  "Lazell Black Onyx Women EDP 3.4":8000,
  "Harmonie De Jour Eau De Parfum":15000,
  "Charm Spray Perfume Eau De Parfum For Women":10000,
  "Daicy Blue Spray Perfume Eau De Parfum":10000,
  "Flower Pink Spray Perfume Eau De Parfum":10000,
  "Gorgeous Flower Spray Perfume Eau De Parfum":10000,
  "Honey Bear Pink Spray Perfume Eau De Parfum":10000,
  "Love Is Forever Spray Perfume Eau De Parfum":10000,
  "Nice Girl Spray Perfume Eau De Parfum For Women":10000,
  "Princess High Heels Pink Spray Perfume Eau De Parfum":10000,
  "Sexy Rose Pink Spray Perfume Eau De Parfum":10000,
  "365 Day Spray Perfume Eau De Parfum For Women":6500,
  "Island Coconut Body Mist":3500,
  "Passion Fruit Body Mist":3500,
  "Watermelon Sugar Body Mist":3500,
  "Pineapple Dream Body Mist":3500,
  "So Fresh La Vanille Angel Cake Body Mist - Vanilla Marshmallow":3500,
  "So Fresh La Vanille Angel Cake Body Mist - Vanilla Coconut":3500,
  "So Fresh La Vanille Angel Cake Body Mist - Vanilla Ice Cream":3500,
  "So Fresh The Beaches Collection Let's Get Away Body Mist - South Beach":3500,
  "So Fresh The Beaches Collection Let's Get Away Body Mist - Copacabana Beach":3500,
  "So Fresh The Beaches Collection Let's Get Away Body Mist - Maui Beach":3500,
  "So Fresh The Dulce Collection Utopia Body Mist - Red Velvet":3500,
  "So Fresh The Dulce Collection Utopia Body Mist - Chocolate Dreams":3500,
  "So Fresh The Dulce Collection Utopia Body Mist - Cherry On Top":3500,
  "So Fresh Wildflower Collection Morning Dew Body Mist - Forget Me Not":3500,
  "So Fresh Wildflower Collection Morning Dew Body Mist - Sunset Blossom":3500,
  "So Fresh Wildflower Collection Morning Dew Body Mist - Desert Willow":3500,
};

// Load quantities from products-list.json
const jsonProducts = JSON.parse(readFileSync('public/api/products-list.json', 'utf8'));
const quantities = {};
jsonProducts.forEach(p => { quantities[p.name] = p.quantity || 0; });

// Extract quantities from categories.ts for perfumes/deodorants/body mists
const catText = readFileSync('src/data/categories.ts', 'utf8');
const blocks = catText.match(/\{[^{}]*name:\s*['"]([^'"]+)['"][^{}]*quantity:\s*(\d+)[^{}]*\}/gs) || [];
blocks.forEach(block => {
  const nm = block.match(/name:\s*['"]([^'"]+)['"]/);
  const qt = block.match(/quantity:\s*(\d+)/);
  if (nm && qt && !quantities[nm[1]]) quantities[nm[1]] = parseInt(qt[1]);
});

// Category groupings
const catMap = {
  'Body Wash':      n => /Body Wash|Deodorant Bar/i.test(n),
  'Body Lotion':    n => /Body Lotion|Moisturizing Lotion|Moisturizing Cream|Lubriderm/i.test(n),
  'Personal Care':  n => /Toothpaste|Mouthwash|Nair|Shaver/i.test(n),
  'Hair':           n => /Shampoo|Conditioner|ColorSilk/i.test(n),
  'Kids & Baby':    n => /Kids|Baby|Paw Patrol/i.test(n),
  'Household':      n => /Febreze|Mop/i.test(n),
  'Kitchen':        n => /Pasta Maker|Chopper|Blender|Garlic|Storage Lids/i.test(n),
  'Electronics':    n => /Watch|Earbuds|Headphones|Headset|Speaker|Power Bank|Fan|Flashlight|Torch|Memory Card|USB|Cable|Charger|Fly Swatter|Thermometer|Blood Pressure|Scale/i.test(n),
  'Perfumes & Deodorants': n => /Perfume|Cologne|EDP|EDT|Deodorant|Antiperspirant|Body Mist|Pheromone|Parfum|Mist/i.test(n),
};

const results = [];
let totalRevFCFA = 0;
let totalUnits = 0;

for (const [name, price] of Object.entries(livePrices)) {
  const qty = quantities[name] || 0;
  const revenue = price * qty;
  totalRevFCFA += revenue;
  totalUnits += qty;

  let cat = 'Other';
  for (const [c, fn] of Object.entries(catMap)) {
    if (fn(name)) { cat = c; break; }
  }
  results.push({ name, price, qty, revenue, cat });
}

// Category summary
const catSummary = {};
for (const r of results) {
  if (!catSummary[r.cat]) catSummary[r.cat] = { units: 0, revenue: 0, items: 0 };
  catSummary[r.cat].units += r.qty;
  catSummary[r.cat].revenue += r.revenue;
  catSummary[r.cat].items++;
}

console.log('\n╔══════════════════════════════════════════════════════════════════╗');
console.log('║           AMERICAN SELECT — PROFITABILITY ANALYSIS              ║');
console.log('╚══════════════════════════════════════════════════════════════════╝\n');

console.log('── REVENUE POTENTIAL BY CATEGORY (current stock × selling price) ──\n');
console.log('Category                  Units    Revenue (FCFA)     Revenue (USD)');
console.log('─'.repeat(68));
let catTotal = 0;
let catUnits = 0;
for (const [cat, s] of Object.entries(catSummary)) {
  const usd = (s.revenue / EXCHANGE).toFixed(0);
  console.log(
    cat.padEnd(26) +
    String(s.units).padStart(5) +
    '    ' + s.revenue.toLocaleString().padStart(15) + ' FCFA' +
    '    $' + usd
  );
  catTotal += s.revenue;
  catUnits += s.units;
}
console.log('─'.repeat(68));
console.log('TOTAL'.padEnd(26) + String(catUnits).padStart(5) + '    ' + catTotal.toLocaleString().padStart(15) + ' FCFA    $' + (catTotal/EXCHANGE).toFixed(0));

console.log('\n── FINANCIAL SUMMARY ──────────────────────────────────────────────\n');
const grossProfit = totalRevFCFA - TOTAL_SPENT_FCFA;
const roi = ((totalRevFCFA / TOTAL_SPENT_FCFA) - 1) * 100;
console.log('Total you spent (all expenses):    ' + TOTAL_SPENT_FCFA.toLocaleString() + ' FCFA   ($' + TOTAL_SPENT_USD.toFixed(2) + ')');
console.log('Total revenue if all stock sold:   ' + totalRevFCFA.toLocaleString() + ' FCFA   ($' + (totalRevFCFA/EXCHANGE).toFixed(0) + ')');
console.log('Gross profit potential:            ' + grossProfit.toLocaleString() + ' FCFA   ($' + (grossProfit/EXCHANGE).toFixed(0) + ')');
console.log('Return on investment (ROI):        ' + roi.toFixed(1) + '%');
console.log('');
console.log('Break-even at:  ~' + Math.round(TOTAL_SPENT_FCFA / totalUnits).toLocaleString() + ' FCFA avg per unit sold  (avg selling price: ' + Math.round(totalRevFCFA/totalUnits).toLocaleString() + ' FCFA)');
