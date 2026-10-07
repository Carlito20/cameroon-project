// Shop-side discount helpers. Mirrors public/api/discount-lib.php:
// a product discount beats a category discount, and colour variants
// ("Name (Colour)") share their base product's discount.

export const EMPTY_DISCOUNTS = { products: {}, categories: {} };

export function fetchDiscounts() {
  return fetch('/api/discounts.php', { cache: 'no-store' })
    .then(r => (r.ok ? r.json() : EMPTY_DISCOUNTS))
    .then(d => ({ products: d.products || {}, categories: d.categories || {} }))
    .catch(() => EMPTY_DISCOUNTS);
}

export function findDiscount(discounts, name, category) {
  if (!discounts || !name) return null;
  const products = discounts.products || {};
  if (products[name]) return products[name];
  // Base product shown on the shop, discount set on one of its colour variants
  const prefix = name + ' (';
  for (const key in products) if (key.startsWith(prefix)) return products[key];
  // Colour variant in the basket, discount set on the base product
  const base = name.replace(/\s\([^()]*\)$/, '');
  if (base !== name && products[base]) return products[base];
  if (category && discounts.categories?.[category]) return discounts.categories[category];
  return null;
}

export function applyDiscount(price, discount) {
  if (!price || !discount) return price;
  const v = discount.type === 'percent'
    ? Math.round(price * (100 - discount.value) / 100)
    : price - discount.value;
  return Math.max(0, v);
}

// "-20%" for either type, so badges read the same everywhere
export function discountBadge(original, sale) {
  if (!original || sale >= original) return '';
  return '-' + Math.round((1 - sale / original) * 100) + '%';
}
