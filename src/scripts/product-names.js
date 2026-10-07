// Display names set in the admin dashboard (✏️ rename). Mirrors
// public/api/product-names-lib.php: the original name stays the key for
// stock, prices and orders; colour variants follow their base product.

export function fetchProductNames() {
  return fetch('/api/product-names.php', { cache: 'no-store' })
    .then(r => (r.ok ? r.json() : {}))
    .then(d => (d && typeof d === 'object' && !Array.isArray(d) ? d : {}))
    .catch(() => ({}));
}

export function shownName(names, name) {
  if (!name || !names) return name;
  if (names[name]) return names[name];
  const m = name.match(/^(.*) \(([^()]*)\)$/);
  return m && names[m[1]] ? `${names[m[1]]} (${m[2]})` : name;
}
