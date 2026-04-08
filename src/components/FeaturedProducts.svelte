<script>
  // ── Full product pool (all have confirmed images) ──────────────────────
  const POOL = [
    { name: "Manual Pasta Maker Machine, 9 Adjustable Thickness Settings",                                                   short: "Manual Pasta Maker",                        price: 20000, stock: 2,  image: "/images/products/manual-pasta-maker-1.jpg",       category: "Kitchen"        },
    { name: "Manual Food Chopper, Pull String Vegetable and Meat Mincer with Clear Container, Hand-Powered Kitchen Processor", short: "Manual Food Chopper",                       price: 8000,  stock: 11, image: "/images/products/manual-food-chopper.jpeg",       category: "Kitchen"        },
    { name: "Dr Teal's Body Wash Relax and Relief with Eucalyptus Spearmint",                                                 short: "Dr Teal's Body Wash — Eucalyptus",          price: 5000,  stock: 3,  image: "/images/products/dr-teals-eucalyptus1.jpg",       category: "Body Care"      },
    { name: "Dr Teal's 24 Hour Moisture+ Body Lotion, Prebiotic Lemon Balm & Essential Oils",                                 short: "Dr Teal's Lotion — Lemon Balm",             price: 5000,  stock: 3,  image: "/images/products/dr-teals-lemon-balm1.jpg",       category: "Body Care"      },
    { name: "Dr Teal's 24 Hour Moisture+ Body Lotion, Coconut Oil & Essential Oils",                                          short: "Dr Teal's Lotion — Coconut Oil",            price: 5000,  stock: 3,  image: "/images/products/dr-teals-lotion-coconut1.jpg",   category: "Body Care"      },
    { name: "Dr Teal's 24 Hour Moisture+ Body Lotion, Lavender Essential Oil",                                                short: "Dr Teal's Lotion — Lavender",               price: 5000,  stock: 3,  image: "/images/products/dr-teals-lotion-lavender1.jpg",  category: "Body Care"      },
    { name: "Dr Teal's Kids 3-in-1 Sleep Bath: Bubble Bath, Body Wash & Shampoo with Melatonin & Essential Oil",             short: "Dr Teal's Kids 3-in-1 Sleep Bath",          price: 4500,  stock: 3,  image: "/images/products/dr-teals-kids.jpg",              category: "Kids"           },
    { name: "Aveeno Daily Moisturizing Body Lotion 18oz",                                                                     short: "Aveeno Daily Moisturizing Lotion 18oz",     price: 6000,  stock: 5,  image: "/images/products/aveeno-lotion.jpg",              category: "Body Care"      },
    { name: "Jergens Ultra Healing Dry Skin Lotion, Hand and Body Moisturizer",                                               short: "Jergens Ultra Healing Lotion",              price: 6000,  stock: 3,  image: "/images/products/jergens-ultra1.jpg",             category: "Body Care"      },
    { name: "Jergens Hydrating Coconut Body Lotion, Hand and Body Moisturizer, Infused with Coconut Oil",                    short: "Jergens Hydrating Coconut Lotion",          price: 6000,  stock: 3,  image: "/images/products/jergens-coconut1.jpg",           category: "Body Care"      },
    { name: "Jergens Shea Butter Hand and Body Lotion, Deep Conditioning Moisturizer",                                        short: "Jergens Shea Butter Lotion",                price: 6000,  stock: 3,  image: "/images/products/jergens-shea1.jpg",              category: "Body Care"      },
    { name: "Olay Essential Botanicals Body Wash, Spiced Chai & Pear",                                                        short: "Olay Body Wash — Spiced Chai & Pear",       price: 3500,  stock: 11, image: "/images/products/olay-spiced-chai.jpg",           category: "Body Care"      },
    { name: "Olay Essential Botanicals Body Wash, White Tea & Cucumber",                                                       short: "Olay Body Wash — White Tea & Cucumber",     price: 3500,  stock: 11, image: "/images/products/olay-white-tea.jpeg",            category: "Body Care"      },
    { name: "Men's Electric Shaver 3 in 1 - Portable USB Rechargeable Shaver Featuring 3D Floating Blades and a Digital Display Suitable for Both Wet and Dry Shaving", short: "Men's Electric Shaver 3-in-1", price: 15000, stock: 6, image: "/images/products/mens-electric-shaver-1.jpeg", category: "Personal Care" },
    { name: "LQT Men's Electric Shaver | Exquisite Packaging Box, USB Charging, Lithium Battery, Matte Texture, Essential for Men, Beard Trimming", short: "LQT Men's Electric Shaver",     price: 12000, stock: 2,  image: "/images/products/mens-shaver-matte-1.jpeg",       category: "Personal Care"  },
    { name: "Acer OHR544 Wireless Headset with Heavy Bass Stereo + Talking Noise Cancellation",                               short: "Acer OHR544 Wireless Headset",              price: 5500,  stock: 5,  image: "/images/products/acer-tws-headset-1.jpeg",        category: "Electronics"    },
    { name: "Nokia Go Earbuds+ TWS-201",                                                                                      short: "Nokia Go Earbuds+ TWS-201",                 price: 8000,  stock: 9,  image: "/images/products/nokia-earbuds-1.jpeg",           category: "Electronics"    },
    { name: "Hyundai LP5t Wireless Headphones with Surround Sound and Noise Cancellation",                                    short: "Hyundai LP5t Wireless Headphones",          price: 7000,  stock: 10, image: "/images/products/hyundai-lp5t-1.jpeg",            category: "Electronics"    },
    { name: "Portable Wireless Speaker, 15W Stereo, RGB Lighting, Suitable for Both Indoor and Outdoor Use",                  short: "Portable RGB Wireless Speaker 15W",         price: 6000,  stock: 3,  image: "/images/products/portable-speaker-rgb-1.jpeg",    category: "Electronics"    },
    { name: "Rechargeable Arm Blood Pressure Monitor with Large LED Screen, Digital Blood Pressure Machine",                   short: "Arm Blood Pressure Monitor",                price: 15000, stock: 3,  image: "/images/products/arm-bp-monitor-1.jpeg",          category: "Health"         },
  ];

  const DAILY_COUNT = 8;

  // ── Seeded daily shuffle ───────────────────────────────────────────────
  // Uses the day-of-year as a seed so it's consistent all day, changes daily
  function getDaySeed() {
    const now = new Date();
    const start = new Date(now.getFullYear(), 0, 0);
    return Math.floor((now - start) / 86400000); // day of year
  }

  function seededShuffle(arr, seed) {
    const a = [...arr];
    let s = seed;
    for (let i = a.length - 1; i > 0; i--) {
      // Simple LCG
      s = (s * 1664525 + 1013904223) & 0xffffffff;
      const j = Math.abs(s) % (i + 1);
      [a[i], a[j]] = [a[j], a[i]];
    }
    return a;
  }

  const featured = seededShuffle(POOL, getDaySeed()).slice(0, DAILY_COUNT);

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
</script>

