<script>
  let isOpen = false;

  const whatsappNumber = "237670358551";
  const whatsappMessage = encodeURIComponent("Hi, I'd like to place an order. Can you help me?");

  function openModal() {
    isOpen = true;
    document.body.style.overflow = 'hidden';
    // Hide navbar on mobile when modal opens
    const navbar = document.querySelector('.navbar');
    if (navbar) navbar.style.display = 'none';
  }

  function closeModal() {
    isOpen = false;
    document.body.style.overflow = 'auto';
    // Show navbar again when modal closes
    const navbar = document.querySelector('.navbar');
    if (navbar) navbar.style.display = '';
  }

  function handleBackdropClick(event) {
    if (event.target === event.currentTarget) {
      closeModal();
    }
  }

  function handleKeydown(event) {
    if (event.key === 'Escape' && isOpen) {
      closeModal();
    }
  }
</script>

<svelte:window on:keydown={handleKeydown} />

<!-- Compact Trigger Button - Positioned to the right -->
<div class="trigger-container">
  <button on:click={openModal} class="trigger-btn">
    <svg class="trigger-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
    </svg>
    <span>How to order?</span>
  </button>
</div>

<!-- Modal -->
{#if isOpen}
  <div class="modal-backdrop" on:click={handleBackdropClick} role="dialog" aria-modal="true">
    <!-- Modal Content -->
    <div class="modal-content">

      <!-- Header - Centered & Stylish -->
      <div class="modal-header">
        <button on:click={closeModal} class="close-btn">
          <svg class="close-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
        <div class="header-content">
          <h2 class="modal-title">How to Order</h2>
          <p class="modal-subtitle">3 simple steps to get your products</p>
        </div>
      </div>

      <!-- Steps - Horizontal Layout -->
      <div class="modal-body">
        <div class="steps-grid">

          <!-- Step 1 -->
          <div class="step-card">
            <div class="step-number">1</div>
            <div class="step-icon">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
            </div>
            <h3 class="step-title">Browse & Select</h3>
            <p class="step-desc">Find products you want</p>
          </div>

          <!-- Step 2 - Highlighted -->
          <div class="step-card step-highlight">
            <div class="step-number">2</div>
            <div class="step-icon whatsapp-icon">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
              </svg>
            </div>
            <h3 class="step-title">Order via WhatsApp</h3>
            <p class="step-desc">Click the green button</p>
          </div>

          <!-- Step 3 -->
          <div class="step-card">
            <div class="step-number">3</div>
            <div class="step-icon">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
              </svg>
            </div>
            <h3 class="step-title">Pay & Receive</h3>
            <p class="step-desc">MoMo / Orange Money</p>
          </div>

        </div>

        <!-- Payment Methods -->
        <div class="payment-methods">
          <span class="payment-badge">MTN MoMo</span>
          <span class="payment-badge">Orange Money</span>
          <span class="payment-badge">Cash on Pickup</span>
        </div>
      </div>

      <!-- Footer -->
      <div class="modal-footer">
        <button on:click={closeModal} class="btn-secondary-modal">
          Got it
        </button>
        <a
          href={`https://wa.me/${whatsappNumber}?text=${whatsappMessage}`}
          target="_blank"
          rel="noopener noreferrer"
          class="btn-primary-modal"
        >
          <svg class="btn-icon" viewBox="0 0 24 24" fill="currentColor">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
          </svg>
          Start Order
        </a>
      </div>

    </div>
  </div>
{/if}

<style>
  /* CSS Variables matching site theme */
  :root {
    --modal-primary: #3498db;
    --modal-secondary: #2c3e50;
    --modal-text: #333;
    --modal-text-light: #666;
    --modal-bg-light: #f8f9fa;
    --modal-border: #e0e0e0;
    --whatsapp-green: #25D366;
  }

  /* Trigger Button */
  .trigger-container {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 1rem;
  }

  .trigger-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    color: #666;
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 50px;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
  }

  .trigger-btn:hover {
    color: #3498db;
    border-color: #3498db;
    box-shadow: 0 2px 8px rgba(52, 152, 219, 0.15);
  }

  .trigger-icon {
    width: 1rem;
    height: 1rem;
  }

  /* Modal Backdrop */
  .modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    overflow-y: auto;
  }

  /* Modal Content */
  .modal-content {
    background: white;
    border-radius: 16px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    width: 100%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    animation: modalIn 0.2s ease-out;
    margin: auto;
  }

  @keyframes modalIn {
    from {
      opacity: 0;
      transform: scale(0.95) translateY(-10px);
    }
    to {
      opacity: 1;
      transform: scale(1) translateY(0);
    }
  }

  /* Modal Header - Centered */
  .modal-header {
    position: relative;
    background: linear-gradient(135deg, #3498db 0%, #2c3e50 100%);
    padding: 1.5rem 2rem;
    text-align: center;
  }

  .close-btn {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: rgba(255,255,255,0.1);
    border: none;
    border-radius: 50%;
    padding: 0.25rem;
    cursor: pointer;
    transition: background 0.2s;
  }

  .close-btn:hover {
    background: rgba(255,255,255,0.2);
  }

  .close-icon {
    width: 1.5rem;
    height: 1.5rem;
    color: rgba(255,255,255,0.8);
  }

  .close-btn:hover .close-icon {
    color: white;
  }

  .header-content {
    padding-top: 0.5rem;
  }

  .modal-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: white;
    margin: 0 0 0.25rem 0;
    letter-spacing: -0.5px;
  }

  .modal-subtitle {
    font-size: 0.875rem;
    color: rgba(255,255,255,0.8);
    margin: 0;
  }

  /* Modal Body */
  .modal-body {
    padding: 1.5rem;
  }

  /* Steps Grid */
  .steps-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
  }

  @media (max-width: 600px) {
    .modal-backdrop {
      padding: 0.5rem;
      align-items: flex-start;
      padding-top: 2rem;
    }

    .modal-content {
      max-height: 85vh;
      border-radius: 12px;
    }

    .modal-header {
      padding: 1rem 1.5rem;
    }

    .modal-title {
      font-size: 1.25rem;
    }

    .modal-body {
      padding: 1rem;
    }

    .steps-grid {
      grid-template-columns: 1fr;
      gap: 0.75rem;
    }

    .step-card {
      padding: 0.75rem;
      display: flex;
      align-items: center;
      text-align: left;
      gap: 0.75rem;
    }

    .step-number {
      width: 2rem;
      height: 2rem;
      font-size: 0.9rem;
      margin: 0;
      flex-shrink: 0;
    }

    .step-icon {
      display: none;
    }

    .step-title {
      font-size: 0.85rem;
      margin: 0;
    }

    .step-desc {
      font-size: 0.75rem;
    }

    .payment-methods {
      margin-top: 0.75rem;
      padding-top: 0.75rem;
      gap: 0.35rem;
    }

    .payment-badge {
      padding: 0.25rem 0.5rem;
      font-size: 0.7rem;
    }

    .modal-footer {
      padding: 0.75rem 1rem;
      flex-direction: row;
      gap: 0.5rem;
    }

    .btn-secondary-modal,
    .btn-primary-modal {
      padding: 0.5rem 0.75rem;
      font-size: 0.8rem;
    }
  }

  .step-card {
    text-align: center;
    padding: 1rem;
    border-radius: 12px;
    background: #f8f9fa;
    transition: all 0.2s ease;
  }

  .step-card:hover {
    background: #eef6fc;
  }

  .step-highlight {
    background: #eef6fc;
    border: 2px solid #3498db;
  }

  .step-number {
    width: 2.5rem;
    height: 2.5rem;
    background: #3498db;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.75rem;
    font-size: 1.125rem;
    font-weight: 700;
  }

  .step-icon {
    width: 2rem;
    height: 2rem;
    margin: 0 auto 0.5rem;
    color: #3498db;
  }

  .step-icon svg {
    width: 100%;
    height: 100%;
  }

  .whatsapp-icon {
    color: #25D366;
  }

  .step-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: #2c3e50;
    margin: 0 0 0.25rem 0;
  }

  .step-desc {
    font-size: 0.8rem;
    color: #666;
    margin: 0;
  }

  /* Payment Methods */
  .payment-methods {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 1.25rem;
    padding-top: 1.25rem;
    border-top: 1px solid #e0e0e0;
  }

  .payment-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
    color: #666;
    background: #f8f9fa;
    border-radius: 50px;
  }

  /* Modal Footer */
  .modal-footer {
    display: flex;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    background: #f8f9fa;
    border-top: 1px solid #e0e0e0;
  }


  .btn-secondary-modal {
    flex: 1;
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #666;
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
  }

  .btn-secondary-modal:hover {
    background: #f0f0f0;
  }

  .btn-primary-modal {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: white;
    background: #25D366;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
  }

  .btn-primary-modal:hover {
    background: #128C7E;
    color: white;
  }

  .btn-icon {
    width: 1rem;
    height: 1rem;
  }
</style>
