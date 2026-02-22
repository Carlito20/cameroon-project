<script>
  import { onMount, onDestroy } from 'svelte';

  // Slides data with Unsplash images - matching exact category names
  const slides = [
    {
      id: 1,
      title: "Kids",
      subtitle: "",
      description: "Quality diapers, wipes, baby care essentials and everything for your little ones from trusted American brands.",
      image: "https://images.unsplash.com/photo-1515488042361-ee00e0ddd4e4?w=1400&h=600&fit=crop&crop=center",
      color: "#e8f4fc",
      link: "/shop?category=kids"
    },
    {
      id: 2,
      title: "Body, Bath &",
      subtitle: "Personal Care",
      description: "Bath soaps, body lotions, oral care, hair care, and grooming products from top North American brands.",
      image: "/images/body-bath-personal-care-home.jpg",
      color: "#fce8f4",
      link: "/shop?category=body-bath"
    },
    {
      id: 3,
      title: "Food &",
      subtitle: "Pantry",
      description: "Authentic American snacks, breakfast items, canned foods, beverages, and spices delivered to Cameroon.",
      image: "/images/food-pantry-slider.jpg",
      color: "#fcf4e8",
      link: "/shop?category=food-pantry"
    },
    {
      id: 4,
      title: "Electronics &",
      subtitle: "Accessories",
      description: "Chargers, power banks, kitchen electronics, cables, and smart home accessories from the USA.",
      image: "https://images.unsplash.com/photo-1468495244123-6c6c332eeece?w=1400&h=600&fit=crop&crop=center",
      color: "#e8ecfc",
      link: "/shop?category=electronics"
    },
    {
      id: 5,
      title: "Household Cleaning",
      subtitle: "& Supplies",
      description: "Laundry detergents, dishwashing supplies, surface cleaners, air fresheners, and more.",
      image: "/images/household-cleaning-slider.jpg",
      color: "#e8fcf4",
      link: "/shop?category=household-cleaning"
    },
    {
      id: 6,
      title: "Kitchen &",
      subtitle: "Dining",
      description: "Cookware, bakeware, food storage, kitchen appliances, and tools for your home.",
      image: "https://images.unsplash.com/photo-1556909212-d5b604d0c90d?w=1400&h=600&fit=crop&crop=center",
      color: "#fcf0e8",
      link: "/shop?category=home-kitchen"
    },
    {
      id: 7,
      title: "Health &",
      subtitle: "Wellness",
      description: "Vitamins, supplements, first aid supplies, pain relief, and wellness devices for your well-being.",
      image: "/images/health-wellness-slider.jpg",
      color: "#f0e8fc",
      link: "/shop?category=health-wellness"
    },
    {
      id: 8,
      title: "Home",
      subtitle: "Essentials",
      description: "Storage, organization, bedding, linens, towels, and home décor to make your house a home.",
      image: "https://images.unsplash.com/photo-1513694203232-719a280e022f?w=1400&h=600&fit=crop&crop=center",
      color: "#e8f0fc",
      link: "/shop?category=home-essentials"
    }
  ];

  let currentSlide = 0;
  let interval;
  let touchStartX = 0;
  let touchEndX = 0;

  function nextSlide() {
    currentSlide = (currentSlide + 1) % slides.length;
  }

  function prevSlide() {
    currentSlide = (currentSlide - 1 + slides.length) % slides.length;
  }

  function goToSlide(index) {
    currentSlide = index;
  }

  function startAutoPlay() {
    interval = setInterval(nextSlide, 5000); // Change slide every 5 seconds
  }

  function stopAutoPlay() {
    if (interval) clearInterval(interval);
  }

  function handleTouchStart(e) {
    touchStartX = e.touches[0].clientX;
    stopAutoPlay();
  }

  function handleTouchMove(e) {
    touchEndX = e.touches[0].clientX;
  }

  function handleTouchEnd() {
    const swipeThreshold = 50;
    const diff = touchStartX - touchEndX;

    if (Math.abs(diff) > swipeThreshold) {
      if (diff > 0) {
        nextSlide(); // Swipe left - go to next
      } else {
        prevSlide(); // Swipe right - go to previous
      }
    }
    touchStartX = 0;
    touchEndX = 0;
    startAutoPlay();
  }

  onMount(() => {
    startAutoPlay();
  });

  onDestroy(() => {
    stopAutoPlay();
  });
</script>

<div
  class="hero-slider"
  on:mouseenter={stopAutoPlay}
  on:mouseleave={startAutoPlay}
  on:touchstart={handleTouchStart}
  on:touchmove={handleTouchMove}
  on:touchend={handleTouchEnd}