<section class="featured-section">
  <div class="container">
    <div class="featured-header">
      <div>
        <h2>Featured Products</h2>
        <p>Today's picks — imported fresh from the USA &amp; Canada</p>
      </div>
      <a href="/shop" class="view-all-btn">View All Products →</a>
    </div>

    <div class="featured-grid">
      {#each featured as product, i}
        <div class="fp-card">
          <a href="/shop" class="fp-img-wrap">
            <img src={product.image} alt={product.short} loading="lazy">
            <span class="fp-category">{product.category}</span>
          </a>
          <div class="fp-body">
            <p class="fp-name">{product.short}</p>
            <p class="fp-price">{fmt(product.price)}</p>
            <button
              class="fp-add-btn"
              class:added={addedIndex === i}
              on:click={() => addToBasket(product, i)}
              disabled={product.stock === 0}
            >
              {addedIndex === i ? '✓ Added' : product.stock === 0 ? 'Out of Stock' : 'Add to Basket'}
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
  }

  .view-all-btn:hover {
    border-color: var(--primary-color, #3498db);
  }

  .featured-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
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
    background: #f0f0f0;
    text-decoration: none;
  }

  .fp-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.35s ease;
    display: block;
  }

  .fp-card:hover .fp-img-wrap img {
    transform: scale(1.05);
  }

  .fp-category {
    position: absolute;
    top: 8px;
    left: 8px;
    background: rgba(0,0,0,0.55);
    color: #fff;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 20px;
    letter-spacing: 0.03em;
    text-transform: uppercase;
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
    background: var(--secondary-color, #2c3e50);
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

    .fp-category {
      font-size: 0.62rem;
    }
  }
</style>
