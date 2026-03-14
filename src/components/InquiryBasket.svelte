<script>
  import { onMount, onDestroy } from 'svelte';

  export let whatsappNumber = "237670358551";
  export let formspreeEndpoint = "https://formspree.io/f/xwvrbryr";

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
  // Generate a shareable cart link encoding current items
  function generateCartLink() {
    const data = inquiryItems.map(i => ({ n: i.name, p: i.price || 0, q: i.quantity || 1, img: i.image || '' }));
    try {
      const encoded = btoa(unescape(encodeURIComponent(JSON.stringify(data))));
      return `https://americanselect.net/shop/?cart=${encoded}`;
    } catch { return ''; }
  }

  // Load cart items from URL ?cart= param on mount
  function loadCartFromUrl() {
    try {
      const params = new URLSearchParams(window.location.search);
      const cartParam = params.get('cart');
      if (!cartParam) return false;
      const data = JSON.parse(decodeURIComponent(escape(atob(cartParam))));
      if (!Array.isArray(data) || !data.length) return false;
      const items = data.map(d => ({
        name: d.n, price: d.p || 0, quantity: d.q || 1,
        image: d.img || '', maxStock: 99, addedAt: Date.now()
      }));
      inquiryItems = items;
      isConfirmationMode = true;
      // Clean URL
      const url = new URL(window.location.href);
      url.searchParams.delete('cart');
      window.history.replaceState({}, '', url.toString());
      // Open basket so customer sees their items
      isOpen = true;
      updateBodyClass();
      return true;
    } catch { return false; }
  }

  onMount(() => {
    // Check URL for pre-loaded cart first
    const loadedFromUrl = loadCartFromUrl();

    // Load saved cart on mount (skip if URL cart was loaded)
    const savedItems = loadedFromUrl ? [] : loadCart();
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
      const existing = inquiryItems.find(i => i.name === item.name);
      if (existing) {
        // Already in cart — add the new qty on top, capped at maxStock
        inquiryItems = inquiryItems.map(i => {
          if (i.name !== item.name) return i;
          const max = i.maxStock || 99;
          const newQty = Math.min((i.quantity || 1) + (item.quantity || 1), max);
          return { ...i, quantity: newQty };
        });
      } else {
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

  async function sendViaWhatsApp() {
    if (inquiryItems.length === 0) return;

    const itemList = inquiryItems.map(item => {
      const qty = item.quantity || 1;
      const itemTotal = item.price ? ` - ${formatPrice(item.price * qty)} FCFA` : '';
      return `• ${item.name} (${qty})${itemTotal}`;
    }).join('\n');

    const cartLink = generateCartLink();
    const paymentLine = selectedPayment ? `\n💳 Preferred Payment: ${selectedPayment}` : '';
    const message = `Hi! I'm interested in ordering (${totalItems} items):\n\n${itemList}\n\nEstimated Total: ${formatPrice(totalPrice)} FCFA${paymentLine}\n\nPlease confirm availability and final price.\n\n🔗 My cart: ${cartLink}`;

    // Send to Formspree for email notification
    try {
      await fetch(formspreeEndpoint, {
        method: 'POST',
        body: JSON.stringify({
          _subject: `New Order - ${totalItems} item${totalItems === 1 ? '' : 's'} - ${formatPrice(totalPrice)} FCFA`,
          order_type: 'WhatsApp Order Request',
          total_items: totalItems,
          estimated_total: `${formatPrice(totalPrice)} FCFA`,
          order_details: itemList,
          payment_method: selectedPayment || 'Not specified',
          cart_link: cartLink,
          full_message: message
        }),
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        }
      });
    } catch (e) {
      console.error('Formspree error:', e);
    }

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

  let selectedPayment = '';
  let isConfirmationMode = false; // true when cart was loaded from admin's reply link

  function selectPayment(method) {
    selectedPayment = selectedPayment === method ? '' : method;
  }

  async function confirmOrder() {
    if (inquiryItems.length === 0) return;

    const itemList = inquiryItems.map(item => {
      const qty = item.quantity || 1;
      const itemTotal = item.price ? ` — ${formatPrice(item.price * qty)} FCFA` : '';
      return `• ${item.name} (×${qty})${itemTotal}`;
    }).join('\n');

    const message = `✅ ORDER CONFIRMED\n\n${itemList}\n\nTotal: ${formatPrice(totalPrice)} FCFA\n💳 Payment: ${selectedPayment || 'To be confirmed'}\n\nPlease process my order. Thank you!`;

    // Create pending order in database
    let orderRef = '';
    try {
      const orderRes = await fetch('/api/orders.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'create',
          items: inquiryItems.map(i => ({ name: i.name, price: i.price || 0, quantity: i.quantity || 1 })),
          total: totalPrice,
          payment_method: selectedPayment || 'Not specified'
        })
      });
      const orderData = await orderRes.json();
      if (orderData.order_ref) orderRef = orderData.order_ref;
    } catch (e) { console.error('Order create error:', e); }

    // Save order for thank-you page
    localStorage.setItem('confirmedOrder', JSON.stringify({
      items: inquiryItems.map(i => ({ name: i.name, price: i.price || 0, quantity: i.quantity || 1, image: i.image || '' })),
      total: totalPrice,
      paymentMethod: selectedPayment || 'To be confirmed',
      orderRef,
      timestamp: Date.now()
    }));

    // Notify via Formspree
    try {
      await fetch(formspreeEndpoint, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({
          _subject: `✅ ORDER CONFIRMED — ${totalItems} item${totalItems === 1 ? '' : 's'} — ${formatPrice(totalPrice)} FCFA`,
          order_type: 'Confirmed Order',
          total_items: totalItems,
          estimated_total: `${formatPrice(totalPrice)} FCFA`,
          payment_method: selectedPayment || 'Not specified',
          order_details: itemList
        })
      });
    } catch (e) { console.error('Formspree error:', e); }

    // Open WhatsApp with confirmation
    window.open(`https://wa.me/${whatsappNumber}?text=${encodeURIComponent(message)}`, '_blank');

    // Clear cart and go to confirmation page
    localStorage.removeItem(STORAGE_KEY);
    window.location.href = '/thank-you/?order=confirmed';
  }

  let previewItem = null;

  function viewProduct(itemName) {
    const item = inquiryItems.find(i => i.name === itemName);
    if (item && item.image) {
      previewItem = item;
    }
  }

  function closePreview() {
    previewItem = null;
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

      <div class="pay-method-section">
        <div class="pay-method-label">How would you like to pay?</div>
        <div class="pay-method-grid">
          <button class="pay-method-btn" class:selected={selectedPayment === 'Cash'} on:click={() => selectPayment('Cash')}>💵 Cash</button>
          <button class="pay-method-btn" class:selected={selectedPayment === 'MTN MoMo'} on:click={() => selectPayment('MTN MoMo')}>🟡 MTN MoMo</button>
          <button class="pay-method-btn" class:selected={selectedPayment === 'Orange Money'} on:click={() => selectPayment('Orange Money')}>🟠 Orange Money</button>
          <button class="pay-method-btn" class:selected={selectedPayment === 'Not sure yet'} on:click={() => selectPayment('Not sure yet')}>🤔 Not sure yet</button>
        </div>
      </div>

      <div class="basket-actions">
        <button class="clear-btn" on:click={clearAll}>Clear All</button>
        <button class="send-btn" on:click={sendViaWhatsApp}>
          <span class="whatsapp-icon">💬</span>
          {isConfirmationMode ? 'Message Us' : 'Order Via WhatsApp'}
        </button>
      </div>
      {#if isConfirmationMode}
        <div class="confirm-order-section">
          <button class="confirm-order-btn" on:click={confirmOrder}>
            ✅ Confirm My Order
          </button>
          <p class="confirm-hint">Tap to finalise and send your order confirmation</p>
        </div>
      {/if}
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
  </div>

  <!-- Expanded Full-Screen Cart — outside float div so parent transform doesn't trap position:fixed -->
  {#if isOpen}
    <div class="mobile-cart-overlay mobile-only">
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

          <div class="pay-method-section">
            <div class="pay-method-label">How would you like to pay?</div>
            <div class="pay-method-grid">
              <button class="pay-method-btn" class:selected={selectedPayment === 'Cash'} on:click={() => selectPayment('Cash')}>💵 Cash</button>
              <button class="pay-method-btn" class:selected={selectedPayment === 'MTN MoMo'} on:click={() => selectPayment('MTN MoMo')}>🟡 MTN MoMo</button>
              <button class="pay-method-btn" class:selected={selectedPayment === 'Orange Money'} on:click={() => selectPayment('Orange Money')}>🟠 Orange Money</button>
              <button class="pay-method-btn" class:selected={selectedPayment === 'Not sure yet'} on:click={() => selectPayment('Not sure yet')}>🤔 Not sure yet</button>
            </div>
          </div>

          <div class="basket-actions">
            <button class="clear-btn" on:click={clearAll}>Clear All</button>
            <button class="send-btn" on:click={sendViaWhatsApp}>
              <span class="whatsapp-icon">💬</span>
              {isConfirmationMode ? 'Message Us' : 'Order Via WhatsApp'}
            </button>
          </div>
          {#if isConfirmationMode}
            <div class="confirm-order-section">
              <button class="confirm-order-btn" on:click={confirmOrder}>
                ✅ Confirm My Order
              </button>
              <p class="confirm-hint">Tap to finalise and send your order confirmation</p>
            </div>
          {/if}
        </div>
      </div>
    </div>
  {/if}
{/if}

<!-- Item Image Preview Lightbox -->
{#if previewItem}
  <div class="cart-preview-overlay" on:click={closePreview} role="dialog" aria-modal="true">
    <div class="cart-preview-content" on:click|stopPropagation>
      <button class="cart-preview-close" on:click={closePreview} aria-label="Close">×</button>
      <img src={previewItem.image} alt={previewItem.name} />
      <p class="cart-preview-name">{previewItem.name}</p>
      {#if previewItem.price}
        <p class="cart-preview-price">{formatPrice(previewItem.price)} FCFA</p>
      {/if}
    </div>
  </div>
{/if}

<style>
  /* Desktop Sidebar Cart */
  .cart-sidebar {
    position: fixed;
    top: 100px;
    right: 20px;
    right: calc(20px + env(safe-area-inset-right, 0px));
    width: 300px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    z-index: 998;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    max-height: calc(100vh - 140px);
    display: flex;
    flex-direction: column;
    -webkit-transform: translateZ(0);
    transform: translateZ(0);
    will-change: transform;
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
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    user-select: none;
    -webkit-user-select: none;
    min-width: 44px;
    min-height: 44px;
  }

  .collapse-btn:hover {
    background: #e9ecef;
    color: #2c3e50;
  }

  /* Collapsed cart button */
  .cart-collapsed {
    position: fixed;
    bottom: calc(100px + env(safe-area-inset-bottom, 0px));
    right: calc(20px + env(safe-area-inset-right, 0px));
    width: 60px;
    height: 60px;
    background: #111111;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    z-index: 998;
    transition: background 0.3s ease, box-shadow 0.3s ease;
    -webkit-transform: translateZ(0);
    transform: translateZ(0);
    will-change: transform;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    user-select: none;
    -webkit-user-select: none;
  }

  .cart-collapsed:hover {
    background: #f0a500;
    transform: scale(1.05);
    box-shadow: 0 6px 20px rgba(240, 165, 0, 0.4);
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
    color: #f0a500;
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
    bottom: calc(80px + env(safe-area-inset-bottom, 0px));
    right: calc(20px + env(safe-area-inset-right, 0px));
    z-index: 999;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    -webkit-transform: translateZ(0);
    transform: translateZ(0);
    will-change: transform;
  }

  .basket-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    background: #111111;
    color: white;
    border: none;
    width: 50px;
    height: 50px;
    min-width: 44px;
    min-height: 44px;
    padding: 0;
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    transition: all 0.3s ease;
    font-size: 0.95rem;
    font-weight: 600;
    position: relative;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    user-select: none;
    -webkit-user-select: none;
  }

  .basket-toggle:hover,
  .basket-toggle:active {
    background: #f0a500;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(240, 165, 0, 0.4);
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
    -webkit-backdrop-filter: blur(2px);
    backdrop-filter: blur(2px);
    z-index: 1001;
    display: flex;
    align-items: flex-end;
    animation: fadeIn 0.2s ease;
    -webkit-transform: translateZ(0);
    transform: translateZ(0);
    will-change: transform;
    padding-bottom: env(safe-area-inset-bottom, 0px);
    padding-left: env(safe-area-inset-left, 0px);
    padding-right: env(safe-area-inset-right, 0px);
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
    width: 44px;
    height: 44px;
    min-width: 44px;
    min-height: 44px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    transition: all 0.2s ease;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    user-select: none;
    -webkit-user-select: none;
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
    object-fit: contain;
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
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.3;
  }

  .item-name-clickable {
    background: none;
    border: none;
    padding: 4px 0;
    text-align: left;
    cursor: pointer;
    transition: color 0.2s ease;
    min-height: 44px;
    display: flex;
    align-items: center;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    user-select: none;
    -webkit-user-select: none;
  }

  .item-name-clickable:hover {
    color: #f0a500;
    text-decoration: underline;
  }

  .item-clickable {
    cursor: pointer;
    border: none;
    padding: 0;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    user-select: none;
    -webkit-user-select: none;
  }

  .item-clickable:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  }

  /* Cart item image preview lightbox */
  .cart-preview-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.85);
    -webkit-backdrop-filter: blur(3px);
    backdrop-filter: blur(3px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 99999;
    padding: calc(1rem + env(safe-area-inset-top, 0px)) calc(1rem + env(safe-area-inset-right, 0px)) calc(1rem + env(safe-area-inset-bottom, 0px)) calc(1rem + env(safe-area-inset-left, 0px));
    -webkit-transform: translateZ(0);
    transform: translateZ(0);
    will-change: transform;
  }

  .cart-preview-content {
    position: relative;
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    max-width: 90vw;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
  }

  .cart-preview-content img {
    max-width: 100%;
    max-height: 70vh;
    object-fit: contain;
    border-radius: 8px;
  }

  .cart-preview-name {
    font-size: 0.95rem;
    color: #333;
    text-align: center;
    margin: 0;
    font-weight: 500;
  }

  .cart-preview-price {
    font-size: 1.1rem;
    color: #f0a500;
    font-weight: 700;
    text-align: center;
    margin: 0;
  }

  .cart-preview-close {
    position: absolute;
    top: -22px;
    right: -22px;
    background: #111111;
    color: white;
    border: none;
    border-radius: 50%;
    width: 44px;
    height: 44px;
    min-width: 44px;
    min-height: 44px;
    font-size: 1.4rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    transition: background 0.2s ease;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
  }

  .cart-preview-close:hover {
    background: #f0a500;
    color: #111111;
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
    color: #f0a500;
  }

  .item-qty-controls {
    display: flex;
    align-items: center;
    gap: 4px;
  }

  .item-qty-btn {
    width: 24px;
    height: 24px;
    min-width: 44px;
    min-height: 44px;
    border: 1px solid #111111;
    background: white;
    color: #111111;
    font-size: 1rem;
    font-weight: bold;
    border-radius: 4px;
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

  .item-qty-btn:hover:not(:disabled),
  .item-qty-btn:active:not(:disabled) {
    background: #f0a500;
    border-color: #f0a500;
    color: #111111;
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
    padding: 0;
    border-radius: 4px;
    transition: background 0.2s;
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
    color: #f0a500;
  }

  .pay-method-section {
    padding: 12px 20px 0;
  }
  .pay-method-label {
    font-size: 12px;
    font-weight: 600;
    color: #666;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
  }
  .pay-method-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px;
    margin-bottom: 12px;
  }
  .pay-method-btn {
    padding: 8px 6px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: white;
    color: #555;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    text-align: center;
    min-height: 44px;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    -webkit-user-select: none;
    user-select: none;
    transition: all 0.15s;
  }
  .pay-method-btn:hover { border-color: #aaa; color: #333; }
  .pay-method-btn.selected {
    border-color: #25a244;
    background: #f0fff4;
    color: #1a7a32;
  }

  .confirm-order-section {
    padding: 12px 20px 16px;
  }
  .confirm-order-btn {
    width: 100%;
    padding: 14px;
    background: #25a244;
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 800;
    cursor: pointer;
    letter-spacing: 0.3px;
    min-height: 50px;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    -webkit-user-select: none;
    user-select: none;
    transition: background 0.15s;
  }
  .confirm-order-btn:hover { background: #1e8a38; }
  .confirm-hint {
    text-align: center;
    font-size: 11px;
    color: #999;
    margin-top: 6px;
    margin-bottom: 0;
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
      bottom: calc(75px + env(safe-area-inset-bottom, 0px));
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
      width: 44px;
      height: 44px;
      min-width: 44px;
      min-height: 44px;
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
      width: 44px;
      height: 44px;
      min-width: 44px;
      min-height: 44px;
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
      width: 44px;
      height: 44px;
      min-width: 44px;
      min-height: 44px;
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
      bottom: calc(70px + env(safe-area-inset-bottom, 0px));
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
      width: 44px;
      height: 44px;
      min-width: 44px;
      min-height: 44px;
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
