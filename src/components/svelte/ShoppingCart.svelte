<script>
  // Shopping cart state
  let cartItems = [];
  let isCartOpen = false;

  // Reactive statement to calculate total items
  $: totalItems = cartItems.reduce((sum, item) => sum + item.quantity, 0);

  // Reactive statement to calculate total price
  $: totalPrice = cartItems.reduce((sum, item) => sum + (item.price * item.quantity), 0);

  // Add item to cart
  function addToCart(product) {
    const existingItem = cartItems.find(item => item.id === product.id);

    if (existingItem) {
      // Item already in cart, increase quantity
      cartItems = cartItems.map(item =>
        item.id === product.id
          ? { ...item, quantity: item.quantity + 1 }
          : item
      );
    } else {
      // New item, add to cart
      cartItems = [...cartItems, { ...product, quantity: 1 }];
    }
  }

  // Remove item from cart
  function removeFromCart(productId) {
    cartItems = cartItems.filter(item => item.id !== productId);
  }

  // Update quantity
  function updateQuantity(productId, newQuantity) {
    if (newQuantity <= 0) {
      removeFromCart(productId);
    } else {
      cartItems = cartItems.map(item =>
        item.id === productId
          ? { ...item, quantity: newQuantity }
          : item
      );
    }
  }

  // Toggle cart visibility
  function toggleCart() {
    isCartOpen = !isCartOpen;
  }

  // Example: Add this function to your global scope so product cards can use it
  // In a real app, you'd use Svelte stores for cross-component communication
</script>

<div class="shopping-cart-widget">
  <!-- Cart Icon Button -->
  <button class="cart-button" on:click={toggleCart}>
    <span class="cart-icon">🛒</span>
    {#if totalItems > 0}
      <span class="cart-badge">{totalItems}</span>
    {/if}
  </button>

  <!-- Cart Dropdown -->
  {#if isCartOpen}
    <div class="cart-dropdown">
      <div class="cart-header">
        <h3>Shopping Cart</h3>
        <button class="close-btn" on:click={toggleCart}>✕</button>
      </div>

      <div class="cart-items">
        {#if cartItems.length === 0}
          <p class="empty-cart">Your cart is empty</p>
        {:else}
          {#each cartItems as item (item.id)}
            <div class="cart-item">
              <div class="item-info">
                <h4>{item.name}</h4>
                <p class="item-price">{item.price.toLocaleString()} XAF</p>
              </div>

              <div class="item-controls">
                <button
                  class="qty-btn"
                  on:click={() => updateQuantity(item.id, item.quantity - 1)}
                >
                  -
                </button>
                <span class="quantity">{item.quantity}</span>
                <button
                  class="qty-btn"
                  on:click={() => updateQuantity(item.id, item.quantity + 1)}
                >
                  +
                </button>
                <button
                  class="remove-btn"
                  on:click={() => removeFromCart(item.id)}
                >
                  🗑️
                </button>
              </div>
            </div>
          {/each}
        {/if}
      </div>

      {#if cartItems.length > 0}
        <div class="cart-footer">
          <div class="cart-total">
            <strong>Total:</strong>
            <strong>{totalPrice.toLocaleString()} XAF</strong>
          </div>
          <button class="checkout-btn">Proceed to Checkout</button>
        </div>
      {/if}
    </div>
  {/if}
</div>

<!-- Demo Section (remove this in production) -->
<div class="demo-section">
  <h3>Demo: Add Items to Cart</h3>
  <div class="demo-products">
    <button on:click={() => addToCart({ id: 1, name: 'Dove Soap', price: 2500 })}>
      Add Dove Soap (2,500 XAF)
    </button>
    <button on:click={() => addToCart({ id: 2, name: 'Baby Diapers', price: 8000 })}>
      Add Baby Diapers (8,000 XAF)
    </button>
    <button on:click={() => addToCart({ id: 3, name: 'Body Lotion', price: 3500 })}>
      Add Body Lotion (3,500 XAF)
    </button>
  </div>
</div>

<style>
  .shopping-cart-widget {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1000;
  }

  .cart-button {
    position: relative;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 50%;
    width: 60px;
    height: 60px;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    transition: transform 0.3s;
  }

  .cart-button:hover {
    transform: scale(1.1);
  }

  .cart-icon {
    font-size: 24px;
  }

  .cart-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #e74c3c;
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
  }

  .cart-dropdown {
    position: absolute;
    top: 70px;
    right: 0;
    width: 350px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    max-height: 500px;
    display: flex;
    flex-direction: column;
  }

  .cart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
  }

  .cart-header h3 {
    margin: 0;
    color: #2c3e50;
  }

  .close-btn {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #7f8c8d;
  }

  .cart-items {
    flex: 1;
    overflow-y: auto;
    padding: 15px;
  }

  .empty-cart {
    text-align: center;
    color: #7f8c8d;
    padding: 40px 20px;
  }

  .cart-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    border-bottom: 1px solid #eee;
  }

  .item-info h4 {
    margin: 0 0 5px 0;
    color: #2c3e50;
    font-size: 14px;
  }

  .item-price {
    color: #667eea;
    font-weight: bold;
    margin: 0;
    font-size: 14px;
  }

  .item-controls {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .qty-btn {
    background: #f8f9fa;
    border: 1px solid #ddd;
    border-radius: 4px;
    width: 28px;
    height: 28px;
    cursor: pointer;
    font-size: 16px;
    transition: background 0.3s;
  }

  .qty-btn:hover {
    background: #e9ecef;
  }

  .quantity {
    min-width: 20px;
    text-align: center;
    font-weight: bold;
  }

  .remove-btn {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 18px;
    margin-left: 8px;
  }

  .cart-footer {
    padding: 15px 20px;
    border-top: 1px solid #eee;
  }

  .cart-total {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
    font-size: 18px;
    color: #2c3e50;
  }

  .checkout-btn {
    width: 100%;
    padding: 12px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 16px;
    font-weight: bold;
    transition: background 0.3s;
  }

  .checkout-btn:hover {
    background: #5568d3;
  }

  /* Demo Section Styles */
  .demo-section {
    margin-top: 500px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
  }

  .demo-section h3 {
    margin-top: 0;
    color: #2c3e50;
  }

  .demo-products {
    display: flex;
    flex-direction: column;
    gap: 10px;
  }

  .demo-products button {
    padding: 12px;
    background: white;
    border: 2px solid #667eea;
    border-radius: 6px;
    cursor: pointer;
    color: #667eea;
    font-size: 14px;
    transition: all 0.3s;
  }

  .demo-products button:hover {
    background: #667eea;
    color: white;
  }
</style>
