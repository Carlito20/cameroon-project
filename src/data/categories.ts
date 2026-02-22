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
            images: ['/images/products/manual-pasta-maker-1.jpg', '/images/products/manual-pasta-maker-2.jpg'],
            price: 20000,
            quantity: 2
          }
        ]
      },
      {
        name: 'Food Storage & Containers',
        items: []
      },
      {
        name: 'Small Kitchen Appliances, Tools & Gadgets',
        items: [
          {
            name: 'Manual Food Chopper, Pull String Vegetable and Meat Mincer with Clear Container, Hand-Powered Kitchen Processor',
            images: ['/images/products/manual-food-chopper.jpeg', '/images/products/manual-food-chopper-2.jpeg'],
            quantity: 11,
            price: 5000
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
            images: ['/images/products/dr-teals-lemon-balm1.jpg', '/images/products/dr-teals-lemon-balm2.jpg'],
            quantity: 10,
            price: 3500
          },
          {
            name: "Dr Teal's Body Wash Relax and Relief with Eucalyptus Spearmint",
            images: ['/images/products/dr-teals-eucalyptus1.jpg', '/images/products/dr-teals-eucalyptus2.jpg'],
            quantity: 10,
            price: 3500
          },
          {
            name: 'Olay Essential Botanicals Body Wash, Spiced Chai & Pear',
            images: ['/images/products/olay-spiced-chai.jpg', '/images/products/olay-back1.jpg', '/images/products/olay-back2.jpg'],
            quantity: 11,
            price: 3000
          },
          {
            name: 'Olay Essential Botanicals Body Wash, White Tea & Cucumber',
            images: ['/images/products/olay-white-tea.jpeg', '/images/products/olay-back1.jpg', '/images/products/olay-back2.jpg'],
            quantity: 11,
            price: 3000
          },
          {
            name: 'Olay Essential Botanicals Body Wash, Lavender Milk & Sandalwood',
            images: ['/images/products/olay-lavender.jpg', '/images/products/olay-back1.jpg', '/images/products/olay-back2.jpg'],
            quantity: 11,
            price: 3000
          }
        ]
      },
      {
        name: 'Body Lotions & Creams',
        items: [
          {
            name: 'CeraVe Daily Moisturizing Lotion For Normal to Dry Skin',
            image: '/images/products/cerave-lotion.jpg',
            quantity: 12,
            price: 8500
          },
          {
            name: 'Cetaphil Moisturizing Cream',
            images: ['/images/products/cetaphil-moisturizer1.jpg', '/images/products/cetaphil-moisturizer2.jpg'],
            quantity: 12,
            price: 10000
          },
          {
            name: 'Aveeno Daily Moisturizing Body Lotion 18oz',
            image: '/images/products/aveeno-lotion.jpg',
            quantity: 12,
            price: 6000
          },
          {
            name: 'Jergens Hydrating Coconut Body Lotion, Hand and Body Moisturizer, Infused with Coconut Oil',
            images: ['/images/products/jergens-coconut1.jpg', '/images/products/jergens-coconut2.jpg', '/images/products/jergens-coconut3.jpg', '/images/products/jergens-coconut4.jpg'],
            quantity: 6,
            price: 6000
          },
          {
            name: 'Jergens Original Scent Dry Skin Body Lotion, Hand and Body Moisturizer, Cherry Almond Essence',
            images: ['/images/products/jergens-cherry1.jpg', '/images/products/jergens-cherry3.jpg'],
            quantity: 6,
            price: 6000
          },
          {
            name: 'Jergens Shea Butter Hand and Body Lotion, Deep Conditioning Moisturizer',
            images: ['/images/products/jergens-shea1.jpg', '/images/products/jergens-shea2.jpg', '/images/products/jergens-common.jpg'],
            quantity: 6,
            price: 6000
          },
          {
            name: 'Jergens Soothing Aloe Body Lotion, Aloe Vera Body and Hand Moisturizer',
            images: ['/images/products/jergens-aloe1.jpg', '/images/products/jergens-aloe2.png', '/images/products/jergens-aloe3.jpg'],
            quantity: 6,
            price: 6000
          },
          {
            name: 'Jergens Ultra Healing Dry Skin Lotion, Hand and Body Moisturizer',
            images: ['/images/products/jergens-ultra1.jpg', '/images/products/jergens-ultra2.jpg', '/images/products/jergens-ultra3.jpg'],
            quantity: 6,
            price: 6000
          },
          {
            name: "Dr Teal's 24 Hour Moisture+ Body Lotion, Prebiotic Lemon Balm & Essential Oils",
            images: ['/images/products/dr-teals-lotion-lemon1.jpg', '/images/products/dr-teals-lotion-lemon2.jpg', '/images/products/dr-teals-lotion-lemon3.jpg'],
            quantity: 6,
            price: 5000
          },
          {
            name: "Dr Teal's 24 Hour Moisture+ Body Lotion, Eucalyptus & Spearmint",
            images: ['/images/products/dr-teals-lotion-eucalyptus1.jpg', '/images/products/dr-teals-lotion-eucalyptus2.jpg'],
            quantity: 6,
            price: 5000
          },
          {
            name: "Dr Teal's 24 Hour Moisture+ Body Lotion, Coconut Oil & Essential Oils",
            images: ['/images/products/dr-teals-lotion-coconut1.jpg', '/images/products/dr-teals-lotion-coconut2.jpg'],
            quantity: 6,
            price: 5000
          },
          {
            name: "Dr Teal's 24 Hour Moisture+ Body Lotion, Shea Butter & Almond",
            images: ['/images/products/dr-teals-lotion-shea1.jpg', '/images/products/dr-teals-lotion-shea2.jpg', '/images/products/dr-teals-lotion-shea3.jpg'],
            quantity: 6,
            price: 5000
          },
          {
            name: "Dr Teal's 24 Hour Moisture+ Body Lotion, Lavender Essential Oil",
            images: ['/images/products/dr-teals-lotion-lavender1.jpg', '/images/products/dr-teals-lotion-lavender2.jpg'],
            quantity: 6,
            price: 5000
          },
          {
            name: 'Vaseline Men Cooling Hydration 3-in-1 Face, Hands & Body Lotion with Menthol',
            images: ['/images/products/vaseline-men-cooling1.jpg', '/images/products/vaseline-men-cooling2.jpg', '/images/products/vaseline-men-cooling3.jpg'],
            quantity: 6,
            price: 6000
          },
          {
            name: 'Vaseline Intensive Care Body Lotion Cocoa Radiant with Pure Cocoa Butter',
            images: ['/images/products/vaseline-cocoa1.jpg', '/images/products/vaseline-cocoa2.jpg', '/images/products/vaseline-cocoa3.jpg'],
            quantity: 6,
            price: 6000
          },
          {
            name: 'Vaseline Intensive Care Soothing Hydration Body Lotion with Aloe Vera',
            images: ['/images/products/vaseline-aloe1.jpg', '/images/products/vaseline-aloe2.jpg'],
            quantity: 6,
            price: 6000
          },
          {
            name: 'Vaseline Intensive Care Sensitive Skin Relief Body Lotion with Colloidal Oatmeal',
            images: ['/images/products/vaseline-sensitive1.jpg', '/images/products/vaseline-sensitive3.jpg'],
            quantity: 6,
            price: 6000
          },
          {
            name: 'Vaseline Intensive Care Calm Healing Body Lotion with Lavender Extract',
            images: ['/images/products/vaseline-lavender1.jpg', '/images/products/vaseline-lavender2.jpg'],
            quantity: 6,
            price: 6000
          },
          {
            name: 'Vaseline Intensive Care Nourishing Moisture Body Lotion with Pure Oat Extract',
            images: ['/images/products/vaseline-oat1.jpg', '/images/products/vaseline-oat2.jpg'],
            quantity: 6,
            price: 6000
          }
        ]
      },
      {
        name: 'Deodorant, Body Mist & Perfumes',
        items: [
          {
            name: 'Men',
            items: [
              { name: 'Paris Night Just for Men', image: '/images/products/paris-night-just-for-men.jpeg', price: 5000, quantity: 6 },
              { name: 'Infinity 3.3 oz Edt For Men', image: '/images/products/infinity-edt-men.jpg', price: 3000, quantity: 6 },
              { name: 'Platinum 3.4 oz Edp For Men', image: '/images/products/platinum-edp-men.jpg', price: 3000, quantity: 6 },
              { name: 'Savage 3.4 oz Edp For Men', image: '/images/products/savage-edp-men.jpg', price: 3000, quantity: 6 },
              { name: 'Taj Max Aqua Sport 3.4 oz Long-Lasting Perfume', image: '/images/products/taj-max-aqua-sport.jpg', price: 6000, quantity: 6 },
              { name: 'Taj Max Exotic Bliss 3.4 oz Long-Lasting Perfume', image: '/images/products/taj-max-exotic-bliss.jpg', price: 6000, quantity: 6 },
              { name: 'Victory 3.3 oz Edt For Men', image: '/images/products/victory-edt-men.jpg', price: 3500, quantity: 3 },
              { name: 'Hercules Paris 3.4 oz Edp For Men', image: '/images/products/hercules-paris-edp-men.jpg', price: 3000, quantity: 6 },
              { name: 'Degree Men Black + White Ultraclear Deodorant', image: '/images/products/degree-men-black-white-ultraclear.jpg', price: 3000, quantity: 15 }
            ]
          },
          {
            name: 'Women',
            items: [
              { name: 'Fantastic Pink 3.4 oz Edp Women', image: '/images/products/fantastic-pink-edp-women.jpg', price: 3000, quantity: 6 },
              { name: 'Fleur De Paris 3.4 oz Edp For Women', image: '/images/products/fleur-de-paris-edp-women.jpg', price: 5000, quantity: 6 },
              { name: 'Lazell Night Bloom For Women Edp 3.4 oz', image: '/images/products/lazell-night-bloom-edp-women.jpg', price: 5000, quantity: 6 },
              { name: 'Lazell Spring For Women Edp 3.4 oz', image: '/images/products/lazell-spring-edp-women.jpg', price: 6000, quantity: 6 },
              { name: 'Miss Coco 3.4 oz Edp For Women', image: '/images/products/miss-coco-edp-women.jpg', price: 3000, quantity: 6 }
            ]
          }
        ]
      },
      {
        name: 'Oral Care (toothpaste, brushes, mouthwash)',
        items: []
      },
      {
        name: 'Feminine Care',
        items: []
      },
      {
        name: "Men's Grooming",
        items: [
          {
            name: "Men's Electric Shaver 3 in 1 - Portable USB Rechargeable Shaver Featuring 3D Floating Blades And a Digital Display Suitable for Both Wet And Dry Shaving",
            price: 15000,
            quantity: 3,
            images: [
              '/images/products/mens-electric-shaver-1.jpeg',
              '/images/products/mens-electric-shaver-2.jpeg',
              '/images/products/mens-electric-shaver-3.jpeg'
            ]
          },
          {
            name: "Men's Electric Shaver | Exquisite Packaging Box, USB Charging, Lithium Battery, Matte Texture, Essential for Men, Beard Trimming",
            price: 12000,
            quantity: 2,
            images: [
              '/images/products/mens-shaver-matte-1.jpeg',
              '/images/products/mens-shaver-matte-2.jpeg'
            ]
          }
        ]
      },
      {
        name: 'Hair Care (shampoo, conditioner, oils)',
        items: []
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
            images: ['/images/products/dr-teals-kids.jpg', '/images/products/dr-teals-kids1.jpg', '/images/products/dr-teals-kids2.jpg'],
            quantity: 12,
            price: 3000
          },
          {
            name: 'Cetaphil Moisturizing Cream',
            images: ['/images/products/cetaphil-moisturizer1.jpg', '/images/products/cetaphil-moisturizer2.jpg'],
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
        items: []
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
              '/images/products/spin-mop-bucket-1.jpeg',
              '/images/products/spin-mop-bucket-2.jpeg',
              '/images/products/spin-mop-bucket-3.jpeg',
              '/images/products/spin-mop-bucket-4.jpeg',
              '/images/products/spin-mop-bucket-5.jpeg',
              '/images/products/spin-mop-bucket-6.jpeg'
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
            quantity: 1,
            colors: ['#ff69b4'],
            images: [
              '/images/products/smart-watch-1.jpeg',
              '/images/products/smart-watch-2.jpeg'
            ]
          },
          {
            name: 'Smartwatch Featuring Wireless Calling, Message Alerts, Various Sports Modes, Information Notifications, Multifunctional Phone Answering/Dialing, Remote Photography, Music Playback, Sports Tracking',
            price: 8000,
            quantity: 6,
            colors: ['#2c2c2c', '#d4af37', '#ff69b4'],
            images: [
              '/images/products/smartwatch-multi-1.jpeg',
              '/images/products/smartwatch-multi-2.jpeg',
              '/images/products/smartwatch-multi-3.jpeg',
              '/images/products/smartwatch-multi-4.jpeg'
            ]
          },
          {
            name: 'Unisex Smartwatch Compatible with Both iPhone and Android',
            price: 8000,
            quantity: 2,
            colors: ['#2c2c2c'],
            images: [
              '/images/products/unisex-smartwatch-1.jpeg',
              '/images/products/unisex-smartwatch-2.jpeg',
              '/images/products/unisex-smartwatch-3.jpeg'
            ]
          },
          {
            name: 'New Smart Watch with 2.01-inch Large Screen, LED Flashlight, Outdoor Sports Watch, Fitness Tracker Compatible with Android and iPhone',
            price: 8000,
            quantity: 1,
            colors: ['#2c2c2c'],
            images: [
              '/images/products/smartwatch-led-1.jpeg',
              '/images/products/smartwatch-led-2.jpeg',
              '/images/products/smartwatch-led-3.jpeg'
            ]
          }
        ]
      },
      {
        name: 'Home Electronics',
        items: [
          {
            name: 'Rechargeable LED Torch/Flashlight With High Power Lamp',
            images: ['/images/products/torch1.jpeg', '/images/products/torch.jpeg', '/images/products/torch2.jpeg', '/images/products/torch3.jpeg'],
            quantity: 15,
            price: 2500
          }
        ]
      },
      {
        name: 'Audio & Entertainment',
        items: [
          {
            name: 'TG537 Portable Wireless Speaker - TWS Technology',
            price: 8000,
            quantity: 6,
            colors: ['#808080', '#e74c3c', '#2c2c2c'],
            images: [
              '/images/products/tg537-speaker-1.jpeg',
              '/images/products/tg537-speaker-2.jpeg',
              '/images/products/tg537-speaker-3.jpeg',
              '/images/products/tg537-speaker-4.jpeg',
              '/images/products/tg537-speaker-5.jpeg'
            ]
          },
          {
            name: 'TG667 Compact And Portable Wireless Speaker. It Supports USB, TF Cards, And FM Radio, And Can Connect to Mobile Phones, Tablets',
            price: 8000,
            quantity: 8,
            colors: ['#e74c3c', '#2980b9', '#2c2c2c', '#800080'],
            images: [
              '/images/products/tg667-speaker-1.jpeg',
              '/images/products/tg667-speaker-2.jpeg',
              '/images/products/tg667-speaker-3.jpeg',
              '/images/products/tg667-speaker-4.jpeg'
            ]
          },
          {
            name: 'Hyundai LP5t Wireless Headphone with Surround Sound and Noise Cancellation',
            price: 5000,
            quantity: 4,
            colors: ['#808080', '#ffffff'],
            images: [
              '/images/products/hyundai-lp5t-1.jpeg',
              '/images/products/hyundai-lp5t-2.jpeg',
              '/images/products/hyundai-lp5t-3.jpeg',
              '/images/products/hyundai-lp5t-4.jpeg'
            ]
          },
          {
            name: 'Wireless Headphones, 5.4 Headphones with 4 ENC Noise-cancelling Microphones, HiFi Stereo Deep Bass, 50 Hours Of Playback Time, USB-C',
            price: 2500,
            quantity: 2,
            colors: ['#2c2c2c'],
            images: [
              '/images/products/wireless-headphones-enc-1.jpeg',
              '/images/products/wireless-headphones-enc-2.jpeg'
            ]
          },
          {
            name: 'Wireless Headphones with Touch Control And Charging Case',
            price: 2500,
            quantity: 3,
            images: [
              '/images/products/wireless-touch-headphones-1.jpeg',
              '/images/products/wireless-touch-headphones-2.jpeg',
              '/images/products/wireless-touch-headphones-3.jpeg'
            ]
          },
          {
            name: 'Acer TWS Wireless Headset with Heavy Bass Stereo + Talking Noise Cancellation',
            price: 3500,
            quantity: 4,
            colors: ['#ff8c00', '#f5f5dc'],
            images: [
              '/images/products/acer-tws-headset-1.jpeg',
              '/images/products/acer-tws-headset-2.jpeg',
              '/images/products/acer-tws-headset-3.jpeg',
              '/images/products/acer-tws-headset-4.jpeg'
            ]
          },
          {
            name: 'Acer Wireless 5.4 Bass Stereo Headset with Noise Cancelling Microphone, Touch Control',
            price: 3500,
            quantity: 6,
            colors: ['#f5f5dc', '#2c2c2c', '#800080'],
            images: [
              '/images/products/acer-wireless-stereo-1.jpeg',
              '/images/products/acer-wireless-stereo-2.jpeg'
            ]
          },
          {
            name: 'Nokia Go Earbuds+ TWS-201',
            price: 6000,
            quantity: 2,
            colors: ['#ffffff', '#2c2c2c'],
            images: [
              '/images/products/nokia-earbuds-1.jpeg',
              '/images/products/nokia-earbuds-2.jpeg',
              '/images/products/nokia-earbuds-3.jpeg',
              '/images/products/nokia-earbuds-4.jpeg'
            ]
          },
          {
            name: 'Wireless Earbuds with Charging Case, USB Type-C Charging, TWS Technology',
            price: 6000,
            quantity: 4,
            colors: ['#ffffff'],
            images: [
              '/images/products/wireless-earbuds-tws-1.jpeg',
              '/images/products/wireless-earbuds-tws-2.jpeg',
              '/images/products/wireless-earbuds-tws-3.jpeg'
            ]
          },
          {
            name: 'Portable Wireless Speaker, 15W Stereo, RGB Lighting, Suitable for Both Indoor And Outdoor Use',
            price: 6500,
            quantity: 3,
            images: [
              '/images/products/portable-speaker-rgb-1.jpeg',
              '/images/products/portable-speaker-rgb-2.jpeg'
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
            name: 'Data Cable, USB C to USB C Charging Cable, Type C to Type C Fast Charging',
            price: 300,
            quantity: 15,
            images: [
              '/images/products/usb-c-cable-1.jpeg',
              '/images/products/usb-c-cable-2.jpeg',
              '/images/products/usb-c-cable-3.jpeg',
              '/images/products/usb-c-cable-4.jpeg'
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
    description: 'Support your health and wellbeing',
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
            name: 'Non Contact Forehead Digital Thermometer',
            images: ['/images/products/therm2.jpeg', '/images/products/therm.jpeg', '/images/products/therm1.jpeg'],
            quantity: 10,
            price: 3000
          },
          {
            name: 'Rechargeable/Battery Powered(Optional) Digital Wrist Blood Pressure Monitor with Large LCD, Voice Broadcast/(Optional)',
            price: 15000,
            quantity: 3,
            images: [
              '/images/products/blood-pressure-monitor-3.jpeg',
              '/images/products/blood-pressure-monitor-4.jpeg'
            ]
          },
          {
            name: 'Rechargeable Arm Blood Pressure Monitor with Large LED Screen, Digital Blood Pressure Machine',
            price: 15000,
            quantity: 3,
            images: [
              '/images/products/arm-bp-monitor-1.jpeg',
              '/images/products/arm-bp-monitor-2.jpeg',
              '/images/products/arm-bp-monitor-3.jpeg',
              '/images/products/arm-bp-monitor-4.jpeg',
              '/images/products/arm-bp-monitor-5.jpeg'
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

