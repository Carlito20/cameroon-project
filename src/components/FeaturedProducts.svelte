<script>
  import { onMount } from 'svelte';
  import { categories } from '../data/categories.ts';

  // Recursively collect all in-stock products with images from a category's items
  function collectProducts(items) {
    const result = [];
    for (const item of items) {
      if (typeof item === 'string') continue;
      if ('items' in item) {
        result.push(...collectProducts(item.items));
      } else {
        const img = (item.images && item.images.length > 0) ? item.images[0] : item.image;
        if (img && item.price && (item.quantity ?? 0) > 0) {
          result.push({ name: item.name, price: item.price, stock: item.quantity, image: img });
        }
      }
    }
    return result;
  }

  // One pool per top-level category, filtered to categories that have products
  const categoryPools = categories
    .map(cat => ({ id: cat.id, name: cat.name, products: collectProducts(cat.items) }))
    .filter(c => c.products.length > 0);

  // Changes every 4 hours — 6 rotations per day
  function getSeed() {
    return Math.floor(Date.now() / (1000 * 60 * 60 * 4));
  }

  let featured = [];

  onMount(() => {
    const seed = getSeed();
    const offsets = [0, 2, 5, 7, 11, 13];
    featured = categoryPools.map((cat, ci) => {
      const p = cat.products[(seed + offsets[ci % offsets.length]) % cat.products.length];
      return { ...p, categoryId: cat.id };
    });
  });

  // ── State & helpers ────────────────────────────────────────────────────
  let addedIndex = -1;

  function addToBasket(product, i) {
    window.dispatchEvent(new CustomEvent('add-to-inquiry', {
      detail: {
        name: product.name,
        image: product.image,
        price: product.price,
        quantity: 1,
        stock: product.stock,
      }
    }));
    addedIndex = i;
    setTimeout(() => { addedIndex = -1; }, 1500);
  }

  function fmt(n) {
    return Number(n).toLocaleString('fr-CM') + ' XAF';
  }

  function shopLink(categoryId, productName) {
    return `/shop?category=${categoryId}&product=${encodeURIComponent(productName)}`;
  }
</script>

<section class="featured-section">
  <div class="container">
    <div class="featured-header">
      <div>
        <h2>Today's Picks</h2>
        <p>Fresh selections updated daily — imported from the USA &amp; Canada</p>
      </div>
      <a href="/shop" class="view-all-btn">View All Products →</a>
    </div>

    <div class="featured-grid">
      {#each featured as product, i}
        <div class="fp-card">
          <a href={shopLink(product.categoryId, product.name)} class="fp-img-wrap">
            <img src={product.image} alt={product.name} loading="lazy">
          </a>
          <div class="fp-body">
            <p class="fp-name">{product.name}</p>
            <p class="fp-price">{fmt(product.price)}</p>
            <button
              class="fp-add-btn"
              class:added={addedIndex === i}
              on:click={() => addToBasket(product, i)}
              disabled={product.stock === 0}
            >
              {addedIndex === i ? '✓ Added' : product.stock === 0 ? 'Out of Stock' : 'Add to Cart'}
            </button>
          </div>
        </div>
      {/each}
    </div>

    <div class="featured-footer">
      <a href="/shop" class="btn">Browse All Products</a>
    </div>
  </div>
</section>

<style>
  .featured-section {
    padding: 4rem 0 3rem;
    background: #f8f9fa;
  }

  .featured-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
  }

  .featured-header h2 {
    font-size: 1.8rem;
    color: var(--secondary-color, #2c3e50);
    margin-bottom: 0.3rem;
  }

  .featured-header p {
    color: #666;
    font-size: 0.95rem;
    margin: 0;
  }

  .view-all-btn {
    color: var(--primary-color, #3498db);
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
    white-space: nowrap;
    padding: 4px 0;
    border-bottom: 2px solid transparent;
    transition: border-color 0.2s;
    -webkit-user-select: none;
    user-select: none;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
  }

  .view-all-btn:hover {
    border-color: var(--primary-color, #3498db);
  }

  .featured-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.25rem;
  }

  .fp-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,.07);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
    -webkit-transform: translateZ(0);
    transform: translateZ(0);
    will-change: transform;
  }

  .fp-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,.12);
  }

  .fp-img-wrap {
    display: block;
    position: relative;
    aspect-ratio: 1 / 1;
    overflow: hidden;
    background: #f8f8f8;
    text-decoration: none;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
  }

  .fp-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 6px;
    transition: transform 0.35s ease;
    display: block;
    box-sizing: border-box;
  }

  .fp-card:hover .fp-img-wrap img {
    transform: scale(1.05);
  }

  .fp-body {
    padding: 0.85rem 1rem 1rem;
    display: flex;
    flex-direction: column;
    flex: 1;
    gap: 0.5rem;
  }

  .fp-name {
    font-size: 0.875rem;
    font-weight: 600;
    color: #222;
    line-height: 1.4;
    flex: 1;
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .fp-price {
    font-size: 1rem;
    font-weight: 700;
    color: var(--primary-color, #3498db);
    margin: 0;
  }

  .fp-add-btn {
    width: 100%;
    padding: 9px 12px;
    background: #111111;
    color: #fff;
    border: none;
    border-radius: 7px;
    font-size: 0.85rem;
    font-weight: 700;
    cursor: pointer;
    min-height: 44px;
    touch-action: manipulation;
    -webkit-user-select: none;
    user-select: none;
    -webkit-tap-highlight-color: transparent;
    -webkit-appearance: none;
    appearance: none;
    transition: background 0.2s ease, transform 0.15s ease;
  }

  .fp-add-btn:hover:not(:disabled) {
    background: var(--gold-color, #c8a951);
    color: #111;
  }

  .fp-add-btn:active:not(:disabled) {
    transform: scale(0.97);
  }

  .fp-add-btn.added {
    background: #27ae60;
    color: #fff;
  }

  .fp-add-btn:disabled {
    background: #ddd;
    color: #999;
    cursor: not-allowed;
  }

  .featured-footer {
    text-align: center;
    margin-top: 2.5rem;
  }

  @media (max-width: 1024px) {
    .featured-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }

  @media (max-width: 600px) {
    .featured-section {
      padding: 2.5rem 0 2rem;
    }

    .featured-header {
      flex-direction: column;
      align-items: flex-start;
      margin-bottom: 1.25rem;
    }

    .featured-header h2 {
      font-size: 1.4rem;
    }

    .featured-grid {
      grid-template-columns: repeat(2, 1fr);
      gap: 0.85rem;
    }

    .fp-body {
      padding: 0.7rem 0.75rem 0.85rem;
    }

    .fp-name {
      font-size: 0.8rem;
    }

    .fp-price {
      font-size: 0.9rem;
    }

    .fp-add-btn {
      font-size: 0.8rem;
      padding: 8px 10px;
      min-height: 44px;
    }
  }

  @media (max-width: 375px) {
    .featured-grid {
      gap: 0.65rem;
    }
  }
</style>