>
  {#each slides as slide, index}
    <div
      class="slide"
      class:active={index === currentSlide}
      style="background-color: {slide.color};"
    >
      <div class="slide-content">
        <div class="slide-text">
          <h1>
            {slide.title}
            <span class="highlight">{slide.subtitle}</span>
          </h1>
          <p>{slide.description}</p>
          <a href={slide.link} class="slide-btn">
            Shop Now
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M5 12h14M12 5l7 7-7 7"/>
            </svg>
          </a>
        </div>
        <div class="slide-image">
          <img src={slide.image} alt={slide.title + ' ' + slide.subtitle} loading="lazy" />
        </div>
      </div>
    </div>
  {/each}

  <!-- Navigation Arrows -->
  <button class="nav-btn prev" on:click={prevSlide} aria-label="Previous slide">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M15 18l-6-6 6-6"/>
    </svg>
  </button>
  <button class="nav-btn next" on:click={nextSlide} aria-label="Next slide">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M9 18l6-6-6-6"/>
    </svg>
  </button>

  <!-- Dots Navigation -->
  <div class="dots">
    {#each slides as slide, index}
      <button
        class="dot"
        class:active={index === currentSlide}
        on:click={() => goToSlide(index)}
        aria-label="Go to slide {index + 1}"
      ></button>
    {/each}
  </div>
</div>

<style>
  .hero-slider {
    position: relative;
    width: 100%;
    height: 500px;
    overflow: hidden;
    border-radius: 0 0 20px 20px;
  }

  .slide {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.6s ease, visibility 0.6s ease;
  }

  .slide.active {
    opacity: 1;
    visibility: visible;
  }

  .slide-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
    height: 100%;
    gap: 2rem;
  }

  .slide-text {
    flex: 1;
    max-width: 500px;
    z-index: 2;
  }

  .slide-text h1 {
    font-size: 3rem;
    font-weight: 800;
    color: #1a1a2e;
    line-height: 1.1;
    margin-bottom: 1rem;
  }

  .slide-text .highlight {
    display: block;
    color: #3498db;
  }

  .slide-text p {
    font-size: 1.1rem;
    color: #4a4a5a;
    line-height: 1.6;
    margin-bottom: 1.5rem;
  }

  .slide-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: #3498db;
    color: white;
    padding: 14px 28px;
    border-radius: 50px;
    font-weight: 600;
    font-size: 1rem;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
  }

  .slide-btn:hover {
    background: #2980b9;
    transform: translateX(5px);
    box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
  }

  .slide-image {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
  }

  .slide-image img {
    max-width: 100%;
    max-height: 400px;
    object-fit: cover;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
  }

  /* Navigation Buttons */
  .nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: white;
    border: none;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    z-index: 10;
    color: #2c3e50;
  }

  .nav-btn:hover {
    background: #3498db;
    color: white;
    transform: translateY(-50%) scale(1.1);
  }

  .nav-btn.prev {
    left: 20px;
  }

  .nav-btn.next {
    right: 20px;
  }

  /* Dots Navigation */
  .dots {
    position: absolute;
    bottom: 25px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 10px;
    z-index: 10;
  }

  .dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #3498db;
    background: transparent;
    cursor: pointer;
    transition: all 0.3s ease;
    padding: 0;
  }

  .dot:hover {
    background: rgba(52, 152, 219, 0.5);
  }

  .dot.active {
    background: #3498db;
    transform: scale(1.2);
  }

  /* Mobile Responsive */
  @media (max-width: 900px) {
    .hero-slider {
      height: auto;
      min-height: 600px;
    }

    .slide-content {
      flex-direction: column;
      text-align: center;
      padding: 2rem 1.5rem 4rem;
    }

    .slide-text {
      max-width: 100%;
      order: 1;
    }

    .slide-text h1 {
      font-size: 2.2rem;
    }

    .slide-text .highlight {
      display: inline;
    }

    .slide-image {
      order: 2;
      margin-top: 1rem;
    }

    .slide-image img {
      max-height: 250px;
    }

    .nav-btn {
      width: 40px;
      height: 40px;
    }

    .nav-btn.prev {
      left: 10px;
    }

    .nav-btn.next {
      right: 10px;
    }
  }

  @media (max-width: 500px) {
    .slide-text h1 {
      font-size: 1.8rem;
    }

    .slide-text p {
      font-size: 1rem;
    }

    .slide-btn {
      padding: 12px 24px;
      font-size: 0.9rem;
    }

    .nav-btn {
      display: none;
    }
  }
</style>
