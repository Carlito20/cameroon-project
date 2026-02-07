export interface Product {
  name: string;
  image?: string;
  images?: string[];
  price?: number;
  quantity?: number;
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
    name: 'Home & Kitchen',
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
        name: 'Small Kitchen Appliances',
        items: []
      },
      {
        name: 'Kitchen Tools & Gadgets',
        items: []
      },
      {
        name: 'Dinnerware & Utensils',
        items: []
      },
      {
        name: 'Cleaning Tools (sponges, mops, brushes)',
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
          }
        ]
      },
      {
        name: 'Deodorants & Perfumes',
        items: [
          { name: 'Paris Night Just for Men', image: '/images/products/paris-night-just-for-men.jpeg', price: 5000, quantity: 10 },
          { name: 'Fantastic Pink 3.4 oz Edp Women', image: '/images/products/fantastic-pink-edp-women.jpg', price: 3000, quantity: 8 },
          { name: 'Fleur De Paris 3.4 oz Edp For Women', image: '/images/products/fleur-de-paris-edp-women.jpg', price: 5000, quantity: 5 },
          { name: 'Infinity 3.3 oz Edt For Men', image: '/images/products/infinity-edt-men.jpg', price: 3000, quantity: 12 },
          { name: 'Lazell Night Bloom For Women Edp 3.4 oz', image: '/images/products/lazell-night-bloom-edp-women.jpg', price: 5000, quantity: 6 },
          { name: 'Lazell Spring For Women Edp 3.4 oz', image: '/images/products/lazell-spring-edp-women.jpg', price: 6000, quantity: 4 },
          { name: 'Miss Coco 3.4 oz Edp For Women', image: '/images/products/miss-coco-edp-women.jpg', price: 3000, quantity: 7 },
          { name: 'Platinum 3.4 oz Edp For Men', image: '/images/products/platinum-edp-men.jpg', price: 3000, quantity: 3 },
          { name: 'Savage 3.4 oz Edp For Men', image: '/images/products/savage-edp-men.jpg', price: 3000, quantity: 9 },
          { name: 'Taj Max Aqua Sport 3.4 oz Long-Lasting Perfume', image: '/images/products/taj-max-aqua-sport.jpg', price: 6000, quantity: 5 },
          { name: 'Taj Max Exotic Bliss 3.4 oz Long-Lasting Perfume', image: '/images/products/taj-max-exotic-bliss.jpg', price: 6000, quantity: 4 },
          { name: 'Victory 3.3 oz Edt For Men', image: '/images/products/victory-edt-men.jpg', price: 3500, quantity: 11 },
          { name: 'Hercules Paris 3.4 oz Edp For Men', image: '/images/products/hercules-paris-edp-men.jpg', price: 3000, quantity: 6 },
          { name: 'Degree Men Black + White Ultraclear Deodorant', image: '/images/products/degree-men-black-white-ultraclear.jpg', price: 3000, quantity: 15 }
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
        items: []
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
        name: 'Baby Diapers',
        items: []
      },
      {
        name: 'Baby Wipes',
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
          }
        ]
      },
      {
        name: 'Baby Feeding (bottles, formula containers)',
        items: []
      },
      {
        name: 'Baby Health & Hygiene',
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
        name: 'Surface Cleaners & Disinfectants',
        items: []
      },
      {
        name: 'Air Fresheners',
        items: []
      },
      {
        name: 'Paper Towels & Tissues',
        items: []
      },
      {
        name: 'Trash Bags & Storage Bags',
        items: []
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
        name: 'Small Electronics (chargers, power banks)',
        items: []
      },
      {
        name: 'Kitchen Electronics (blenders, kettles)',
        items: []
      },
      {
        name: 'Home Electronics',
        items: [
          {
            name: 'Rechargeable LED Flashlight With High Power Lamp',
            images: ['/images/products/torch1.jpeg', '/images/products/torch.jpeg', '/images/products/torch2.jpeg', '/images/products/torch3.jpeg'],
            quantity: 15,
            price: 2500
          }
        ]
      },
      {
        name: 'Cables & Adapters',
        items: []
      },
      {
        name: 'Smart Home Accessories',
        items: []
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
        items: [
          {
            name: 'Non Contact Forehead Digital Thermometer',
            images: ['/images/products/therm2.jpeg', '/images/products/therm.jpeg', '/images/products/therm1.jpeg'],
            quantity: 10,
            price: 3000
          }
        ]
      },
      {
        name: 'Pain Relief & OTC Products',
        items: []
      },
      {
        name: 'Sanitizers & Masks',
        items: []
      },
      {
        name: 'Wellness Devices (thermometers, BP monitors)',
        items: []
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
        name: 'Home Fragrances',
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
        name: 'Beverages (powders, drink mixes)',
        items: []
      },
      {
        name: 'Spices & Condiments',
        items: []
      }
    ]
  }
];

