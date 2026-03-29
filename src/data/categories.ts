export interface Product {
  name: string;
  image?: string;
  images?: string[];
  price?: number;
  quantity?: number;
  colors?: string[];
}

export interface SubCategory {
  name: string;
  icon?: string;
  items: (string | Product)[];
}

export type CategoryItem = string | Product | SubCategory;

export interface Category {
  id: string;
  name: string;
  icon: string;
  description: string;
  items: CategoryItem[];
}

// Type guards
export function isSubCategory(item: CategoryItem): item is SubCategory {
  return typeof item === 'object' && 'items' in item;
}

export function isProduct(item: CategoryItem): item is Product {
  return typeof item === 'object' && 'name' in item && !('items' in item);
}

// Helper to get product name
export function getProductName(item: string | Product): string {
  return typeof item === 'string' ? item : item.name;
}

// Helper to get product image
export function getProductImage(item: string | Product): string | undefined {
  return typeof item === 'string' ? undefined : item.image;
}

export const categories: Category[] = [
  {
    id: 'home-kitchen',
    name: 'Kitchen & Dining',
    icon: '',
    description: 'Cookware, bakeware, storage, and kitchen essentials',
    items: [
      {
        name: 'Cookware & Bakeware',
        items: [
          {
            name: 'Manual Pasta Maker Machine, 9 Adjustable Thickness Settings',
            images: ['/images/products/manual-pasta-maker-1.webp', '/images/products/manual-pasta-maker-2.webp'],
            price: 20000,
            quantity: 2
          }
        ]
      },
      {
        name: 'Food Storage & Containers',
        items: [
          {
            name: 'Reusable Stretchable Storage Lids for Round Pots, Bowls, and Cups – Air-Tight, Food-Safe',
            price: 2000,
            quantity: 15,
            images: [
              '/images/products/storage-lids-1.webp',
              '/images/products/storage-lids-2.webp',
              '/images/products/storage-lids-3.webp'
            ]
          },
          {
            name: '1000 Reusable Stretchable Storage Lids for Round Pots, Bowls, and Cups – Air-Tight, Food-Safe',
            price: 3000,
            quantity: 5,
            images: [
              '/images/products/storage-lids-1000-1.webp',
              '/images/products/storage-lids-2.webp'
            ]
          }
        ]
      },
      {
        name: 'Small Kitchen Appliances, Tools & Gadgets',
        items: [
          {
            name: 'Manual Food Chopper, Pull String Vegetable and Meat Mincer with Clear Container, Hand-Powered Kitchen Processor',
            images: ['/images/products/manual-food-chopper.webp', '/images/products/manual-food-chopper-2.webp'],
            quantity: 11,
            price: 8000
          },
          {
            name: '8.45oz RZSYZH USB Rechargeable Electric mini Garlic  Blender with Stainless Steel Blades for Automatic Chopping',
            images: [
              '/images/products/usb-electric-blender-1.webp',
              '/images/products/usb-electric-blender-2.webp',
              '/images/products/usb-electric-blender-3.webp',
              '/images/products/usb-electric-blender-4.webp'
            ],
            quantity: 5,
            price: 5000
          },
          {
            name: '380Ml Rechargeable Portable Blender Cup, Electric USB Juicer Blender, Mini Blender for Shakes And Smoothies, Juice',
            price: 8000,
            quantity: 5,
            colors: ['#2e7d32', '#2c2c2c', '#ff69b4', '#7b1fa2', '#1565c0'],
            images: [
              '/images/products/blender-380ml-1.webp',
              '/images/products/blender-380ml-2.webp',
              '/images/products/blender-380ml-3.webp',
              '/images/products/blender-380ml-4.webp',
              '/images/products/blender-380ml-5.webp',
              '/images/products/blender-380ml-6.webp',
              '/images/products/blender-380ml-7.webp',
              '/images/products/blender-380ml-8.webp',
              '/images/products/blender-380ml-9.webp',
              '/images/products/blender-380ml-10.webp'
            ]
          },
          {
            name: 'Manual Chopper. No Need for Electricity',
            images: [
              '/images/products/manual-chopper-no-electricity-1.webp',
              '/images/products/manual-chopper-no-electricity-2.webp',
              '/images/products/manual-chopper-no-electricity-3.webp',
              '/images/products/manual-chopper-no-electricity-4.webp'
            ],
            quantity: 4,
            colors: ['#ff69b4', '#808080'],
            price: 10000
          }
        ]
      },
      {
        name: 'Dining & Entertaining',
        items: []
      }
    ]
  },
  {
    id: 'body-bath',
    name: 'Body, Bath & Personal Care',
    icon: '',
    description: 'Personal hygiene and grooming products',
    items: [
      {
        name: 'Bath Soaps & Shower Gels',
        items: [
          {
            name: "Dr Teal's Body Wash with Prebiotic Lemon Balm and Essential Oil Blend",
            images: ['/images/products/dr-teals-lemon-balm-sage-wash1.webp', '/images/products/dr-teals-lemon-balm-blend2.webp', '/images/products/dr-teals-lemon-balm-blend3.webp'],
            quantity: 3,
            price: 5000
          },
          {
            name: "Dr Teal's Body Wash Relax and Relief with Eucalyptus Spearmint",
            images: ['/images/products/dr-teals-eucalyptus-wash1.webp', '/images/products/dr-teals-eucalyptus-wash2.webp', '/images/products/dr-teals-eucalyptus-wash3.webp'],
            quantity: 3,
            price: 5000
          },
          {
            name: "Dr Teal's Vanilla Comfort Body Wash with Pure Epsom Salt",
            images: ['/images/products/dr-teals-vanilla-comfort-wash1.webp', '/images/products/dr-teals-vanilla-comfort-wash2.webp', '/images/products/dr-teals-vanilla-comfort-wash3.webp'],
            quantity: 3,
            price: 5000
          },
          {
            name: "Dr Teal's Hawaiian Bliss Body Wash with Alaea Red Sea Salt, Hibiscus & Papaya, 24 fl oz",
            images: ['/images/products/dr-teals-hawaiian-bliss-wash1.webp', '/images/products/dr-teals-hawaiian-bliss-wash2.webp', '/images/products/dr-teals-hawaiian-bliss-wash3.webp'],
            quantity: 3,
            price: 5000
          },
          {
            name: "Dr Teal's Body Wash with Pure Epsom Salt, Soothe & Sleep with Lavender",
            images: ['/images/products/dr-teals-soothe-sleep-lavender-wash1.webp', '/images/products/dr-teals-soothe-sleep-lavender-wash2.webp'],
            quantity: 3,
            price: 5000
          },
          {
            name: "Dr Teal's Body Wash, Nourish & Protect with Coconut Oil",
            images: ['/images/products/dr-teals-nourish-protect-coconut-wash1.webp', '/images/products/dr-teals-nourish-protect-coconut-wash2.webp'],
            quantity: 3,
            price: 5000
          },
          {
            name: "Dr Teal's Body Wash with Pure Epsom Salt, Shea Butter & Almond",
            images: ['/images/products/dr-teals-shea-almond-wash1.webp', '/images/products/dr-teals-shea-almond-wash2.webp', '/images/products/dr-teals-shea-almond-wash3.webp'],
            quantity: 3,
            price: 5000
          },
          {
            name: 'Olay Essential Botanicals Body Wash, Spiced Chai & Pear',
            images: ['/images/products/olay-spiced-chai.webp', '/images/products/olay-back1.webp', '/images/products/olay-back2.webp'],
            quantity: 11,
            price: 3500
          },
          {
            name: 'Olay Essential Botanicals Body Wash, White Tea & Cucumber',
            images: ['/images/products/olay-white-tea.webp', '/images/products/olay-back1.webp', '/images/products/olay-back2.webp'],
            quantity: 11,
            price: 3500
          },
          {
            name: 'Olay Essential Botanicals Body Wash, Lavender Milk & Sandalwood',
            images: ['/images/products/olay-lavender.webp', '/images/products/olay-back1.webp', '/images/products/olay-back2.webp'],
            quantity: 11,
            price: 3500
          },
          {
            name: 'Irish Spring Original Clean Deodorant Bar Soap',
            images: ['/images/products/irish-spring-soap-1.webp', '/images/products/irish-spring-soap-2.webp', '/images/products/irish-spring-soap-3.webp'],
            quantity: 40,
            price: 1300
          }
        ]
      },
      {
        name: 'Body Lotions & Creams',
        items: [
          {
            name: 'CeraVe Daily Moisturizing Lotion For Normal to Dry Skin',
            image: '/images/products/cerave-lotion.webp',
            quantity: 12,
            price: 8500
          },
          {
            name: 'Cetaphil Moisturizing Cream',
            images: ['/images/products/cetaphil-moisturizer1.webp', '/images/products/cetaphil-moisturizer2.webp'],
            quantity: 12,
            price: 10000
          },
          {
            name: 'Aveeno Daily Moisturizing Body Lotion 18oz',
            image: '/images/products/aveeno-lotion.webp',
            quantity: 5,
            price: 6000
          },
          {
            name: 'Jergens Hydrating Coconut Body Lotion, Hand and Body Moisturizer, Infused with Coconut Oil',
            images: ['/images/products/jergens-coconut1.webp', '/images/products/jergens-coconut2.webp', '/images/products/jergens-coconut3.webp', '/images/products/jergens-coconut4.webp'],
            quantity: 3,
            price: 6000
          },
          {
            name: 'Jergens Original Scent Dry Skin Body Lotion, Hand and Body Moisturizer, Cherry Almond Essence',
            images: ['/images/products/jergens-cherry1.webp', '/images/products/jergens-cherry3.webp'],
            quantity: 3,
            price: 6000
          },
          {
            name: 'Jergens Shea Butter Hand and Body Lotion, Deep Conditioning Moisturizer',
            images: ['/images/products/jergens-shea1.webp', '/images/products/jergens-shea2.webp', '/images/products/jergens-common.webp'],
            quantity: 3,
            price: 6000
          },
          {
            name: 'Jergens Soothing Aloe Body Lotion, Aloe Vera Body and Hand Moisturizer',
            images: ['/images/products/jergens-aloe1.webp', '/images/products/jergens-aloe2.webp', '/images/products/jergens-aloe3.webp'],
            quantity: 3,
            price: 6000
          },
          {
            name: 'Jergens Ultra Healing Dry Skin Lotion, Hand and Body Moisturizer',
            images: ['/images/products/jergens-ultra1.webp', '/images/products/jergens-ultra2.webp', '/images/products/jergens-ultra3.webp'],
            quantity: 3,
            price: 6000
          },
          {
            name: "Dr Teal's 24 Hour Moisture+ Body Lotion, Prebiotic Lemon Balm & Essential Oils",
            images: ['/images/products/dr-teals-lotion-lemon1.webp', '/images/products/dr-teals-lotion-lemon2.webp', '/images/products/dr-teals-lotion-lemon3.webp'],
            quantity: 3,
            price: 5000
          },
          {
            name: "Dr Teal's 24 Hour Moisture+ Body Lotion, Eucalyptus & Spearmint",
            images: ['/images/products/dr-teals-lotion-eucalyptus1.webp', '/images/products/dr-teals-lotion-eucalyptus2.webp'],
            quantity: 3,
            price: 5000
          },
          {
            name: "Dr Teal's 24 Hour Moisture+ Body Lotion, Coconut Oil & Essential Oils",
            images: ['/images/products/dr-teals-lotion-coconut1.webp', '/images/products/dr-teals-lotion-coconut2.webp'],
            quantity: 3,
            price: 5000
          },
          {
            name: "Dr Teal's 24 Hour Moisture+ Body Lotion, Shea Butter & Almond",
            images: ['/images/products/dr-teals-lotion-shea1.webp', '/images/products/dr-teals-lotion-shea2.webp', '/images/products/dr-teals-lotion-shea3.webp'],
            quantity: 3,
            price: 5000
          },
          {
            name: "Dr Teal's 24 Hour Moisture+ Body Lotion, Lavender Essential Oil",
            images: ['/images/products/dr-teals-lotion-lavender1.webp', '/images/products/dr-teals-lotion-lavender2.webp'],
            quantity: 3,
            price: 5000
          },
          {
            name: "Dr Teal's 24 Hour Moisture Body Lotion, Vanilla Comfort",
            images: ['/images/products/dr-teals-lotion-vanilla1.webp', '/images/products/dr-teals-lotion-vanilla2.webp'],
            quantity: 3,
            price: 5000
          },
          {
            name: 'Vaseline Men Cooling Hydration 3-in-1 Face, Hands & Body Lotion with Menthol',
            images: ['/images/products/vaseline-men-cooling1.webp', '/images/products/vaseline-men-cooling2.webp', '/images/products/vaseline-men-cooling3.webp'],
            quantity: 6,
            price: 6000
          },
          {
            name: 'Vaseline Intensive Care Body Lotion Cocoa Radiant with Pure Cocoa Butter',
            images: ['/images/products/vaseline-cocoa1.webp', '/images/products/vaseline-cocoa2.webp', '/images/products/vaseline-cocoa3.webp'],
            quantity: 6,
            price: 6000
          },
          {
            name: 'Vaseline Intensive Care Soothing Hydration Body Lotion with Aloe Vera',
            images: ['/images/products/vaseline-aloe1.webp', '/images/products/vaseline-aloe2.webp'],
            quantity: 6,
            price: 6000
          },
          {
            name: 'Vaseline Intensive Care Sensitive Skin Relief Body Lotion with Colloidal Oatmeal',
            images: ['/images/products/vaseline-sensitive1.webp', '/images/products/vaseline-sensitive3.webp'],
            quantity: 4,
            price: 6000
          },
          {
            name: 'Vaseline Intensive Care Calm Healing Body Lotion with Lavender Extract',
            images: ['/images/products/vaseline-lavender1.webp', '/images/products/vaseline-lavender2.webp'],
            quantity: 6,
            price: 6000
          },
          {
            name: 'Vaseline Intensive Care Nourishing Moisture Body Lotion with Pure Oat Extract',
            images: ['/images/products/vaseline-oat1.webp', '/images/products/vaseline-oat2.webp'],
            quantity: 6,
            price: 6000
          },
          {
            name: 'Lubriderm Fragrance Free Daily Moisture Lotion + Pro-Ceramide, Shea Butter & Glycerin, Face, Hand & Body Lotion for Sensitive Skin, Hydrating Lotion for Healthier-Looking Skin',
            images: ['/images/products/lubriderm-pro-ceramide1.webp', '/images/products/lubriderm-pro-ceramide2.webp', '/images/products/lubriderm-pro-ceramide3.webp', '/images/products/lubriderm-ingredients.webp'],
            quantity: 8,
            price: 10000
          },
          {
            name: 'Lubriderm Daily Moisture Lotion + Pro-Ceramide, Shea Butter & Glycerin, Hydrating Face, Hand & Body Lotion, 24-hour Moisturizer for Dry Skin, 6 Fl Oz',
            images: ['/images/products/lubriderm-daily-6oz-1.webp', '/images/products/lubriderm-daily-6oz-2.webp', '/images/products/lubriderm-ingredients.webp'],
            quantity: 4,
            price: 3500
          },
        ]
      },
      {
        name: 'Deodorant, Body Mist & Perfumes',
        items: [
          {
            name: 'Men',
            items: [
              { name: 'Paris Night Just for Men', image: '/images/products/paris-night-just-for-men.webp', price: 5000, quantity: 6 },
              { name: 'Infinity 3.3 oz EDT For Men', image: '/images/products/infinity-edt-men.webp', price: 5000, quantity: 6 },
              { name: 'Platinum 3.4 oz EDP For Men', image: '/images/products/platinum-edp-men.webp', price: 5000, quantity: 6 },
              { name: 'Savage 3.4 oz EDP For Men', image: '/images/products/savage-edp-men.webp', price: 5000, quantity: 6 },
              { name: 'Taj Max Aqua Sport 3.4 oz Long-Lasting Perfume', image: '/images/products/taj-max-aqua-sport.webp', price: 12000, quantity: 6 },
              { name: 'Taj Max Exotic Bliss 3.4 oz Long-Lasting Perfume', image: '/images/products/taj-max-exotic-bliss.webp', price: 12000, quantity: 6 },
              { name: 'Victory 3.3 oz EDT For Men', image: '/images/products/victory-edt-men.webp', price: 5000, quantity: 3 },
              { name: 'Hercules Paris 3.4 oz EDP For Men', image: '/images/products/hercules-paris-edp-men.webp', price: 5000, quantity: 6 },
              { name: 'Degree Men Black + White Ultraclear Deodorant', image: '/images/products/degree-men-black-white-ultraclear.webp', price: 3000, quantity: 15 },
              { name: 'Loveryblack Affinity At Midnight Pheromone Perfume (Unisex)', images: ['/images/products/loveryblack-at-midnight-unisex-1.webp', '/images/products/loveryblack-at-midnight-unisex-2.webp', '/images/products/loveryblack-at-midnight-unisex-3.webp', '/images/products/loveryblack-at-midnight-unisex-4.webp'], price: 15000, quantity: 2 },
              { name: 'Loveryblack Affinity For Him Pheromone Cologne', images: ['/images/products/loveryblack-for-him-1.webp', '/images/products/loveryblack-for-him-2.webp', '/images/products/loveryblack-for-him-3.webp', '/images/products/loveryblack-for-him-4.webp'], price: 15000, quantity: 2 },
              { name: 'Loveryblack Affinity Pure Passion Pheromone Perfume (Unisex)', images: ['/images/products/loveryblack-pure-passion-unisex-1.webp', '/images/products/loveryblack-pure-passion-unisex-2.webp', '/images/products/loveryblack-pure-passion-unisex-3.webp', '/images/products/loveryblack-pure-passion-unisex-4.webp'], price: 15000, quantity: 2 },
              { name: 'Mens Cologne Bross 3.4oz Eau De Parfum Spray, Masculine Mist', images: ['/images/products/mens-cologne-bross-1.webp', '/images/products/mens-cologne-bross-2.webp'], price: 10000, quantity: 2 },
              { name: "Men'S Extreme 3.4oz Eau De Parfum - Men Perfume Spray", images: ['/images/products/mens-extreme-edp-1.webp', '/images/products/mens-extreme-edp-2.webp'], price: 10000, quantity: 2 },
              { name: "Men'S Salvang 3.4oz Eau De Parfume", images: ['/images/products/mens-salvang-edp-1.webp', '/images/products/mens-salvang-edp-2.webp'], price: 10000, quantity: 2 },
              { name: 'Daspar De Homme Men Perfume with Pheromones', image: '/images/products/daspar-de-homme-pheromones-1.webp', price: 4000, quantity: 4 },
              { name: 'Adventure Club Perfume For Men', images: ['/images/products/adventure-club-perfume-men-3.webp', '/images/products/adventure-club-perfume-men-2.webp'], price: 7000, quantity: 3 },
              { name: 'Azure Vantage Aqua Spray Cologne Eau De Parfum For Men', images: ['/images/products/azure-vantage-cologne-men-1.webp', '/images/products/azure-vantage-cologne-men-2.webp', '/images/products/azure-vantage-cologne-men-3.webp'], price: 5000, quantity: 3 },
              { name: 'Investor Gold Spray Cologne Eau De Parfum For Men', images: ['/images/products/investor-gold-cologne-men-5.webp', '/images/products/investor-gold-cologne-men-1.webp', '/images/products/investor-gold-cologne-men-2.webp', '/images/products/investor-gold-cologne-men-3.webp', '/images/products/investor-gold-cologne-men-4.webp'], price: 5000, quantity: 3 },
              { name: 'Invincible Black Spray Cologne For Men EDP', images: ['/images/products/invincible-black-cologne-men-3.webp', '/images/products/invincible-black-cologne-men-2.webp'], price: 5000, quantity: 3 },
              { name: 'Invincible Platinum Spray Cologne Eau De Toilette For Men', images: ['/images/products/invincible-platinum-cologne-men-1.webp', '/images/products/invincible-platinum-cologne-men-3.webp', '/images/products/invincible-platinum-cologne-men-2.webp'], price: 5000, quantity: 3 },
              { name: 'Magic Code Spray Cologne Eau De Toilette For Men', images: ['/images/products/magic-code-cologne-men-1.webp', '/images/products/magic-code-cologne-men-2.webp', '/images/products/magic-code-cologne-men-3.webp'], price: 5000, quantity: 3 },
              { name: 'Prism Cologne Eau De Toilette For Men', images: ['/images/products/cologne-edt-men-1.webp', '/images/products/cologne-edt-men-2.webp', '/images/products/cologne-edt-men-3.webp', '/images/products/cologne-edt-men-4.webp'], price: 5000, quantity: 3 },
              { name: 'Victorious Heroes Spray Cologne Eau De Toilette For Men', images: ['/images/products/victorious-heroes-cologne-men-1.webp', '/images/products/victorious-heroes-cologne-men-2.webp', '/images/products/victorious-heroes-cologne-men-3.webp', '/images/products/victorious-heroes-cologne-men-4.webp'], price: 5500, quantity: 3 },
              { name: 'Bleu De Rivoli Eau De Parfum', images: ['/images/products/bleu-de-rivoli-edp-men-2.webp', '/images/products/bleu-de-rivoli-edp-men-1.webp', '/images/products/bleu-de-rivoli-edp-men-3.webp'], price: 12000, quantity: 2 },
              { name: 'Blue Intense Eau De Parfum', images: ['/images/products/blue-intense-edp-men-1.webp', '/images/products/blue-intense-edp-men-2.webp'], price: 12000, quantity: 2 },
              { name: 'Eau De Vivre Eau De Parfum', images: ['/images/products/eau-de-vivre-edp-men-2.webp', '/images/products/eau-de-vivre-edp-men-1.webp', '/images/products/eau-de-vivre-edp-men-3.webp'], price: 12000, quantity: 2 },
              { name: 'Imperial Oud Eau De Parfum', images: ['/images/products/imperial-oud-edp-men-2.webp', '/images/products/imperial-oud-edp-men-1.webp', '/images/products/imperial-oud-edp-men-3.webp'], price: 12000, quantity: 2 },
              { name: 'Legende Intense Eau De Parfum', images: ['/images/products/legende-intense-edp-men-2.webp', '/images/products/legende-intense-edp-men-1.webp', '/images/products/legende-intense-edp-men-3.webp'], price: 12000, quantity: 2 },
              { name: 'Reverie Pour Homme Eau De Parfum', images: ['/images/products/reverie-pour-homme-edp-men-2.webp', '/images/products/reverie-pour-homme-edp-men-1.webp', '/images/products/reverie-pour-homme-edp-men-3.webp'], price: 12000, quantity: 2 }
            ]
          },
          {
            name: 'Women',
            items: [
              { name: 'Fantastic Pink 3.4 oz EDP For Women', image: '/images/products/fantastic-pink-edp-women.webp', price: 5000, quantity: 6 },
              { name: 'Fleur De Paris 3.4 oz EDP For Women', image: '/images/products/fleur-de-paris-edp-women.webp', price: 8000, quantity: 6 },
              { name: 'Lazell Night Bloom For Women EDP 3.4 oz', image: '/images/products/lazell-night-bloom-edp-women.webp', price: 8000, quantity: 8 },
              { name: 'Lazell Spring For Women EDP 3.4 oz', image: '/images/products/lazell-spring-edp-women.webp', price: 8000, quantity: 6 },
              { name: 'Miss Coco 3.4 oz EDP For Women', image: '/images/products/miss-coco-edp-women.webp', price: 5000, quantity: 6 },
              { name: 'Island Coconut Body Mist', image: '/images/products/island-coconut-body-mist.webp', price: 3500, quantity: 8 },
              { name: 'Passion Fruit Body Mist', image: '/images/products/passion-fruit-body-mist.webp', price: 3500, quantity: 8 },
              { name: 'Watermelon Sugar Body Mist', image: '/images/products/watermelon-sugar-body-mist.webp', price: 3500, quantity: 4 },
              { name: 'Pineapple Dream Body Mist', image: '/images/products/pineapple-dream-body-mist.webp', price: 3500, quantity: 4 },
              { name: 'Charm Spray Perfume Eau De Parfum For Women', images: ['/images/products/charm-spray-perfume1.webp', '/images/products/charm-spray-perfume2.webp', '/images/products/charm-spray-perfume3.webp'], price: 10000, quantity: 3 },
              { name: 'Daicy Blue Spray Perfume Eau De Parfum', images: ['/images/products/daicy-blue-spray1.webp', '/images/products/daicy-blue-spray2.webp', '/images/products/daicy-blue-spray3.webp'], price: 10000, quantity: 3 },
              { name: 'Flower Eau De Parfum For Women', images: ['/images/products/flower-edp-women1.webp', '/images/products/flower-edp-women2.webp'], price: 10000, quantity: 3 },
              { name: 'Flower Pink Spray Perfume Eau De Parfum', images: ['/images/products/flower-pink-spray1.webp', '/images/products/flower-pink-spray2.webp', '/images/products/flower-pink-spray3.webp'], price: 10000, quantity: 3 },
              { name: 'Gorgeous Flower Spray Perfume Eau De Parfum', images: ['/images/products/gorgeous-flower-spray1.webp', '/images/products/gorgeous-flower-spray2.webp', '/images/products/gorgeous-flower-spray3.webp'], price: 10000, quantity: 3 },
              { name: 'Honey Bear Pink Spray Perfume Eau De Parfum', images: ['/images/products/honey-bear-pink-spray1.webp', '/images/products/honey-bear-pink-spray2.webp', '/images/products/honey-bear-pink-spray3.webp'], price: 10000, quantity: 3 },
              { name: 'Love Is Forever Spray Perfume Eau De Parfum', images: ['/images/products/love-is-forever-spray1.webp', '/images/products/love-is-forever-spray2.webp'], price: 10000, quantity: 3 },
              { name: 'Nice Girl Spray Perfume Eau De Parfum For Women', images: ['/images/products/nice-girl-spray1.webp', '/images/products/nice-girl-spray2.webp', '/images/products/nice-girl-spray3.webp'], price: 10000, quantity: 3 },
              { name: 'Princess High Heels Pink Spray Perfume Eau De Parfum', images: ['/images/products/princess-high-heels1.webp', '/images/products/princess-high-heels2.webp', '/images/products/princess-high-heels3.webp', '/images/products/princess-high-heels4.webp'], price: 10000, quantity: 3 },
              { name: 'Sexy Rose Pink Spray Perfume Eau De Parfum', images: ['/images/products/sexy-rose-pink-spray1.webp', '/images/products/sexy-rose-pink-spray2.webp', '/images/products/sexy-rose-pink-spray3.webp'], price: 10000, quantity: 3 },
              { name: 'Loveryblack Affinity At Midnight Pheromone Perfume (Unisex)', images: ['/images/products/loveryblack-at-midnight-unisex-1.webp', '/images/products/loveryblack-at-midnight-unisex-2.webp', '/images/products/loveryblack-at-midnight-unisex-3.webp', '/images/products/loveryblack-at-midnight-unisex-4.webp'], price: 15000, quantity: 2 },
              { name: 'Loveryblack Affinity Pure Passion Pheromone Perfume (Unisex)', images: ['/images/products/loveryblack-pure-passion-unisex-1.webp', '/images/products/loveryblack-pure-passion-unisex-2.webp', '/images/products/loveryblack-pure-passion-unisex-3.webp', '/images/products/loveryblack-pure-passion-unisex-4.webp'], price: 15000, quantity: 2 },
              { name: 'Lazell Black Onyx Women EDP 3.4', images: ['/images/products/lazell-black-onyx-women-edp-1.webp', '/images/products/lazell-black-onyx-women-edp-3.webp'], price: 8000, quantity: 3 },
              { name: '365 Day Spray Perfume Eau De Parfum For Women', images: ['/images/products/365-day-spray-perfume-women-1.webp', '/images/products/365-day-spray-perfume-women-2.webp', '/images/products/365-day-spray-perfume-women-3.webp'], price: 6500, quantity: 3 },
              { name: 'Harmonie De Jour Eau De Parfum', images: ['/images/products/harmonie-de-jour-edp-women-1.webp', '/images/products/harmonie-de-jour-edp-women-2.webp', '/images/products/harmonie-de-jour-edp-women-3.webp'], price: 15000, quantity: 2 }
            ]
          }
        ]
      },
      {
        name: 'Oral Care (toothpaste, brushes, mouthwash)',
        items: [
          {
            name: 'Crest Pro-Health Advanced Mouthwash',
            images: ['/images/products/crest-mouthwash-1.webp', '/images/products/crest-mouthwash-2.webp'],
            quantity: 9,
            price: 4000
          }
        ]
      },
      {
        name: 'Feminine Care',
        items: []
      },
      {
        name: "Men's Grooming",
        items: [
          {
            name: "Men's Electric Shaver 3 in 1 - Portable USB Rechargeable Shaver Featuring 3D Floating Blades and a Digital Display Suitable for Both Wet and Dry Shaving",
            price: 15000,
            quantity: 6,
            images: [
              '/images/products/mens-electric-shaver-1.webp',
              '/images/products/mens-electric-shaver-2.webp',
              '/images/products/mens-electric-shaver-3.webp'
            ]
          },
          {
            name: "LQT Men's Electric Shaver | Exquisite Packaging Box, USB Charging, Lithium Battery, Matte Texture, Essential for Men, Beard Trimming",
            price: 12000,
            quantity: 2,
            images: [
              '/images/products/mens-shaver-matte-1.webp',
              '/images/products/mens-shaver-matte-2.webp'
            ]
          }
        ]
      },
      {
        name: 'Hair Care (shampoo, conditioner, oils)',
        items: [
          {
            name: 'Suave Keratin Infusion Smoothing Shampoo & Conditioner Set For Frizzy Hair',
            images: [
              '/images/products/suave-keratin-shampoo-conditioner1.webp',
              '/images/products/suave-keratin-shampoo-conditioner2.webp',
              '/images/products/suave-keratin-shampoo-conditioner3.webp',
              '/images/products/suave-keratin-shampoo-conditioner4.webp',
            ],
            price: 10000,
            quantity: 2
          },
          {
            name: 'Suave Moisturizing Shampoo & Conditioner With Almond & Shea Butter',
            images: [
              '/images/products/suave-almond-shea-shampoo-conditioner1.webp',
              '/images/products/suave-almond-shea-shampoo-conditioner2.webp',
              '/images/products/suave-almond-shea-shampoo-conditioner3.webp',
              '/images/products/suave-almond-shea-shampoo-conditioner4.webp',
            ],
            price: 10000,
            quantity: 2
          },
          {
            name: 'TRESemmé Rich Moisture Shampoo and Conditioner Set',
            images: [
              '/images/products/tresemme-rich-moisture-shampoo-conditioner1.webp',
              '/images/products/tresemme-rich-moisture-shampoo-conditioner2.webp',
              '/images/products/tresemme-rich-moisture-shampoo-conditioner3.webp',
              '/images/products/tresemme-rich-moisture-shampoo-conditioner4.webp',
            ],
            price: 10000,
            quantity: 2
          },
          {
            name: 'TRESemmé Shampoo & Conditioner Keratin Smooth',
            images: [
              '/images/products/tresemme-keratin-smooth-shampoo-conditioner1.webp',
              '/images/products/tresemme-keratin-smooth-shampoo-conditioner2.webp',
              '/images/products/tresemme-keratin-smooth-shampoo-conditioner3.webp',
              '/images/products/tresemme-keratin-smooth-shampoo-conditioner4.webp',
            ],
            price: 10000,
            quantity: 2
          },
          {
            name: 'TRESemmé Shampoo And Conditioner Anti-Breakage',
            images: [
              '/images/products/tresemme-anti-breakage-shampoo-conditioner1.webp',
              '/images/products/tresemme-anti-breakage-shampoo-conditioner2.webp',
              '/images/products/tresemme-anti-breakage-shampoo-conditioner3.webp',
            ],
            price: 10000,
            quantity: 2
          },
          {
            name: 'TRESemmé Shampoo and Conditioner for Women Amplified Volume Twin Pack',
            images: [
              '/images/products/tresemme-amplified-volume-shampoo-conditioner1.webp',
              '/images/products/tresemme-amplified-volume-shampoo-conditioner2.webp',
              '/images/products/tresemme-amplified-volume-shampoo-conditioner3.webp',
            ],
            price: 10000,
            quantity: 2
          },
          {
            name: 'TRESemmé Silky & Smooth Anti-Frizz Shampoo & Conditioner for Frizzy Hair',
            images: [
              '/images/products/tresemme-silky-smooth-shampoo-conditioner1.webp',
              '/images/products/tresemme-silky-smooth-shampoo-conditioner2.webp',
              '/images/products/tresemme-silky-smooth-shampoo-conditioner3.webp',
            ],
            price: 10000,
            quantity: 2
          },
        ]
      }
    ]
  },
  {
    id: 'kids',
    name: 'Kids & Babies',
    icon: '',
    description: 'Everything for your little ones',
    items: [
      {
        name: 'Baby Diapers & Wipes',
        items: []
      },
      {
        name: 'Baby Bath & Skincare',
        items: [
          {
            name: "Dr Teal's Kids 3-in-1 Sleep Bath: Bubble Bath, Body Wash & Shampoo with Melatonin & Essential Oil",
            images: ['/images/products/dr-teals-kids-melatonin1.webp', '/images/products/dr-teals-kids-melatonin2.webp', '/images/products/dr-teals-kids-melatonin3.webp'],
            quantity: 3,
            price: 4500
          },
          {
            name: "Dr Teal's Kids 3-in-1 Bubble Bath, Body Wash & Shampoo with Ashwagandha",
            images: ['/images/products/dr-teals-kids-ashwagandha1.webp', '/images/products/dr-teals-kids-ashwagandha2.webp', '/images/products/dr-teals-kids-ashwagandha3.webp'],
            quantity: 3,
            price: 4500
          },
          {
            name: 'Cetaphil Moisturizing Cream',
            images: ['/images/products/cetaphil-moisturizer1.webp', '/images/products/cetaphil-moisturizer2.webp'],
            quantity: 12,
            price: 10000
          }
        ]
      },
      {
        name: 'Nursing & Feeding',
        items: []
      },
      {
        name: 'Baby Health',
        items: []
      }
    ]
  },
  {
    id: 'household-cleaning',
    name: 'Household Cleaning & Supplies',
    icon: '',
    description: 'Keep your home clean and fresh',
    items: [
      {
        name: 'Laundry Detergents',
        items: []
      },
      {
        name: 'Dishwashing Supplies',
        items: []
      },
      {
        name: 'Cleaning Supplies & Disinfectants',
        items: []
      },
      {
        name: 'Air Fresheners',
        items: [
          {
            name: 'Febreze Air Freshener Spray, Odor-Fighting Room Spray, Linen & Sky',
            images: ['/images/products/febreze-linen-sky-1.webp', '/images/products/febreze-linen-sky-2.webp', '/images/products/febreze-linen-sky-3.webp'],
            quantity: 4,
            price: 2000
          },
          {
            name: 'Febreze Air Mist Air Freshener Spray, Heavy Duty Crisp Clean',
            images: ['/images/products/febreze-crisp-clean-1.webp', '/images/products/febreze-crisp-clean-2.webp', '/images/products/febreze-crisp-clean-3.webp'],
            quantity: 4,
            price: 2000
          },
          {
            name: 'Febreze Air Mist Air Freshener Spray Hawaiian Aloha',
            images: ['/images/products/febreze-hawaiian-aloha-1.webp', '/images/products/febreze-hawaiian-aloha-2.webp', '/images/products/febreze-hawaiian-aloha-3.webp'],
            quantity: 4,
            price: 2000
          },
          {
            name: 'Febreze Air Effects Air Freshener - Mountain Scent',
            images: ['/images/products/febreze-mountain-scent-1.webp', '/images/products/febreze-mountain-scent-2.webp'],
            quantity: 4,
            price: 2000
          }
        ]
      },
      {
        name: 'Trash Bags & Storage Bags',
        items: []
      },
      {
        name: 'Cleaning Tools (sponges, mops, brushes)',
        items: [
          {
            name: 'Mop and Bucket Set, 360° Spin Mop and Bucket Set and 4 Microfiber Mop Refills',
            price: 15000,
            quantity: 3,
            images: [
              '/images/products/spin-mop-bucket-1.webp',
              '/images/products/spin-mop-bucket-2.webp',
              '/images/products/spin-mop-bucket-3.webp',
              '/images/products/spin-mop-bucket-4.webp',
              '/images/products/spin-mop-bucket-5.webp'
            ]
          }
        ]
      }
    ]
  },
  {
    id: 'electronics',
    name: 'Electronics & Accessories',
    icon: '',
    description: 'Essential electronics and accessories',
    items: [
      {
        name: 'Mobile & Accessories',
        items: [
          {
            name: 'Smart Watch Compatible with iPhone & Android Devices',
            price: 5000,
            quantity: 4,
            colors: ['#ff69b4', '#2c2c2c'],
            images: [
              '/images/products/smart-watch-1.webp',
              '/images/products/smart-watch-2.webp'
            ]
          },
          {
            name: 'Z68 Smartwatch Featuring Wireless Calling, Message Alerts, Various Sports Modes, Information Notifications, Multifunctional Phone Answering/Dialing, Remote Photography, Music Playback, Sports Tracking',
            price: 8000,
            quantity: 6,
            colors: ['#2c2c2c', '#d4af37', '#ff69b4'],
            images: [
              '/images/products/smartwatch-multi-1.webp',
              '/images/products/smartwatch-multi-2.webp',
              '/images/products/smartwatch-multi-3.webp',
              '/images/products/smartwatch-multi-4.webp'
            ]
          },
          {
            name: 'LAXASFIT Unisex Smartwatch Compatible with Both iPhone and Android',
            price: 8000,
            quantity: 2,
            colors: ['#2c2c2c'],
            images: [
              '/images/products/unisex-smartwatch-1.webp',
              '/images/products/unisex-smartwatch-2.webp',
              '/images/products/unisex-smartwatch-3.webp'
            ]
          },
          {
            name: 'PLEIVO New Smart Watch with 2.01-inch Large Screen, LED Flashlight, Outdoor Sports Watch, Fitness Tracker Compatible with Android and iPhone',
            price: 8000,
            quantity: 1,
            colors: ['#2c2c2c'],
            images: [
              '/images/products/smartwatch-led-1.webp',
              '/images/products/smartwatch-led-2.webp'
            ]
          },
          {
            name: 'Smart Watch with A Metal Frame, Ceramic Base, And A 2.01-inch Large Screen',
            price: 9000,
            quantity: 1,
            colors: ['#2c2c2c'],
            images: [
              '/images/products/smartwatch-round-1.webp',
              '/images/products/smartwatch-round-2.webp',
              '/images/products/smartwatch-round-3.webp'
            ]
          },
          {
            name: 'GT4 PRO Smartwatch Featuring a Full Touchscreen, Wireless Calling, Over 100 Sports Modes, Weather Updates, Stopwatch, Timer, Alarm, SMS Notifications',
            price: 8000,
            quantity: 2,
            colors: ['#2c2c2c'],
            images: [
              '/images/products/smartwatch-versatile-1.webp',
              '/images/products/smartwatch-versatile-2.webp',
              '/images/products/smartwatch-versatile-3.webp'
            ]
          },
          {
            name: 'Doviico 1.83-inch Touch Screen, Supports Call Function, Incoming Call And Message Notifications, Multifunctional Fitness And Sports Smart Bracelet, Wireless Connection with Android And Phones',
            price: 5000,
            quantity: 2,
            colors: ['#ff69b4'],
            images: [
              '/images/products/smart-bracelet-183-1.webp',
              '/images/products/smart-bracelet-183-2.webp'
            ]
          },
          {
            name: '256 GB Memory Card with Adapter - Enough Storage for your data',
            price: 4000,
            quantity: 10,
            images: [
              '/images/products/memory-card-256gb-1.webp',
              '/images/products/memory-card-256gb-2.webp',
              '/images/products/memory-card-256gb-3.webp'
            ]
          },
          {
            name: '20000mAh Large-Capacity Mobile Power Bank',
            price: 7000,
            quantity: 15,
            images: [
              '/images/products/power-bank-20000mah-1.webp',
              '/images/products/power-bank-20000mah-2.webp',
              '/images/products/power-bank-20000mah-3.webp'
            ]
          }
        ]
      },
      {
        name: 'Home Electronics',
        items: [
          {
            name: 'Rechargeable LED Torch/Flashlight With High Power Lamp',
            images: ['/images/products/torch1.webp', '/images/products/torch.webp', '/images/products/torch2.webp'],
            quantity: 15,
            price: 2500
          },
          {
            name: 'Rechargeable Portable Fan',
            price: 3000,
            quantity: 9,
            colors: ['#800080', '#ff69b4', '#2c2c2c'],
            images: [
              '/images/products/rechargeable-fan1.webp',
              '/images/products/rechargeable-fan2.webp',
              '/images/products/rechargeable-fan3.webp',
              '/images/products/rechargeable-fan4.webp'
            ]
          },
          {
            name: 'Dark Grey USB Rechargeable Fan',
            price: 3000,
            quantity: 4,
            colors: ['#2c2c2c'],
            images: [
              '/images/products/dark-grey-usb-fan1.webp',
              '/images/products/dark-grey-usb-fan2.webp'
            ]
          },
          {
            name: 'YISSVIC Rechargeable Electric Fly Swatter Foldable Bug Zapper',
            price: 10000,
            quantity: 6,
            images: [
              '/images/products/yissvic-fly-swatter-1.webp',
              '/images/products/yissvic-fly-swatter-2.webp',
              '/images/products/yissvic-fly-swatter-3.webp'
            ]
          }
        ]
      },
      {
        name: 'Audio & Entertainment',
        items: [
          {
            name: 'TG192 A Large Capacity 2400mAh Outdoor Wireless Speaker',
            price: 12000,
            quantity: 5,
            colors: ['#2e7d32', '#1565c0', '#c62828', '#2c2c2c', '#4a5240'],
            images: [
              '/images/products/tg192-speaker-1.webp',
              '/images/products/tg192-speaker-2.webp',
              '/images/products/tg192-speaker-3.webp',
              '/images/products/tg192-speaker-4.webp',
              '/images/products/tg192-speaker-5.webp',
              '/images/products/tg192-speaker-6.webp'
            ]
          },
          {
            name: 'TG537 Portable Wireless Speaker - TWS Technology',
            price: 10000,
            quantity: 4,
            colors: ['#808080', '#e74c3c', '#2c2c2c', '#00bcd4'],
            images: [
              '/images/products/tg537-speaker-1.webp',
              '/images/products/tg537-speaker-2.webp',
              '/images/products/tg537-speaker-3.webp',
              '/images/products/tg537-speaker-4.webp',
              '/images/products/tg537-speaker-5.webp'
            ]
          },
          {
            name: 'TG667 Compact and Portable Wireless Speaker. It Supports USB, TF Cards, and FM Radio, and Can Connect to Mobile Phones, Tablets',
            price: 8000,
            quantity: 8,
            colors: ['#e74c3c', '#2980b9', '#2c2c2c', '#800080'],
            images: [
              '/images/products/tg667-speaker-1.webp',
              '/images/products/tg667-speaker-2.webp',
              '/images/products/tg667-speaker-3.webp',
              '/images/products/tg667-speaker-4.webp'
            ]
          },
          {
            name: 'Hyundai LP5t Wireless Headphones with Surround Sound and Noise Cancellation',
            price: 7000,
            quantity: 10,
            colors: ['#808080', '#ffffff'],
            images: [
              '/images/products/hyundai-lp5t-1.webp',
              '/images/products/hyundai-lp5t-2.webp',
              '/images/products/hyundai-lp5t-3.webp'
            ]
          },
          {
            name: 'JS59 Wireless Headphones, 5.4 Headphones with 4 ENC Noise-Cancelling Microphones, HiFi Stereo, 50 Hours of Playback Time, USB-C',
            price: 2500,
            quantity: 2,
            colors: ['#2c2c2c'],
            images: [
              '/images/products/wireless-headphones-enc-1.webp',
              '/images/products/wireless-headphones-enc-2.webp'
            ]
          },
          {
            name: 'YD03 Wireless Earbuds',
            price: 4000,
            quantity: 5,
            images: [
              '/images/products/wireless-earbuds-tws-1.webp',
              '/images/products/wireless-earbuds-tws-2.webp',
              '/images/products/wireless-earbuds-tws-3.webp'
            ]
          },
          {
            name: 'Airpod Pro',
            price: 15000,
            quantity: 10,
            images: [
              '/images/products/airpod-pro-1.webp',
              '/images/products/airpod-pro-2.webp'
            ]
          },
          {
            name: 'Monster Wireless Earbuds with CVC 8.0 Noise Reduction',
            price: 4000,
            quantity: 1,
            images: [
              '/images/products/monster-earbuds-1.webp',
              '/images/products/monster-earbuds-2.webp',
              '/images/products/monster-earbuds-3.webp'
            ]
          },
          {
            name: 'Acer OHR544 Wireless Headset with Heavy Bass Stereo + Talking Noise Cancellation',
            price: 5500,
            quantity: 5,
            colors: ['#ff8c00', '#f5f5dc', '#2c2c2c'],
            images: [
              '/images/products/acer-tws-headset-1.webp',
              '/images/products/acer-tws-headset-2.webp',
              '/images/products/acer-tws-headset-3.webp',
              '/images/products/acer-tws-headset-4.webp'
            ]
          },
          {
            name: 'Ace OHR501 Wireless 5.4 Bass Stereo Headset with Noise Cancelling Microphone, Touch Control',
            price: 5500,
            quantity: 3,
            colors: ['#2c2c2c', '#800080'],
            images: [
              '/images/products/acer-wireless-stereo-1.webp',
              '/images/products/acer-wireless-stereo-2.webp',
              '/images/products/acer-wireless-stereo-3.webp'
            ]
          },
          {
            name: 'Nokia Go Earbuds+ TWS-201',
            price: 8000,
            quantity: 9,
            colors: ['#ffffff', '#2c2c2c'],
            images: [
              '/images/products/nokia-earbuds-1.webp',
              '/images/products/nokia-earbuds-2.webp',
              '/images/products/nokia-earbuds-3.webp',
              '/images/products/nokia-earbuds-4.webp',
              '/images/products/nokia-earbuds-5.webp'
            ]
          },
          {
            name: 'Wireless Earbuds with Charging Case, USB Type-C Charging, TWS Technology',
            price: 6000,
            quantity: 7,
            colors: ['#ffffff'],
            images: [
              '/images/products/usb-tws-earbuds-1.webp',
              '/images/products/usb-tws-earbuds-2.webp'
            ]
          },
          {
            name: 'Y01 Over the Ear Wireless Headphones',
            price: 13000,
            quantity: 1,
            images: [
              '/images/products/y01-headphones3.webp',
              '/images/products/y01-headphones1.webp',
              '/images/products/y01-headphones2.webp',
            ]
          },
          {
            name: 'TOZO T6 Wireless Earbuds IPX8 Waterproof',
            price: 8000,
            quantity: 1,
            images: [
              '/images/products/tozo-t6-earbuds1.webp',
              '/images/products/tozo-t6-earbuds2.webp',
              '/images/products/tozo-t6-earbuds3.webp',
            ]
          },
          {
            name: 'Portable Wireless Speaker, 15W Stereo, RGB Lighting, Suitable for Both Indoor and Outdoor Use',
            price: 6000,
            quantity: 3,
            images: [
              '/images/products/portable-speaker-rgb-1.webp',
              '/images/products/portable-speaker-rgb-2.webp'
            ]
          }
        ]
      },
      {
        name: 'Computers & Tablets',
        items: []
      },
      {
        name: 'Cables & Adapters',
        items: [
          {
            name: '4-in-1 USB Mini SD Card Reader with Dual Card Slots',
            price: 2000,
            quantity: 10,
            images: [
              '/images/products/usb-sd-card-reader-4in1-1.webp',
              '/images/products/usb-sd-card-reader-4in1-2.webp',
              '/images/products/usb-sd-card-reader-4in1-3.webp',
              '/images/products/usb-sd-card-reader-4in1-4.webp'
            ]
          },
          {
            name: 'USB 2.0 Type-C Flash Drive 128GB High-Speed Memory Stick',
            price: 4000,
            quantity: 5,
            images: [
              '/images/products/usb-typec-flash-drive-128gb-1.webp',
              '/images/products/usb-typec-flash-drive-128gb-2.webp'
            ]
          },
          {
            name: 'Data Cable, USB C to USB C Charging Cable, Type C to Type C Fast Charging',
            price: 1000,
            quantity: 15,
            images: [
              '/images/products/usb-c-cable-1.webp',
              '/images/products/usb-c-cable-2.webp',
              '/images/products/usb-c-cable-3.webp',
              '/images/products/usb-c-cable-4.webp'
            ]
          },
          {
            name: '7-in-1 USB Extender Hub',
            price: 5000,
            quantity: 10,
            images: [
              '/images/products/usb-extender-7in1-1.webp',
              '/images/products/usb-extender-7in1-2.webp',
              '/images/products/usb-extender-7in1-3.webp',
              '/images/products/usb-extender-7in1-4.webp',
              '/images/products/usb-extender-7in1-5.webp'
            ]
          },
          {
            name: 'USB to USB C Charging Cable',
            price: 1000,
            quantity: 15,
            images: [
              '/images/products/usb-to-usbc-cable1.webp',
              '/images/products/usb-to-usbc-cable2.webp',
              '/images/products/usb-to-usbc-cable3.webp'
            ]
          },
          {
            name: 'USB iPhone Charging Cable',
            price: 1000,
            quantity: 10,
            image: '/images/products/usb-iphone-cable1.webp'
          },
          {
            name: '45W High Quality Type-C Charger - Super Fast Charging',
            price: 3500,
            quantity: 40,
            colors: ['#2c2c2c', '#ffffff'],
            images: [
              '/images/products/45w-type-c-charger-4.webp',
              '/images/products/45w-type-c-charger-1.webp',
              '/images/products/45w-type-c-charger-2.webp',
              '/images/products/45w-type-c-charger-3.webp'
            ]
          }
        ]
      }
    ]
  },
  {
    id: 'health-wellness',
    name: 'Health & Wellness',
    icon: '',
    description: 'Support your health and well-being',
    items: [
      {
        name: 'Vitamins & Supplements',
        items: []
      },
      {
        name: 'First Aid Supplies',
        items: []
      },
      {
        name: 'Pain Relief & OTC Products',
        items: []
      },
      {
        name: 'Wellness Devices',
        items: [
          {
            name: 'Non-Contact Forehead Digital Thermometer',
            images: ['/images/products/therm2.webp', '/images/products/therm.webp', '/images/products/therm1.webp'],
            quantity: 10,
            price: 3000
          },
          {
            name: 'Rechargeable/Battery Powered (Optional) Digital Wrist Blood Pressure Monitor with Large LCD, Voice Broadcast (Optional)',
            price: 15000,
            quantity: 3,
            images: [
              '/images/products/blood-pressure-monitor-3.webp',
              '/images/products/blood-pressure-monitor-4.webp'
            ]
          },
          {
            name: 'Rechargeable Arm Blood Pressure Monitor with Large LED Screen, Digital Blood Pressure Machine',
            price: 15000,
            quantity: 3,
            images: [
              '/images/products/arm-bp-monitor-1.webp',
              '/images/products/arm-bp-monitor-2.webp',
              '/images/products/arm-bp-monitor-3.webp',
              '/images/products/arm-bp-monitor-4.webp'
            ]
          },
          {
            name: 'Rechargeable Digital Scale with USB Charging',
            price: 10000,
            quantity: 4,
            images: [
              '/images/products/digital-bathroom-scale1.webp',
              '/images/products/digital-bathroom-scale2.webp',
              '/images/products/digital-bathroom-scale3.webp'
            ]
          }
        ]
      }
    ]
  },
  {
    id: 'home-essentials',
    name: 'Home Essentials',
    icon: '',
    description: 'Comfort and organization for your home',
    items: [
      {
        name: 'Storage & Organization',
        items: []
      },
      {
        name: 'Bedding & Linens',
        items: []
      },
      {
        name: 'Towels',
        items: []
      },
      {
        name: 'Light Home Décor',
        items: []
      }
    ]
  },
  {
    id: 'food-pantry',
    name: 'Food & Pantry',
    icon: '',
    description: 'Non-perishable food items (subject to import regulations)',
    items: [
      {
        name: 'Snacks & Chips',
        items: []
      },
      {
        name: 'Breakfast Items',
        items: []
      },
      {
        name: 'Canned Foods',
        items: []
      },
      {
        name: 'Beverages',
        items: []
      },
      {
        name: 'Dry Goods',
        items: []
      }
    ]
  }
];

