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

  // Track which items have been added to inquiry
  let addedItems = new Set();

  // Track which item has the confirmation popup open
  let confirmingItem = null;

  function handleInquiryClick(item, categoryName) {
    if (addedItems.has(item)) {
      // Item already added - show confirmation popup
      confirmingItem = confirmingItem === item ? null : item;
    } else {
      // Add new item
      addToInquiry(item, categoryName);
    }
  }

  function addToInquiry(item, categoryName) {
    // Dispatch custom event for InquiryBasket to listen to
    const event = new CustomEvent('add-to-inquiry', {
      detail: { name: item, category: categoryName }
    });
    window.dispatchEvent(event);

    // Mark as added for visual feedback
    addedItems.add(item);
    addedItems = addedItems; // Trigger reactivity
  }

  function removeFromInquiry(item) {
    // Dispatch custom event to remove from InquiryBasket
    const event = new CustomEvent('remove-from-inquiry', {
      detail: { name: item }
    });
    window.dispatchEvent(event);

    // Remove from local tracking
    addedItems.delete(item);
    addedItems = addedItems; // Trigger reactivity
    confirmingItem = null;
  }

  function keepItem() {
    confirmingItem = null;
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
          <div class="product-actions-wrapper">
            <div class="product-actions">
              <button
                class="btn btn-small btn-inquiry"
                class:added={addedItems.has(item)}
                on:click={() => handleInquiryClick(item, category.name)}
              >
                {addedItems.has(item) ? '✓ Added' : '+ Add to List'}
              </button>
              <a
                href={getWhatsAppLink(item)}
                target="_blank"
                rel="noopener noreferrer"
                class="btn btn-small btn-whatsapp"
              >
                WhatsApp
              </a>
            </div>

            <!-- Confirmation Popup -->
            {#if confirmingItem === item}
              <div class="confirm-popup">
                <div class="confirm-message">Already in your list!</div>
                <div class="confirm-actions">
                  <button class="confirm-btn remove-btn" on:click={() => removeFromInquiry(item)}>
                    Remove
                  </button>
                  <button class="confirm-btn keep-btn" on:click={keepItem}>
                    Keep
                  </button>
                </div>
              </div>
            {/if}
          </div>
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

  .product-actions {
    display: flex;
    gap: 0.5rem;
    flex-shrink: 0;
  }

  .btn-inquiry {
    background: #3498db;
    color: white;
    border: none;
    cursor: pointer;
  }

  .btn-inquiry:hover {
    background: #2980b9;
  }

  .btn-inquiry.added {
    background: #27ae60;
  }

  .btn-inquiry.added:hover {
    background: #219a52;
  }

  .product-actions-wrapper {
    position: relative;
  }

  .confirm-popup {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    background: white;
    border-radius: 10px;
    box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15);
    padding: 12px 16px;
    z-index: 50;
    min-width: 180px;
    animation: popupFadeIn 0.2s ease;
  }

  .confirm-popup::before {
    content: '';
    position: absolute;
    top: -6px;
    right: 20px;
    width: 12px;
    height: 12px;
    background: white;
    transform: rotate(45deg);
    box-shadow: -2px -2px 4px rgba(0, 0, 0, 0.05);
  }

  @keyframes popupFadeIn {
    from {
      opacity: 0;
      transform: translateY(-5px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .confirm-message {
    font-size: 0.9rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 10px;
    text-align: center;
  }

  .confirm-actions {
    display: flex;
    gap: 8px;
  }

  .confirm-btn {
    flex: 1;
    padding: 8px 12px;
    border: none;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .confirm-btn.remove-btn {
    background: #fee2e2;
    color: #dc2626;
  }

  .confirm-btn.remove-btn:hover {
    background: #fecaca;
  }

  .confirm-btn.keep-btn {
    background: #d1fae5;
    color: #059669;
  }

  .confirm-btn.keep-btn:hover {
    background: #a7f3d0;
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

    .product-actions {
      flex-direction: column;
      width: 100%;
    }

    .product-actions .btn {
      width: 100%;
      justify-content: center;
    }

    .product-actions-wrapper {
      width: 100%;
    }

    .confirm-popup {
      left: 50%;
      right: auto;
      transform: translateX(-50%);
      min-width: 200px;
    }

    .confirm-popup::before {
      left: 50%;
      right: auto;
      transform: translateX(-50%) rotate(45deg);
    }

    .showing-category {
      flex-direction: column;
      text-align: center;
    }
  }
</style>
