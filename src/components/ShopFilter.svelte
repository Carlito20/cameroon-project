<script>
  import { onMount } from 'svelte';

  // Props passed from Astro
  export let categories = [];
  export let whatsappNumber = "237670358551";

  // Helper functions for product items
  function isSubCategory(item) {
    return typeof item === 'object' && 'items' in item;
  }

  function getProductName(item) {
    return typeof item === 'string' ? item : item.name;
  }

  function getProductImage(item) {
    return typeof item === 'string' ? null : item.image;
  }

  function getProductImages(item) {
    if (typeof item === 'string') return null;
    return item.images || (item.image ? [item.image] : null);
  }

  function getProductPrice(item) {
    return typeof item === 'string' ? null : item.price;
  }

  function getProductQuantity(item) {
    return typeof item === 'string' ? null : item.quantity;
  }

  function formatPrice(price) {
    return price ? `${price.toLocaleString()} XAF` : null;
  }

  function isImagePath(icon) {
    return icon && icon.startsWith('/');
  }

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

    // Close dropdown when clicking outside
    const handleClickOutside = (event) => {
      if (isDropdownOpen && !event.target.closest('.custom-dropdown')) {
        isDropdownOpen = false;
      }
    };
    document.addEventListener('click', handleClickOutside);
    return () => document.removeEventListener('click', handleClickOutside);
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
    const name = getProductName(item);
    const message = encodeURIComponent(`Hi, I'm interested in ordering: ${name}\n\nPlease let me know the price and availability.`);
    return `https://wa.me/${whatsappNumber}?text=${message}`;
  }

  // Track which items have been added to inquiry with their quantities
  let addedItems = {};  // { productName: quantity }

  // Track selected quantities before adding (default 1)
  let selectedQuantities = {};

  // Track which item has the confirmation popup open
  let confirmingItem = null;

  // Get or initialize quantity for a product
  function getSelectedQty(productName) {
    return selectedQuantities[productName] || 1;
  }

  // Increment quantity
  function incrementQty(productName, maxQty) {
    const current = getSelectedQty(productName);
    const max = maxQty || 99;
    if (current < max) {
      selectedQuantities[productName] = current + 1;
      selectedQuantities = selectedQuantities; // Trigger reactivity
    }
  }

  // Decrement quantity
  function decrementQty(productName) {
    const current = getSelectedQty(productName);
    if (current > 1) {
      selectedQuantities[productName] = current - 1;
      selectedQuantities = selectedQuantities; // Trigger reactivity
    }
  }

  // Track which sub-categories are expanded
  let expandedSubCategories = new Set();

  // Image lightbox
  let lightboxImage = null;
  let lightboxAlt = '';

  // Custom dropdown state
  let isDropdownOpen = false;

  function toggleDropdown() {
    isDropdownOpen = !isDropdownOpen;
  }

  function selectCategory(categoryId) {
    selectedCategory = categoryId;
    filterCategories();
    isDropdownOpen = false;

    // Update URL without reload
    const url = new URL(window.location.href);
    if (selectedCategory === 'all') {
      url.searchParams.delete('category');
    } else {
      url.searchParams.set('category', selectedCategory);
    }
    window.history.pushState({}, '', url);
  }

  function getSelectedCategoryName() {
    if (selectedCategory === 'all') return 'All Categories';
    const cat = categories.find(c => c.id === selectedCategory);
    return cat ? cat.name : 'All Categories';
  }

  function getSelectedCategoryIcon() {
    if (selectedCategory === 'all') return '📦';
    const cat = categories.find(c => c.id === selectedCategory);
    return cat ? cat.icon : '📦';
  }

  function openLightbox(imageSrc, alt) {
    lightboxImage = imageSrc;
    lightboxAlt = alt;
  }

  function closeLightbox() {
    lightboxImage = null;
    lightboxAlt = '';
  }

  function toggleSubCategory(name) {
    if (expandedSubCategories.has(name)) {
      expandedSubCategories.delete(name);
    } else {
      expandedSubCategories.add(name);
    }
    expandedSubCategories = expandedSubCategories; // Trigger reactivity
  }

  function handleInquiryClick(item, categoryName, stockQty) {
    if (addedItems[item]) {
      // Item already added - show confirmation popup
      confirmingItem = confirmingItem === item ? null : item;
    } else {
      // Add new item with selected quantity
      addToInquiry(item, categoryName, stockQty);
    }
  }

  function addToInquiry(item, categoryName, stockQty) {
    const qty = getSelectedQty(item);

    // Dispatch custom event for InquiryBasket to listen to
    const event = new CustomEvent('add-to-inquiry', {
      detail: { name: item, category: categoryName, quantity: qty, maxStock: stockQty || 99 }
    });
    window.dispatchEvent(event);

    // Mark as added with quantity for visual feedback
    addedItems[item] = qty;
    addedItems = addedItems; // Trigger reactivity
  }

  function removeFromInquiry(item) {
    // Dispatch custom event to remove from InquiryBasket
    const event = new CustomEvent('remove-from-inquiry', {
      detail: { name: item }
    });
    window.dispatchEvent(event);

    // Remove from local tracking
    delete addedItems[item];
    addedItems = addedItems; // Trigger reactivity

    // Reset selected quantity
    delete selectedQuantities[item];
    selectedQuantities = selectedQuantities;

    confirmingItem = null;
  }

  function keepItem() {
    confirmingItem = null;
  }
