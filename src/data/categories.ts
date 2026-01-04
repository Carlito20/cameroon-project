export interface Category {
  id: string;
  name: string;
  icon: string;
  description: string;
  items: string[];
}

export const categories: Category[] = [
  {
    id: 'home-kitchen',
    name: 'Home & Kitchen',
    icon: '🏠',
    description: 'Cookware, bakeware, storage, and kitchen essentials',
    items: [
      'Cookware & Bakeware',
      'Food Storage & Containers',
      'Small Kitchen Appliances',
      'Kitchen Tools & Gadgets',
      'Dinnerware & Utensils',
      'Cleaning Tools (sponges, mops, brushes)'
    ]
  },
  {
    id: 'body-bath',
    name: 'Body, Bath & Personal Care',
    icon: '🛁',
    description: 'Personal hygiene and grooming products',
    items: [
      'Bath Soaps & Shower Gels',
      'Body Lotions & Creams',
      'Deodorants',
      'Oral Care (toothpaste, brushes, mouthwash)',
      'Feminine Care',
      "Men's Grooming",
      'Hair Care (shampoo, conditioner, oils)'
    ]
  },
  {
    id: 'baby-diapers',
    name: 'Baby & Diapers',
    icon: '👶',
    description: 'Everything for your little ones',
    items: [
      'Baby Diapers',
      'Baby Wipes',
      'Baby Bath & Skincare',
      'Baby Feeding (bottles, formula containers)',
      'Baby Health & Hygiene'
    ]
  },
  {
    id: 'household-cleaning',
    name: 'Household Cleaning & Supplies',
    icon: '🧼',
    description: 'Keep your home clean and fresh',
    items: [
      'Laundry Detergents',
      'Dishwashing Supplies',
      'Surface Cleaners & Disinfectants',
      'Air Fresheners',
      'Paper Towels & Tissues',
      'Trash Bags & Storage Bags'
    ]
  },
  {
    id: 'electronics',
    name: 'Electronics & Accessories',
    icon: '🔌',
    description: 'Essential electronics and accessories',
    items: [
      'Small Electronics (chargers, power banks)',
      'Kitchen Electronics (blenders, kettles)',
      'Home Electronics',
      'Cables & Adapters',
      'Batteries',
      'Smart Home Accessories'
    ]
  },
  {
    id: 'health-wellness',
    name: 'Health & Wellness',
    icon: '🧴',
    description: 'Support your health and wellbeing',
    items: [
      'Vitamins & Supplements',
      'First Aid Supplies',
      'Pain Relief & OTC Products',
      'Sanitizers & Masks',
      'Wellness Devices (thermometers, BP monitors)'
    ]
  },
  {
    id: 'home-essentials',
    name: 'Home Essentials',
    icon: '🧺',
    description: 'Comfort and organization for your home',
    items: [
      'Storage & Organization',
      'Bedding & Linens',
      'Towels',
      'Home Fragrances',
      'Light Home Décor'
    ]
  },
  {
    id: 'food-pantry',
    name: 'Food & Pantry',
    icon: '🧃',
    description: 'Non-perishable food items (subject to import regulations)',
    items: [
      'Snacks & Chips',
      'Breakfast Items',
      'Canned Foods',
      'Beverages (powders, drink mixes)',
      'Spices & Condiments'
    ]
  }
];

