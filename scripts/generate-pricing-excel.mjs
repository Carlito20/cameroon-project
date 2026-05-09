import ExcelJS from 'exceljs';

const data = [
  // BODY WASH
  ["BODY WASH","Dr Teal's Body Wash - Prebiotic Lemon Balm 24oz",5.87,5000,8500,"Underpriced"],
  ["BODY WASH","Dr Teal's Body Wash - Eucalyptus Spearmint 24oz",5.87,5000,8500,"Underpriced"],
  ["BODY WASH","Dr Teal's Body Wash - Vanilla Comfort 24oz",5.87,5000,8500,"Underpriced"],
  ["BODY WASH","Dr Teal's Body Wash - Hawaiian Bliss 24oz",5.87,5000,8500,"Underpriced"],
  ["BODY WASH","Dr Teal's Body Wash - Soothe & Sleep Lavender 24oz",5.87,5000,8500,"Underpriced"],
  ["BODY WASH","Dr Teal's Body Wash - Coconut Oil 24oz",5.87,5000,8500,"Underpriced"],
  ["BODY WASH","Dr Teal's Body Wash - Shea Butter & Almond 24oz",5.87,5000,8500,"Underpriced"],
  ["BODY WASH","Olay Essential Botanicals - Spiced Chai & Pear",7.00,3500,10000,"WAY UNDERPRICED"],
  ["BODY WASH","Olay Essential Botanicals - White Tea & Cucumber",7.00,3500,10000,"WAY UNDERPRICED"],
  ["BODY WASH","Olay Essential Botanicals - Lavender Milk & Sandalwood",7.00,3500,10000,"WAY UNDERPRICED"],
  ["BODY WASH","Suave Men Hydrating 3-in-1 Body Hair Face Wash 18oz",2.97,3000,4500,"Underpriced"],
  ["BODY WASH","Suave Moisturizing Body Wash - Cocoa Butter & Shea 18oz",2.97,3000,4500,"Underpriced"],
  ["BODY WASH","Suave Cocoa Butter + Shea Body Wash 30oz",4.68,5000,7000,"Underpriced"],
  ["BODY WASH","Suave Moisturizing Body Wash - Sweet Pea & Violet 18oz",2.97,3000,4500,"Underpriced"],
  ["BODY WASH","Suave Ocean Breeze Body Wash 18oz",2.97,3000,4500,"Underpriced"],
  ["BODY WASH","Suave Ocean Breeze Body Wash 30oz",4.68,5000,7000,"Underpriced"],
  ["BODY WASH","Suave Wild Cherry Blossom Body Wash 30oz",4.68,5000,7000,"Underpriced"],
  ["BODY WASH","Suave Strawberry Delight Body Wash 30oz",4.68,5000,7000,"Underpriced"],
  ["BODY WASH","Suave Milk & Honey Body Wash 30oz",4.68,5000,7000,"Underpriced"],
  ["BODY WASH","NIVEA MEN Maximum Hydration 3-in-1 Body Wash",6.50,3500,9500,"WAY UNDERPRICED"],
  ["BODY WASH","Irish Spring Original Clean Deodorant Bar Soap",1.00,1300,1500,"Acceptable"],
  ["BODY WASH","St. Ives Body Wash - Pink Lemon & Mandarin Orange 22oz",4.50,4500,6500,"Slight raise"],
  ["BODY WASH","St. Ives Body Wash - Sea Salt & Pacific Kelp 22oz",4.50,4500,6500,"Slight raise"],
  ["BODY WASH","St. Ives Body Wash - Oatmeal & Shea Butter 22oz",4.50,4500,6500,"Slight raise"],
  // BODY LOTION
  ["BODY LOTION","Olay Body Lotion - Age Defying with Niacinamide Serum",10.00,10000,15000,"Could go higher"],
  ["BODY LOTION","Olay Body Lotion - Nourishing with Hyaluronic Acid Serum",10.00,10000,15000,"Could go higher"],
  ["BODY LOTION","Olay Body Lotion - Smoothing with Retinol Serum",10.00,10000,15000,"Could go higher"],
  ["BODY LOTION","Olay Body Lotion - Tone Enhancing with AHA Serum",10.00,10000,15000,"Could go higher"],
  ["BODY LOTION","Aveeno Daily Moisturizing Body Lotion 18oz",9.00,6000,13500,"WAY UNDERPRICED"],
  ["BODY LOTION","Jergens Hydrating Coconut Body Lotion",6.00,6500,9000,"Slight raise"],
  ["BODY LOTION","Jergens Shea Butter Hand and Body Lotion",6.00,6500,9000,"Slight raise"],
  ["BODY LOTION","Jergens Soothing Aloe Body Lotion",6.00,6500,9000,"Slight raise"],
  ["BODY LOTION","Jergens Skin Firming Body Lotion with Collagen & Elastin",6.00,6500,9000,"Slight raise"],
  ["BODY LOTION","Jergens Ultra Healing Dry Skin Lotion",6.00,6500,9000,"Slight raise"],
  ["BODY LOTION","Dr Teal's 24hr Moisture+ Body Lotion - Prebiotic Lemon Balm",6.00,5000,9000,"Underpriced"],
  ["BODY LOTION","Dr Teal's 24hr Moisture+ Body Lotion - Eucalyptus & Spearmint",6.00,5000,9000,"Underpriced"],
  ["BODY LOTION","Dr Teal's 24hr Moisture+ Body Lotion - Coconut Oil",6.00,5000,9000,"Underpriced"],
  ["BODY LOTION","Dr Teal's 24hr Moisture+ Body Lotion - Shea Butter & Almond",6.00,5000,9000,"Underpriced"],
  ["BODY LOTION","Dr Teal's 24hr Moisture Body Lotion - Lavender",6.00,5000,9000,"Underpriced"],
  ["BODY LOTION","Dr Teal's 24hr Moisture Body Lotion - Vanilla Comfort",6.00,5000,9000,"Underpriced"],
  ["BODY LOTION","Vaseline Men Cooling Hydration 3-in-1 Lotion with Menthol",6.00,6500,9000,"Slight raise"],
  ["BODY LOTION","Vaseline Men Fast Absorbing 3-in-1 Lotion",6.00,6500,9000,"Slight raise"],
  ["BODY LOTION","Vaseline Intensive Care - Cocoa Radiant",6.00,6500,9000,"Slight raise"],
  ["BODY LOTION","Vaseline Intensive Care - Soothing Hydration Aloe Vera",6.00,6500,9000,"Slight raise"],
  ["BODY LOTION","Vaseline Intensive Care - Sensitive Skin Relief Oatmeal",6.00,6500,9000,"Slight raise"],
  ["BODY LOTION","Vaseline Intensive Care - Calm Healing Lavender",6.00,6500,9000,"Slight raise"],
  ["BODY LOTION","Vaseline Intensive Care - Unscented Advanced Repair",6.00,6500,9000,"Slight raise"],
  ["BODY LOTION","Vaseline Intensive Care - Nourishing Moisture Oat Extract",6.00,6500,9000,"Slight raise"],
  ["BODY LOTION","St. Ives Hand & Body Lotion - Coconut & Orchid",6.00,6000,9000,"Slight raise"],
  ["BODY LOTION","St. Ives Hand & Body Lotion - Sweet Almond Oil",6.00,6000,9000,"Slight raise"],
  ["BODY LOTION","St. Ives Hand & Body Lotion - Oatmeal & Shea Butter",6.00,6000,9000,"Slight raise"],
  ["BODY LOTION","St. Ives Hand & Body Lotion - Collagen & Elastin with Pump",6.00,6000,9000,"Slight raise"],
  ["BODY LOTION","St. Ives Hand & Body Lotion - Rose & Argan Oil",6.00,6000,9000,"Slight raise"],
  ["BODY LOTION","St. Ives Hand & Body Lotion - Vitamin E & Avocado Oil",6.00,6000,9000,"Slight raise"],
  ["BODY LOTION","Cetaphil Face & Body Moisturizing Lotion (all skin types)",13.00,10000,19500,"VERY UNDERPRICED"],
  ["BODY LOTION","CeraVe Daily Moisturizing Face & Body Lotion (Normal to Dry)",15.00,10000,22500,"VERY UNDERPRICED"],
  ["BODY LOTION","CeraVe Moisturizing Cream (Normal to Very Dry)",20.00,10000,30000,"SELLING AT A LOSS"],
  ["BODY LOTION","Lubriderm Fragrance Free Daily Moisture Lotion (large)",10.00,10000,15000,"Could go higher"],
  ["BODY LOTION","Lubriderm Daily Moisture Lotion 6oz",4.00,3500,6000,"Underpriced"],
  // PERSONAL CARE
  ["PERSONAL CARE","Colgate Cavity Protection Toothpaste with Fluoride",2.50,1500,3500,"Underpriced"],
  ["PERSONAL CARE","Crest Pro-Health Advanced Mouthwash",6.00,4000,9000,"Underpriced"],
  ["PERSONAL CARE","Nair Body Cream Hair Removal Cream",9.00,4500,13500,"WAY UNDERPRICED"],
  ["PERSONAL CARE","Men's Electric Shaver 3-in-1 USB Rechargeable",18.00,15000,27000,"Underpriced"],
  ["PERSONAL CARE","LQT Men's Electric Shaver (Exquisite Box / USB Charging)",22.00,12000,33000,"VERY UNDERPRICED"],
  // HAIR
  ["HAIR","Suave Keratin Infusion Smoothing Shampoo & Conditioner Set",6.00,10000,9000,"Good margin"],
  ["HAIR","Suave Moisturizing Shampoo & Conditioner - Almond & Shea Butter",6.00,10000,9000,"Good margin"],
  ["HAIR","TRESemmé Rich Moisture Shampoo & Conditioner Set (28oz each)",11.00,10000,16500,"Could go higher"],
  ["HAIR","TRESemmé Keratin Smooth Shampoo & Conditioner Set",11.00,10000,16500,"Could go higher"],
  ["HAIR","TRESemmé Anti-Breakage Shampoo & Conditioner Set",11.00,10000,16500,"Could go higher"],
  ["HAIR","TRESemmé Amplified Volume Shampoo & Conditioner Set",11.00,10000,16500,"Could go higher"],
  ["HAIR","TRESemmé Silky & Smooth Anti-Frizz Shampoo & Conditioner Set",11.00,10000,16500,"Could go higher"],
  ["HAIR","Revlon ColorSilk Permanent Hair Color - Silver Blonde",4.50,4500,6500,"Underpriced"],
  ["HAIR","Revlon ColorSilk Permanent Hair Color - Dark Blonde",4.50,4500,6500,"Underpriced"],
  ["HAIR","Revlon ColorSilk Permanent Hair Color - Radiant Raspberry",4.50,4500,6500,"Underpriced"],
  ["HAIR","Revlon ColorSilk Permanent Hair Color - Auburn Brown",4.50,4500,6500,"Underpriced"],
  ["HAIR","Revlon ColorSilk Permanent Hair Color - Burgundy",4.50,4500,6500,"Underpriced"],
  ["HAIR","Revlon ColorSilk Permanent Hair Color - Bright Auburn",4.50,4500,6500,"Underpriced"],
  ["HAIR","Revlon ColorSilk Permanent Hair Color - Vibrant Violet",4.50,4500,6500,"Underpriced"],
  ["HAIR","Revlon ColorSilk Permanent Hair Color - Deep Burgundy",4.50,4500,6500,"Underpriced"],
  ["HAIR","Revlon ColorSilk Permanent Hair Color - Brown Black",4.50,4500,6500,"Underpriced"],
  ["HAIR","Revlon ColorSilk Permanent Hair Color - Black",4.50,4500,6500,"Underpriced"],
  ["HAIR","Revlon ColorSilk Permanent Hair Color - Ultra Light Ash Blonde",4.50,4500,6500,"Underpriced"],
  // KIDS & BABY
  ["KIDS & BABY","Dr Teal's Kids 3-in-1 Sleep Bath - Melatonin & Essential Oil",6.00,4500,9000,"Underpriced"],
  ["KIDS & BABY","Dr Teal's Kids 3-in-1 Bubble Bath - Ashwagandha",6.00,4500,9000,"Underpriced"],
  ["KIDS & BABY","Suave Kids Paw Patrol 3-in-1 Shampoo Conditioner Body Wash",4.00,4500,6000,"Acceptable"],
  ["KIDS & BABY","Cetaphil Baby Daily Lotion with Organic Calendula",10.00,8500,15000,"Underpriced"],
  ["KIDS & BABY","CeraVe Baby Lotion with Ceramides Niacinamide & Vitamin E",14.00,8000,21000,"VERY UNDERPRICED"],
  // HOUSEHOLD
  ["HOUSEHOLD","Febreze Air Freshener - Linen & Sky 8.8oz",3.97,2000,6000,"WAY UNDERPRICED"],
  ["HOUSEHOLD","Febreze Air Mist - Heavy Duty Crisp Clean 8.8oz",3.97,2000,6000,"WAY UNDERPRICED"],
  ["HOUSEHOLD","Febreze Air Mist - Hawaiian Aloha 8.8oz",3.97,2000,6000,"WAY UNDERPRICED"],
  ["HOUSEHOLD","Febreze Air Effects - Mountain Scent 8.8oz",3.97,2000,6000,"WAY UNDERPRICED"],
  ["HOUSEHOLD","360 Spin Mop and Bucket Set with 4 Microfiber Refills",40.00,15000,60000,"SELLING AT A LOSS"],
  // KITCHEN
  ["KITCHEN","Manual Pasta Maker Machine (9 Thickness Settings)",30.00,20000,45000,"VERY UNDERPRICED"],
  ["KITCHEN","Reusable Stretchable Storage Lids (small set)",7.00,2000,10500,"WAY UNDERPRICED"],
  ["KITCHEN","1000 Reusable Stretchable Storage Lids",10.00,3000,15000,"WAY UNDERPRICED"],
  ["KITCHEN","Manual Food Chopper Pull String Vegetable & Meat Mincer",12.00,8000,18000,"Underpriced"],
  ["KITCHEN","Electric Mini Garlic Blender USB Rechargeable 8.45oz",12.00,5000,18000,"VERY UNDERPRICED"],
  ["KITCHEN","380mL Rechargeable Portable Blender Cup - Green",20.00,8000,30000,"VERY UNDERPRICED"],
  ["KITCHEN","380mL Rechargeable Portable Blender Cup - Black",20.00,8000,30000,"VERY UNDERPRICED"],
  ["KITCHEN","380mL Rechargeable Portable Blender Cup - Pink",20.00,8000,30000,"VERY UNDERPRICED"],
  ["KITCHEN","380mL Rechargeable Portable Blender Cup - Deep Purple",20.00,8000,30000,"VERY UNDERPRICED"],
  ["KITCHEN","380mL Rechargeable Portable Blender Cup - Blue",20.00,8000,30000,"VERY UNDERPRICED"],
  ["KITCHEN","Manual Chopper No Electricity - Pink",12.00,10000,18000,"Underpriced"],
  ["KITCHEN","Manual Chopper No Electricity - Gray",12.00,10000,18000,"Underpriced"],
  // ELECTRONICS
  ["ELECTRONICS","Smart Watch iPhone & Android - Pink",28.00,5000,42000,"WAY UNDERPRICED"],
  ["ELECTRONICS","Smart Watch iPhone & Android - Black",28.00,5000,42000,"WAY UNDERPRICED"],
  ["ELECTRONICS","Z68 Smartwatch (Wireless Calling / Sports) - Black",35.00,8000,52500,"SELLING AT A LOSS"],
  ["ELECTRONICS","Z68 Smartwatch (Wireless Calling / Sports) - Gold",35.00,8000,52500,"SELLING AT A LOSS"],
  ["ELECTRONICS","Z68 Smartwatch (Wireless Calling / Sports) - Pink",35.00,8000,52500,"SELLING AT A LOSS"],
  ["ELECTRONICS","LAXASFIT Unisex Smartwatch (iPhone & Android)",35.00,8000,52500,"SELLING AT A LOSS"],
  ["ELECTRONICS","LAXASFIT Unisex Smartwatch - Black",35.00,8000,52500,"SELLING AT A LOSS"],
  ["ELECTRONICS","PLEIVO Smartwatch 2.01in LED Flashlight Sports Tracker",35.00,8000,52500,"SELLING AT A LOSS"],
  ["ELECTRONICS","PLEIVO Smartwatch 2.01in LED Flashlight - Black",35.00,8000,52500,"SELLING AT A LOSS"],
  ["ELECTRONICS","Smart Watch Metal Frame Ceramic Base 2.01in Screen",38.00,9000,57000,"SELLING AT A LOSS"],
  ["ELECTRONICS","Smart Watch Metal Frame Ceramic Base 2.01in Screen - Black",38.00,9000,57000,"SELLING AT A LOSS"],
  ["ELECTRONICS","GT4 PRO Smartwatch Full Touchscreen 100+ Sports Modes",35.00,8000,52500,"SELLING AT A LOSS"],
  ["ELECTRONICS","GT4 PRO Smartwatch Full Touchscreen 100+ Sports Modes - Black",35.00,8000,52500,"SELLING AT A LOSS"],
  ["ELECTRONICS","Doviico 1.83in Smart Fitness Bracelet",25.00,5000,37500,"WAY UNDERPRICED"],
  ["ELECTRONICS","Doviico 1.83in Smart Fitness Bracelet - Pink",25.00,5000,37500,"WAY UNDERPRICED"],
  ["ELECTRONICS","256 GB Memory Card with Adapter",18.00,4000,27000,"WAY UNDERPRICED"],
  ["ELECTRONICS","20000mAh Large-Capacity Mobile Power Bank",28.00,7000,42000,"SELLING AT A LOSS"],
  ["ELECTRONICS","Rechargeable LED Torch / Flashlight High Power",12.00,2500,18000,"WAY UNDERPRICED"],
  ["ELECTRONICS","Rechargeable Portable Fan - Purple",18.00,3000,27000,"SELLING AT A LOSS"],
  ["ELECTRONICS","Rechargeable Portable Fan - Pink",18.00,3000,27000,"SELLING AT A LOSS"],
  ["ELECTRONICS","Rechargeable Portable Fan - Black",18.00,3000,27000,"SELLING AT A LOSS"],
  ["ELECTRONICS","Dark Grey USB Rechargeable Fan",18.00,3000,27000,"SELLING AT A LOSS"],
  ["ELECTRONICS","Dark Grey USB Rechargeable Fan - Black",18.00,3000,27000,"SELLING AT A LOSS"],
  ["ELECTRONICS","YISSVIC Electric Fly Swatter Foldable Bug Zapper",18.00,10000,27000,"Underpriced"],
  ["ELECTRONICS","TG192 Outdoor Wireless Speaker 2400mAh - Green",25.00,12000,37500,"Underpriced"],
  ["ELECTRONICS","TG192 Outdoor Wireless Speaker 2400mAh - Blue",25.00,12000,37500,"Underpriced"],
  ["ELECTRONICS","TG192 Outdoor Wireless Speaker 2400mAh - Dark Red",25.00,12000,37500,"Underpriced"],
  ["ELECTRONICS","TG192 Outdoor Wireless Speaker 2400mAh - Black",25.00,12000,37500,"Underpriced"],
  ["ELECTRONICS","TG192 Outdoor Wireless Speaker 2400mAh - Army Green",25.00,12000,37500,"Underpriced"],
  ["ELECTRONICS","TG537 Portable Wireless Speaker TWS - Blue",20.00,10000,30000,"Underpriced"],
  ["ELECTRONICS","TG537 Portable Wireless Speaker TWS - Red",20.00,10000,30000,"Underpriced"],
  ["ELECTRONICS","TG537 Portable Wireless Speaker TWS - Black",20.00,10000,30000,"Underpriced"],
  ["ELECTRONICS","TG537 Portable Wireless Speaker TWS - Cyan",20.00,10000,30000,"Underpriced"],
  ["ELECTRONICS","TG667 Compact Speaker (USB TF FM Radio) - Red",15.00,8000,22500,"Underpriced"],
  ["ELECTRONICS","TG667 Compact Speaker (USB TF FM Radio) - Blue",15.00,8000,22500,"Underpriced"],
  ["ELECTRONICS","TG667 Compact Speaker (USB TF FM Radio) - Black",15.00,8000,22500,"Underpriced"],
  ["ELECTRONICS","TG667 Compact Speaker (USB TF FM Radio) - Purple",15.00,8000,22500,"Underpriced"],
  ["ELECTRONICS","Portable Wireless Speaker 15W Stereo RGB Lighting",25.00,6000,37500,"VERY UNDERPRICED"],
  ["ELECTRONICS","Hyundai LP5t Wireless Headphones Noise Cancellation - Gray",25.00,7000,37500,"VERY UNDERPRICED"],
  ["ELECTRONICS","Hyundai LP5t Wireless Headphones Noise Cancellation - White",25.00,7000,37500,"VERY UNDERPRICED"],
  ["ELECTRONICS","JS59 Wireless Headphones 4 ENC Mics HiFi 50hr USB-C",30.00,2500,45000,"SELLING AT A LOSS"],
  ["ELECTRONICS","JS59 Wireless Headphones 4 ENC Mics HiFi 50hr USB-C - Black",30.00,2500,45000,"SELLING AT A LOSS"],
  ["ELECTRONICS","YD03 Wireless Earbuds",18.00,4000,27000,"WAY UNDERPRICED"],
  ["ELECTRONICS","Airpod Pro (clone)",25.00,15000,37500,"Underpriced"],
  ["ELECTRONICS","Monster Wireless Earbuds CVC 8.0 Noise Reduction",28.00,4000,42000,"SELLING AT A LOSS"],
  ["ELECTRONICS","Acer OHR544 Wireless Headset Heavy Bass - Orange",32.00,5500,48000,"SELLING AT A LOSS"],
  ["ELECTRONICS","Acer OHR544 Wireless Headset Heavy Bass - Beige",32.00,5500,48000,"SELLING AT A LOSS"],
  ["ELECTRONICS","Acer OHR544 Wireless Headset Heavy Bass - Black",32.00,5500,48000,"SELLING AT A LOSS"],
  ["ELECTRONICS","Ace OHR501 Wireless Headset Touch Control - Purple",32.00,5500,48000,"SELLING AT A LOSS"],
  ["ELECTRONICS","Ace OHR501 Wireless Headset Touch Control - Black",32.00,5500,48000,"SELLING AT A LOSS"],
  ["ELECTRONICS","Nokia Go Earbuds+ TWS-201 - White",40.00,8000,60000,"SELLING AT A LOSS"],
  ["ELECTRONICS","Nokia Go Earbuds+ TWS-201 - Black",40.00,8000,60000,"SELLING AT A LOSS"],
  ["ELECTRONICS","Wireless Earbuds with Charging Case USB-C TWS",25.00,6000,37500,"VERY UNDERPRICED"],
  ["ELECTRONICS","Wireless Earbuds with Charging Case USB-C TWS - White",25.00,6000,37500,"VERY UNDERPRICED"],
  ["ELECTRONICS","Y01 Over the Ear Wireless Headphones",32.00,13000,48000,"Underpriced"],
  ["ELECTRONICS","TOZO T6 Wireless Earbuds IPX8 Waterproof",30.00,8000,45000,"VERY UNDERPRICED"],
  ["ELECTRONICS","4-in-1 USB Mini SD Card Reader Dual Card Slots",7.00,2000,10500,"WAY UNDERPRICED"],
  ["ELECTRONICS","USB 2.0 Type-C Flash Drive 128GB High-Speed",12.00,4000,18000,"Underpriced"],
  ["ELECTRONICS","7-in-1 USB Extender Hub",20.00,5000,30000,"VERY UNDERPRICED"],
  // HEALTH
  ["HEALTH","Non-Contact Forehead Digital Thermometer",20.00,3000,30000,"SELLING AT A LOSS"],
  ["HEALTH","Digital Wrist Blood Pressure Monitor Rechargeable LCD",32.00,15000,48000,"VERY UNDERPRICED"],
  ["HEALTH","Rechargeable Arm Blood Pressure Monitor Large LED Screen",40.00,15000,60000,"SELLING AT A LOSS"],
  ["HEALTH","Rechargeable Digital Scale with USB Charging",20.00,10000,30000,"Underpriced"],
  // CABLES & CHARGERS
  ["CABLES & CHARGERS","Data Cable USB-C to USB-C Fast Charging",7.00,1000,10500,"WAY UNDERPRICED"],
  ["CABLES & CHARGERS","USB to USB-C Charging Cable",7.00,1000,10500,"WAY UNDERPRICED"],
  ["CABLES & CHARGERS","USB iPhone Charging Cable",7.00,1000,10500,"WAY UNDERPRICED"],
  ["CABLES & CHARGERS","45W Type-C Charger Super Fast Charging - Black",15.00,3500,22500,"WAY UNDERPRICED"],
  ["CABLES & CHARGERS","45W Type-C Charger Super Fast Charging - White",15.00,3500,22500,"WAY UNDERPRICED"],
];

