<script>
  import { onMount } from 'svelte';

  export let whatsappNumber = "237670358551";

  // Inquiry items stored in this component
  let inquiryItems = [];
  let isOpen = false;

  // Listen for custom events from ShopFilter
  onMount(() => {
    // Add item to inquiry
    window.addEventListener('add-to-inquiry', (e) => {
      const item = e.detail;
      if (!inquiryItems.find(i => i.name === item.name)) {
        inquiryItems = [...inquiryItems, {
          ...item,
          quantity: item.quantity || 1,
          maxStock: item.maxStock || 99
        }];
      }
    });

    // Remove item from inquiry
    window.addEventListener('remove-from-inquiry', (e) => {
      const item = e.detail;
      inquiryItems = inquiryItems.filter(i => i.name !== item.name);
    });
  });

  // Get total items count
  function getTotalItems() {
    return inquiryItems.reduce((sum, item) => sum + (item.quantity || 1), 0);
  }

  // Update quantity for an item (respecting stock limits)
  function updateItemQty(itemName, delta) {
    inquiryItems = inquiryItems.map(item => {
      if (item.name === itemName) {
        const currentQty = item.quantity || 1;
        const maxQty = item.maxStock || 99;
        const newQty = Math.max(1, Math.min(maxQty, currentQty + delta));
        return { ...item, quantity: newQty };
      }
      return item;
    });
  }

  // Check if item is at max stock
  function isAtMaxStock(item) {
    return item.maxStock && (item.quantity || 1) >= item.maxStock;
  }

  function removeItem(itemName) {
    inquiryItems = inquiryItems.filter(i => i.name !== itemName);
  }

  function clearAll() {
    inquiryItems = [];
    isOpen = false;
  }

  function sendViaWhatsApp() {
    if (inquiryItems.length === 0) return;

    const itemList = inquiryItems.map(item => {
      const qty = item.quantity || 1;
      return `• ${item.name} x${qty}${item.category ? ` (${item.category})` : ''}`;
    }).join('\n');

    const totalItems = getTotalItems();
    const message = `Hi! I'm interested in ordering (${totalItems} items):\n\n${itemList}\n\nPlease confirm availability and total price.`;

    const encodedMessage = encodeURIComponent(message);
    const whatsappUrl = `https://wa.me/${whatsappNumber}?text=${encodedMessage}`;

    window.open(whatsappUrl, '_blank');
  }

  function toggleBasket() {
    isOpen = !isOpen;
  }
</script>

<!-- Floating Basket Button -->
{#if inquiryItems.length > 0}
  <div class="inquiry-basket-float">
    <button class="basket-toggle" on:click={toggleBasket}>
      <span class="basket-icon">🛒</span>
      <span class="basket-count">{getTotalItems()}</span>
      <span class="basket-label">Cart</span>
    </button>

    <!-- Expanded Basket Panel -->
    {#if isOpen}
      <div class="basket-panel">
        <div class="basket-header">
          <h3>🛒 Your Cart</h3>
          <button class="close-btn" on:click={() => isOpen = false}>✕</button>
        </div>

        <div class="basket-items">
          {#each inquiryItems as item (item.name)}
            <div class="basket-item">
              <div class="item-details">
                <span class="item-name">{item.name}</span>
                {#if item.category}
                  <span class="item-category">{item.category}</span>
                {/if}
              </div>
              <div class="item-qty-controls">
                <button class="item-qty-btn" on:click={() => updateItemQty(item.name, -1)} disabled={(item.quantity || 1) <= 1}>−</button>
                <span class="item-qty">{item.quantity || 1}</span>
                <button class="item-qty-btn" on:click={() => updateItemQty(item.name, 1)} disabled={isAtMaxStock(item)}>+</button>
              </div>
              <button class="remove-btn" on:click={() => removeItem(item.name)}>✕</button>
            </div>
          {/each}
        </div>

        <div class="basket-actions">
          <button class="clear-btn" on:click={clearAll}>Clear All</button>
          <button class="send-btn" on:click={sendViaWhatsApp}>
            <span class="whatsapp-icon">💬</span>
            Send via WhatsApp
          </button>
        </div>
      </div>
    {/if}
  </div>
{/if}

<style>
  .inquiry-basket-float {
    position: fixed;
    bottom: 100px;
    right: 20px;
    z-index: 999;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  }

  .basket-toggle {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #3498db;
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 50px;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(52, 152, 219, 0.4);
    transition: all 0.3s ease;
    font-size: 0.95rem;
    font-weight: 600;
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
    background: #e74c3c;
    color: white;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    font-weight: bold;
  }

  .basket-label {
    display: none;
  }

  @media (min-width: 500px) {
    .basket-label {
      display: inline;
    }
  }

  .basket-panel {
    position: absolute;
    bottom: 60px;
    right: 0;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    width: 320px;
    max-height: 400px;
    overflow: hidden;
    animation: slideUp 0.2s ease;
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
    max-height: 220px;
    overflow-y: auto;
    padding: 10px 0;
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
    font-size: 0.9rem;
    color: #2c3e50;
  }

  .item-category {
    font-size: 0.75rem;
    color: #6c757d;
    background: #e9ecef;
    padding: 2px 8px;
    border-radius: 10px;
    width: fit-content;
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
      bottom: 90px;
      right: 15px;
    }

    .basket-toggle {
      padding: 10px 16px;
      min-height: 48px;
    }

    .basket-panel {
      width: 300px;
      max-height: 350px;
    }

    .basket-items {
      max-height: 180px;
    }

    .basket-item {
      padding: 8px 15px;
    }

    .item-qty-btn {
      width: 28px;
      height: 28px;
      min-width: 28px;
    }

    .basket-actions {
      padding: 12px 15px;
    }

    .close-btn {
      width: 32px;
      height: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
  }

  @media (max-width: 400px) {
    .inquiry-basket-float {
      bottom: 85px;
      right: 10px;
    }

    .basket-panel {
      position: fixed;
      bottom: 0;
      right: 0;
      left: 0;
      width: 100%;
      max-height: 60vh;
      border-radius: 16px 16px 0 0;
    }

    .basket-toggle {
      padding: 10px 14px;
      font-size: 0.9rem;
    }

    .basket-icon {
      font-size: 1.2rem;
    }

    .basket-count {
      width: 22px;
      height: 22px;
      font-size: 0.8rem;
    }

    .basket-header {
      padding: 12px 15px;
    }

    .basket-header h3 {
      font-size: 0.95rem;
    }

    .basket-items {
      max-height: calc(60vh - 140px);
      -webkit-overflow-scrolling: touch;
    }

    .basket-item {
      padding: 10px 15px;
    }

    .item-name {
      font-size: 0.85rem;
    }

    .item-category {
      font-size: 0.7rem;
    }

    .item-qty-btn {
      width: 32px;
      height: 32px;
      min-width: 32px;
    }

    .remove-btn {
      padding: 6px 10px;
      font-size: 1rem;
    }

    .basket-actions {
      flex-direction: column;
      gap: 8px;
    }

    .clear-btn, .send-btn {
      flex: none;
      width: 100%;
      min-height: 44px;
    }

    .send-btn {
      font-size: 0.95rem;
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
