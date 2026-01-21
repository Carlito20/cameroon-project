<script>
  import { onMount, onDestroy } from 'svelte';

  export let whatsappNumber = "237670358551";

  // Slides with background images and unique messages for each category
  const slides = [
    {
      url: "https://images.unsplash.com/photo-1491013516836-7db643ee125a?w=1920&h=800&fit=crop",
      title: "Everything for Your Little Ones",
      subtitle: "Kids Products",
      description: "Quality diapers, wipes, baby care essentials from trusted American brands."
    },
    {
      url: "https://images.unsplash.com/photo-1556228578-0d85b1a4d571?w=1920&h=800&fit=crop",
      title: "Look & Feel Your Best",
      subtitle: "Body & Personal Care",
      description: "Premium skincare, haircare, and grooming products for the whole family."
    },
    {
      url: "https://images.unsplash.com/photo-1606787366850-de6330128bfc?w=1920&h=800&fit=crop",
      title: "Taste the American Favorites",
      subtitle: "Food & Pantry",
      description: "Authentic snacks, cereals, canned goods and pantry essentials."
    },
    {
      url: "https://images.unsplash.com/photo-1498049794561-7780e7231661?w=1920&h=800&fit=crop",
      title: "Stay Connected & Powered",
      subtitle: "Electronics & Accessories",
      description: "Chargers, gadgets, and tech accessories at competitive prices."
    },
    {
      url: "https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=1920&h=800&fit=crop",
      title: "Keep Your Home Sparkling",
      subtitle: "Household Cleaning",
      description: "Laundry, dishwashing, and cleaning supplies for a fresh home."
    },
    {
      url: "https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=1920&h=800&fit=crop",
      title: "Cook Like a Pro",
      subtitle: "Home & Kitchen",
      description: "Cookware, appliances, and kitchen tools for every meal."
    },
    {
      url: "https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=1920&h=800&fit=crop",
      title: "Your Wellness Matters",
      subtitle: "Health & Wellness",
      description: "Vitamins, supplements, and health products for your wellbeing."
    },
    {
      url: "https://images.unsplash.com/photo-1616046229478-9901c5536a45?w=1920&h=800&fit=crop",
      title: "Make Your House a Home",
      subtitle: "Home Essentials",
      description: "Bedding, storage, towels, and décor for comfortable living."
    }
  ];

  let currentSlide = 0;
  let interval;

  function nextSlide() {
    currentSlide = (currentSlide + 1) % slides.length;
  }

  function goToSlide(index) {
    currentSlide = index;
  }

  onMount(() => {
    interval = setInterval(nextSlide, 5000); // Change every 5 seconds
  });

  onDestroy(() => {
    if (interval) clearInterval(interval);
  });
</script>

