<script>
  import { onMount, onDestroy } from 'svelte';

  export let whatsappNumber = "237670358551";

  // Cart expiration time: 1 hour in milliseconds
  const CART_EXPIRY_MS = 60 * 60 * 1000;
  const STORAGE_KEY = 'americanSelectCart';

  // Inquiry items stored in this component
  let inquiryItems = [];
  let isOpen = false;
  let isDesktopExpanded = false; // Desktop cart starts collapsed
  let expiryCheckInterval;
  let hasLoadedFromStorage = false; // Prevent saving before initial load

  // Reactive total count - updates automatically when inquiryItems changes
  $: totalItems = inquiryItems.reduce((sum, item) => sum + (item.quantity || 1), 0);

  // Reactive total price - calculates sum of all items
  $: totalPrice = inquiryItems.reduce((sum, item) => {
    const price = parseFloat(item.price) || 0;
    const qty = item.quantity || 1;
    return sum + (price * qty);
  }, 0);

  // Format price with commas
  function formatPrice(price) {
    return price.toLocaleString('en-US');
  }

  // Save cart to localStorage whenever it changes (but only after initial load)
  $: if (typeof window !== 'undefined' && hasLoadedFromStorage) {
    saveCart(inquiryItems);
  }

  // Save cart to localStorage
  function saveCart(items) {
    if (items.length > 0) {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
    } else {
      localStorage.removeItem(STORAGE_KEY);
    }
  }

  // Load cart from localStorage
  function loadCart() {
    try {
      const saved = localStorage.getItem(STORAGE_KEY);
      if (saved) {
        return JSON.parse(saved);
      }
    } catch (e) {
      console.error('Error loading cart:', e);
    }
    return [];
  }

  // Remove expired items (older than 1 hour)
  function removeExpiredItems() {
    const now = Date.now();
    const validItems = inquiryItems.filter(item => {
      const addedAt = item.addedAt || now;
      return (now - addedAt) < CART_EXPIRY_MS;
    });

    if (validItems.length !== inquiryItems.length) {
      inquiryItems = validItems;
    }
  }

  // Listen for custom events from ShopFilter
  onMount(() => {
    // Load saved cart on mount
    const savedItems = loadCart();
    if (savedItems.length > 0) {
      inquiryItems = savedItems;
      // Remove any expired items immediately
      removeExpiredItems();

      // Notify ShopFilter about loaded cart items
      setTimeout(() => {
        window.dispatchEvent(new CustomEvent('cart-loaded', {
          detail: { items: inquiryItems }
        }));
      }, 100);
    }

    // Mark as loaded so reactive statement can now save changes
    hasLoadedFromStorage = true;

    // Check for expired items every minute
    expiryCheckInterval = setInterval(removeExpiredItems, 60 * 1000);

    // Add item to inquiry
    window.addEventListener('add-to-inquiry', (e) => {
      const item = e.detail;
      if (!inquiryItems.find(i => i.name === item.name)) {
        inquiryItems = [...inquiryItems, {
          ...item,
          quantity: item.quantity || 1,
          maxStock: item.maxStock || 99,
          addedAt: Date.now()
        }];
      }
    });

    // Remove item from inquiry
    window.addEventListener('remove-from-inquiry', (e) => {
      const item = e.detail;
      inquiryItems = inquiryItems.filter(i => i.name !== item.name);
    });

    // Update cart quantity from product listing
    window.addEventListener('update-cart-qty', (e) => {
      const { name, quantity } = e.detail;
      inquiryItems = inquiryItems.map(item => {
        if (item.name === name) {
          return { ...item, quantity };
        }
        return item;
      });
    });
  });

  onDestroy(() => {
    if (expiryCheckInterval) {
      clearInterval(expiryCheckInterval);
    }
  });

  // Update quantity for an item (respecting stock limits)
  function updateItemQty(itemName, delta) {
    let newQuantity;
    inquiryItems = inquiryItems.map(item => {
      if (item.name === itemName) {
        const currentQty = item.quantity || 1;
        const maxQty = item.maxStock || 99;
        newQuantity = Math.max(1, Math.min(maxQty, currentQty + delta));
        return { ...item, quantity: newQuantity };
      }
      return item;
    });

    // Sync with ShopFilter
    if (newQuantity !== undefined) {
      window.dispatchEvent(new CustomEvent('cart-qty-updated', {
        detail: { name: itemName, quantity: newQuantity }
      }));
    }
  }

  // Check if item is at max stock
  function isAtMaxStock(item) {
    return item.maxStock && (item.quantity || 1) >= item.maxStock;
  }

  function removeItem(itemName) {
    inquiryItems = inquiryItems.filter(i => i.name !== itemName);
    // Notify ShopFilter that item was removed
    window.dispatchEvent(new CustomEvent('item-removed-from-cart', {
      detail: { name: itemName }
    }));
  }

  function clearAll() {
    inquiryItems = [];
    localStorage.removeItem(STORAGE_KEY);
    isOpen = false;
    updateBodyClass();
  }

  function sendViaWhatsApp() {
    if (inquiryItems.length === 0) return;

    const itemList = inquiryItems.map(item => {
      const qty = item.quantity || 1;
      const itemTotal = item.price ? ` - ${formatPrice(item.price * qty)} FCFA` : '';
      return `• ${item.name} (${qty})${itemTotal}`;
    }).join('\n');

    const message = `Hi! I'm interested in ordering (${totalItems} items):\n\n${itemList}\n\nEstimated Total: ${formatPrice(totalPrice)} FCFA\n\nPlease confirm availability and final price.`;

    const encodedMessage = encodeURIComponent(message);
    const whatsappUrl = `https://wa.me/${whatsappNumber}?text=${encodedMessage}`;

    window.open(whatsappUrl, '_blank');
  }

  function toggleBasket() {
    isOpen = !isOpen;
    updateBodyClass();
  }

  function toggleDesktopCart() {
    isDesktopExpanded = !isDesktopExpanded;
  }

  function closeBasket() {
    isOpen = false;
    updateBodyClass();
  }

  function viewProduct(itemName) {
    // Navigate to shop page with search query for this product
    const searchQuery = encodeURIComponent(itemName);
    window.location.href = `/shop?search=${searchQuery}`;
  }

  function updateBodyClass() {
    if (typeof document !== 'undefined') {
      if (isOpen) {
        document.body.classList.add('cart-open');
      } else {
        document.body.classList.remove('cart-open');
      }
    }
  }
