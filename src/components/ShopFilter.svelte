<script>
  import { onMount, onDestroy, tick } from 'svelte';

  // Props passed from Astro
  export let categories = [];
  export let whatsappNumber = "237679457181";

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

  let apiStock = {};

  function getProductQuantity(item) {
    if (typeof item === 'string') return null;
    const name = item.name;
    if (apiStock[name] !== undefined) return apiStock[name];
    return item.quantity;
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
    '#2e7d32': 'Green',
    '#1565c0': 'Blue',
    '#c62828': 'Dark Red',
    '#4a5240': 'Army Green',
    '#1a237e': 'Indigo',
    '#4a148c': 'Deep Purple',
    '#7b1fa2': 'Deep Purple',
    '#b71c1c': 'Crimson',
    '#e65100': 'Dark Orange',
    '#33691e': 'Olive Green',
    '#00bcd4': 'Cyan',
  };

  function getColorName(hex) {
    return colorNames[hex.toLowerCase()] || hex;
  }

  let selectedColors = {};
  let needsColorItems = {};
  let activeImageIndexes = {}; // productName -> image index driven by color selection

  function selectColor(productName, color, colors) {
    if (selectedColors[productName] === color) {
      delete selectedColors[productName];
      delete activeImageIndexes[productName];
    } else {
      selectedColors[productName] = color;
      if (colors) {
        const idx = colors.indexOf(color);
        if (idx !== -1) activeImageIndexes[productName] = idx;
      }
    }
    selectedColors = selectedColors;
    activeImageIndexes = activeImageIndexes;
  }

  function selectColorAndAdd(productItem, colorHex, categoryName, stockQty, itemPrice) {
    const name = getProductName(productItem);
    const colors = getProductColors(productItem);
    if (selectedColors[name] === colorHex) {
      delete selectedColors[name];
      delete activeImageIndexes[name];
      selectedColors = selectedColors;
      activeImageIndexes = activeImageIndexes;
      return;
    }
    selectedColors[name] = colorHex;
    if (colors) {
      const idx = colors.indexOf(colorHex);
      if (idx !== -1) activeImageIndexes[name] = idx;
    }
    selectedColors = selectedColors;
    activeImageIndexes = activeImageIndexes;
    addToInquiry(productItem, categoryName, stockQty, itemPrice);
  }

  function needsColor(productItem) {
    const colors = getProductColors(productItem);
    if (!colors || colors.length <= 1) return false;
    const name = getProductName(productItem);
    return !selectedColors[name];
  }

  function getDisplayName(productItem) {
    const name = getProductName(productItem);
    const color = selectedColors[name];
    return color ? `${name} (${getColorName(color)})` : name;
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

  // Search through all products (handles any depth of subcategory nesting)
  function searchItems(items, lowerQuery, categoryName, subCategoryName, results) {
    items.forEach(item => {
      if (isSubCategory(item)) {
        // Recurse into nested subcategories
        searchItems(item.items, lowerQuery, categoryName, item.name, results);
      } else {
        const name = getProductName(item);
        if (name.toLowerCase().includes(lowerQuery)) {
          results.push({
            product: item,
            productName: name,
            categoryName,
            subCategoryName,
            price: getProductPrice(item),
            quantity: getProductQuantity(item),
            images: getProductImages(item),
            colors: getProductColors(item)
          });
        }
      }
    });
  }

  function performSearch(query) {
    if (!query || query.trim().length < 1) {
      searchResults = [];
      return;
    }
    const lowerQuery = query.toLowerCase().trim();
    const results = [];
    categories.forEach(category => {
      searchItems(category.items, lowerQuery, category.name, null, results);
    });
    searchResults = results;
  }

  // Reactive search - triggers when searchQuery or categories changes
  $: performSearch(searchQuery), categories;

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
    const productParam = urlParams.get('product');

    if (categoryParam && categories.find(c => c.id === categoryParam)) {
      selectedCategory = categoryParam;
      filterCategories();
    } else if (!searchParam) {
      // No category or search param — start showcase immediately on mount
      startShowcase();
    }

    // If search parameter exists, populate the search field
    if (searchParam) {
      searchQuery = decodeURIComponent(searchParam);
    }

    // If a specific product was linked (e.g. from Today's Picks), open its modal
    if (productParam) {
      const match = getAllProductsFlat().find(sp => sp.productName === productParam);
      if (match) {
        tick().then(() => openProductModal(match));
      }
    }

    // Fetch live stock from API — overrides categories.ts quantities
    fetch('/api/stock.php?action=all')
      .then(r => r.json())
      .then(data => { apiStock = data; })
      .catch(() => {}); // silently fall back to categories.ts values

    // Listen for cart loaded from localStorage (on page refresh)
    const handleCartLoaded = (e) => {
      const { items } = e.detail;
      cartItems = items.map(i => ({ ...i }));
      items.forEach(item => {
        addedItems[item.name] = item.quantity || 1;
      });
      addedItems = addedItems;
    };
    window.addEventListener('cart-loaded', handleCartLoaded);

    // Listen for item removed from cart
    const handleItemRemoved = (e) => {
      const { name } = e.detail;
      cartItems = cartItems.filter(i => i.name !== name);
      delete addedItems[name];
      addedItems = addedItems;
    };
    window.addEventListener('item-removed-from-cart', handleItemRemoved);

    // Listen for cart quantity updates from cart sidebar
    const handleCartQtyUpdated = (e) => {
      const { name, quantity } = e.detail;
      cartItems = cartItems.map(i => i.name === name ? { ...i, quantity } : i);
      if (addedItems[name]) {
        addedItems[name] = quantity;
        addedItems = addedItems;
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

    // Sticky subcategory header scroll handler
    window.addEventListener('scroll', updateStickyHeaders, { passive: true });
    window.addEventListener('resize', updateStickyHeaders, { passive: true });

    // Reset to All Categories when Shop nav link is clicked while already on the page
    const handleShopNavClick = () => {
      selectedCategory = 'all';
      filterCategories();
      searchQuery = '';
      stopShowcase();
      startShowcase(); // start immediately, no reactive delay
      window.history.pushState({}, '', '/shop');
      window.scrollTo({ top: 0, behavior: 'smooth' });
    };
    window.addEventListener('shop-nav-clicked', handleShopNavClick);

    const handleKeyDown = (e) => {
      if (e.key === 'Escape') {
        if (productModal) closeProductModal();
        else if (lightboxImages.length > 0) closeLightbox();
      }
    };
    document.addEventListener('keydown', handleKeyDown);

    return () => {
      document.removeEventListener('keydown', handleKeyDown);
      document.removeEventListener('click', handleClickOutside);
      window.removeEventListener('cart-loaded', handleCartLoaded);
      window.removeEventListener('cart-qty-updated', handleCartQtyUpdated);
      window.removeEventListener('item-removed-from-cart', handleItemRemoved);
      window.removeEventListener('scroll', updateStickyHeaders);
      window.removeEventListener('resize', updateStickyHeaders);
      window.removeEventListener('shop-nav-clicked', handleShopNavClick);
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
    const message = encodeURIComponent(`Hi, I have a question about: ${name}\n\n`);
    return `https://wa.me/${whatsappNumber}?text=${message}`;
  }

  // Share product link
  let sharedItem = null;
  async function shareProduct(name) {
    const url = window.location.origin + '/shop?search=' + encodeURIComponent(name);
    try {
      await navigator.clipboard.writeText(url);
    } catch {
      const ta = document.createElement('textarea');
      ta.value = url; ta.style.position = 'fixed'; ta.style.opacity = '0';
      document.body.appendChild(ta); ta.select();
      document.execCommand('copy'); document.body.removeChild(ta);
    }
    sharedItem = name;
    setTimeout(() => { sharedItem = null; }, 2000);
  }

  // Track which items have been added to inquiry with their quantities
  let addedItems = {};  // { displayName: quantity }

  // Mirror of cart contents for live stock calculation
  let cartItems = [];
  $: cartTotals = cartItems.reduce((acc, item) => {
    const baseName = item.name.replace(/\s\([^)]+\)$/, '');
    acc[baseName] = (acc[baseName] || 0) + (item.quantity || 1);
    // Also track color-specific key (e.g. "Fan (Purple)") for per-color stock
    if (item.name !== baseName) {
      acc[item.name] = (acc[item.name] || 0) + (item.quantity || 1);
    }
    return acc;
  }, {});

  // Reactive map: productName -> how many of the selected color are already in cart
  // Re-evaluates whenever cartTotals or selectedColors changes (both read directly)
  $: colorStockInfo = Object.fromEntries(
    Object.entries(selectedColors).map(([name, colorHex]) => [
      name,
      cartTotals[`${name} (${getColorName(colorHex)})`] || 0
    ])
  );

  function getRemainingStock(productItem) {
    const qty = getProductQuantity(productItem);
    if (qty == null) return qty;
    const baseName = getProductName(productItem);
    return Math.max(0, qty - (cartTotals[baseName] || 0));
  }

  function isOutOfStock(productItem) {
    const remaining = getRemainingStock(productItem);
    return remaining !== null && remaining !== undefined && remaining === 0;
  }

  // Per-color max stock: uses colorQuantities if defined, else divides evenly
  function getColorMax(productItem, colorHex) {
    if (typeof productItem !== 'string' && productItem.colorQuantities && productItem.colorQuantities[colorHex] !== undefined) {
      return productItem.colorQuantities[colorHex];
    }
    const qty = getProductQuantity(productItem);
    if (qty == null) return null;
    const colors = getProductColors(productItem);
    return colors && colors.length > 1 ? Math.ceil(qty / colors.length) : qty;
  }

  // Per-color remaining stock: use per-color API stock if available, else use getColorMax
  function getColorRemaining(productItem, colorHex) {
    const baseName = getProductName(productItem);
    const colorKey = `${baseName} (${getColorName(colorHex)})`;
    if (apiStock[colorKey] !== undefined) {
      return Math.max(0, apiStock[colorKey] - (cartTotals[colorKey] || 0));
    }
    const max = getColorMax(productItem, colorHex);
    if (max == null) return null;
    return Math.max(0, max - (cartTotals[colorKey] || 0));
  }

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

  // Zoom & pan state
  let zoomScale = 1;
  let panX = 0;
  let panY = 0;
  $: isZoomed = zoomScale > 1.05;

  // Pinch state
  let lastPinchDist = 0;
  let isPanning = false;
  let panTouchStartX = 0, panTouchStartY = 0;
  let panStartXZ = 0, panStartYZ = 0;

  // Mouse drag state
  let isMouseDragging = false;
  let mouseDragStartX = 0, mouseDragStartY = 0;
  let panMouseStartX = 0, panMouseStartY = 0;

  function resetZoom() { zoomScale = 1; panX = 0; panY = 0; }

  // Custom dropdown state
  let isDropdownOpen = false;

  function toggleDropdown() {
    isDropdownOpen = !isDropdownOpen;
  }

  function selectCategory(categoryId) {
    selectedCategory = categoryId;
    filterCategories();
    isDropdownOpen = false;

    if (categoryId === 'all') {
      stopShowcase();
      startShowcase(); // start immediately, no reactive delay
    }

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
    resetZoom();
  }

  function toggleZoom() {
    if (isZoomed) { resetZoom(); } else { zoomScale = 2; }
  }

  function nextImage() {
    resetZoom();
    if (lightboxIndex < lightboxImages.length - 1) {
      lightboxIndex++;
    } else {
      lightboxIndex = 0;
    }
  }

  function prevImage() {
    resetZoom();
    if (lightboxIndex > 0) {
      lightboxIndex--;
    } else {
      lightboxIndex = lightboxImages.length - 1;
    }
  }

  function handleTouchStart(e) {
    if (e.touches.length === 2) {
      const dx = e.touches[0].clientX - e.touches[1].clientX;
      const dy = e.touches[0].clientY - e.touches[1].clientY;
      lastPinchDist = Math.sqrt(dx * dx + dy * dy);
    } else if (e.touches.length === 1) {
      if (isZoomed) {
        isPanning = true;
        panTouchStartX = e.touches[0].clientX;
        panTouchStartY = e.touches[0].clientY;
        panStartXZ = panX;
        panStartYZ = panY;
      } else {
        touchStartX = e.touches[0].clientX;
        touchEndX = touchStartX;
      }
    }
  }

  function handleTouchMove(e) {
    if (e.touches.length === 2) {
      e.preventDefault();
      const dx = e.touches[0].clientX - e.touches[1].clientX;
      const dy = e.touches[0].clientY - e.touches[1].clientY;
      const dist = Math.sqrt(dx * dx + dy * dy);
      if (lastPinchDist > 0) {
        zoomScale = Math.min(4, Math.max(1, zoomScale * (dist / lastPinchDist)));
        if (zoomScale <= 1) { panX = 0; panY = 0; }
      }
      lastPinchDist = dist;
    } else if (e.touches.length === 1 && isPanning && isZoomed) {
      e.preventDefault();
      panX = panStartXZ + (e.touches[0].clientX - panTouchStartX);
      panY = panStartYZ + (e.touches[0].clientY - panTouchStartY);
    } else if (e.touches.length === 1 && !isZoomed) {
      touchEndX = e.touches[0].clientX;
    }
  }

  function handleTouchEnd(e) {
    isPanning = false;
    lastPinchDist = 0;
    if (zoomScale < 1.1) { zoomScale = 1; panX = 0; panY = 0; }
    if (!isZoomed) handleSwipe();
  }

  function handleLightboxWheel(e) {
    e.preventDefault();
    const factor = e.deltaY < 0 ? 1.12 : 0.88;
    zoomScale = Math.min(4, Math.max(1, zoomScale * factor));
    if (zoomScale <= 1) { panX = 0; panY = 0; }
  }

  function handleLightboxMouseDown(e) {
    if (!isZoomed) return;
    isMouseDragging = true;
    mouseDragStartX = e.clientX;
    mouseDragStartY = e.clientY;
    panMouseStartX = panX;
    panMouseStartY = panY;
    e.preventDefault();
  }

  function handleLightboxMouseMove(e) {
    if (!isMouseDragging) return;
    panX = panMouseStartX + (e.clientX - mouseDragStartX);
    panY = panMouseStartY + (e.clientY - mouseDragStartY);
  }

  function handleLightboxMouseUp() {
    isMouseDragging = false;
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

  // JS-based sticky: applies position:fixed to subcategory headers as user scrolls.
  // CSS position:sticky is unreliable when ancestors have overflow constraints.
  function applyFixed(header, section, top, zIndex) {
    // Always derive left/width from the SECTION (always in flow, never stale)
    // rather than the header itself (which may already be position:fixed).
    const sectionRect = section.getBoundingClientRect();
    const styles = getComputedStyle(section);
    const borderL = parseFloat(styles.borderLeftWidth) || 0;
    const borderR = parseFloat(styles.borderRightWidth) || 0;
    const padL    = parseFloat(styles.paddingLeft) || 0;
    const padR    = parseFloat(styles.paddingRight) || 0;
    const safeLeft = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--sat') || '0') || 0;
    const left  = sectionRect.left + borderL + padL + safeLeft;
    const width = section.offsetWidth - borderL - borderR - padL - padR;

    header.setAttribute('data-is-fixed', '1');
    header.style.position = 'fixed';
    header.style.top = top + 'px';
    header.style.left = left + 'px';
    header.style.width = width + 'px';
    header.style.zIndex = zIndex;

    if (!section.querySelector(':scope > .js-sticky-spacer')) {
      const spacer = document.createElement('div');
      spacer.className = 'js-sticky-spacer';
      spacer.style.height = header.offsetHeight + 'px';
      header.after(spacer);
    }
  }

  function removeFixed(header, section) {
    header.removeAttribute('data-is-fixed');
    header.style.position = '';
    header.style.top = '';
    header.style.left = '';
    header.style.width = '';
    header.style.zIndex = '';
    const spacer = section.querySelector(':scope > .js-sticky-spacer');
    if (spacer) spacer.remove();
  }

  function updateStickyHeaders() {
    const navbar = document.querySelector('.navbar');
    const navbarBottom = navbar ? Math.max(0, navbar.getBoundingClientRect().bottom) : 0;

    // Top-level subcategory headers (e.g. Deodorants, Perfumes)
    document.querySelectorAll('.subcategory-section.expanded').forEach(section => {
      const header = section.querySelector(':scope > .subcategory-header');
      if (!header) return;

      const sectionRect = section.getBoundingClientRect();
      const headerHeight = header.offsetHeight;
      const shouldFix = sectionRect.top < navbarBottom &&
                        sectionRect.bottom > navbarBottom + headerHeight;

      if (shouldFix) {
        applyFixed(header, section, navbarBottom, '200');
      } else if (header.hasAttribute('data-is-fixed')) {
        removeFixed(header, section);
      }
    });

    // Nested subcategory headers (Men / Women) — stack below the parent floating bar
    document.querySelectorAll('.nested-subcategory.expanded').forEach(section => {
      const header = section.querySelector(':scope > .nested-subcategory-header');
      if (!header) return;

      const parentHeader = section.closest('.subcategory-section')
        ?.querySelector(':scope > .subcategory-header');
      const nestedTop = parentHeader?.hasAttribute('data-is-fixed')
        ? parentHeader.getBoundingClientRect().bottom
        : navbarBottom;

      const sectionRect = section.getBoundingClientRect();
      const headerHeight = header.offsetHeight;
      const shouldFix = sectionRect.top < nestedTop &&
                        sectionRect.bottom > nestedTop + headerHeight;

      if (shouldFix) {
        applyFixed(header, section, nestedTop, '199');
      } else if (header.hasAttribute('data-is-fixed')) {
        removeFixed(header, section);
      }
    });

    // Clean up any fixed headers inside now-collapsed sections
    const collapsedFixed = '.subcategory-section:not(.expanded) .subcategory-header[data-is-fixed],'
      + '.nested-subcategory:not(.expanded) .nested-subcategory-header[data-is-fixed]';
    document.querySelectorAll(collapsedFixed).forEach(header => {
      const section = header.closest('.subcategory-section, .nested-subcategory');
      if (section) removeFixed(header, section);
    });
  }

  async function toggleSubCategory(name) {
    const isCollapsing = expandedSubCategories.has(name);

    if (isCollapsing) {
      // Find the section by its data-name before collapsing
      let targetSection = null;
      document.querySelectorAll('[data-name]').forEach(s => {
        if (s.dataset.name === name) targetSection = s;
      });
      const header = targetSection?.querySelector(':scope > .subcategory-header, :scope > .nested-subcategory-header');
      const wasFixed = header?.hasAttribute('data-is-fixed') ?? false;

      expandedSubCategories.delete(name);
      expandedSubCategories = expandedSubCategories;
      await tick();
      updateStickyHeaders();

      // Scroll back so the collapsed header lands just below the navbar
      if (wasFixed && targetSection) {
        const navEl = document.querySelector('.navbar');
        const navbarBottom = navEl ? Math.max(0, navEl.getBoundingClientRect().bottom) : 0;
        const sectionTop = targetSection.getBoundingClientRect().top + window.scrollY;
        window.scrollTo({ top: sectionTop - navbarBottom - 8, behavior: 'smooth' });
      }
    } else {
      expandedSubCategories.add(name);
      expandedSubCategories = expandedSubCategories;
      await tick();
      updateStickyHeaders();
    }
  }

  function handleInquiryClick(productItem, categoryName, stockQty, itemPrice) {
    if (needsColor(productItem)) {
      const name = getProductName(productItem);
      needsColorItems[name] = true;
      needsColorItems = needsColorItems;
      setTimeout(() => { delete needsColorItems[name]; needsColorItems = needsColorItems; }, 600);
      return;
    }
    addToInquiry(productItem, categoryName, stockQty, itemPrice);
  }

  function addToInquiry(productItem, categoryName, stockQty, itemPrice) {
    const itemName = getProductName(productItem);
    const qty = getSelectedQty(itemName);
    const itemImage = getProductImage(productItem);
    const selectedColor = selectedColors[itemName];
    const displayName = selectedColor ? `${itemName} (${getColorName(selectedColor)})` : itemName;
    const effectiveMax = selectedColor
      ? (getColorRemaining(productItem, selectedColor) ?? stockQty ?? 99)
      : (stockQty ?? 99);

    // Dispatch custom event for InquiryBasket to listen to
    const event = new CustomEvent('add-to-inquiry', {
      detail: {
        name: displayName,
        category: categoryName,
        quantity: qty,
        maxStock: effectiveMax,
        price: itemPrice || 0,
        image: itemImage
      }
    });
    window.dispatchEvent(event);

    // Update cartItems mirror for live stock calculation
    const existing = cartItems.find(i => i.name === displayName);
    if (existing) {
      cartItems = cartItems.map(i => i.name === displayName ? { ...i, quantity: Math.min((i.quantity || 1) + qty, effectiveMax) } : i);
    } else {
      cartItems = [...cartItems, { name: displayName, quantity: qty, maxStock: effectiveMax }];
    }

    // Mark as added with quantity for visual feedback (keyed by color-specific name)
    addedItems[displayName] = qty;
    addedItems = addedItems;
    // Reset after 1.5s so user can add more of the same color
    setTimeout(() => {
      delete addedItems[displayName];
      addedItems = addedItems;
    }, 1500);
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

  // ── Random Product Showcase ──────────────────────────────────────────────
  const SHOWCASE_DURATION = 10000;
  const SHOWCASE_STEP = 4;
  const SHOWCASE_SHOW = 12;
  // Eagerly populate so products show immediately without waiting for onMount
  let showcaseProducts = getAllProductsFlat();
  let showcaseOffset = 0;
  let showcaseTimer = null;
  let showcaseActive = false;

  function getAllProductsFlat() {
    const all = [];
    function collect(items, catId, catName, subCatName) {
      items.forEach(item => {
        if (isSubCategory(item)) {
          collect(item.items, catId, catName, item.name);
        } else {
          const imgs = getProductImages(item);
          if (imgs && imgs.length > 0) {
            all.push({
              product: item,
              productName: getProductName(item),
              categoryId: catId,
              categoryName: catName,
              subCategoryName: subCatName,
              price: getProductPrice(item),
              quantity: getProductQuantity(item),
              images: imgs,
              colors: getProductColors(item)
            });
          }
        }
      });
    }
    categories.forEach(cat => collect(cat.items, cat.id, cat.name, null));
    return all;
  }

  function shuffleArr(arr) {
    const a = [...arr];
    for (let i = a.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [a[i], a[j]] = [a[j], a[i]];
    }
    return a;
  }

  $: showcaseVisible = showcaseProducts.length > 0
    ? Array.from({ length: SHOWCASE_SHOW }, (_, i) =>
        showcaseProducts[(showcaseOffset + i) % showcaseProducts.length]
      )
    : [];

  $: showcaseVisibleSorted = sortBy === 'default' ? showcaseVisible : sortProducts(showcaseVisible, sortBy);

  function runShowcaseCycle() {
    clearTimeout(showcaseTimer);
    showcaseTimer = setTimeout(() => {
      showcaseOffset = (showcaseOffset + SHOWCASE_STEP) % showcaseProducts.length;
      runShowcaseCycle();
    }, SHOWCASE_DURATION);
  }

  function startShowcase() {
    if (showcaseActive) return;
    showcaseProducts = shuffleArr(getAllProductsFlat());
    showcaseOffset = Math.floor(Math.random() * Math.max(showcaseProducts.length, 1));
    showcaseActive = true;
    runShowcaseCycle();
  }

  function stopShowcase() {
    showcaseActive = false;
    clearTimeout(showcaseTimer);
    showcaseTimer = null;
  }

  function nextShowcaseProduct() {
    showcaseOffset = (showcaseOffset + SHOWCASE_STEP) % showcaseProducts.length;
    runShowcaseCycle();
  }

  function prevShowcaseProduct() {
    showcaseOffset = (showcaseOffset - SHOWCASE_STEP + showcaseProducts.length) % showcaseProducts.length;
    runShowcaseCycle();
  }

  // Start/stop showcase based on view state
  $: {
    if (selectedCategory === 'all' && (!searchQuery || searchQuery.trim().length < 1)) {
      if (!showcaseActive) startShowcase();
    } else {
      if (showcaseActive) stopShowcase();
    }
  }

  onDestroy(() => stopShowcase());

  // ── Product Detail Modal ─────────────────────────────────────────────────
  let productModal = null;
  let modalImageIndex = 0;

  function openProductModal(sp) {
    productModal = sp;
    modalImageIndex = 0;
  }

  function closeProductModal() {
    productModal = null;
    modalImageIndex = 0;
  }

  function modalNextImage() {
    if (!productModal) return;
    modalImageIndex = (modalImageIndex + 1) % productModal.images.length;
  }

  function modalPrevImage() {
    if (!productModal) return;
    modalImageIndex = (modalImageIndex - 1 + productModal.images.length) % productModal.images.length;
  }

  function handleModalTouchEnd(e) {
    touchEndX = e.changedTouches[0].clientX;
    const diff = touchStartX - touchEndX;
    if (Math.abs(diff) > 50) {
      if (diff > 0) modalNextImage();
      else modalPrevImage();
    }
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
        autocomplete="off"
        autocorrect="off"
        autocapitalize="off"
        spellcheck="false"
      />
      {#if searchQuery}
        <button class="search-clear" on:click={clearSearch} aria-label="Clear search">✕</button>
      {/if}
    </div>
    {#if expandedSubCategories.size > 0 || (searchQuery && searchQuery.trim().length >= 2) || selectedCategory === 'all'}
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
          <div class="product-item" class:has-image={result.images} class:product-sold-out={isOutOfStock(result.product)}>
            {#if result.images}
              <div class="product-images">
                <button class="product-image" on:click={() => openLightbox(result.images, result.productName)}>
                  <img src={result.images[activeImageIndexes[result.productName] ?? 0]} alt={result.productName} />
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
                  {#if getProductQuantity(result.product) > 0}
                    <span class="in-stock">In Stock: {getRemainingStock(result.product)}</span>
                    {#if result.colors}
                      <span class="color-dots" class:shake={needsColorItems[result.productName]}>
                        {#each result.colors as color}
                          {@const soldOut = getColorRemaining(result.product, color) === 0}
                          <button class="color-dot" style="background: {color};" title={getColorName(color)} class:selected={selectedColors[result.productName] === color} class:sold-out={soldOut} disabled={soldOut} on:click|stopPropagation={() => selectColor(result.productName, color, result.colors)}></button>
                        {/each}
                        {#if selectedColors[result.productName]}
                          <span class="color-label">{getColorName(selectedColors[result.productName])}</span>
                        {/if}
                      </span>
                    {/if}
                  {:else}
                    <span class="out-of-stock">Currently unavailable</span>
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
                <button class="qty-btn" on:click={() => incrementQty(result.productName, getRemainingStock(result.product))} disabled={selectedColors[result.productName] && result.colors && result.colors.length > 1 ? (displayQuantities[result.productName] || 1) >= (getColorRemaining(result.product, selectedColors[result.productName]) ?? 99) : getRemainingStock(result.product) != null && (displayQuantities[result.productName] || 1) >= getRemainingStock(result.product)}>+</button>
              </div>
              <div class="product-actions">
                <button
                  class="btn btn-small btn-inquiry"
                  class:added={addedItems[getDisplayName(result.product)]}
                  class:needs-color={!selectedColors[result.productName] && result.colors && result.colors.length > 1}
                  disabled={isOutOfStock(result.product) || !!(selectedColors[result.productName] && result.colors && result.colors.length > 1 && getColorRemaining(result.product, selectedColors[result.productName]) === 0)}
                  on:click={() => handleInquiryClick(result.product, result.subCategoryName || result.categoryName, result.quantity, result.price)}
                >
                  {addedItems[getDisplayName(result.product)] ? `✓ Added (${addedItems[getDisplayName(result.product)]})` : 'Add to Cart'}
                </button>
                <a
                  href={getWhatsAppLink(result.product)}
                  target="_blank"
                  rel="noopener noreferrer"
                  class="btn btn-small btn-whatsapp"
                >
                  WhatsApp
                </a>
                <button class="btn-share" title="Copy link" on:click={() => shareProduct(result.productName)}>
                  {sharedItem === result.productName ? '✓' : '🔗'}
                </button>
              </div>
            </div>
          </div>
        {/each}
      </div>
    {/if}
  </div>
{:else if selectedCategory === 'all'}
<!-- Random Product Showcase -->
{#if showcaseVisible.length > 0}
  <div class="showcase-wrapper">
    <div class="showcase-nav">
      <button class="showcase-nav-btn" on:click={prevShowcaseProduct} aria-label="Previous products">&#8249;</button>
      <button class="showcase-nav-btn" on:click={nextShowcaseProduct} aria-label="Next products">&#8250;</button>
    </div>
    {#key showcaseOffset + sortBy}
      <div class="showcase-grid">
        {#each showcaseVisibleSorted as sp (sp.productName)}
          <div class="product-item has-image">
            <a href="/shop?category={sp.categoryId}" class="showcase-item-link" on:click|preventDefault={() => openProductModal(sp)}>
              <div class="product-images">
                <img src={sp.images[activeImageIndexes[sp.productName] ?? 0]} alt={sp.productName} class="showcase-thumb" />
              </div>
              <div class="product-info">
                <p class="product-category-tag">{sp.categoryName}{sp.subCategoryName ? ` › ${sp.subCategoryName}` : ''}</p>
                <h4>{sp.productName}</h4>
                {#if sp.price}
                  <p class="product-price">{formatPrice(sp.price)}</p>
                {/if}
                {#if sp.quantity !== null && sp.quantity !== undefined}
                  <p class="product-quantity">
                    {#if getProductQuantity(sp.product) > 0}
                      <span class="in-stock">In Stock: {getRemainingStock(sp.product)}</span>
                    {:else}
                      <span class="out-of-stock">Currently unavailable</span>
                    {/if}
                  </p>
                {:else}
                  <p class="product-note">Available &bull; Imported from USA/Canada</p>
                {/if}
              </div>
            </a>
            {#if sp.colors && sp.quantity > 0}
              <p class="product-quantity" style="padding: 0 0.5rem;">
                <span class="color-dots" class:shake={needsColorItems[sp.productName]}>
                  {#each sp.colors as color}
                    {@const soldOut = getColorRemaining(sp.product, color) === 0}
                    <button class="color-dot" style="background: {color};" title={getColorName(color)} class:selected={selectedColors[sp.productName] === color} class:sold-out={soldOut} disabled={soldOut} on:click|stopPropagation={() => selectColor(sp.productName, color, sp.colors)}></button>
                  {/each}
                  {#if selectedColors[sp.productName]}
                    <span class="color-label">{getColorName(selectedColors[sp.productName])}</span>
                  {/if}
                </span>
              </p>
            {/if}
            <div class="product-actions-wrapper">
              <div class="quantity-selector">
                <button class="qty-btn" on:click={() => decrementQty(sp.productName)} disabled={(displayQuantities[sp.productName] || 1) <= 1}>−</button>
                <span class="qty-value">{displayQuantities[sp.productName] || 1}</span>
                <button class="qty-btn" on:click={() => incrementQty(sp.productName, getRemainingStock(sp.product))} disabled={selectedColors[sp.productName] && sp.colors && sp.colors.length > 1 ? (displayQuantities[sp.productName] || 1) >= (getColorRemaining(sp.product, selectedColors[sp.productName]) ?? 99) : getRemainingStock(sp.product) != null && (displayQuantities[sp.productName] || 1) >= getRemainingStock(sp.product)}>+</button>
              </div>
              <div class="product-actions">
                <button
                  class="btn btn-small btn-inquiry"
                  class:added={addedItems[getDisplayName(sp.product)]}
                  class:needs-color={!selectedColors[sp.productName] && sp.colors && sp.colors.length > 1}
                  disabled={isOutOfStock(sp.product) || !!(selectedColors[sp.productName] && sp.colors && sp.colors.length > 1 && getColorRemaining(sp.product, selectedColors[sp.productName]) === 0)}
                  on:click={() => handleInquiryClick(sp.product, sp.subCategoryName || sp.categoryName, sp.quantity, sp.price)}
                >
                  {addedItems[getDisplayName(sp.product)] ? `✓ Added (${addedItems[getDisplayName(sp.product)]})` : 'Add to Cart'}
                </button>
                <a href={getWhatsAppLink(sp.product)} target="_blank" rel="noopener noreferrer" class="btn btn-small btn-whatsapp">WhatsApp</a>
                <button class="btn-share" title="Copy link" on:click={() => shareProduct(sp.productName)}>
                  {sharedItem === sp.productName ? '✓' : '🔗'}
                </button>
              </div>
            </div>
          </div>
        {/each}
      </div>
    {/key}
  </div>
{/if}
{:else}
<!-- Specific Category Grid -->
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
          <div class="subcategory-section" class:expanded={expandedSubCategories.has(item.name)} data-name={item.name}>
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
                  <div class="nested-subcategory" class:expanded={expandedSubCategories.has(subItem.name)} data-name={subItem.name}>
                    <button class="nested-subcategory-header" on:click={() => toggleSubCategory(subItem.name)}>
                      <h4>{subItem.name}</h4>
                      <span class="subcategory-toggle">{expandedSubCategories.has(subItem.name) ? '−' : '+'}</span>
                    </button>
                    {#if expandedSubCategories.has(subItem.name)}
                      <div class="nested-subcategory-products">
                        {#each sortItems(subItem.items, sortBy) as nestedProduct}
                          <div class="product-item" class:has-image={getProductImages(nestedProduct)} class:product-sold-out={isOutOfStock(nestedProduct)}>
                            {#if getProductImages(nestedProduct)}
                              <div class="product-images">
                                <button class="product-image" on:click={() => openLightbox(getProductImages(nestedProduct), getProductName(nestedProduct))}>
                                  <img src={getProductImages(nestedProduct)[activeImageIndexes[getProductName(nestedProduct)] ?? 0]} alt={getProductName(nestedProduct)} />
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
                                    <span class="in-stock">In Stock: {getRemainingStock(nestedProduct)}</span>
                                    {#if getProductColors(nestedProduct)}
                                      <span class="color-dots" class:shake={needsColorItems[getProductName(nestedProduct)]}>
                                        {#each getProductColors(nestedProduct) as color}
                                          {@const soldOut = getColorRemaining(nestedProduct, color) === 0}
                                          {@const nestedName = getProductName(nestedProduct)}
                                          <button class="color-dot" style="background: {color};" title={getColorName(color)} class:selected={selectedColors[nestedName] === color} class:sold-out={soldOut} disabled={soldOut} on:click|stopPropagation={() => selectColor(nestedName, color, getProductColors(nestedProduct))}></button>
                                        {/each}
                                        {#if selectedColors[getProductName(nestedProduct)]}
                                          <span class="color-label">{getColorName(selectedColors[getProductName(nestedProduct)])}</span>
                                        {/if}
                                      </span>
                                    {/if}
                                  {:else}
                                    <span class="out-of-stock">Currently unavailable</span>
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
                                <button class="qty-btn" on:click={() => incrementQty(getProductName(nestedProduct), getRemainingStock(nestedProduct))} disabled={selectedColors[getProductName(nestedProduct)] && getProductColors(nestedProduct) && getProductColors(nestedProduct).length > 1 ? (displayQuantities[getProductName(nestedProduct)] || 1) >= (getColorRemaining(nestedProduct, selectedColors[getProductName(nestedProduct)]) ?? 99) : getRemainingStock(nestedProduct) != null && (displayQuantities[getProductName(nestedProduct)] || 1) >= getRemainingStock(nestedProduct)}>+</button>
                              </div>
                              <div class="product-actions">
                                <button
                                  class="btn btn-small btn-inquiry"
                                  class:added={addedItems[getDisplayName(nestedProduct)]}
                                  class:needs-color={!selectedColors[getProductName(nestedProduct)] && getProductColors(nestedProduct) && getProductColors(nestedProduct).length > 1}
                                  disabled={isOutOfStock(nestedProduct) || !!(selectedColors[getProductName(nestedProduct)] && getProductColors(nestedProduct) && getProductColors(nestedProduct).length > 1 && getColorRemaining(nestedProduct, selectedColors[getProductName(nestedProduct)]) === 0)}
                                  on:click={() => handleInquiryClick(nestedProduct, subItem.name, getProductQuantity(nestedProduct), getProductPrice(nestedProduct))}
                                >
                                  {addedItems[getDisplayName(nestedProduct)] ? `✓ Added (${addedItems[getDisplayName(nestedProduct)]})` : 'Add to Cart'}
                                </button>
                                <a
                                  href={getWhatsAppLink(nestedProduct)}
                                  target="_blank"
                                  rel="noopener noreferrer"
                                  class="btn btn-small btn-whatsapp"
                                >
                                  WhatsApp
                                </a>
                                <button class="btn-share" title="Copy link" on:click={() => shareProduct(getProductName(nestedProduct))}>
                                  {sharedItem === getProductName(nestedProduct) ? '✓' : '🔗'}
                                </button>
                              </div>
                            </div>
                          </div>
                        {/each}
                      </div>
                    {/if}
                  </div>
                {:else}
                <div class="product-item" class:has-image={getProductImages(subItem)} class:product-sold-out={isOutOfStock(subItem)}>
                  {#if getProductImages(subItem)}
                    <div class="product-images">
                      <button class="product-image" on:click={() => openLightbox(getProductImages(subItem), getProductName(subItem))}>
                        <img src={getProductImages(subItem)[activeImageIndexes[getProductName(subItem)] ?? 0]} alt={getProductName(subItem)} />
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
                          <span class="in-stock">In Stock: {getRemainingStock(subItem)}</span>
                          {#if getProductColors(subItem)}
                            <span class="color-dots" class:shake={needsColorItems[getProductName(subItem)]}>
                              {#each getProductColors(subItem) as color}
                                {@const soldOut = getColorRemaining(subItem, color) === 0}
                                {@const subItemName = getProductName(subItem)}
                                <button class="color-dot" style="background: {color};" title={getColorName(color)} class:selected={selectedColors[subItemName] === color} class:sold-out={soldOut} disabled={soldOut} on:click|stopPropagation={() => selectColor(subItemName, color, getProductColors(subItem))}></button>
                              {/each}
                              {#if selectedColors[getProductName(subItem)]}
                                <span class="color-label">{getColorName(selectedColors[getProductName(subItem)])}</span>
                              {/if}
                            </span>
                          {/if}
                        {:else}
                          <span class="out-of-stock">Currently unavailable</span>
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
                      <button class="qty-btn" on:click={() => incrementQty(getProductName(subItem), getRemainingStock(subItem))} disabled={selectedColors[getProductName(subItem)] && getProductColors(subItem) && getProductColors(subItem).length > 1 ? (displayQuantities[getProductName(subItem)] || 1) >= (getColorRemaining(subItem, selectedColors[getProductName(subItem)]) ?? 99) : getRemainingStock(subItem) != null && (displayQuantities[getProductName(subItem)] || 1) >= getRemainingStock(subItem)}>+</button>
                    </div>
                    <div class="product-actions">
                      <button
                        class="btn btn-small btn-inquiry"
                        class:added={addedItems[getDisplayName(subItem)]}
                        class:needs-color={!selectedColors[getProductName(subItem)] && getProductColors(subItem) && getProductColors(subItem).length > 1}
                        disabled={isOutOfStock(subItem) || !!(selectedColors[getProductName(subItem)] && getProductColors(subItem) && getProductColors(subItem).length > 1 && getColorRemaining(subItem, selectedColors[getProductName(subItem)]) === 0)}
                        on:click={() => handleInquiryClick(subItem, item.name, getProductQuantity(subItem), getProductPrice(subItem))}
                      >
                        {addedItems[getDisplayName(subItem)] ? `✓ Added (${addedItems[getDisplayName(subItem)]})` : 'Add to Cart'}
                      </button>
                      <a
                        href={getWhatsAppLink(subItem)}
                        target="_blank"
                        rel="noopener noreferrer"
                        class="btn btn-small btn-whatsapp"
                      >
                        WhatsApp
                      </a>
                      <button class="btn-share" title="Copy link" on:click={() => shareProduct(getProductName(subItem))}>
                        {sharedItem === getProductName(subItem) ? '✓' : '🔗'}
                      </button>
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
          <div class="product-item" class:has-image={getProductImages(item)} class:product-sold-out={isOutOfStock(item)}>
            {#if getProductImages(item)}
              <div class="product-images">
                <button class="product-image" on:click={() => openLightbox(getProductImages(item), getProductName(item))}>
                  <img src={getProductImages(item)[activeImageIndexes[getProductName(item)] ?? 0]} alt={getProductName(item)} />
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
                    <span class="in-stock">In Stock: {getRemainingStock(item)}</span>
                    {#if getProductColors(item)}
                      <span class="color-dots" class:shake={needsColorItems[getProductName(item)]}>
                        {#each getProductColors(item) as color}
                          {@const soldOut = getColorRemaining(item, color) === 0}
                          {@const itemName = getProductName(item)}
                          <button class="color-dot" style="background: {color};" title={getColorName(color)} class:selected={selectedColors[itemName] === color} class:sold-out={soldOut} disabled={soldOut} on:click|stopPropagation={() => selectColor(itemName, color, getProductColors(item))}></button>
                        {/each}
                        {#if selectedColors[getProductName(item)]}
                          <span class="color-label">{getColorName(selectedColors[getProductName(item)])}</span>
                        {/if}
                      </span>
                    {/if}
                  {:else}
                    <span class="out-of-stock">Currently unavailable</span>
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
                <button class="qty-btn" on:click={() => incrementQty(getProductName(item), getRemainingStock(item))} disabled={selectedColors[getProductName(item)] && getProductColors(item) && getProductColors(item).length > 1 ? (displayQuantities[getProductName(item)] || 1) >= (getColorRemaining(item, selectedColors[getProductName(item)]) ?? 99) : getRemainingStock(item) != null && (displayQuantities[getProductName(item)] || 1) >= getRemainingStock(item)}>+</button>
              </div>
              <div class="product-actions">
                <button
                  class="btn btn-small btn-inquiry"
                  class:added={addedItems[getDisplayName(item)]}
                  class:needs-color={!selectedColors[getProductName(item)] && getProductColors(item) && getProductColors(item).length > 1}
                  disabled={isOutOfStock(item) || !!(selectedColors[getProductName(item)] && getProductColors(item) && getProductColors(item).length > 1 && getColorRemaining(item, selectedColors[getProductName(item)]) === 0)}
                  on:click={() => handleInquiryClick(item, category.name, getProductQuantity(item), getProductPrice(item))}
                >
                  {addedItems[getDisplayName(item)] ? `✓ Added (${addedItems[getDisplayName(item)]})` : 'Add to Cart'}
                </button>
                <a
                  href={getWhatsAppLink(item)}
                  target="_blank"
                  rel="noopener noreferrer"
                  class="btn btn-small btn-whatsapp"
                >
                  WhatsApp
                </a>
                <button class="btn-share" title="Copy link" on:click={() => shareProduct(getProductName(item))}>
                  {sharedItem === getProductName(item) ? '✓' : '🔗'}
                </button>
              </div>

            </div>
          </div>
        {/if}
      {/each}
    </div>
  </div>
{/each}
{/if}

<!-- Product Detail Modal -->
{#if productModal}
  <div class="product-modal-overlay" on:click={closeProductModal} role="dialog" aria-modal="true">
    <div class="product-modal" on:click|stopPropagation>
      <button class="product-modal-close" on:click={closeProductModal} aria-label="Close">×</button>
      <div class="product-modal-body">
        <!-- Image Gallery -->
        <div class="product-modal-gallery">
          <img
            src={productModal.images[modalImageIndex]}
            alt={productModal.productName}
            class="product-modal-img"
            on:touchstart={handleTouchStart}
            on:touchend={handleModalTouchEnd}
          />
          {#if productModal.images.length > 1}
            <button class="modal-nav modal-prev" on:click={modalPrevImage} aria-label="Previous image">‹</button>
            <button class="modal-nav modal-next" on:click={modalNextImage} aria-label="Next image">›</button>
            <div class="modal-dots">
              {#each productModal.images as _, i}
                <button class="modal-dot" class:active={i === modalImageIndex} on:click={() => modalImageIndex = i} aria-label="Image {i + 1}"></button>
              {/each}
            </div>
          {/if}
        </div>
        <!-- Product Info -->
        <div class="product-modal-info">
          <p class="product-modal-category">{productModal.categoryName}{productModal.subCategoryName ? ` › ${productModal.subCategoryName}` : ''}</p>
          <h2 class="product-modal-name">{productModal.productName}</h2>
          {#if productModal.price}
            <p class="product-modal-price">{formatPrice(productModal.price)}</p>
          {/if}
          {#if productModal.quantity !== null && productModal.quantity !== undefined}
            <p class="product-modal-stock">
              {#if getProductQuantity(productModal.product) > 0}
                <span class="in-stock">In Stock: {getRemainingStock(productModal.product)}</span>
              {:else}
                <span class="out-of-stock">Currently unavailable</span>
              {/if}
            </p>
          {:else}
            <p class="product-modal-note">Available · Imported from USA/Canada</p>
          {/if}
          {#if productModal.colors && getProductQuantity(productModal.product) > 0}
            <div class="product-modal-colors">
              <span class="color-dots" class:shake={needsColorItems[productModal.productName]}>
                {#each productModal.colors as color}
                  {@const soldOut = getColorRemaining(productModal.product, color) === 0}
                  <button class="color-dot" style="background: {color};" title={getColorName(color)} class:selected={selectedColors[productModal.productName] === color} class:sold-out={soldOut} disabled={soldOut} on:click|stopPropagation={() => { selectColor(productModal.productName, color, productModal.colors); const ci = productModal.colors.indexOf(color); if (ci !== -1 && ci < productModal.images.length) modalImageIndex = ci; }}></button>
                {/each}
                {#if selectedColors[productModal.productName]}
                  <span class="color-label">{getColorName(selectedColors[productModal.productName])}</span>
                {/if}
              </span>
            </div>
          {/if}
          <div class="quantity-selector" style="justify-content: flex-start; margin-top: 1rem;">
            <button class="qty-btn" on:click={() => decrementQty(productModal.productName)} disabled={(displayQuantities[productModal.productName] || 1) <= 1}>−</button>
            <span class="qty-value">{displayQuantities[productModal.productName] || 1}</span>
            <button class="qty-btn" on:click={() => incrementQty(productModal.productName, getRemainingStock(productModal.product))} disabled={selectedColors[productModal.productName] && productModal.colors && productModal.colors.length > 1 ? (displayQuantities[productModal.productName] || 1) >= (getColorRemaining(productModal.product, selectedColors[productModal.productName]) ?? 99) : getRemainingStock(productModal.product) != null && (displayQuantities[productModal.productName] || 1) >= getRemainingStock(productModal.product)}>+</button>
          </div>
          <div class="product-modal-actions">
            <button
              class="btn btn-inquiry"
              class:added={addedItems[getDisplayName(productModal.product)]}
              class:needs-color={!selectedColors[productModal.productName] && productModal.colors && productModal.colors.length > 1}
              disabled={!!(selectedColors[productModal.productName] && productModal.colors && productModal.colors.length > 1 && getColorRemaining(productModal.product, selectedColors[productModal.productName]) === 0)}
              on:click={() => handleInquiryClick(productModal.product, productModal.subCategoryName || productModal.categoryName, productModal.quantity, productModal.price)}
            >
              {addedItems[getDisplayName(productModal.product)] ? `✓ Added (${addedItems[getDisplayName(productModal.product)]})` : 'Add to Cart'}
            </button>
            <a href={getWhatsAppLink(productModal.product)} target="_blank" rel="noopener noreferrer" class="btn btn-whatsapp">WhatsApp</a>
            <button class="btn-share btn-share-modal" title="Copy link" on:click={() => shareProduct(productModal.productName)}>
              {sharedItem === productModal.productName ? '✓ Copied!' : '🔗 Copy Link'}
            </button>
          </div>
          <a href="/shop?category={productModal.categoryId}" class="product-modal-view-all">View all in {productModal.categoryName} →</a>
        </div>
      </div>
    </div>
  </div>
{/if}

<!-- Image Lightbox with Gallery -->
{#if lightboxImages.length > 0}
  <div class="lightbox-overlay" class:zoomed={isZoomed} on:click={closeLightbox} role="dialog" aria-modal="true">
    <div
      class="lightbox-content"
      class:zoomed={isZoomed}
      on:click|stopPropagation
      on:touchstart|nonpassive={handleTouchStart}
      on:touchmove|nonpassive={handleTouchMove}
      on:touchend={handleTouchEnd}
      on:wheel|nonpassive={handleLightboxWheel}
      on:mousedown={handleLightboxMouseDown}
      on:mousemove={handleLightboxMouseMove}
      on:mouseup={handleLightboxMouseUp}
      on:mouseleave={handleLightboxMouseUp}
      style="cursor: {isZoomed ? (isMouseDragging ? 'grabbing' : 'grab') : 'default'}"
    >
      <button class="lightbox-close" on:click={closeLightbox} aria-label="Close">×</button>
      {#if lightboxImages.length > 1 && !isZoomed}
        <button class="lightbox-nav lightbox-prev" on:click={prevImage} aria-label="Previous image">‹</button>
      {/if}
      <div class="lightbox-image-container">
        <img
          src={lightboxImages[lightboxIndex]}
          alt={lightboxAlt}
          style="transform: translate({panX}px, {panY}px) scale({zoomScale}); transform-origin: center center; transition: {isPanning || isMouseDragging ? 'none' : 'transform 0.2s ease'};"
          draggable="false"
          on:click={toggleZoom}
        />
      </div>
      {#if lightboxImages.length > 1 && !isZoomed}
        <button class="lightbox-nav lightbox-next" on:click={nextImage} aria-label="Next image">›</button>
        <div class="lightbox-dots">
          {#each lightboxImages as _, i}
            <button class="lightbox-dot" class:active={i === lightboxIndex} on:click={() => { resetZoom(); lightboxIndex = i; }} aria-label="Go to image {i + 1}"></button>
          {/each}
        </div>
      {/if}
      {#if !isZoomed}
        <p class="lightbox-caption">{lightboxAlt} {lightboxImages.length > 1 ? `(${lightboxIndex + 1}/${lightboxImages.length})` : ''}</p>
      {/if}
      <button class="lightbox-zoom-hint" on:click={toggleZoom}>
        {isZoomed ? '🔍− Pinch out or scroll to zoom more' : '🔍+ Pinch / scroll to zoom'}
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
    justify-content: flex-start;
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
    touch-action: manipulation;
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
    touch-action: manipulation;
    user-select: none;
    -webkit-user-select: none;
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
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    user-select: none;
    -webkit-user-select: none;
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
    will-change: transform;
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
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    user-select: none;
    -webkit-user-select: none;
  }

  @media (min-width: 769px) {
    .dropdown-option {
      justify-content: flex-start;
      text-align: left;
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
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    user-select: none;
    -webkit-user-select: none;
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
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    user-select: none;
    -webkit-user-select: none;
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
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    user-select: none;
    -webkit-user-select: none;
  }

  .nested-subcategory-header:hover,
  .nested-subcategory-header:active,
  .nested-subcategory-header:focus {
    background: #f0a500;
    color: #111111;
    outline: none;
  }

  .nested-subcategory.expanded .nested-subcategory-header {
    background: #c8890a;
    color: #111111;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    overflow: visible; /* allow ::before touch target to extend beyond button bounds */
  }

  .nested-subcategory.expanded .nested-subcategory-header h4 {
    color: #111111;
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
    max-width: 180px;
    height: 180px;
    position: relative;
    border: none;
    background: none;
    padding: 0;
    border-radius: 0;
    cursor: zoom-in;
    transition: transform 0.2s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    outline: none;
  }

  .product-image:hover {
    transform: scale(1.05);
  }

  .product-image img {
    width: 100%;
    height: 100%;
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
    padding: calc(1rem + env(safe-area-inset-top, 0px)) calc(1rem + env(safe-area-inset-right, 0px)) calc(1rem + env(safe-area-inset-bottom, 0px)) calc(1rem + env(safe-area-inset-left, 0px));
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
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    user-select: none;
    -webkit-user-select: none;
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
    min-width: 44px;
    min-height: 44px;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    user-select: none;
    -webkit-user-select: none;
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
    user-select: none;
    -webkit-user-select: none;
    align-items: center;
    justify-content: center;
  }

  .lightbox-image-container.zoomed {
    overflow: hidden;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior: contain;
    max-width: 95vw;
    max-height: 85vh;
    cursor: zoom-out;
    touch-action: none;
  }

  .lightbox-content img {
    cursor: zoom-in;
    user-select: none;
    -webkit-user-select: none;
    -webkit-user-drag: none;
    -webkit-touch-callout: none;
    touch-action: manipulation;
    pointer-events: auto;
  }

  .lightbox-content.zoomed img {
    cursor: grab;
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
    min-height: 44px;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    user-select: none;
    -webkit-user-select: none;
  }

  .lightbox-zoom-hint:hover {
    background: rgba(0, 0, 0, 0.8);
  }

  .product-info h4 {
    margin: 0 0 0.25rem 0;
    color: #2c3e50;
    font-size: 0.95rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.3;
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
    flex-wrap: wrap;
    align-items: center;
    gap: 5px;
    margin-left: 8px;
    vertical-align: middle;
    max-width: 100%;
  }

  .color-dot {
    display: inline-block;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2.5px solid #e0e0e0;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
    cursor: pointer;
    padding: 0;
    flex-shrink: 0;
    transition: transform 0.12s ease, border-color 0.12s ease, box-shadow 0.12s ease;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    -webkit-user-select: none;
    user-select: none;
    position: relative;
    will-change: transform;
  }

  /* Expand touch target to 44px for iOS/Android without changing visual size */
  .color-dot::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    min-width: 44px;
    min-height: 44px;
  }

  .color-dot:hover {
    transform: scale(1.25);
    border-color: #aaa;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
  }

  .color-dot:active {
    transform: scale(0.9);
    transition: transform 0.08s ease;
  }

  .color-dot.selected {
    border: 3px solid #2c3e50;
    box-shadow: 0 0 0 3px rgba(44, 62, 80, 0.4);
    transform: scale(1.2);
  }

  .color-dot.sold-out {
    opacity: 0.35;
    cursor: not-allowed;
    -webkit-filter: grayscale(60%);
    filter: grayscale(60%);
  }

  .product-sold-out {
    opacity: 0.55;
    -webkit-filter: grayscale(40%);
    filter: grayscale(40%);
    pointer-events: none;
  }
  .product-sold-out .btn-inquiry,
  .product-sold-out .qty-btn {
    cursor: not-allowed;
  }

  @keyframes shake-dots {
    0%, 100% { transform: translateX(0); }
    20% { transform: translateX(-5px); }
    40% { transform: translateX(5px); }
    60% { transform: translateX(-4px); }
    80% { transform: translateX(3px); }
  }
  .color-dots.shake {
    animation: shake-dots 0.5s ease;
    outline: 2px solid #e74c3c;
    border-radius: 4px;
  }

  /* No color selected: clickable so shake animation fires, but JS blocks add */
  .btn-inquiry.needs-color {
    cursor: pointer;
    -webkit-tap-highlight-color: transparent;
  }
  .btn-inquiry.needs-color:hover,
  .btn-inquiry.needs-color:active {
    transform: none;
    box-shadow: none;
  }

  /* Color selected but out of stock: fully non-interactive */
  .btn-inquiry:disabled {
    pointer-events: none;
    cursor: default;
    -webkit-tap-highlight-color: transparent;
  }
  .btn-inquiry:disabled:hover,
  .btn-inquiry:disabled:active {
    transform: none;
    box-shadow: none;
  }

  .color-label {
    font-size: 0.78rem;
    font-weight: 600;
    color: #2c3e50;
    background: #f0f4f8;
    border-radius: 4px;
    padding: 1px 6px;
    margin-left: 0;
    white-space: nowrap;
    flex-basis: 100%;
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

  .btn-share {
    background: transparent;
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 4px 8px;
    font-size: 0.85rem;
    cursor: pointer;
    color: #666;
    white-space: nowrap;
    min-height: 32px;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    -webkit-user-select: none;
    user-select: none;
    transition: border-color 0.2s, color 0.2s;
    flex-shrink: 0;
  }

  .btn-share:hover {
    border-color: #aaa;
    color: #333;
  }

  .btn-share-modal {
    padding: 8px 14px;
    font-size: 0.9rem;
    min-height: 40px;
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
    min-height: 44px;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    user-select: none;
    -webkit-user-select: none;
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
    touch-action: manipulation;
    user-select: none;
    -webkit-user-select: none;
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
      justify-content: flex-start;
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
    }

    .subcategory-section {
      margin: 0.25rem 0;
    }

    .subcategory-header {
      padding: 0.45rem 0.75rem;
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
      padding: 0.4rem;
    }

    .product-images {
      margin-bottom: 0.3rem;
    }

    /* Fixed image height on tablet */
    .product-image {
      height: 130px;
    }

    .product-image img {
      height: 100%;
      object-fit: contain;
    }

    .product-info h4 {
      font-size: 0.85rem;
      margin: 0 0 0.1rem 0;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .product-category-tag {
      font-size: 0.7rem;
    }

    .product-note {
      font-size: 0.75rem;
    }

    .product-price {
      font-size: 0.92rem;
      margin: 0.1rem 0;
    }

    .product-quantity {
      font-size: 0.75rem;
      margin: 0.1rem 0;
    }

    .in-stock {
      font-size: 0.75rem;
    }

    .quantity-selector {
      margin-bottom: 0.3rem;
      gap: 0.3rem;
    }

    /* Keep 44px min touch target but reduce visual size */
    .qty-btn {
      width: 28px;
      height: 28px;
      min-width: 44px;
      min-height: 44px;
      font-size: 1rem;
    }

    .qty-value {
      font-size: 0.9rem;
      min-width: 24px;
    }

    .product-actions {
      flex-direction: column;
      width: 100%;
      gap: 0.25rem;
    }

    .product-actions .btn {
      width: 100%;
      justify-content: center;
      min-height: 44px;
      padding: 9px 8px;
      font-size: 0.88rem;
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
      height: 80px;
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
    }

    .subcategory-section {
      margin: 0.125rem 0;
    }

    .subcategory-header {
      padding: 0.375rem 0.625rem;
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
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .product-note {
      font-size: 0.75rem;
    }

    .product-price {
      font-size: 1rem;
    }

    .product-image {
      max-width: 140px;
      height: 115px;
    }

    .product-image img {
      height: 100%;
      object-fit: contain;
    }

    .qty-btn {
      width: 26px;
      height: 26px;
      min-width: 44px;
      min-height: 44px;
    }

    .qty-value {
      min-width: 20px;
      font-size: 0.85rem;
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

  /* ── Random Product Showcase ─────────────────────────────────────────── */
  .showcase-wrapper {
    margin-bottom: 1rem;
  }

  .showcase-nav {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
  }

  .showcase-nav-btn {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: 2px solid #111111;
    background: white;
    color: #111111;
    font-size: 1.6rem;
    line-height: 1;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    user-select: none;
    -webkit-user-select: none;
    -webkit-transform: translateZ(0);
    transform: translateZ(0);
    will-change: transform;
  }

  .showcase-nav-btn:hover,
  .showcase-nav-btn:active {
    background: #f0a500;
    border-color: #f0a500;
    color: white;
  }

  .showcase-item-link {
    display: block;
    text-decoration: none;
    color: inherit;
    cursor: pointer;
    -webkit-tap-highlight-color: transparent;
  }

  .showcase-item-link:hover .showcase-thumb {
    transform: scale(1.04);
  }

  .showcase-item-link:hover h4 {
    color: #f0a500;
  }

  .showcase-thumb {
    width: 100%;
    height: 200px;
    border-radius: 0;
    object-fit: contain;
    display: block;
    transition: transform 0.2s ease;
  }

  @media (max-width: 600px) {
    .showcase-thumb {
      height: 150px;
    }
  }

  .showcase-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.5rem;
  }

  @media (min-width: 600px) {
    .showcase-grid {
      grid-template-columns: repeat(3, 1fr);
    }
  }

  @media (min-width: 900px) {
    .showcase-grid {
      grid-template-columns: repeat(4, 1fr);
    }
  }

  /* ── Product Detail Modal ─────────────────────────────────────────────── */
  .product-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.75);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: calc(1rem + env(safe-area-inset-top, 0px)) calc(1rem + env(safe-area-inset-right, 0px)) calc(1rem + env(safe-area-inset-bottom, 0px)) calc(1rem + env(safe-area-inset-left, 0px));
    -webkit-transform: translateZ(0);
    transform: translateZ(0);
    will-change: transform;
  }

  .product-modal {
    background: white;
    border-radius: 12px;
    max-width: 860px;
    width: 95vw;
    max-height: 90vh;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior: contain;
    position: relative;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
    -webkit-transform: translateZ(0);
    transform: translateZ(0);
    will-change: transform;
  }

  .product-modal-close {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: none;
    background: rgba(0, 0, 0, 0.08);
    color: #333;
    font-size: 1.6rem;
    line-height: 1;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    user-select: none;
    -webkit-user-select: none;
  }

  .product-modal-close:hover {
    background: rgba(0, 0, 0, 0.15);
  }

  .product-modal-body {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
    min-height: 400px;
  }

  .product-modal-gallery {
    position: relative;
    background: #f8f8f8;
    border-radius: 12px 0 0 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    min-height: 320px;
  }

  .product-modal-img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    max-height: 420px;
    padding: 1rem;
    display: block;
  }

  .modal-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(0, 0, 0, 0.45);
    border: none;
    color: white;
    font-size: 2rem;
    cursor: pointer;
    padding: 0.4rem 0.7rem;
    border-radius: 6px;
    z-index: 5;
    min-width: 44px;
    min-height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    user-select: none;
    -webkit-user-select: none;
  }

  .modal-nav:hover {
    background: rgba(0, 0, 0, 0.7);
  }

  .modal-prev { left: 6px; }
  .modal-next { right: 6px; }

  .modal-dots {
    position: absolute;
    bottom: 8px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 6px;
  }

  .modal-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.5);
    border: none;
    cursor: pointer;
    padding: 0;
    position: relative;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    user-select: none;
    -webkit-user-select: none;
  }

  .modal-dot::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    min-width: 44px;
    min-height: 44px;
  }

  .modal-dot.active {
    background: white;
  }

  .product-modal-info {
    padding: 2rem 1.75rem 2.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .product-modal-category {
    font-size: 0.8rem;
    color: #888;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }

  .product-modal-name {
    font-size: 1.2rem;
    font-weight: 700;
    color: #2c3e50;
    margin: 0;
    line-height: 1.3;
  }

  .product-modal-price {
    font-size: 1.4rem;
    font-weight: 700;
    color: #f0a500;
    margin: 0;
  }

  .product-modal-stock {
    margin: 0;
    font-size: 0.9rem;
  }

  .product-modal-note {
    margin: 0;
    font-size: 0.85rem;
    color: #666;
  }

  .product-modal-colors {
    margin-top: 0.25rem;
  }

  .product-modal-actions {
    display: flex;
    gap: 0.75rem;
    margin-top: 0.5rem;
    flex-wrap: wrap;
  }

  .product-modal-actions .btn {
    flex: 1;
    min-width: 110px;
    text-align: center;
    justify-content: center;
    min-height: 44px;
    padding: 10px 14px;
    display: flex;
    align-items: center;
  }

  .product-modal-view-all {
    display: inline-block;
    margin-top: 0.75rem;
    font-size: 0.9rem;
    color: #f0a500;
    text-decoration: none;
    font-weight: 600;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    user-select: none;
    -webkit-user-select: none;
    min-height: 44px;
    display: flex;
    align-items: center;
  }

  .product-modal-view-all:hover {
    text-decoration: underline;
  }

  @media (max-width: 600px) {
    .product-modal-body {
      grid-template-columns: 1fr;
    }

    .product-modal-gallery {
      border-radius: 12px 12px 0 0;
      min-height: 220px;
    }

    .product-modal-img {
      max-height: 260px;
    }

    .product-modal-info {
      padding: 1.25rem 1rem calc(2rem + env(safe-area-inset-bottom, 0px));
    }

    .product-modal-name {
      font-size: 1.05rem;
    }

    .product-modal-price {
      font-size: 1.2rem;
    }

    .product-modal-actions {
      flex-direction: column;
    }

    .product-modal-actions .btn {
      width: 100%;
      min-height: 48px;
    }
  }
</style>