<section class="welcome-hero">
  <!-- Background Images -->
  <div class="background-slideshow">
    {#each slides as slide, index}
      <div
        class="bg-image"
        class:active={index === currentSlide}
        style="background-image: url('{slide.url}');"
      ></div>
    {/each}
    <div class="overlay"></div>
  </div>

  <!-- Content - Changes with each slide -->
  <div class="welcome-content">
    <p class="welcome-intro">Welcome to <span class="brand-name">American Select Market</span></p>

    {#each slides as slide, index}
      <div class="slide-text" class:active={index === currentSlide}>
        <h1>{slide.title}</h1>
        <p class="subtitle">{slide.subtitle}</p>
        <p class="description">{slide.description}</p>
      </div>
    {/each}

    <div class="welcome-cta">
      <a href="/shop" class="btn btn-primary">Start Shopping</a>
      <a
        href={`https://wa.me/${whatsappNumber}?text=${encodeURIComponent("Hi, I'm interested in your products!")}`}
        target="_blank"
        rel="noopener noreferrer"
        class="btn btn-outline"
      >
        Chat on WhatsApp
      </a>
    </div>
  </div>

  <!-- Slide Indicators -->
  <div class="indicators">
    {#each slides as _, index}
      <button
        class="indicator"
        class:active={index === currentSlide}
        on:click={() => goToSlide(index)}
        aria-label="Go to slide {index + 1}"
      ></button>
    {/each}
  </div>
</section>

<style>
  .welcome-hero {
    position: relative;
    min-height: 500px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
  }

  /* Background Slideshow */
  .background-slideshow {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1;
  }

  .bg-image {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-size: cover;
    background-position: center;
    opacity: 0;
    transition: opacity 1.2s ease-in-out;
    transform: scale(1.05);
  }

  .bg-image.active {
    opacity: 1;
    transform: scale(1);
    transition: opacity 1.2s ease-in-out, transform 8s ease-out;
  }

  /* Lighter overlay to show images better */
  .overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(
      135deg,
      rgba(44, 62, 80, 0.5) 0%,
      rgba(0, 0, 0, 0.4) 100%
    );
    z-index: 2;
  }

  /* Content */
  .welcome-content {
    position: relative;
    z-index: 3;
    text-align: center;
    color: white;
    max-width: 900px;
    padding: 2rem;
  }

  .welcome-intro {
    font-size: 1.1rem;
    margin-bottom: 0.5rem;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
    letter-spacing: 1px;
  }

  .brand-name {
    color: #f1c40f;
    font-weight: 700;
    font-size: 1.3rem;
  }

  /* Slide text - changes with each slide */
  .slide-text {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    width: 100%;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.6s ease, transform 0.6s ease;
  }

  .slide-text.active {
    position: relative;
    left: auto;
    transform: none;
    opacity: 1;
    visibility: visible;
  }

  .slide-text h1 {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    text-shadow: 0 3px 15px rgba(0, 0, 0, 0.5);
    line-height: 1.2;
  }

  .slide-text .subtitle {
    font-size: 1.4rem;
    color: #f1c40f;
    font-weight: 600;
    margin-bottom: 1rem;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.4);
  }

  .slide-text .description {
    font-size: 1.15rem;
    line-height: 1.6;
    margin-bottom: 1.5rem;
    text-shadow: 0 1px 8px rgba(0, 0, 0, 0.4);
    max-width: 700px;
    margin-left: auto;
    margin-right: auto;
  }

  .welcome-cta {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 1.5rem;
  }

  .btn {
    display: inline-block;
    padding: 14px 32px;
    font-size: 1rem;
    font-weight: 600;
    text-decoration: none;
    border-radius: 50px;
    transition: all 0.3s ease;
    cursor: pointer;
  }

  .btn-primary {
    background: #3498db;
    color: white;
    border: 2px solid #3498db;
    box-shadow: 0 4px 15px rgba(52, 152, 219, 0.4);
  }

  .btn-primary:hover {
    background: #2980b9;
    border-color: #2980b9;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(52, 152, 219, 0.5);
  }

  .btn-outline {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border: 2px solid white;
    backdrop-filter: blur(5px);
  }

  .btn-outline:hover {
    background: white;
    color: #2c3e50;
  }

  /* Indicators */
  .indicators {
    position: absolute;
    bottom: 25px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 10px;
    z-index: 4;
  }

  .indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid rgba(255, 255, 255, 0.7);
    background: transparent;
    cursor: pointer;
    padding: 0;
    transition: all 0.3s ease;
  }

  .indicator:hover {
    border-color: white;
    background: rgba(255, 255, 255, 0.4);
  }

  .indicator.active {
    background: #f1c40f;
    border-color: #f1c40f;
    transform: scale(1.3);
  }

  /* Mobile Responsive */
  @media (max-width: 768px) {
    .welcome-hero {
      min-height: 450px;
    }

    .slide-text h1 {
      font-size: 2rem;
    }

    .slide-text .subtitle {
      font-size: 1.1rem;
    }

    .slide-text .description {
      font-size: 1rem;
    }

    .welcome-cta {
      flex-direction: column;
      align-items: center;
    }

    .btn {
      width: 100%;
      max-width: 280px;
      padding: 12px 24px;
    }
  }

  @media (max-width: 480px) {
    .welcome-hero {
      min-height: 420px;
    }

    .welcome-intro {
      font-size: 0.95rem;
    }

    .brand-name {
      font-size: 1.1rem;
    }

    .slide-text h1 {
      font-size: 1.6rem;
    }

    .slide-text .subtitle {
      font-size: 1rem;
    }

    .slide-text .description {
      font-size: 0.9rem;
      padding: 0 1rem;
    }

    .indicators {
      bottom: 15px;
    }
  }
</style>
