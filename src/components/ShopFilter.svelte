<script>
  import { onMount } from 'svelte';

  // Props passed from Astro
  export let categories = [];
  export let whatsappNumber = "237670358551";

  let selectedCategory = 'all';
  let filteredCategories = categories;

  onMount(() => {
    // Read category from URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    const categoryParam = urlParams.get('category');

    if (categoryParam && categories.find(c => c.id === categoryParam)) {
      selectedCategory = categoryParam;
      filterCategories();
    }
  });

  function filterCategories() {
    if (selectedCategory === 'all') {
      filteredCategories = categories;
    } else {
      filteredCategories = categories.filter(c => c.id === selectedCategory);
    }
  }

  function handleCategoryChange(event) {
    selectedCategory = event.target.value;
    filterCategories();

    // Update URL without reload
    const url = new URL(window.location.href);
    if (selectedCategory === 'all') {
      url.searchParams.delete('category');
    } else {
      url.searchParams.set('category', selectedCategory);
    }
    window.history.pushState({}, '', url);
  }

  function getWhatsAppLink(item) {
    const message = encodeURIComponent(`Hi, I'm interested in ordering: ${item}\n\nPlease let me know the price and availability.`);
    return `https://wa.me/${whatsappNumber}?text=${message}`;
  }
</script>

<!-- Category Filter Dropdown -->
<div class="filter-container">
  <label for="category-select" class="filter-label">
    <span class="filter-icon">📁</span>
    Filter by Category:
  </label>
  <select
    id="category-select"
    class="category-select"
    bind:value={selectedCategory}
    on:change={handleCategoryChange}
  >
    <option value="all">All Categories</option>
    {#each categories as category}
      <option value={category.id}>{category.icon} {category.name}</option>
    {/each}
  </select>
</div>

<!-- Showing indicator -->
{#if selectedCategory !== 'all'}
  <div class="showing-category">
    Showing: <strong>{filteredCategories[0]?.name || 'All'}</strong>
    <button class="clear-filter" on:click={() => { selectedCategory = 'all'; filterCategories(); window.history.pushState({}, '', '/shop'); }}>
      ✕ Show All
    </button>
  </div>
{/if}

<!-- Categories Grid -->
{#each filteredCategories as category (category.id)}
  <div class="category-section" id={category.id}>
    <div class="category-header">
      <span class="category-icon-large">{category.icon}</span>
      <div>
        <h2>{category.name}</h2>
        <p>{category.description}</p>
      </div>
    </div>
    <div class="products-grid">
      {#each category.items as item}
        <div class="product-item">
          <div class="product-info">
            <h4>{item}</h4>
            <p class="product-note">Available - Imported from USA/Canada</p>
          </div>
          <a
            href={getWhatsAppLink(item)}
            target="_blank"
            rel="noopener noreferrer"
            class="btn btn-small btn-whatsapp"
          >
            Order via WhatsApp
          </a>
        </div>
      {/each}
    </div>
  </div>
{/each}

<style>
  .filter-container {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    flex-wrap: wrap;
  }

  .filter-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: #2c3e50;
  }

  .filter-icon {
    font-size: 1.2rem;
  }

  .category-select {
    padding: 0.6rem 1rem;
    font-size: 0.95rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    background: white;
    color: #333;
    cursor: pointer;
    min-width: 200px;
    transition: all 0.2s ease;
  }

  .category-select:hover {
    border-color: #3498db;
  }

  .category-select:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.15);
  }

  .showing-category {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding: 0.75rem 1rem;
    background: #eef6fc;
    border-radius: 8px;
    color: #2c3e50;
    font-size: 0.95rem;
  }

  .clear-filter {
    background: #3498db;
    color: white;
    border: none;
    padding: 0.35rem 0.75rem;
    border-radius: 5px;
    font-size: 0.85rem;
    cursor: pointer;
    transition: background 0.2s;
  }

  .clear-filter:hover {
    background: #2c3e50;
  }

  /* Category section styles */
  .category-section {
    margin-bottom: 3rem;
    padding: 1.5rem;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
  }

  .category-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e0e0e0;
  }

  .category-icon-large {
    font-size: 3rem;
  }

  .category-header h2 {
    margin: 0 0 0.25rem 0;
    color: #2c3e50;
  }

  .category-header p {
    margin: 0;
    color: #666;
    font-size: 0.95rem;
  }

  .products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
  }

  .product-item {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    transition: all 0.2s ease;
    border: 2px solid transparent;
  }

  .product-item:hover {
    border-color: #3498db;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  }

  .product-info h4 {
    margin: 0 0 0.25rem 0;
    color: #2c3e50;
    font-size: 0.95rem;
  }

  .product-note {
    margin: 0;
    font-size: 0.8rem;
    color: #666;
  }

  .btn {
    display: inline-block;
    padding: 8px 14px;
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
    border-radius: 6px;
    transition: all 0.2s ease;
    white-space: nowrap;
  }

  .btn-whatsapp {
    background: #25D366;
    color: white;
  }

  .btn-whatsapp:hover {
    background: #128C7E;
  }

  @media (max-width: 768px) {
    .filter-container {
      flex-direction: column;
      align-items: stretch;
    }

    .category-select {
      width: 100%;
    }

    .category-header {
      flex-direction: column;
      text-align: center;
    }

    .products-grid {
      grid-template-columns: 1fr;
    }

    .product-item {
      flex-direction: column;
      text-align: center;
    }

    .showing-category {
      flex-direction: column;
      text-align: center;
    }
  }
</style>