</script>

<!-- Desktop Sidebar Cart -->
{#if inquiryItems.length > 0}
  {#if isDesktopExpanded}
    <div class="cart-sidebar desktop-only">
      <div class="basket-header">
        <h3>🛒 Your Cart ({totalItems} {totalItems === 1 ? 'item' : 'items'})</h3>
        <button class="collapse-btn" on:click={toggleDesktopCart} aria-label="Collapse cart">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>
      </div>

      <div class="basket-items">
        {#each inquiryItems as item (item.name)}
          <div class="basket-item-with-image">
            {#if item.image}
              <button class="item-image item-image-small item-clickable" on:click={() => viewProduct(item.name)} aria-label="View {item.name}">
                <img src={item.image} alt={item.name} />
              </button>
            {:else}
              <button class="item-image item-image-small item-image-placeholder item-clickable" on:click={() => viewProduct(item.name)} aria-label="View {item.name}">
                <span>📦</span>
              </button>
            {/if}
            <div class="item-details">
              <button class="item-name item-name-clickable" on:click={() => viewProduct(item.name)}>{item.name}</button>
              {#if item.price}
                <span class="item-price">{formatPrice(item.price * (item.quantity || 1))} FCFA</span>
              {/if}
              <div class="item-qty-controls">
                <button class="item-qty-btn" on:click={() => updateItemQty(item.name, -1)} disabled={(item.quantity || 1) <= 1}>−</button>
                <span class="item-qty">{item.quantity || 1}</span>
                <button class="item-qty-btn" on:click={() => updateItemQty(item.name, 1)} disabled={isAtMaxStock(item)}>+</button>
              </div>
            </div>
            <button class="remove-btn" on:click={() => removeItem(item.name)}>✕</button>
          </div>
        {/each}
      </div>

      <div class="basket-total">
        <span class="total-label">Total:</span>
        <span class="total-price">{formatPrice(totalPrice)} FCFA</span>
      </div>

      <div class="basket-actions">
        <button class="clear-btn" on:click={clearAll}>Clear All</button>
        <button class="send-btn" on:click={sendViaWhatsApp}>
          <span class="whatsapp-icon">💬</span>
          Order Via WhatsApp
        </button>
      </div>
    </div>
  {:else}
    <button class="cart-collapsed desktop-only" on:click={toggleDesktopCart} aria-label="Expand cart">
      <span class="cart-collapsed-icon">🛒</span>
      {#key totalItems}
        <span class="cart-collapsed-count">{totalItems}</span>
      {/key}
    </button>
  {/if}
{/if}

<!-- Mobile Floating Basket Button -->
{#if inquiryItems.length > 0}
  <div class="inquiry-basket-float mobile-only">
    {#if !isOpen}
      <button class="basket-toggle" on:click={toggleBasket}>
        <span class="basket-icon">🛒</span>
        {#key totalItems}
          <span class="basket-count">{totalItems}</span>
        {/key}
      </button>
    {/if}

    <!-- Expanded Full-Screen Cart -->
    {#if isOpen}
      <div class="mobile-cart-overlay">
        <div class="mobile-cart-expanded">
          <div class="basket-header">
            <h3>🛒 Your Cart ({totalItems} {totalItems === 1 ? 'item' : 'items'})</h3>
            <button class="collapse-cart-btn" on:click={closeBasket} aria-label="Collapse cart">
              <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </div>

          <div class="basket-items">
            {#each inquiryItems as item (item.name)}
              <div class="basket-item-with-image">
                {#if item.image}
                  <button class="item-image item-clickable" on:click={() => viewProduct(item.name)} aria-label="View {item.name}">
                    <img src={item.image} alt={item.name} />
                  </button>
                {:else}
                  <button class="item-image item-image-placeholder item-clickable" on:click={() => viewProduct(item.name)} aria-label="View {item.name}">
                    <span>📦</span>
                  </button>
                {/if}
                <div class="item-details">
                  <button class="item-name item-name-clickable" on:click={() => viewProduct(item.name)}>{item.name}</button>
                  {#if item.price}
                    <span class="item-price">{formatPrice(item.price * (item.quantity || 1))} FCFA</span>
                  {/if}
                  <div class="item-qty-controls">
                    <button class="item-qty-btn" on:click={() => updateItemQty(item.name, -1)} disabled={(item.quantity || 1) <= 1}>−</button>
                    <span class="item-qty">{item.quantity || 1}</span>
                    <button class="item-qty-btn" on:click={() => updateItemQty(item.name, 1)} disabled={isAtMaxStock(item)}>+</button>
                  </div>
                </div>
                <button class="remove-btn" on:click={() => removeItem(item.name)}>✕</button>
              </div>
            {/each}
          </div>

          <div class="basket-footer">
            <div class="basket-total">
              <span class="total-label">Total:</span>
              <span class="total-price">{formatPrice(totalPrice)} FCFA</span>
            </div>

            <div class="basket-actions">
              <button class="clear-btn" on:click={clearAll}>Clear All</button>
              <button class="send-btn" on:click={sendViaWhatsApp}>
                <span class="whatsapp-icon">💬</span>
                Order Via WhatsApp
              </button>
            </div>
          </div>
        </div>
      </div>
    {/if}
  </div>
{/if}

<style>
  /* Desktop Sidebar Cart */
  .cart-sidebar {
    position: fixed;
    top: 100px;
    right: 20px;
    width: 300px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    z-index: 998;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    max-height: calc(100vh - 140px);
    display: flex;
    flex-direction: column;
  }

  .cart-sidebar .basket-header {
    border-radius: 12px 12px 0 0;
    justify-content: space-between;
  }

  .collapse-btn {
    background: none;
    border: none;
    color: #6c757d;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
  }

  .collapse-btn:hover {
    background: #e9ecef;
    color: #2c3e50;
  }

  /* Collapsed cart button */
  .cart-collapsed {
    position: fixed;
    bottom: 100px;
    right: 20px;
    width: 60px;
    height: 60px;
    background: #3498db;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 15px rgba(52, 152, 219, 0.4);
    z-index: 998;
    transition: all 0.3s ease;
  }

  .cart-collapsed:hover {
    background: #2980b9;
    transform: scale(1.05);
    box-shadow: 0 6px 20px rgba(52, 152, 219, 0.5);
  }

  .cart-collapsed-icon {
    font-size: 1.5rem;
  }

  .cart-collapsed-count {
    position: absolute;
    top: -2px;
    right: -2px;
    background: #e74c3c;
    color: white;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: bold;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    animation: countPop 0.3s ease;
  }

  .cart-sidebar .basket-items {
    flex: 1;
    overflow-y: auto;
  }

  .cart-sidebar .basket-actions {
    border-radius: 0 0 12px 12px;
  }

  /* Show/hide based on screen size */
  .desktop-only {
    display: flex;
    flex-direction: column;
  }

  .mobile-only {
    display: none;
  }

  @media (max-width: 1024px) {
    .desktop-only {
      display: none;
    }

    .mobile-only {
      display: block;
    }

    .mobile-order-bar.mobile-only {
      display: flex;
    }
  }

  /* Mobile Fixed Bottom Order Bar */
  .mobile-order-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 12px 16px;
    padding-bottom: calc(12px + env(safe-area-inset-bottom));
    background: white;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15);
    z-index: 1000;
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .mobile-item-count {
    font-size: 1rem;
    color: #2c3e50;
    font-weight: 600;
    text-align: center;
    padding-bottom: 6px;
  }

  .mobile-total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 8px;
    border-top: 1px solid #e9ecef;
  }

  .mobile-total-label {
    font-size: 1rem;
    font-weight: 600;
    color: #2c3e50;
  }

  .mobile-total-price {
    font-size: 1.3rem;
    font-weight: 700;
    color: #3498db;
  }

  .mobile-order-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px 20px;
    background: #25D366;
    color: white;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    font-size: 1.1rem;
    font-weight: 600;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    transition: background 0.2s ease;
  }

  .mobile-order-btn:hover,
  .mobile-order-btn:active {
    background: #128C7E;
  }

  .mobile-order-btn .whatsapp-icon {
    font-size: 1.3rem;
  }

  .inquiry-basket-float {
    position: fixed;
    bottom: 80px;
    right: 20px;
    z-index: 999;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  }

  .basket-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    background: #3498db;
    color: white;
    border: none;
    width: 50px;
    height: 50px;
    padding: 0;
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(52, 152, 219, 0.4);
    transition: all 0.3s ease;
    font-size: 0.95rem;
    font-weight: 600;
    position: relative;
  }

  .basket-toggle:hover {
    background: #2980b9;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(52, 152, 219, 0.5);
  }

  .basket-icon {
    font-size: 1.3rem;
  }

  .basket-count {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #e74c3c;
    color: white;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: bold;
    animation: countPop 0.3s ease;
  }

  @keyframes countPop {
    0% { transform: scale(1); }
    50% { transform: scale(1.3); }
    100% { transform: scale(1); }
  }

  .basket-label {
    display: none;
  }

  /* Mobile Full-Screen Cart */
  .mobile-cart-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1001;
    display: flex;
    align-items: flex-end;
    animation: fadeIn 0.2s ease;
  }

  @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }

  .mobile-cart-expanded {
    width: 100%;
    max-height: 85vh;
    background: white;
    border-radius: 20px 20px 0 0;
    display: flex;
    flex-direction: column;
    animation: slideUpFull 0.3s ease;
  }

  @keyframes slideUpFull {
    from {
      transform: translateY(100%);
    }
    to {
      transform: translateY(0);
    }
  }

  .mobile-cart-expanded .basket-header {
    border-radius: 20px 20px 0 0;
    padding: 18px 20px;
  }

  .collapse-cart-btn {
    background: #e9ecef;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    transition: all 0.2s ease;
  }

  .collapse-cart-btn:hover {
    background: #dee2e6;
    color: #2c3e50;
  }

  /* Cart item with image */
  .basket-item-with-image {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    border-bottom: 1px solid #f1f1f1;
    gap: 12px;
  }

  .basket-item-with-image:last-child {
    border-bottom: none;
  }

  .item-image {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
    background: #f8f9fa;
  }

  .item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .item-image-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
  }

  /* Smaller image for desktop sidebar */
  .item-image-small {
    width: 45px;
    height: 45px;
    border-radius: 6px;
  }

  .item-image-small.item-image-placeholder {
    font-size: 1.2rem;
  }

  .basket-item-with-image .item-details {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  .basket-item-with-image .item-qty-controls {
    margin-top: 6px;
  }

  .basket-item-with-image .remove-btn {
    align-self: flex-start;
  }

  .basket-panel {
    position: absolute;
    bottom: 60px;
    right: 0;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    width: 320px;
    max-height: 450px;
    overflow: hidden;
    animation: slideUp 0.2s ease;
    display: flex;
    flex-direction: column;
  }

  .basket-footer {
    flex-shrink: 0;
    background: white;
    border-top: 1px solid #e9ecef;
  }

  @keyframes slideUp {
    from {
      opacity: 0;
      transform: translateY(10px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .basket-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
  }

  .basket-header h3 {
    margin: 0;
    font-size: 1rem;
    color: #2c3e50;
  }

  .close-btn {
    background: none;
    border: none;
    font-size: 1.2rem;
    color: #6c757d;
    cursor: pointer;
    padding: 0;
    line-height: 1;
  }

  .close-btn:hover {
    color: #2c3e50;
  }

  .basket-items {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 10px 0;
    min-height: 0;
    -webkit-overflow-scrolling: touch;
    scroll-behavior: smooth;
    overscroll-behavior: contain;
  }

  .basket-item {
    display: flex;
    align-items: center;
    padding: 10px 20px;
    border-bottom: 1px solid #f1f1f1;
    gap: 10px;
  }

  .basket-item:last-child {
    border-bottom: none;
  }

  .item-details {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
  }

  .item-name {
    font-size: 1rem;
    font-weight: 500;
    color: #2c3e50;
  }

  .item-name-clickable {
    background: none;
    border: none;
    padding: 0;
    text-align: left;
    cursor: pointer;
    transition: color 0.2s ease;
  }

  .item-name-clickable:hover {
    color: #3498db;
    text-decoration: underline;
  }

  .item-clickable {
    cursor: pointer;
    border: none;
    padding: 0;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }

  .item-clickable:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  }

  .item-category {
    font-size: 0.8rem;
    color: #6c757d;
    background: #e9ecef;
    padding: 2px 8px;
    border-radius: 10px;
    width: fit-content;
  }

  .item-price {
    font-size: 0.95rem;
    font-weight: 600;
    color: #3498db;
  }

  .item-qty-controls {
    display: flex;
    align-items: center;
    gap: 4px;
  }

  .item-qty-btn {
    width: 24px;
    height: 24px;
    border: 1px solid #3498db;
    background: white;
    color: #3498db;
    font-size: 1rem;
    font-weight: bold;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
  }

  .item-qty-btn:hover:not(:disabled) {
    background: #3498db;
    color: white;
  }

  .item-qty-btn:disabled {
    border-color: #ccc;
    color: #ccc;
    cursor: not-allowed;
  }

  .item-qty {
    min-width: 24px;
    text-align: center;
    font-size: 0.9rem;
    font-weight: 600;
    color: #2c3e50;
  }

  .remove-btn {
    background: none;
    border: none;
    color: #dc3545;
    cursor: pointer;
    font-size: 0.9rem;
    padding: 2px 6px;
    border-radius: 4px;
    transition: background 0.2s;
  }

  .remove-btn:hover {
    background: #fee;
  }

  .basket-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 20px;
    background: #e8f4fc;
    border-top: 1px solid #e9ecef;
  }

  .total-label {
    font-size: 1.1rem;
    font-weight: 600;
    color: #2c3e50;
  }

  .total-price {
    font-size: 1.25rem;
    font-weight: 700;
    color: #3498db;
  }

  .basket-actions {
    display: flex;
    gap: 10px;
    padding: 15px 20px;
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
  }

  .clear-btn {
    flex: 1;
    padding: 10px;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    color: #6c757d;
    cursor: pointer;
    font-size: 0.85rem;
    transition: all 0.2s;
  }

  .clear-btn:hover {
    background: #f8f9fa;
    color: #2c3e50;
  }

  .send-btn {
    flex: 2;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 15px;
    background: #25D366;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.2s;
  }

  .send-btn:hover {
    background: #128C7E;
  }

  .whatsapp-icon {
    font-size: 1.1rem;
  }

  /* Mobile adjustments */
  @media (max-width: 768px) {
    .inquiry-basket-float {
      bottom: 75px;
      right: 15px;
    }

    .mobile-order-bar {
      display: flex;
      padding: 10px 12px;
      padding-bottom: calc(10px + env(safe-area-inset-bottom));
    }

    .mobile-item-count {
      font-size: 1.05rem;
    }

    .mobile-order-btn {
      padding: 14px 18px;
      font-size: 1.1rem;
    }

    .mobile-total-label {
      font-size: 1.05rem;
    }

    .mobile-total-price {
      font-size: 1.3rem;
    }

    .basket-toggle {
      width: 48px;
      height: 48px;
    }

    .basket-panel {
      width: 320px;
      max-height: 420px;
    }

    .basket-items {
      padding: 10px 0;
    }

    .basket-header h3 {
      font-size: 1.1rem;
    }

    .basket-item {
      padding: 12px 18px;
    }

    .item-name {
      font-size: 1.05rem;
    }

    .item-price {
      font-size: 1rem;
    }

    .item-qty {
      font-size: 1rem;
    }

    .item-qty-btn {
      width: 32px;
      height: 32px;
      min-width: 32px;
      font-size: 1.1rem;
    }

    .total-label {
      font-size: 1.15rem;
    }

    .total-price {
      font-size: 1.3rem;
    }

    .basket-actions {
      padding: 14px 18px;
    }

    .clear-btn {
      font-size: 0.95rem;
      padding: 12px;
    }

    .send-btn {
      font-size: 1rem;
      padding: 12px 16px;
    }

    .close-btn {
      width: 36px;
      height: 36px;
      font-size: 1.4rem;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    /* Mobile expanded cart styles */
    .mobile-cart-expanded {
      max-height: 80vh;
    }

    .mobile-cart-expanded .basket-header {
      padding: 16px 18px;
    }

    .mobile-cart-expanded .basket-header h3 {
      font-size: 1.15rem;
    }

    .item-image {
      width: 65px;
      height: 65px;
    }

    .basket-item-with-image {
      padding: 14px 18px;
    }

    .basket-item-with-image .item-name {
      font-size: 1.05rem;
    }

    .basket-item-with-image .item-price {
      font-size: 1rem;
    }

    .basket-item-with-image .item-qty-btn {
      width: 34px;
      height: 34px;
      font-size: 1.1rem;
    }

    .basket-item-with-image .item-qty {
      font-size: 1rem;
      min-width: 28px;
    }

    .mobile-cart-expanded .basket-footer {
      padding-bottom: env(safe-area-inset-bottom);
    }
  }

  @media (max-width: 400px) {
    .inquiry-basket-float {
      bottom: 70px;
      right: 10px;
    }

    .basket-panel {
      position: fixed;
      bottom: 70px;
      right: 0;
      left: 0;
      width: 100%;
      max-height: calc(65vh - 70px);
      border-radius: 16px 16px 0 0;
      display: flex;
      flex-direction: column;
    }

    .basket-toggle {
      width: 46px;
      height: 46px;
    }

    .basket-icon {
      font-size: 1.2rem;
    }

    .basket-count {
      width: 20px;
      height: 20px;
      font-size: 0.7rem;
      top: -3px;
      right: -3px;
    }

    .basket-header {
      padding: 14px 16px;
    }

    .basket-header h3 {
      font-size: 1.05rem;
    }

    .basket-items {
      -webkit-overflow-scrolling: touch;
      padding: 8px 0;
      scroll-behavior: smooth;
    }

    .basket-item {
      padding: 12px 16px;
    }

    .item-name {
      font-size: 1rem;
    }

    .item-price {
      font-size: 0.95rem;
    }

    .item-category {
      font-size: 0.8rem;
    }

    .item-qty {
      font-size: 1rem;
    }

    .item-qty-btn {
      width: 36px;
      height: 36px;
      min-width: 36px;
      font-size: 1.2rem;
    }

    .remove-btn {
      padding: 8px 12px;
      font-size: 1.1rem;
    }

    .total-label {
      font-size: 1.1rem;
    }

    .total-price {
      font-size: 1.25rem;
    }

    .basket-actions {
      flex-direction: column;
      gap: 10px;
      padding: 14px 16px;
    }

    .clear-btn, .send-btn {
      flex: none;
      width: 100%;
      min-height: 48px;
    }

    .clear-btn {
      font-size: 1rem;
    }

    .send-btn {
      font-size: 1.05rem;
    }

    .mobile-order-bar {
      display: flex;
      padding: 10px 12px;
      padding-bottom: calc(10px + env(safe-area-inset-bottom));
    }

    .mobile-item-count {
      font-size: 1rem;
      padding-bottom: 6px;
    }

    .mobile-order-btn {
      padding: 12px 16px;
      font-size: 1.05rem;
    }

    .mobile-total-row {
      padding-top: 8px;
    }

    .mobile-total-label {
      font-size: 1rem;
    }

    .mobile-total-price {
      font-size: 1.2rem;
    }

    /* Small phone expanded cart */
    .mobile-cart-expanded {
      max-height: 85vh;
    }

    .item-image {
      width: 55px;
      height: 55px;
    }

    .basket-item-with-image {
      padding: 12px 16px;
      gap: 10px;
    }

    .basket-item-with-image .item-name {
      font-size: 1rem;
    }

    .basket-item-with-image .item-price {
      font-size: 0.95rem;
    }

    .basket-item-with-image .item-qty-btn {
      width: 32px;
      height: 32px;
      font-size: 1rem;
    }
  }

  /* Touch-friendly improvements */
  @media (hover: none) and (pointer: coarse) {
    .basket-toggle:hover {
      transform: none;
    }

    .item-qty-btn, .remove-btn, .clear-btn, .send-btn, .close-btn {
      min-height: 44px;
      min-width: 44px;
    }
  }
</style>