// Color map per status
const statusColors = {
  "Good margin":       { bg: "FF2E7D32", font: "FFFFFFFF" }, // dark green
  "Acceptable":        { bg: "FF4CAF50", font: "FFFFFFFF" }, // green
  "Could go higher":   { bg: "FF8BC34A", font: "FF000000" }, // light green
  "Slight raise":      { bg: "FFFFF9C4", font: "FF000000" }, // light yellow
  "Could raise":       { bg: "FFFFF9C4", font: "FF000000" }, // light yellow
  "Underpriced":       { bg: "FFFFC107", font: "FF000000" }, // amber
  "WAY UNDERPRICED":   { bg: "FFFF5722", font: "FFFFFFFF" }, // deep orange
  "VERY UNDERPRICED":  { bg: "FFE53935", font: "FFFFFFFF" }, // red
  "SELLING AT A LOSS": { bg: "FF7B1FA2", font: "FFFFFFFF" }, // purple
};

const wb = new ExcelJS.Workbook();
wb.creator = 'American Select';

// ── LEGEND sheet ─────────────────────────────────────────────
const legend = wb.addWorksheet('Legend');
legend.columns = [
  { width: 22 },
  { width: 48 },
];
legend.addRow(['STATUS', 'MEANING']).eachCell(c => {
  c.font = { bold: true, color: { argb: 'FFFFFFFF' } };
  c.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF212121' } };
  c.alignment = { vertical: 'middle', horizontal: 'center' };
});
const legendRows = [
  ["Good margin",       "You are already making a strong profit — keep it."],
  ["Acceptable",        "Price is close to right. Minor adjustment optional."],
  ["Could go higher",   "Priced fairly but you could safely earn a bit more."],
  ["Slight raise",      "Small increase recommended (+20–30%)."],
  ["Underpriced",       "Needs a price raise — you are leaving money on the table."],
  ["WAY UNDERPRICED",   "Significant gap — raise price urgently."],
  ["VERY UNDERPRICED",  "You are barely covering costs — raise immediately."],
  ["SELLING AT A LOSS", "You are losing money on every sale — fix this first."],
];
legendRows.forEach(([status, meaning]) => {
  const row = legend.addRow([status, meaning]);
  const col = statusColors[status];
  row.eachCell(c => {
    c.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: col.bg } };
    c.font = { bold: status === 'SELLING AT A LOSS' || status === 'VERY UNDERPRICED', color: { argb: col.font } };
    c.alignment = { vertical: 'middle', wrapText: true };
    c.border = { bottom: { style: 'thin', color: { argb: 'FF444444' } } };
  });
  row.height = 22;
});

