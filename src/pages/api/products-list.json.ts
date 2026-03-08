import type { APIRoute } from 'astro';
import { categories, isSubCategory } from '../../data/categories';

export const GET: APIRoute = () => {
  const products: { name: string; quantity: number }[] = [];

  function collectProducts(items: any[]) {
    for (const item of items) {
      if (isSubCategory(item)) {
        collectProducts(item.items);
      } else if (typeof item === 'object' && item !== null && 'name' in item) {
        products.push({
          name: item.name,
          quantity: item.quantity ?? 0
        });
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
