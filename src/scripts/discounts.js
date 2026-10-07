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

export function findDiscount(discounts, name, category, now = Date.now()) {
  if (!discounts || !name) return null;
  const live = d => (d && !isExpired(d, now) ? d : null);
  const products = discounts.products || {};
  if (live(products[name])) return products[name];
  // Base product shown on the shop, discount set on one of its colour variants
  const prefix = name + ' (';
  for (const key in products) if (key.startsWith(prefix) && live(products[key])) return products[key];
  // Colour variant in the basket, discount set on the base product
  const base = name.replace(/\s\([^()]*\)$/, '');
  if (base !== name && live(products[base])) return products[base];
  if (category) return live(discounts.categories?.[category]);
  return null;
}

// A sale runs to the end of its ends_on day in Cameroon time (UTC+1, no DST)
export function saleEndTime(discount) {
  if (!discount?.ends_on) return null;
  const [y, m, d] = discount.ends_on.split('-').map(Number);
  return Date.UTC(y, m - 1, d + 1) - 3600 * 1000;
}

export function isExpired(discount, now = Date.now()) {
  const end = saleEndTime(discount);
  return end !== null && now >= end;
}

// "Sale ends 31 Oct · 12 days left" → "Ends today · 5h 12m left" → "Ends in 12m"
export function saleEndsLabel(discount, now = Date.now()) {
  const end = saleEndTime(discount);
  if (end === null || now >= end) return '';
  const date = new Date(end - 1).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', timeZone: 'Africa/Douala' });
  const mins  = Math.ceil((end - now) / 60000);
  const days  = Math.floor(mins / 1440);
  const hours = Math.floor((mins % 1440) / 60);
  const m     = mins % 60;
  if (days >= 2) return `Sale ends ${date} · ${days} days left`;
  if (days === 1) return `Sale ends ${date} · 1d ${hours}h left`;
  if (hours >= 1) return `Ends today · ${hours}h ${m}m left`;
  return `Ends in ${m}m`;
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
