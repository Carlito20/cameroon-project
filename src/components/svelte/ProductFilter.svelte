<script>
  // This is the JavaScript/logic section
  let selectedCategory = 'all';
  let searchTerm = '';

  // Sample products - replace with your real data
  let products = [
    { id: 1, name: 'Dove Soap', category: 'body-care', price: 2500 },
    { id: 2, name: 'Baby Diapers', category: 'baby', price: 8000 },
    { id: 3, name: 'Laundry Detergent', category: 'cleaning', price: 5000 },
    { id: 4, name: 'Body Lotion', category: 'body-care', price: 3500 },
    { id: 5, name: 'Kitchen Towels', category: 'home', price: 1500 },
  ];

  // Categories for your business
  const categories = [
    { value: 'all', label: 'All Products' },
    { value: 'home', label: 'Home & Kitchen' },
    { value: 'body-care', label: 'Body & Bath' },
    { value: 'baby', label: 'Baby & Diapers' },
    { value: 'cleaning', label: 'Household Cleaning' },
    { value: 'electronics', label: 'Electronics' },
  ];

  // Reactive statement - automatically updates when dependencies change
  $: filteredProducts = products.filter(product => {
    const matchesCategory = selectedCategory === 'all' || product.category === selectedCategory;
    const matchesSearch = product.name.toLowerCase().includes(searchTerm.toLowerCase());
    return matchesCategory && matchesSearch;
  });
</script>

<!-- This is the HTML/template section -->
<div class="product-filter">
  <h2>Shop Our Products</h2>

  <!-- Search Box -->
  <div class="search-box">
    <input
      type="text"
      bind:value={searchTerm}
      placeholder="Search products..."
      class="search-input"
    />
  </div>

  <!-- Category Filter -->
  <div class="category-buttons">
    {#each categories as category}
      <button
        class="category-btn {selectedCategory === category.value ? 'active' : ''}"
        on:click={() => selectedCategory = category.value}
      >
        {category.label}
      </button>
    {/each}
  </div>

  <!-- Product Results -->
  <div class="products-grid">
    {#if filteredProducts.length === 0}
      <p class="no-results">No products found. Try a different search or category.</p>
    {:else}
      {#each filteredProducts as product (product.id)}
        <div class="product-card">
          <h3>{product.name}</h3>
          <p class="category">{categories.find(c => c.value === product.category)?.label}</p>
          <p class="price">{product.price.toLocaleString()} XAF</p>
          <button class="add-to-cart">Add to Cart</button>
        </div>
      {/each}
    {/if}
  </div>

  <p class="result-count">
    Showing {filteredProducts.length} of {products.length} products
  </p>
</div>

<!-- This is the CSS/styling section -->
<style>
  .product-filter {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
  }

  h2 {
    text-align: center;
    color: #2c3e50;
    margin-bottom: 30px;
  }

  .search-box {
    margin-bottom: 20px;
  }

  .search-input {
    width: 100%;
    padding: 12px 20px;
    font-size: 16px;
    border: 2px solid #ddd;
    border-radius: 8px;
    transition: border-color 0.3s;
  }

  .search-input:focus {
    outline: none;
    border-color: #667eea;
  }

  .category-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 30px;
  }

  .category-btn {
    padding: 10px 20px;
    background: #f8f9fa;
    border: 2px solid #ddd;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 14px;
  }

  .category-btn:hover {
    background: #e9ecef;
    border-color: #667eea;
  }

  .category-btn.active {
    background: #667eea;
    color: white;
    border-color: #667eea;
  }

  .products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
  }

  .product-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    transition: transform 0.3s, box-shadow 0.3s;
  }

  .product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  }

  .product-card h3 {
    margin: 0 0 10px 0;
    color: #2c3e50;
    font-size: 18px;
  }

  .category {
    color: #7f8c8d;
    font-size: 14px;
    margin: 5px 0;
  }

  .price {
    color: #667eea;
    font-size: 20px;
    font-weight: bold;
    margin: 10px 0;
  }

  .add-to-cart {
    width: 100%;
    padding: 10px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
    transition: background 0.3s;
  }

  .add-to-cart:hover {
    background: #5568d3;
  }

  .no-results {
    text-align: center;
    color: #7f8c8d;
    padding: 40px;
    font-size: 18px;
  }

  .result-count {
    text-align: center;
    color: #7f8c8d;
    font-size: 14px;
  }
</style>
