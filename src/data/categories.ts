export interface Product {
  name: string;
  image?: string;
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
    icon: '🏠',
    description: 'Cookware, bakeware, storage, and kitchen essentials',
    items: [
      {
        name: 'Cookware & Bakeware',
        items: []
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
    icon: '🛁',
    description: 'Personal hygiene and grooming products',
    items: [
      {
        name: 'Bath Soaps & Shower Gels',
        items: []
      },
      {
        name: 'Body Lotions & Creams',
        items: []
      },
      {
        name: 'Deodorants & Perfumes',
        items: [
          { name: 'Paris Night Just for Men', image: '/images/products/paris-night-just-for-men.jpeg' }
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
    name: 'Kids',
    icon: '👶',
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
        items: []
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
    icon: '🧼',
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
    icon: '🔌',
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
        items: []
      },
      {
        name: 'Cables & Adapters',
        items: []
      },
      {
        name: 'Batteries',
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
    icon: '🧴',
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
    icon: '🧺',
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
    icon: '🧃',
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