// ── MAIN sheet ───────────────────────────────────────────────
const ws = wb.addWorksheet('Pricing Analysis');

ws.columns = [
  { key: 'category',  width: 18  },
  { key: 'product',   width: 58  },
  { key: 'us_price',  width: 16  },
  { key: 'current',   width: 18  },
  { key: 'suggested', width: 18  },
  { key: 'status',    width: 22  },
];

// Header row
const headerRow = ws.addRow(['Category', 'Product', 'US Retail (USD)', 'Current Price (XAF)', 'Suggested Price (XAF)', 'Status']);
headerRow.height = 30;
headerRow.eachCell(c => {
  c.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF1A237E' } };
  c.font = { bold: true, color: { argb: 'FFFFFFFF' }, size: 11 };
  c.alignment = { vertical: 'middle', horizontal: 'center', wrapText: true };
  c.border = { bottom: { style: 'medium', color: { argb: 'FFD4AF37' } } };
});

// Freeze header
ws.views = [{ state: 'frozen', ySplit: 1 }];

// Auto-filter
ws.autoFilter = { from: 'A1', to: 'F1' };

let lastCategory = '';
let shadingToggle = false;

data.forEach(([category, product, usPrice, current, suggested, status]) => {
  if (category !== lastCategory) {
    shadingToggle = !shadingToggle;
    lastCategory = category;
  }

  const row = ws.addRow([category, product, usPrice, current, suggested, status]);
  row.height = 18;

  const col = statusColors[status] ?? { bg: 'FFFFFFFF', font: 'FF000000' };
  const baseBg = shadingToggle ? 'FFF5F5F5' : 'FFFFFFFF';

  row.getCell(1).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFE8EAF6' } };
  row.getCell(1).font = { bold: true, size: 10, color: { argb: 'FF1A237E' } };

  row.getCell(2).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: baseBg } };
  row.getCell(2).font = { size: 10 };

  // US Price — formatted as currency
  row.getCell(3).value = usPrice;
  row.getCell(3).numFmt = '"$"#,##0.00';
  row.getCell(3).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: baseBg } };
  row.getCell(3).alignment = { horizontal: 'center' };

  // Current Price
  row.getCell(4).value = current;
  row.getCell(4).numFmt = '#,##0 "XAF"';
  row.getCell(4).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: baseBg } };
  row.getCell(4).alignment = { horizontal: 'center' };

  // Suggested Price — bold
  row.getCell(5).value = suggested;
  row.getCell(5).numFmt = '#,##0 "XAF"';
  row.getCell(5).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFFF8E1' } };
  row.getCell(5).font = { bold: true, size: 10 };
  row.getCell(5).alignment = { horizontal: 'center' };

  // Status cell — fully color coded
  const statusCell = row.getCell(6);
  statusCell.value = status;
  statusCell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: col.bg } };
  statusCell.font = { bold: true, size: 10, color: { argb: col.font } };
  statusCell.alignment = { horizontal: 'center', vertical: 'middle' };

  // Light border between rows
  row.eachCell(c => {
    c.border = { bottom: { style: 'hair', color: { argb: 'FFDDDDDD' } } };
    c.alignment = { ...c.alignment, vertical: 'middle' };
  });
});

