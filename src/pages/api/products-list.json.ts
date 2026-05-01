import type { APIRoute } from 'astro';
import { categories, isSubCategory } from '../../data/categories';

const colorNames: Record<string, string> = {
  '#ff69b4': 'Pink',        '#2c2c2c': 'Black',         '#d4af37': 'Gold',
  '#808080': 'Gray',        '#e74c3c': 'Red',            '#2980b9': 'Blue',
  '#800080': 'Purple',      '#ffffff': 'White',          '#ff8c00': 'Orange',
  '#f5f5dc': 'Beige',       '#c0c0c0': 'Silver',         '#008000': 'Green',
  '#00008b': 'Dark Blue',   '#8b0000': 'Dark Red',       '#ffd700': 'Yellow',
  '#2e7d32': 'Green',       '#1565c0': 'Blue',           '#c62828': 'Dark Red',
  '#4a5240': 'Army Green',  '#1a237e': 'Indigo',         '#4a148c': 'Deep Purple',
  '#7b1fa2': 'Deep Purple', '#b71c1c': 'Crimson',        '#e65100': 'Dark Orange',
  '#33691e': 'Olive Green', '#00bcd4': 'Cyan',
  '#c0b89a': 'Silver Blonde',       '#b8860b': 'Dark Blonde',
  '#c2185b': 'Radiant Raspberry',   '#8b4513': 'Auburn Brown',
  '#800020': 'Burgundy',            '#c0392b': 'Bright Auburn',
  '#6a0dad': 'Vibrant Violet',      '#5c0029': 'Deep Burgundy',
  '#3d1c02': 'Brown Black',         '#e8dcb0': 'Ultra Light Ash Blonde',
};

const getColorName = (hex: string) => colorNames[hex.toLowerCase()] || hex;

export const GET: APIRoute = () => {
  const products: { name: string; quantity: number; price: number; image: string }[] = [];
  const seen = new Set<string>();

  const add = (name: string, quantity: number, price: number, image: string) => {
    if (!seen.has(name)) {
      seen.add(name);
      products.push({ name, quantity, price, image });
    }
  };

  function collectProducts(items: any[]) {
    for (const item of items) {
      if (isSubCategory(item)) {
        collectProducts(item.items);
        continue;
      }
      if (typeof item !== 'object' || item === null || !('name' in item)) continue;

      const name: string = item.name;
      const price: number = item.price ?? 0;
      const colors: string[] = item.colors ?? [];
      const colorQuantities: Record<string, number> = item.colorQuantities ?? {};
      const baseImage: string = item.images?.[0] ?? item.image ?? '';

      if (colors.length > 1 && item.colorImages) {
        // Each color is a distinct physical product with its own images (e.g. Revlon)
        // Add base entry for safety, then one entry per color
        add(name, item.quantity ?? 0, price, baseImage);
        for (const hex of colors) {
          const colorName = getColorName(hex);
          const qty = colorQuantities[hex] !== undefined
            ? colorQuantities[hex]
            : Math.ceil((item.quantity ?? 0) / colors.length);
          const colorImg = item.colorImages[hex]?.[0] ?? baseImage;
          add(`${name} (${colorName})`, qty, price, colorImg);
        }
      } else {
        // Single entry regardless of color count — blenders, fans, speakers etc.
        // all share one barcode across colors
        add(name, item.quantity ?? 0, price, baseImage);
      }
    }
  }

  for (const category of categories) {
    collectProducts(category.items);
  }

  return new Response(JSON.stringify(products), {
    headers: { 'Content-Type': 'application/json' }
  });
};
