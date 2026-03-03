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
    if (typeof item === 'string') return null;
    // Return single image or first image from images array
    return item.image || (item.images && item.images.length > 0 ? item.images[0] : null);
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

  function getProductColors(item) {
    return typeof item === 'string' ? null : item.colors;
  }

  const colorNames = {
    '#ff69b4': 'Pink',
    '#2c2c2c': 'Black',
    '#d4af37': 'Gold',
    '#808080': 'Gray',
    '#e74c3c': 'Red',
    '#2980b9': 'Blue',
    '#800080': 'Purple',
    '#ffffff': 'White',
    '#ff8c00': 'Orange',
    '#f5f5dc': 'Beige',
    '#c0c0c0': 'Silver',
    '#008000': 'Green',
    '#00008b': 'Dark Blue',
    '#8b0000': 'Dark Red',
    '#ffd700': 'Yellow',
  };

  function getColorName(hex) {
    return colorNames[hex.toLowerCase()] || hex;
  }

  let selectedColors = {};

  function selectColor(productName, color) {
    if (selectedColors[productName] === color) {
      delete selectedColors[productName];
    } else {
      selectedColors[productName] = color;
    }
    selectedColors = selectedColors;
  }

  function formatPrice(price) {
    return price ? `${price.toLocaleString()} XAF` : null;
  }

  function isImagePath(icon) {
    return icon && icon.startsWith('/');
  }

  let selectedCategory = 'all';
  let filteredCategories = categories;

  // Search functionality
  let searchQuery = '';
  let searchResults = [];

  // Sort functionality
  let sortBy = 'default';

  // Search through all products
  function performSearch(query) {
    if (!query || query.trim().length < 2) {
      searchResults = [];
      return;
    }

    const lowerQuery = query.toLowerCase().trim();
    const results = [];

    categories.forEach(category => {
      category.items.forEach(item => {
        if (isSubCategory(item)) {
          // Search within subcategory items
          item.items.forEach(subItem => {
            const name = getProductName(subItem);
            if (name.toLowerCase().includes(lowerQuery)) {
              results.push({
                product: subItem,
                productName: name,
                categoryName: category.name,
                subCategoryName: item.name,
                price: getProductPrice(subItem),
                quantity: getProductQuantity(subItem),
                images: getProductImages(subItem),
                colors: getProductColors(subItem)
              });
            }
          });
        } else {
          // Search regular items
          const name = getProductName(item);
          if (name.toLowerCase().includes(lowerQuery)) {
            results.push({
              product: item,
              productName: name,
              categoryName: category.name,
              subCategoryName: null,
              price: getProductPrice(item),
              quantity: getProductQuantity(item),
              images: getProductImages(item),
              colors: getProductColors(item)
            });
          }
        }
      });
    });

    searchResults = results;
  }

  // Reactive search - triggers when searchQuery changes
  $: performSearch(searchQuery);

  // Sort helper for product items
  function sortProducts(items, sort) {
    if (sort === 'default') return items;
    return [...items].sort((a, b) => {
      const nameA = (a.productName || getProductName(a)).toLowerCase();
      const nameB = (b.productName || getProductName(b)).toLowerCase();
      const priceA = a.price || getProductPrice(a) || 0;
      const priceB = b.price || getProductPrice(b) || 0;
      if (sort === 'name-asc') return nameA.localeCompare(nameB);
      if (sort === 'name-desc') return nameB.localeCompare(nameA);
      if (sort === 'price-low') return priceA - priceB;
      if (sort === 'price-high') return priceB - priceA;
      return 0;
    });
  }

  // Reactive sorted search results
  $: sortedSearchResults = sortProducts(searchResults, sortBy);

  // Sort regular product items (for category/subcategory views)
  function sortItems(items, sort) {
    if (sort === 'default') return items;
    return [...items].sort((a, b) => {
      const nameA = getProductName(a).toLowerCase();
      const nameB = getProductName(b).toLowerCase();
      const priceA = getProductPrice(a) || 0;
      const priceB = getProductPrice(b) || 0;
      if (sort === 'name-asc') return nameA.localeCompare(nameB);
      if (sort === 'name-desc') return nameB.localeCompare(nameA);
      if (sort === 'price-low') return priceA - priceB;
      if (sort === 'price-high') return priceB - priceA;
      return 0;
    });
  }

  function clearSearch() {
    // Check if we came from cart and should return to previous page
    const cameFromCart = sessionStorage.getItem('cameFromCart');
    if (cameFromCart) {
      sessionStorage.removeItem('cameFromCart');
      window.history.back();
      return;
    }

    // Otherwise just clear the search
    searchQuery = '';
    searchResults = [];
    const url = new URL(window.location.href);
    url.searchParams.delete('search');
    window.history.pushState({}, '', url);
  }

  onMount(() => {
    // Read category and search from URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const categoryParam = urlParams.get('category');
    const searchParam = urlParams.get('search');

    if (categoryParam && categories.find(c => c.id === categoryParam)) {
      selectedCategory = categoryParam;
      filterCategories();
    }

    // If search parameter exists, populate the search field
    if (searchParam) {
      searchQuery = decodeURIComponent(searchParam);
    }

    // Listen for cart loaded from localStorage (on page refresh)
    const handleCartLoaded = (e) => {
      const { items } = e.detail;
      items.forEach(item => {
        addedItems[item.name] = item.quantity || 1;
      });
      addedItems = addedItems; // Trigger reactivity
    };
    window.addEventListener('cart-loaded', handleCartLoaded);

    // Listen for item removed from cart
    const handleItemRemoved = (e) => {
      const { name } = e.detail;
      delete addedItems[name];
      addedItems = addedItems;
    };
    window.addEventListener('item-removed-from-cart', handleItemRemoved);

    // Listen for cart quantity updates from cart sidebar
    const handleCartQtyUpdated = (e) => {
      const { name, quantity } = e.detail;
      if (addedItems[name]) {
        addedItems[name] = quantity;
        addedItems = addedItems; // Trigger reactivity
      }
    };
    window.addEventListener('cart-qty-updated', handleCartQtyUpdated);

    // Close dropdown when clicking outside
    const handleClickOutside = (event) => {
      if (isDropdownOpen && !event.target.closest('.custom-dropdown')) {
        isDropdownOpen = false;
      }
    };
    document.addEventListener('click', handleClickOutside);

    return () => {
      document.removeEventListener('click', handleClickOutside);
      window.removeEventListener('cart-loaded', handleCartLoaded);
      window.removeEventListener('cart-qty-updated', handleCartQtyUpdated);
      window.removeEventListener('item-removed-from-cart', handleItemRemoved);
    };
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

  // Reactive combined quantities - merges cart and pre-cart quantities for display
  $: displayQuantities = { ...selectedQuantities, ...addedItems };

  // Get quantity for display (reactive via displayQuantities)
  function getSelectedQty(productName) {
    return displayQuantities[productName] || 1;
  }

  // Increment quantity
  function incrementQty(productName, maxQty) {
    const current = getSelectedQty(productName);
    const max = maxQty || 99;
    if (current < max) {
      const newQty = current + 1;
      if (addedItems[productName]) {
        // Update cart quantity
        addedItems[productName] = newQty;
        addedItems = addedItems;
        // Notify cart to update
        window.dispatchEvent(new CustomEvent('update-cart-qty', {
          detail: { name: productName, quantity: newQty }
        }));
      } else {
        selectedQuantities[productName] = newQty;
        selectedQuantities = selectedQuantities;
      }
    }
  }

  // Decrement quantity
  function decrementQty(productName) {
    const current = getSelectedQty(productName);
    if (current > 1) {
      const newQty = current - 1;
      if (addedItems[productName]) {
        // Update cart quantity
        addedItems[productName] = newQty;
        addedItems = addedItems;
        // Notify cart to update
        window.dispatchEvent(new CustomEvent('update-cart-qty', {
          detail: { name: productName, quantity: newQty }
        }));
      } else {
        selectedQuantities[productName] = newQty;
        selectedQuantities = selectedQuantities;
      }
    }
  }

  // Track which sub-categories are expanded
  let expandedSubCategories = new Set();

  // Image lightbox with gallery support
  let lightboxImages = [];
  let lightboxIndex = 0;
  let lightboxAlt = '';
  let touchStartX = 0;
  let touchEndX = 0;
  let isZoomed = false;

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

  function openLightbox(images, alt, startIndex = 0) {
    lightboxImages = Array.isArray(images) ? images : [images];
    lightboxIndex = startIndex;
    lightboxAlt = alt;
  }

  function closeLightbox() {
    lightboxImages = [];
    lightboxIndex = 0;
    lightboxAlt = '';
    isZoomed = false;
  }

  function toggleZoom() {
    isZoomed = !isZoomed;
  }

  function nextImage() {
    isZoomed = false;
    if (lightboxIndex < lightboxImages.length - 1) {
      lightboxIndex++;
    } else {
      lightboxIndex = 0;
    }
  }

  function prevImage() {
    isZoomed = false;
    if (lightboxIndex > 0) {
      lightboxIndex--;
    } else {
      lightboxIndex = lightboxImages.length - 1;
    }
  }

  function handleTouchStart(e) {
    touchStartX = e.changedTouches[0].screenX;
  }

  function handleTouchEnd(e) {
    touchEndX = e.changedTouches[0].screenX;
    handleSwipe();
  }

  function handleSwipe() {
    const swipeThreshold = 50;
    const diff = touchStartX - touchEndX;
    if (Math.abs(diff) > swipeThreshold) {
      if (diff > 0) {
        nextImage();
      } else {
        prevImage();
      }
    }
  }

  function toggleSubCategory(name) {
    if (expandedSubCategories.has(name)) {
      expandedSubCategories.delete(name);
    } else {
      expandedSubCategories.add(name);
    }
    expandedSubCategories = expandedSubCategories; // Trigger reactivity
  }

  function handleInquiryClick(productItem, categoryName, stockQty, itemPrice) {
    addToInquiry(productItem, categoryName, stockQty, itemPrice);
  }

  function addToInquiry(productItem, categoryName, stockQty, itemPrice) {
    const itemName = getProductName(productItem);
    const qty = getSelectedQty(itemName);
    const itemImage = getProductImage(productItem);

    // Dispatch custom event for InquiryBasket to listen to
    const event = new CustomEvent('add-to-inquiry', {
      detail: {
        name: itemName,
        category: categoryName,
        quantity: qty,
        maxStock: stockQty || 99,
        price: itemPrice || 0,
        image: itemImage
      }
    });
    window.dispatchEvent(event);

    // Mark as added with quantity for visual feedback
    addedItems[itemName] = qty;
    addedItems = addedItems; // Trigger reactivity
  }

  function removeFromInquiry(productItem) {
    const itemName = getProductName(productItem);
    // Dispatch custom event to remove from InquiryBasket
    const event = new CustomEvent('remove-from-inquiry', {
      detail: { name: itemName }
    });
    window.dispatchEvent(event);

    // Remove from local tracking
    delete addedItems[itemName];
    addedItems = addedItems; // Trigger reactivity

    // Reset selected quantity
    delete selectedQuantities[itemName];
    selectedQuantities = selectedQuantities;

    confirmingItem = null;
  }

  function keepItem() {
    confirmingItem = null;
  }
</script>

<!-- Category Filter and Search -->
<div class="filter-container">
  <div class="filter-group">
    <span class="filter-label">Filter by Category:</span>
    <div class="custom-dropdown">
      <button class="dropdown-trigger" on:click={toggleDropdown} type="button">
        <span class="dropdown-selected">
          {getSelectedCategoryName()}
        </span>
        <span class="dropdown-arrow" class:open={isDropdownOpen}>▼</span>
      </button>
      {#if isDropdownOpen}
        <div class="dropdown-options">
          <button class="dropdown-option" class:selected={selectedCategory === 'all'} on:click={() => selectCategory('all')}>
            All Categories
          </button>
          {#each categories as category}
            <button class="dropdown-option" class:selected={selectedCategory === category.id} on:click={() => selectCategory(category.id)}>
              {category.name}
            </button>
          {/each}
        </div>
      {/if}
    </div>
  </div>
  <div class="search-group">
    <div class="search-input-wrapper">
      <span class="search-icon">🔍</span>
      <input
        type="text"
        class="search-input"
        placeholder="Search products..."
        bind:value={searchQuery}
      />
      {#if searchQuery}
        <button class="search-clear" on:click={clearSearch} aria-label="Clear search">✕</button>
      {/if}
    </div>
    {#if expandedSubCategories.size > 0 || (searchQuery && searchQuery.trim().length >= 2)}
      <div class="sort-wrapper">
        <label class="sort-label" for="sort-select">Sort by:</label>
        <select id="sort-select" class="sort-select" bind:value={sortBy}>
          <option value="default">Default</option>
          <option value="name-asc">Name (A-Z)</option>
          <option value="name-desc">Name (Z-A)</option>
          <option value="price-low">Price (Low-High)</option>
          <option value="price-high">Price (High-Low)</option>
        </select>
      </div>
    {/if}
  </div>
</div>

<!-- Showing indicator -->
{#if searchQuery && searchQuery.trim().length >= 2}
  <div class="showing-category">
    {#if searchResults.length > 0}
      Found <strong>{searchResults.length}</strong> {searchResults.length === 1 ? 'product' : 'products'} for "<strong>{searchQuery}</strong>"
    {:else}
      No products found for "<strong>{searchQuery}</strong>"
    {/if}
    <button class="clear-filter" on:click={clearSearch}>
      ✕ Clear Search
    </button>
  </div>
{:else if selectedCategory !== 'all'}
  <div class="showing-category">
    Showing: <strong>{filteredCategories[0]?.name || 'All'}</strong>
    <button class="clear-filter" on:click={() => { selectedCategory = 'all'; filterCategories(); window.history.pushState({}, '', '/shop'); }}>
      ✕ Show All
    </button>
  </div>
{/if}

<!-- Search Results -->
{#if searchQuery && searchQuery.trim().length >= 2}
  <div class="search-results-section">
    {#if sortedSearchResults.length === 0}
      <div class="no-results">
        <span class="no-results-icon">🔍</span>
        <p>No products found matching "<strong>{searchQuery}</strong>"</p>
        <p class="no-results-hint">Try a different search term or browse categories</p>
      </div>
    {:else}
      <div class="products-grid">
        {#each sortedSearchResults as result (result.productName)}
          <div class="product-item" class:has-image={result.images}>
            {#if result.images}
              <div class="product-images">
                <button class="product-image" on:click={() => openLightbox(result.images, result.productName)}>
                  <img src={result.images[0]} alt={result.productName} />
                                  </button>
              </div>
            {/if}
            <div class="product-info">
              <h4>{result.productName}</h4>
              <p class="product-category-tag">
                {result.categoryName}{result.subCategoryName ? ` > ${result.subCategoryName}` : ''}
              </p>
              {#if result.price}
                <p class="product-price">{formatPrice(result.price)}</p>
              {/if}
              {#if result.quantity !== null && result.quantity !== undefined}
                <p class="product-quantity">
                  {#if result.quantity > 0}
                    <span class="in-stock">In Stock: {result.quantity}</span>
                    {#if result.colors}
                      <span class="color-dots">
                        {#each result.colors as color}
                          <button class="color-dot" style="background: {color};" title={getColorName(color)} class:selected={selectedColors[result.productName] === color} on:click|stopPropagation={() => selectColor(result.productName, color)}></button>
                        {/each}
                        {#if selectedColors[result.productName]}
                          <span class="color-label">{getColorName(selectedColors[result.productName])}</span>
                        {/if}
                      </span>
                    {/if}
                  {:else}
                    <span class="out-of-stock">Out of Stock</span>
                  {/if}
                </p>
              {:else}
                <p class="product-note">Available - Imported from USA/Canada</p>
              {/if}
            </div>
            <div class="product-actions-wrapper">
              <div class="quantity-selector">
                <button class="qty-btn" on:click={() => decrementQty(result.productName)} disabled={(displayQuantities[result.productName] || 1) <= 1}>−</button>
                <span class="qty-value">{displayQuantities[result.productName] || 1}</span>
                <button class="qty-btn" on:click={() => incrementQty(result.productName, result.quantity)} disabled={result.quantity && (displayQuantities[result.productName] || 1) >= result.quantity}>+</button>
              </div>
              <div class="product-actions">
                <button
                  class="btn btn-small btn-inquiry"
                  class:added={addedItems[result.productName]}
                  on:click={() => handleInquiryClick(result.product, result.subCategoryName || result.categoryName, result.quantity, result.price)}
                >
                  {addedItems[result.productName] ? `✓ Added (${addedItems[result.productName]})` : 'Add to Cart'}
                </button>
                <a
                  href={getWhatsAppLink(result.product)}
                  target="_blank"
                  rel="noopener noreferrer"
                  class="btn btn-small btn-whatsapp"
                >
                  WhatsApp
                </a>
              </div>
            </div>
          </div>
        {/each}
      </div>
    {/if}
  </div>
{:else}
<!-- Categories Grid -->
{#each filteredCategories as category (category.id)}
  <div class="category-section" id={category.id}>
    <div class="category-header">
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
              <h3>{item.name}</h3>
              <span class="subcategory-toggle">{expandedSubCategories.has(item.name) ? '−' : '+'}</span>
            </button>
            {#if expandedSubCategories.has(item.name)}
            <div class="subcategory-products">
              {#if item.items.length === 0}
                <p class="no-products">Products coming soon...</p>
              {/if}
              {#each sortItems(item.items, sortBy) as subItem}
                {#if isSubCategory(subItem)}
                  <!-- Nested sub-category (e.g., Men/Women under Deodorants) -->
                  <div class="nested-subcategory" class:expanded={expandedSubCategories.has(subItem.name)}>
                    <button class="nested-subcategory-header" on:click={() => toggleSubCategory(subItem.name)}>
                      <h4>{subItem.name}</h4>
                      <span class="subcategory-toggle">{expandedSubCategories.has(subItem.name) ? '−' : '+'}</span>
                    </button>
                    {#if expandedSubCategories.has(subItem.name)}
                      <div class="nested-subcategory-products">
                        {#each sortItems(subItem.items, sortBy) as nestedProduct}
                          <div class="product-item" class:has-image={getProductImages(nestedProduct)}>
                            {#if getProductImages(nestedProduct)}
                              <div class="product-images">
                                <button class="product-image" on:click={() => openLightbox(getProductImages(nestedProduct), getProductName(nestedProduct))}>
                                  <img src={getProductImages(nestedProduct)[0]} alt={getProductName(nestedProduct)} />
                                </button>
                              </div>
                            {/if}
                            <div class="product-info">
                              <h4>{getProductName(nestedProduct)}</h4>
                              {#if getProductPrice(nestedProduct)}
                                <p class="product-price">{formatPrice(getProductPrice(nestedProduct))}</p>
                              {/if}
                              {#if getProductQuantity(nestedProduct) !== null && getProductQuantity(nestedProduct) !== undefined}
                                <p class="product-quantity">
                                  {#if getProductQuantity(nestedProduct) > 0}
                                    <span class="in-stock">In Stock: {getProductQuantity(nestedProduct)}</span>
                                    {#if getProductColors(nestedProduct)}
                                      <span class="color-dots">
                                        {#each getProductColors(nestedProduct) as color}
                                          <button class="color-dot" style="background: {color};" title={getColorName(color)} class:selected={selectedColors[getProductName(nestedProduct)] === color} on:click|stopPropagation={() => selectColor(getProductName(nestedProduct), color)}></button>
                                        {/each}
                                        {#if selectedColors[getProductName(nestedProduct)]}
                                          <span class="color-label">{getColorName(selectedColors[getProductName(nestedProduct)])}</span>
                                        {/if}
                                      </span>
                                    {/if}
                                  {:else}
                                    <span class="out-of-stock">Out of Stock</span>
                                  {/if}
                                </p>
                              {:else}
                                <p class="product-note">Available - Imported from USA/Canada</p>
                              {/if}
                            </div>
                            <div class="product-actions-wrapper">
                              <div class="quantity-selector">
                                <button class="qty-btn" on:click={() => decrementQty(getProductName(nestedProduct))} disabled={(displayQuantities[getProductName(nestedProduct)] || 1) <= 1}>−</button>
                                <span class="qty-value">{displayQuantities[getProductName(nestedProduct)] || 1}</span>
                                <button class="qty-btn" on:click={() => incrementQty(getProductName(nestedProduct), getProductQuantity(nestedProduct))} disabled={getProductQuantity(nestedProduct) && (displayQuantities[getProductName(nestedProduct)] || 1) >= getProductQuantity(nestedProduct)}>+</button>
                              </div>
                              <div class="product-actions">
                                <button
                                  class="btn btn-small btn-inquiry"
                                  class:added={addedItems[getProductName(nestedProduct)]}
                                  on:click={() => handleInquiryClick(nestedProduct, subItem.name, getProductQuantity(nestedProduct), getProductPrice(nestedProduct))}
                                >
                                  {addedItems[getProductName(nestedProduct)] ? `✓ Added (${addedItems[getProductName(nestedProduct)]})` : 'Add to Cart'}
                                </button>
                                <a
                                  href={getWhatsAppLink(nestedProduct)}
                                  target="_blank"
                                  rel="noopener noreferrer"
                                  class="btn btn-small btn-whatsapp"
                                >
                                  WhatsApp
                                </a>
                              </div>
                            </div>
                          </div>
                        {/each}
                      </div>
                    {/if}
                  </div>
                {:else}
                <div class="product-item" class:has-image={getProductImages(subItem)}>
                  {#if getProductImages(subItem)}
                    <div class="product-images">
                      <button class="product-image" on:click={() => openLightbox(getProductImages(subItem), getProductName(subItem))}>
                        <img src={getProductImages(subItem)[0]} alt={getProductName(subItem)} />
                                              </button>
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
                          {#if getProductColors(subItem)}
                            <span class="color-dots">
                              {#each getProductColors(subItem) as color}
                                <button class="color-dot" style="background: {color};" title={getColorName(color)} class:selected={selectedColors[getProductName(subItem)] === color} on:click|stopPropagation={() => selectColor(getProductName(subItem), color)}></button>
                              {/each}
                              {#if selectedColors[getProductName(subItem)]}
                                <span class="color-label">{getColorName(selectedColors[getProductName(subItem)])}</span>
                              {/if}
                            </span>
                          {/if}
                        {:else}
                          <span class="out-of-stock">Out of Stock</span>
                        {/if}
                      </p>
                    {:else}
                      <p class="product-note">Available - Imported from USA/Canada</p>
                    {/if}
                  </div>
                  <div class="product-actions-wrapper">
                    <div class="quantity-selector">
                      <button class="qty-btn" on:click={() => decrementQty(getProductName(subItem))} disabled={(displayQuantities[getProductName(subItem)] || 1) <= 1}>−</button>
                      <span class="qty-value">{displayQuantities[getProductName(subItem)] || 1}</span>
                      <button class="qty-btn" on:click={() => incrementQty(getProductName(subItem), getProductQuantity(subItem))} disabled={getProductQuantity(subItem) && (displayQuantities[getProductName(subItem)] || 1) >= getProductQuantity(subItem)}>+</button>
                    </div>
                    <div class="product-actions">
                      <button
                        class="btn btn-small btn-inquiry"
                        class:added={addedItems[getProductName(subItem)]}
                        on:click={() => handleInquiryClick(subItem, item.name, getProductQuantity(subItem), getProductPrice(subItem))}
                      >
                        {addedItems[getProductName(subItem)] ? `✓ Added (${addedItems[getProductName(subItem)]})` : 'Add to Cart'}
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
                  </div>
                </div>
                {/if}
              {/each}
            </div>
            {/if}
          </div>
        {:else}
          <!-- Regular product item -->
          <div class="product-item" class:has-image={getProductImages(item)}>
            {#if getProductImages(item)}
              <div class="product-images">
                <button class="product-image" on:click={() => openLightbox(getProductImages(item), getProductName(item))}>
                  <img src={getProductImages(item)[0]} alt={getProductName(item)} />
                                  </button>
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
                    {#if getProductColors(item)}
                      <span class="color-dots">
                        {#each getProductColors(item) as color}
                          <button class="color-dot" style="background: {color};" title={getColorName(color)} class:selected={selectedColors[getProductName(item)] === color} on:click|stopPropagation={() => selectColor(getProductName(item), color)}></button>
                        {/each}
                        {#if selectedColors[getProductName(item)]}
                          <span class="color-label">{getColorName(selectedColors[getProductName(item)])}</span>
                        {/if}
                      </span>
                    {/if}
                  {:else}
                    <span class="out-of-stock">Out of Stock</span>
                  {/if}
                </p>
              {:else}
                <p class="product-note">Available - Imported from USA/Canada</p>
              {/if}
            </div>
            <div class="product-actions-wrapper">
              <div class="quantity-selector">
                <button class="qty-btn" on:click={() => decrementQty(getProductName(item))} disabled={(displayQuantities[getProductName(item)] || 1) <= 1}>−</button>
                <span class="qty-value">{displayQuantities[getProductName(item)] || 1}</span>
                <button class="qty-btn" on:click={() => incrementQty(getProductName(item), getProductQuantity(item))} disabled={getProductQuantity(item) && (displayQuantities[getProductName(item)] || 1) >= getProductQuantity(item)}>+</button>
              </div>
              <div class="product-actions">
                <button
                  class="btn btn-small btn-inquiry"
                  class:added={addedItems[getProductName(item)]}
                  on:click={() => handleInquiryClick(item, category.name, getProductQuantity(item), getProductPrice(item))}
                >
                  {addedItems[getProductName(item)] ? `✓ Added (${addedItems[getProductName(item)]})` : 'Add to Cart'}
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

            </div>
          </div>
        {/if}
      {/each}
    </div>
  </div>
{/each}
{/if}

<!-- Image Lightbox with Gallery -->
{#if lightboxImages.length > 0}
  <div class="lightbox-overlay" class:zoomed={isZoomed} on:click={closeLightbox} role="dialog" aria-modal="true">
    <div class="lightbox-content" class:zoomed={isZoomed} on:click|stopPropagation on:touchstart={handleTouchStart} on:touchend={handleTouchEnd}>
      <button class="lightbox-close" on:click={closeLightbox} aria-label="Close">×</button>
      {#if lightboxImages.length > 1 && !isZoomed}
        <button class="lightbox-nav lightbox-prev" on:click={prevImage} aria-label="Previous image">‹</button>
      {/if}
      <div class="lightbox-image-container" class:zoomed={isZoomed}>
        <img
          src={lightboxImages[lightboxIndex]}
          alt={lightboxAlt}
          class:zoomed={isZoomed}
          on:click={toggleZoom}
        />
      </div>
      {#if lightboxImages.length > 1 && !isZoomed}
        <button class="lightbox-nav lightbox-next" on:click={nextImage} aria-label="Next image">›</button>
        <div class="lightbox-dots">
          {#each lightboxImages as _, i}
            <button class="lightbox-dot" class:active={i === lightboxIndex} on:click={() => lightboxIndex = i} aria-label="Go to image {i + 1}"></button>
          {/each}
        </div>
      {/if}
      {#if !isZoomed}
        <p class="lightbox-caption">{lightboxAlt} {lightboxImages.length > 1 ? `(${lightboxIndex + 1}/${lightboxImages.length})` : ''}</p>
      {/if}
      <button class="lightbox-zoom-hint" on:click={toggleZoom}>
        {isZoomed ? '🔍- Tap to zoom out' : '🔍+ Tap image to zoom'}
      </button>
    </div>
  </div>
{/if}

<style>
  :global(button:focus),
  :global(button:focus-visible),
  :global(a:focus),
  :global(a:focus-visible),
  :global(*:focus),
  :global(*:focus-visible) {
    outline: none !important;
    box-shadow: none !important;
  }

  .filter-container {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    flex-wrap: wrap;
    position: relative;
  }

  @media (min-width: 769px) {
    .filter-container {
      justify-content: space-between;
    }
  }

  .filter-group {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
  }

  .filter-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: #2c3e50;
  }

  /* Search Styles */
  .search-group {
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }

  
  .search-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    width: 280px;
  }

  .search-icon {
    position: absolute;
    left: 12px;
    font-size: 1rem;
    pointer-events: none;
  }

  .search-input {
    width: 100%;
    padding: 0.6rem 2.5rem 0.6rem 2.5rem;
    font-size: 0.95rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    background: white;
    color: #333;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    -webkit-appearance: none;
    appearance: none;
  }

  .search-input:hover {
    border-color: #111111;
  }

  .search-input:focus {
    outline: none;
    border-color: #111111;
    box-shadow: 0 0 0 3px rgba(240, 165, 0, 0.2);
  }

  .search-input::placeholder {
    color: #999;
  }

  .search-clear {
    position: absolute;
    right: 8px;
    background: #e0e0e0;
    border: none;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    color: #666;
    transition: all 0.2s ease;
    /* Extend touch target without changing visual layout */
    position: absolute;
  }

  /* Invisible touch-target extension for search-clear */
  .search-clear::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    min-width: 44px;
    min-height: 44px;
  }

  .search-clear:hover {
    background: #ccc;
    color: #333;
  }

  /* Search Results */
  .search-results-section {
    margin-bottom: 2rem;
  }

  .no-results {
    text-align: center;
    padding: 3rem 1rem;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
  }

  .no-results-icon {
    font-size: 3rem;
    display: block;
    margin-bottom: 1rem;
    opacity: 0.5;
  }

  .no-results p {
    margin: 0.5rem 0;
    color: #2c3e50;
  }

  .no-results-hint {
    color: #666;
    font-size: 0.9rem;
  }

  .product-category-tag {
    font-size: 0.75rem;
    color: #666;
    background: #e9ecef;
    padding: 2px 8px;
    border-radius: 10px;
    display: inline-block;
    margin-bottom: 0.25rem;
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
    border-color: #111111;
  }

  .dropdown-trigger:focus {
    outline: none;
    border-color: #111111;
    box-shadow: 0 0 0 3px rgba(240, 165, 0, 0.2);
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
    -webkit-overflow-scrolling: touch;
    overscroll-behavior: contain;
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

  @media (min-width: 769px) {
    .dropdown-option {
      justify-content: center;
      text-align: center;
    }
  }

  .dropdown-option:hover {
    background: #f0f7ff;
  }

  .dropdown-option.selected {
    background: #fff8e6;
    color: #f0a500;
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
    background: #111111;
    color: white;
    border: none;
    padding: 0.35rem 0.75rem;
    border-radius: 5px;
    font-size: 0.85rem;
    cursor: pointer;
    transition: background 0.2s;
  }

  .clear-filter:hover,
  .clear-filter:active {
    background: #f0a500;
    color: #111111;
  }

  /* Category section styles */
  .category-section {
    margin-bottom: 0.625rem;
    padding: 0.625rem;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    overflow: hidden;
  }

  .category-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    padding-bottom: 0.375rem;
    border-bottom: 2px solid #e0e0e0;
  }

  .category-icon-large {
    font-size: 1.5rem;
  }

  .category-icon-image {
    width: 56px;
    height: 56px;
    object-fit: contain;
    border-radius: 6px;
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
    gap: 0.5rem;
  }

  /* Sub-category styles */
  .subcategory-section {
    grid-column: 1 / -1;
    background: white;
    border-radius: 10px;
    padding: 0;
    margin: 0.35rem 0;
    border: 1px solid #d0d0d0;
    box-sizing: border-box;
  }

  /* Collapsed: clip content so border-radius looks right */
  .subcategory-section:not(.expanded) {
    overflow: hidden;
  }

  /* Expanded: must be visible so sticky header can escape the section */
  .subcategory-section.expanded {
    overflow: visible;
  }

  .subcategory-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.55rem 1rem;
    border: none;
    background: #111111;
    color: white;
    cursor: pointer;
    width: 100%;
    text-align: left;
    border-radius: 0;
    transition: all 0.2s ease;
  }

  .subcategory-header:hover,
  .subcategory-header:active,
  .subcategory-header:focus {
    background: #f0a500;
    color: #111111;
    outline: none;
  }

  .subcategory-section:not(.expanded) .subcategory-header {
    border-radius: 0;
  }

  .subcategory-section.expanded .subcategory-header {
    background: #f0a500;
    color: #111111;
    border-radius: 9px 9px 0 0;
    position: sticky;
    /* Below desktop navbar (~112px) + safe area for iPhone notch */
    top: calc(112px + env(safe-area-inset-top, 0px));
    z-index: 50;
    -webkit-transform: translateZ(0);
    transform: translateZ(0);
    will-change: transform;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.18);
  }

  .subcategory-section.expanded .subcategory-header h3 {
    color: #111111;
  }

  /* Round the bottom of the products container since section overflow is now visible */
  .subcategory-section.expanded .subcategory-products {
    border-radius: 0 0 9px 9px;
    border: 1px solid #d0d0d0;
    border-top: none;
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
    gap: 0.5rem;
    padding: 0.75rem;
    border-top: 1px solid #e0e0e0;
  }

  /* Nested sub-category styles (e.g., Men/Women under Deodorants) */
  .nested-subcategory {
    grid-column: 1 / -1;
    background: white;
    border-radius: 6px;
    padding: 0.5rem;
    margin: 0.25rem 0;
    border: 1px solid #e0e0e0;
  }

  .nested-subcategory-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.5rem 0.75rem;
    background: #2a2a2a;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    width: 100%;
    text-align: left;
    transition: all 0.2s ease;
  }

  .nested-subcategory-header:hover,
  .nested-subcategory-header:active,
  .nested-subcategory-header:focus {
    background: #f0a500;
    color: #111111;
    outline: none;
  }

  .nested-subcategory-header h4 {
    margin: 0;
    color: white;
    font-size: 1rem;
  }

  .nested-subcategory-products {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 0.5rem;
    margin-top: 0.5rem;
    padding-top: 0.5rem;
    border-top: 1px solid #e9ecef;
  }

  .no-products {
    grid-column: 1 / -1;
    text-align: center;
    color: #666;
    font-style: italic;
    padding: 1rem;
  }

  .product-item {
    background: transparent;
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
    border-color: transparent;
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
    position: relative;
    border: none;
    background: none;
    padding: 0;
    border-radius: 0;
    cursor: zoom-in;
    transition: transform 0.2s ease;
    display: inline-block;
    outline: none;
  }

  .product-image:hover {
    transform: scale(1.05);
  }

  .product-image img {
    width: 100%;
    height: auto;
    border-radius: 0;
    object-fit: contain;
    display: block;
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
    padding: env(safe-area-inset-top, 1rem) env(safe-area-inset-right, 1rem) env(safe-area-inset-bottom, 1rem) env(safe-area-inset-left, 1rem);
    -webkit-transform: translateZ(0);
    transform: translateZ(0);
    will-change: transform;
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
    top: -44px;
    right: 0;
    background: rgba(0, 0, 0, 0.6);
    border: none;
    color: white;
    font-size: 2.5rem;
    cursor: pointer;
    padding: 0 8px;
    line-height: 1;
    border-radius: 8px;
    min-width: 44px;
    min-height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .lightbox-close:hover {
    color: #ccc;
  }

  .lightbox-caption {
    color: white;
    margin-top: 1rem;
    font-size: 1.1rem;
  }

  .lightbox-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(0, 0, 0, 0.6);
    border: none;
    color: white;
    font-size: 3rem;
    cursor: pointer;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    transition: background 0.2s ease;
    z-index: 10;
  }

  .lightbox-nav:hover {
    background: rgba(0, 0, 0, 0.85);
  }

  .lightbox-prev {
    left: -60px;
  }

  .lightbox-next {
    right: -60px;
  }

  .lightbox-dots {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 1rem;
  }

  .lightbox-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.4);
    border: none;
    cursor: pointer;
    padding: 0;
    transition: background 0.2s ease;
    position: relative;
  }

  /* Extend touch target to 44px without changing visual dot size */
  .lightbox-dot::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    min-width: 44px;
    min-height: 44px;
  }

  .lightbox-dot:hover,
  .lightbox-dot.active {
    background: white;
  }

  .lightbox-image-container {
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .lightbox-image-container.zoomed {
    overflow: auto;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior: contain;
    max-width: 95vw;
    max-height: 85vh;
    cursor: zoom-out;
    touch-action: pan-x pan-y;
  }

  .lightbox-content img {
    cursor: zoom-in;
    transition: transform 0.3s ease;
  }

  .lightbox-content img.zoomed {
    transform: scale(2);
    cursor: zoom-out;
  }

  .lightbox-zoom-hint {
    position: absolute;
    bottom: -40px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0, 0, 0, 0.6);
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    cursor: pointer;
    white-space: nowrap;
  }

  .lightbox-zoom-hint:hover {
    background: rgba(0, 0, 0, 0.8);
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
    color: #f0a500;
  }

  .product-quantity {
    margin: 0.25rem 0;
    font-size: 0.85rem;
    color: #f0a500;
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
    min-width: 44px;
    min-height: 44px;
    border: 2px solid #111111;
    background: white;
    color: #111111;
    font-size: 1.2rem;
    font-weight: bold;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    user-select: none;
    -webkit-user-select: none;
  }

  .qty-btn:hover:not(:disabled),
  .qty-btn:active:not(:disabled) {
    background: #f0a500;
    border-color: #f0a500;
    color: #111111;
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
    color: #f0a500;
    font-weight: 600;
  }

  .color-dots {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    margin-left: 8px;
    vertical-align: middle;
  }

  .color-dot {
    display: inline-block;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 2px solid #e0e0e0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
    cursor: pointer;
    padding: 0;
    flex-shrink: 0;
    transition: transform 0.15s ease, border-color 0.15s ease;
  }

  .color-dot:hover {
    transform: scale(1.2);
    border-color: #999;
  }

  .color-dot.selected {
    border: 2.5px solid #2c3e50;
    box-shadow: 0 0 0 2px rgba(44, 62, 80, 0.35);
    transform: scale(1.15);
  }

  .color-label {
    font-size: 0.78rem;
    font-weight: 600;
    color: #2c3e50;
    background: #f0f4f8;
    border-radius: 4px;
    padding: 1px 6px;
    margin-left: 2px;
    white-space: nowrap;
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
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    user-select: none;
    -webkit-user-select: none;
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
    background: #111111;
    color: white;
    border: none;
    cursor: pointer;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
  }

  .btn-inquiry:hover,
  .btn-inquiry:active {
    background: #f0a500;
    color: #111111;
  }

  .btn-inquiry.added {
    background: #f0a500;
    color: #111111;
  }

  .btn-inquiry.added:hover,
  .btn-inquiry.added:active {
    background: #d4940a;
    color: #111111;
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

  /* Sort Styles */
  .sort-wrapper {
    display: flex;
    align-items: center;
    gap: 0.4rem;
  }

  .sort-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #2c3e50;
    white-space: nowrap;
  }

  .sort-select {
    padding: 0.6rem 0.75rem;
    font-size: 0.9rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    background: white;
    color: #333;
    cursor: pointer;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    -webkit-appearance: none;
    appearance: none;
  }

  .sort-select:hover {
    border-color: #111111;
  }

  .sort-select:focus {
    outline: none;
    border-color: #111111;
    box-shadow: 0 0 0 3px rgba(240, 165, 0, 0.2);
  }

  @media (max-width: 768px) {
    .filter-container {
      flex-direction: column;
      align-items: stretch;
      padding: 0.75rem;
      gap: 0.75rem;
    }

    .filter-group {
      flex-direction: column;
      width: 100%;
    }

    .filter-label {
      justify-content: center;
    }

    .custom-dropdown {
      width: 100%;
    }

    .search-group {
      width: 100%;
      flex-direction: column;
    }

    .sort-wrapper {
      width: 100%;
      justify-content: center;
    }

    .sort-select {
      flex: 1;
      min-height: 48px;
      font-size: 1rem;
    }

    .search-input-wrapper {
      max-width: none;
      width: 100%;
    }

    .search-input {
      padding: 0.75rem 2.5rem;
      min-height: 48px;
      /* Explicitly 16px to prevent iOS auto-zoom on focus */
      font-size: 16px;
    }

    .search-clear {
      width: 28px;
      height: 28px;
      font-size: 0.85rem;
    }

    .dropdown-trigger {
      padding: 0.75rem 1rem;
      min-height: 48px;
      font-size: 1rem;
    }

    .dropdown-option {
      padding: 0.75rem 1rem;
      min-height: 48px;
      font-size: 1rem;
    }

    .no-results {
      padding: 2rem 1rem;
    }

    .no-results-icon {
      font-size: 2.5rem;
    }

    .product-category-tag {
      font-size: 0.8rem;
    }

    .category-section {
      padding: 0.5rem;
      margin-bottom: 0.5rem;
      overflow: hidden;
    }

    .subcategory-section {
      margin: 0.25rem 0;
    }

    .subcategory-header {
      padding: 0.45rem 0.75rem;
    }

    /* Mobile navbar is shorter (~104px) */
    .subcategory-section.expanded .subcategory-header {
      top: calc(104px + env(safe-area-inset-top, 0px));
    }

    .category-header {
      flex-direction: row;
      text-align: left;
      gap: 0.375rem;
      padding-bottom: 0.375rem;
      margin-bottom: 0.375rem;
    }

    .category-header h2 {
      font-size: 1.5rem;
    }

    .category-header p {
      font-size: 1rem;
    }

    .category-icon-large {
      font-size: 1.25rem;
    }

    .category-icon-image {
      width: 40px;
      height: 40px;
    }

    .products-grid {
      grid-template-columns: repeat(2, 1fr);
      gap: 0.375rem;
    }

    .subcategory-products {
      grid-template-columns: repeat(2, 1fr);
    }

    .nested-subcategory-products {
      grid-template-columns: repeat(2, 1fr);
    }

    .product-item {
      flex-direction: column;
      text-align: center;
      padding: 0.5rem;
    }

    .product-info h4 {
      font-size: 0.95rem;
    }

    .product-note {
      font-size: 0.8rem;
    }

    .product-price {
      font-size: 1rem;
    }

    .in-stock {
      font-size: 0.8rem;
    }

    .quantity-selector {
      margin-bottom: 0.5rem;
    }

    .qty-btn {
      width: 36px;
      height: 36px;
      min-width: 36px;
      min-height: 36px;
      font-size: 1.1rem;
    }

    .product-actions {
      flex-direction: column;
      width: 100%;
    }

    .product-actions .btn {
      width: 100%;
      justify-content: center;
      min-height: 48px;
      padding: 14px 18px;
      font-size: 1.05rem;
    }

    .product-actions-wrapper {
      width: 100%;
    }

    /* Search results: 2-column compact grid so more items are visible */
    .search-results-section .products-grid {
      grid-template-columns: repeat(2, 1fr);
      gap: 0.6rem;
    }

    .search-results-section .product-item {
      padding: 0.65rem;
    }

    .search-results-section .product-image {
      max-width: 80px;
    }

    .search-results-section .product-info h4 {
      font-size: 0.9rem;
    }

    .search-results-section .product-price {
      font-size: 1rem;
    }

    .search-results-section .product-note,
    .search-results-section .product-category-tag,
    .search-results-section .product-quantity {
      font-size: 0.75rem;
    }

    .search-results-section .qty-btn {
      width: 36px;
      height: 36px;
      min-width: 36px;
      min-height: 44px;
      font-size: 1.1rem;
    }

    .search-results-section .qty-value {
      min-width: 22px;
      font-size: 0.9rem;
    }

    .search-results-section .quantity-selector {
      gap: 0.2rem;
    }

    .search-results-section .product-actions .btn {
      padding: 10px 6px;
      font-size: 0.85rem;
      min-height: 44px;
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
      gap: 0.75rem;
    }

    .clear-filter {
      min-height: 44px;
      padding: 0.5rem 1rem;
    }

    .subcategory-header h3 {
      font-size: 1.15rem;
    }

    .product-price {
      font-size: 1.2rem;
    }

    .lightbox-close {
      top: -50px;
      font-size: 3rem;
    }

    .lightbox-nav {
      font-size: 2rem;
      padding: 0.5rem 0.75rem;
    }

    .lightbox-prev {
      left: 10px;
    }

    .lightbox-next {
      right: 10px;
    }
  }

  /* Small phones */
  @media (max-width: 480px) {
    .filter-container {
      padding: 0.5rem;
      margin-bottom: 0.5rem;
    }

    .filter-label {
      font-size: 0.9rem;
    }

    .category-section {
      padding: 0.375rem;
      border-radius: 8px;
      overflow: hidden;
    }

    .subcategory-section {
      margin: 0.125rem 0;
    }

    .subcategory-header {
      padding: 0.375rem 0.625rem;
    }

    /* Extra-small navbar is shorter (~66px) */
    .subcategory-section.expanded .subcategory-header {
      top: calc(66px + env(safe-area-inset-top, 0px));
    }

    .category-header h2 {
      font-size: 1rem;
    }

    .category-header p {
      font-size: 0.85rem;
    }

    .category-icon-large {
      font-size: 1.125rem;
    }

    .category-icon-image {
      width: 36px;
      height: 36px;
    }

    .subcategory-icon {
      font-size: 1rem;
    }

    .subcategory-toggle {
      width: 24px;
      height: 24px;
      font-size: 1rem;
      position: relative;
    }

    .subcategory-toggle::before {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      min-width: 44px;
      min-height: 44px;
    }

    .product-item {
      padding: 0.375rem;
    }

    .product-info h4 {
      font-size: 0.95rem;
    }

    .product-note {
      font-size: 0.75rem;
    }

    .product-price {
      font-size: 1rem;
    }

    .product-image {
      max-width: 100px;
    }

    .qty-btn {
      width: 44px;
      height: 44px;
      min-width: 44px;
      min-height: 44px;
    }

    .qty-value {
      min-width: 32px;
      font-size: 1rem;
    }

    .confirm-popup {
      padding: 10px 12px;
      min-width: 180px;
    }

    .confirm-message {
      font-size: 0.85rem;
    }

    .confirm-btn {
      padding: 10px 8px;
      font-size: 0.8rem;
    }

    /* Keep 2-column search results on small phones, but more compact */
    .search-results-section .products-grid {
      grid-template-columns: repeat(2, 1fr);
      gap: 0.5rem;
    }

    .search-results-section .product-item {
      padding: 0.5rem;
    }

    .search-results-section .product-image {
      max-width: 70px;
    }

    .search-results-section .product-info h4 {
      font-size: 0.8rem;
    }

    .search-results-section .product-actions .btn {
      padding: 9px 4px;
      font-size: 0.78rem;
    }
  }
</style>