</script>

<!-- Category Filter Dropdown -->
<div class="filter-container">
  <span class="filter-label">Filter by Category:</span>
  <div class="custom-dropdown">
    <button class="dropdown-trigger" on:click={toggleDropdown} type="button">
      <span class="dropdown-selected">
        {#if isImagePath(getSelectedCategoryIcon())}
          <img src={getSelectedCategoryIcon()} alt="" class="dropdown-option-icon" />
        {:else}
          <span class="dropdown-option-emoji">{getSelectedCategoryIcon()}</span>
        {/if}
        {getSelectedCategoryName()}
      </span>
      <span class="dropdown-arrow" class:open={isDropdownOpen}>▼</span>
    </button>
    {#if isDropdownOpen}
      <div class="dropdown-options">
        <button class="dropdown-option" class:selected={selectedCategory === 'all'} on:click={() => selectCategory('all')}>
          <span class="dropdown-option-emoji">📦</span>
          All Categories
        </button>
        {#each categories as category}
          <button class="dropdown-option" class:selected={selectedCategory === category.id} on:click={() => selectCategory(category.id)}>
            {#if isImagePath(category.icon)}
              <img src={category.icon} alt="" class="dropdown-option-icon" />
            {:else}
              <span class="dropdown-option-emoji">{category.icon}</span>
            {/if}
            {category.name}
          </button>
        {/each}
      </div>
    {/if}
  </div>
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
      {#if isImagePath(category.icon)}
        <img src={category.icon} alt={category.name} class="category-icon-image" />
      {:else}
        <span class="category-icon-large">{category.icon}</span>
      {/if}
      <div>
        <h2>{category.name}</h2>
        <p>{category.description}</p>
      </div>
    </div>
    <div class="products-grid">
      {#each category.items as item}
        {#if isSubCategory(item)}
          <!-- Sub-category -->
          <div class="subcategory-section" class:expanded={expandedSubCategories.has(item.name)}>
            <button class="subcategory-header" on:click={() => toggleSubCategory(item.name)}>
              {#if item.icon}<span class="subcategory-icon">{item.icon}</span>{/if}
              <h3>{item.name}</h3>
              <span class="subcategory-toggle">{expandedSubCategories.has(item.name) ? '−' : '+'}</span>
            </button>
            {#if expandedSubCategories.has(item.name)}
            <div class="subcategory-products">
              {#if item.items.length === 0}
                <p class="no-products">Products coming soon...</p>
              {/if}
              {#each item.items as subItem}
                <div class="product-item" class:has-image={getProductImages(subItem)}>
                  {#if getProductImages(subItem)}
                    <div class="product-images">
                      {#each getProductImages(subItem) as img}
                        <button class="product-image" on:click={() => openLightbox(img, getProductName(subItem))}>
                          <img src={img} alt={getProductName(subItem)} />
                        </button>
                      {/each}
                    </div>
                  {/if}
                  <div class="product-info">
                    <h4>{getProductName(subItem)}</h4>
                    {#if getProductPrice(subItem)}
                      <p class="product-price">{formatPrice(getProductPrice(subItem))}</p>
                    {/if}
                    {#if getProductQuantity(subItem) !== null && getProductQuantity(subItem) !== undefined}
                      <p class="product-quantity">
                        {#if getProductQuantity(subItem) > 0}
                          <span class="in-stock">In Stock: {getProductQuantity(subItem)}</span>
                        {:else}
                          <span class="out-of-stock">Out of Stock</span>
                        {/if}
                      </p>
                    {:else}
                      <p class="product-note">Available - Imported from USA/Canada</p>
                    {/if}
                  </div>
                  <div class="product-actions-wrapper">
                    {#if !addedItems[getProductName(subItem)]}
                      <div class="quantity-selector">
                        <button class="qty-btn" on:click={() => decrementQty(getProductName(subItem))} disabled={getSelectedQty(getProductName(subItem)) <= 1}>−</button>
                        <span class="qty-value">{getSelectedQty(getProductName(subItem))}</span>
                        <button class="qty-btn" on:click={() => incrementQty(getProductName(subItem), getProductQuantity(subItem))} disabled={getProductQuantity(subItem) && getSelectedQty(getProductName(subItem)) >= getProductQuantity(subItem)}>+</button>
                      </div>
                    {/if}
                    <div class="product-actions">
                      <button
                        class="btn btn-small btn-inquiry"
                        class:added={addedItems[getProductName(subItem)]}
                        on:click={() => handleInquiryClick(getProductName(subItem), item.name, getProductQuantity(subItem))}
                      >
                        {addedItems[getProductName(subItem)] ? `✓ Added (${addedItems[getProductName(subItem)]})` : '+ Add to List'}
                      </button>
                      <a
                        href={getWhatsAppLink(subItem)}
                        target="_blank"
                        rel="noopener noreferrer"
                        class="btn btn-small btn-whatsapp"
                      >
                        WhatsApp
                      </a>
                    </div>
                    {#if confirmingItem === getProductName(subItem)}
                      <div class="confirm-popup">
                        <div class="confirm-message">Already in your list ({addedItems[getProductName(subItem)]})</div>
                        <div class="confirm-actions">
                          <button class="confirm-btn remove-btn" on:click={() => removeFromInquiry(getProductName(subItem))}>
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
            {/if}
          </div>
        {:else}
          <!-- Regular product item -->
          <div class="product-item" class:has-image={getProductImages(item)}>
            {#if getProductImages(item)}
              <div class="product-images">
                {#each getProductImages(item) as img}
                  <button class="product-image" on:click={() => openLightbox(img, getProductName(item))}>
                    <img src={img} alt={getProductName(item)} />
                  </button>
                {/each}
              </div>
            {/if}
            <div class="product-info">
              <h4>{getProductName(item)}</h4>
              {#if getProductPrice(item)}
                <p class="product-price">{formatPrice(getProductPrice(item))}</p>
              {/if}
              {#if getProductQuantity(item) !== null && getProductQuantity(item) !== undefined}
                <p class="product-quantity">
                  {#if getProductQuantity(item) > 0}
                    <span class="in-stock">In Stock: {getProductQuantity(item)}</span>
                  {:else}
                    <span class="out-of-stock">Out of Stock</span>
                  {/if}
                </p>
              {:else}
                <p class="product-note">Available - Imported from USA/Canada</p>
              {/if}
            </div>
            <div class="product-actions-wrapper">
              {#if !addedItems[getProductName(item)]}
                <div class="quantity-selector">
                  <button class="qty-btn" on:click={() => decrementQty(getProductName(item))} disabled={getSelectedQty(getProductName(item)) <= 1}>−</button>
                  <span class="qty-value">{getSelectedQty(getProductName(item))}</span>
                  <button class="qty-btn" on:click={() => incrementQty(getProductName(item), getProductQuantity(item))} disabled={getProductQuantity(item) && getSelectedQty(getProductName(item)) >= getProductQuantity(item)}>+</button>
                </div>
              {/if}
              <div class="product-actions">
                <button
                  class="btn btn-small btn-inquiry"
                  class:added={addedItems[getProductName(item)]}
                  on:click={() => handleInquiryClick(getProductName(item), category.name, getProductQuantity(item))}
                >
                  {addedItems[getProductName(item)] ? `✓ Added (${addedItems[getProductName(item)]})` : '+ Add to List'}
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
              {#if confirmingItem === getProductName(item)}
                <div class="confirm-popup">
                  <div class="confirm-message">Already in your list ({addedItems[getProductName(item)]})</div>
                  <div class="confirm-actions">
                    <button class="confirm-btn remove-btn" on:click={() => removeFromInquiry(getProductName(item))}>
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
        {/if}
      {/each}
    </div>
  </div>
{/each}

<!-- Image Lightbox -->
{#if lightboxImage}
  <div class="lightbox-overlay" on:click={closeLightbox} role="dialog" aria-modal="true">
    <div class="lightbox-content" on:click|stopPropagation>
      <button class="lightbox-close" on:click={closeLightbox} aria-label="Close">×</button>
      <img src={lightboxImage} alt={lightboxAlt} />
      <p class="lightbox-caption">{lightboxAlt}</p>
    </div>
  </div>
{/if}

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

  /* Custom Dropdown Styles */
  .custom-dropdown {
    position: relative;
    min-width: 220px;
  }

  .dropdown-trigger {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 0.6rem 1rem;
    font-size: 0.95rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    background: white;
    color: #333;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .dropdown-trigger:hover {
    border-color: #3498db;
  }

  .dropdown-trigger:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.15);
  }

  .dropdown-selected {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .dropdown-arrow {
    font-size: 0.7rem;
    transition: transform 0.2s ease;
  }

  .dropdown-arrow.open {
    transform: rotate(180deg);
  }

  .dropdown-options {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 100;
    max-height: 300px;
    overflow-y: auto;
  }

  .dropdown-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.6rem 1rem;
    border: none;
    background: none;
    font-size: 0.95rem;
    color: #333;
    cursor: pointer;
    text-align: left;
    transition: background 0.15s ease;
  }

  .dropdown-option:hover {
    background: #f0f7ff;
  }

  .dropdown-option.selected {
    background: #e8f4fc;
    color: #3498db;
    font-weight: 600;
  }

  .dropdown-option:first-child {
    border-radius: 6px 6px 0 0;
  }

  .dropdown-option:last-child {
    border-radius: 0 0 6px 6px;
  }

  .dropdown-option-icon {
    width: 32px;
    height: 32px;
    object-fit: contain;
    border-radius: 4px;
  }

  .dropdown-option-emoji {
    font-size: 1.1rem;
    width: 24px;
    text-align: center;
  }

  /* Close dropdown when clicking outside */
  :global(body.dropdown-open) {
    overflow: hidden;
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

  .category-icon-image {
    width: 150px;
    height: 150px;
    object-fit: contain;
    border-radius: 12px;
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

  /* Sub-category styles */
  .subcategory-section {
    grid-column: 1 / -1;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 12px;
    padding: 1.5rem;
    margin: 0.5rem 0;
    border: 2px solid #e0e0e0;
  }

  .subcategory-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    margin: -1.5rem -1.5rem 0 -1.5rem;
    border: none;
    background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    color: white;
    cursor: pointer;
    width: calc(100% + 3rem);
    text-align: left;
    border-radius: 12px 12px 0 0;
    transition: all 0.2s ease;
  }

  .subcategory-header:hover {
    background: linear-gradient(135deg, #2980b9 0%, #1a5276 100%);
  }

  .subcategory-section:not(.expanded) .subcategory-header {
    border-radius: 12px;
    margin-bottom: -1.5rem;
  }

  .subcategory-icon {
    font-size: 1.5rem;
  }

  .subcategory-header h3 {
    margin: 0;
    color: white;
    font-size: 1.1rem;
    flex: 1;
  }

  .subcategory-toggle {
    font-size: 1.5rem;
    font-weight: bold;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
  }

  .subcategory-products {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #d0d0d0;
  }

  .no-products {
    grid-column: 1 / -1;
    text-align: center;
    color: #666;
    font-style: italic;
    padding: 1rem;
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

  .product-item.has-image {
    flex-direction: column;
    text-align: center;
  }

  .product-images {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 0.75rem;
  }

  .product-image {
    width: 100%;
    max-width: 120px;
  }

  .product-image {
    border: none;
    background: none;
    padding: 0;
    cursor: zoom-in;
    transition: transform 0.2s ease;
  }

  .product-image:hover {
    transform: scale(1.05);
  }

  .product-image img {
    width: 100%;
    height: auto;
    border-radius: 8px;
    object-fit: cover;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  }

  /* Lightbox styles */
  .lightbox-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1rem;
  }

  .lightbox-content {
    position: relative;
    max-width: 90vw;
    max-height: 90vh;
    text-align: center;
  }

  .lightbox-content img {
    max-width: 100%;
    max-height: 80vh;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
  }

  .lightbox-close {
    position: absolute;
    top: -40px;
    right: 0;
    background: none;
    border: none;
    color: white;
    font-size: 2.5rem;
    cursor: pointer;
    padding: 0;
    line-height: 1;
  }

  .lightbox-close:hover {
    color: #ccc;
  }

  .lightbox-caption {
    color: white;
    margin-top: 1rem;
    font-size: 1.1rem;
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

  .product-price {
    margin: 0.25rem 0;
    font-size: 1.1rem;
    font-weight: 700;
    color: #27ae60;
  }

  .product-quantity {
    margin: 0.25rem 0;
    font-size: 0.85rem;
  }

  /* Quantity Selector */
  .quantity-selector {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
  }

  .qty-btn {
    width: 32px;
    height: 32px;
    border: 2px solid #3498db;
    background: white;
    color: #3498db;
    font-size: 1.2rem;
    font-weight: bold;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
  }

  .qty-btn:hover:not(:disabled) {
    background: #3498db;
    color: white;
  }

  .qty-btn:disabled {
    border-color: #ccc;
    color: #ccc;
    cursor: not-allowed;
  }

  .qty-value {
    min-width: 40px;
    text-align: center;
    font-size: 1.1rem;
    font-weight: 600;
    color: #2c3e50;
  }

  .in-stock {
    color: #27ae60;
    font-weight: 600;
  }

  .out-of-stock {
    color: #e74c3c;
    font-weight: 600;
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

    .custom-dropdown {
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