// ── SUMMARY sheet ────────────────────────────────────────────
const summary = wb.addWorksheet('Summary by Category');
summary.columns = [
  { width: 20 },
  { width: 14 },
  { width: 18 },
  { width: 20 },
  { width: 22 },
];

const sumHeader = summary.addRow(['Category', '# Items', 'Avg US Price', 'Avg Current (XAF)', 'Avg Suggested (XAF)']);
sumHeader.height = 28;
sumHeader.eachCell(c => {
  c.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF1A237E' } };
  c.font = { bold: true, color: { argb: 'FFFFFFFF' }, size: 11 };
  c.alignment = { vertical: 'middle', horizontal: 'center' };
  c.border = { bottom: { style: 'medium', color: { argb: 'FFD4AF37' } } };
});

const categories = {};
data.forEach(([cat, , us, cur, sug]) => {
  if (!categories[cat]) categories[cat] = { count: 0, us: 0, cur: 0, sug: 0 };
  categories[cat].count++;
  categories[cat].us += us;
  categories[cat].cur += cur;
  categories[cat].sug += sug;
});

const catColors = {
  'BODY WASH':        'FFBBDEFB',
  'BODY LOTION':      'FFB3E5FC',
  'PERSONAL CARE':    'FFB2EBF2',
  'HAIR':             'FFB2DFDB',
  'KIDS & BABY':      'FFDCEDC8',
  'HOUSEHOLD':        'FFFFF9C4',
  'KITCHEN':          'FFFFE0B2',
  'ELECTRONICS':      'FFFCE4EC',
  'HEALTH':           'FFF3E5F5',
  'CABLES & CHARGERS':'FFEFEBE9',
};

Object.entries(categories).forEach(([cat, d]) => {
  const row = summary.addRow([
    cat,
    d.count,
    +(d.us / d.count).toFixed(2),
    Math.round(d.cur / d.count),
    Math.round(d.sug / d.count),
  ]);
  row.height = 22;
  const bg = catColors[cat] ?? 'FFFFFFFF';
  row.getCell(1).font = { bold: true };
  row.getCell(3).numFmt = '"$"#,##0.00';
  row.getCell(4).numFmt = '#,##0 "XAF"';
  row.getCell(5).numFmt = '#,##0 "XAF"';
  row.eachCell(c => {
    c.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: bg } };
    c.alignment = { vertical: 'middle', horizontal: 'center' };
    c.border = { bottom: { style: 'thin', color: { argb: 'FFCCCCCC' } } };
  });
  row.getCell(1).alignment = { horizontal: 'left', vertical: 'middle' };
});

await wb.xlsx.writeFile('AmericanSelect_PricingAnalysis.xlsx');
console.log('Done — AmericanSelect_PricingAnalysis.xlsx created.');
