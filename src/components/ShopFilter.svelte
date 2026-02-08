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
                images: getProductImages(subItem)
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
              images: getProductImages(item)
            });
          }
        }
      });
    });

    searchResults = results;
  }

  // Reactive search - triggers when searchQuery changes
  $: performSearch(searchQuery);

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
    const itemName = getProductName(productItem);
    if (addedItems[itemName]) {
      // Item already added - show confirmation popup
      confirmingItem = confirmingItem === itemName ? null : itemName;
    } else {
      // Add new item with selected quantity
      addToInquiry(productItem, categoryName, stockQty, itemPrice);
    }
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
    {#if searchResults.length === 0}
      <div class="no-results">
        <span class="no-results-icon">🔍</span>
        <p>No products found matching "<strong>{searchQuery}</strong>"</p>
        <p class="no-results-hint">Try a different search term or browse categories</p>
      </div>
    {:else}
      <div class="products-grid">
        {#each searchResults as result (result.productName)}
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
              {#if confirmingItem === result.productName}
                <div class="confirm-popup">
                  <div class="confirm-message">Already in your list ({addedItems[result.productName]})</div>
                  <div class="confirm-actions">
                    <button class="confirm-btn remove-btn" on:click={() => removeFromInquiry(result.product)}>
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
              {#each item.items as subItem}
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
                    {#if confirmingItem === getProductName(subItem)}
                      <div class="confirm-popup">
                        <div class="confirm-message">Already in your list ({addedItems[getProductName(subItem)]})</div>
                        <div class="confirm-actions">
                          <button class="confirm-btn remove-btn" on:click={() => removeFromInquiry(subItem)}>
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

              <!-- Confirmation Popup -->
              {#if confirmingItem === getProductName(item)}
                <div class="confirm-popup">
                  <div class="confirm-message">Already in your list ({addedItems[getProductName(item)]})</div>
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
    transition: all 0.2s ease;
  }

  .search-input:hover {
    border-color: #3498db;
  }

  .search-input:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.15);
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
    overflow: hidden;
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
    border-radius: 0;
    padding: 1.5rem;
    margin: 0.5rem -1.6rem;
    border: none;
    border-top: 2px solid #e0e0e0;
    border-bottom: 2px solid #e0e0e0;
    width: calc(100% + 3.2rem);
    box-sizing: border-box;
  }

  .subcategory-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.6rem;
    margin: -1.5rem -1.6rem 0 -1.6rem;
    border: none;
    background: #3498db;
    color: white;
    cursor: pointer;
    width: calc(100% + 3.2rem);
    text-align: left;
    border-radius: 0;
    transition: all 0.2s ease;
  }

  .subcategory-header:hover {
    background: #2980b9;
  }

  .subcategory-section:not(.expanded) .subcategory-header {
    border-radius: 0;
    margin-bottom: -1.6rem;
    padding-bottom: 1.6rem;
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
    position: relative;
    border: none;
    background: none;
    padding: 0;
    cursor: zoom-in;
    transition: transform 0.2s ease;
    display: inline-block;
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

  .lightbox-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    font-size: 3rem;
    cursor: pointer;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    transition: background 0.2s ease;
  }

  .lightbox-nav:hover {
    background: rgba(255, 255, 255, 0.4);
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
    max-width: 95vw;
    max-height: 85vh;
    cursor: zoom-out;
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
    }

    .search-input-wrapper {
      max-width: none;
    }

    .search-input {
      padding: 0.75rem 2.5rem;
      min-height: 48px;
      font-size: 1rem;
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
      padding: 1rem;
      margin-bottom: 1.5rem;
      overflow: hidden;
    }

    .subcategory-section {
      padding: 1rem;
      margin: 0.5rem -1.1rem;
      width: calc(100% + 2.2rem);
    }

    .subcategory-header {
      margin: -1rem -1.1rem 0 -1.1rem;
      padding: 1rem 1.1rem;
      width: calc(100% + 2.2rem);
    }

    .subcategory-section:not(.expanded) .subcategory-header {
      margin-bottom: -1.1rem;
      padding-bottom: 1.1rem;
    }

    .category-header {
      flex-direction: column;
      text-align: center;
      gap: 0.75rem;
      padding-bottom: 0.75rem;
      margin-bottom: 1rem;
    }

    .category-header h2 {
      font-size: 1.5rem;
    }

    .category-header p {
      font-size: 1rem;
    }

    .category-icon-large {
      font-size: 2.5rem;
    }

    .category-icon-image {
      width: 100px;
      height: 100px;
    }

    .products-grid {
      grid-template-columns: 1fr;
      gap: 0.75rem;
    }

    .subcategory-products {
      grid-template-columns: 1fr;
    }

    .product-item {
      flex-direction: column;
      text-align: center;
      padding: 1rem;
    }

    .product-info h4 {
      font-size: 1.1rem;
    }

    .product-note {
      font-size: 0.9rem;
    }

    .quantity-selector {
      margin-bottom: 0.5rem;
    }

    .qty-btn {
      width: 40px;
      height: 40px;
      font-size: 1.3rem;
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
      margin-bottom: 1rem;
    }

    .filter-label {
      font-size: 0.9rem;
    }

    .category-section {
      padding: 0.75rem;
      border-radius: 8px;
      overflow: hidden;
    }

    .subcategory-section {
      padding: 0.75rem;
      margin: 0.5rem -0.85rem;
      width: calc(100% + 1.7rem);
    }

    .subcategory-header {
      margin: -0.75rem -0.85rem 0 -0.85rem;
      padding: 0.75rem 0.85rem;
      width: calc(100% + 1.7rem);
    }

    .subcategory-section:not(.expanded) .subcategory-header {
      margin-bottom: -0.85rem;
      padding-bottom: 0.85rem;
    }

    .category-header h2 {
      font-size: 1.2rem;
    }

    .category-header p {
      font-size: 0.9rem;
    }

    .category-icon-large {
      font-size: 2rem;
    }

    .category-icon-image {
      width: 80px;
      height: 80px;
    }

    .subcategory-icon {
      font-size: 1.25rem;
    }

    .subcategory-toggle {
      width: 28px;
      height: 28px;
      font-size: 1.25rem;
    }

    .product-item {
      padding: 0.75rem;
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
      width: 36px;
      height: 36px;
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
  }
</style>
