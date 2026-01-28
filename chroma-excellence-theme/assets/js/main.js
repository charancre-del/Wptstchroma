/**
 * Main JavaScript
 * Data-attribute based modular approach
 *
 * @package Chroma_Excellence
 */

document.addEventListener('DOMContentLoaded', function () {
  const safeParseJSON = (value, fallback) => {
    try {
      return JSON.parse(value);
    } catch (e) {
      console.warn('Failed to parse JSON payload', e);
      return fallback;
    }
  };

  /**
   * Mobile Nav Toggle
   */
  const mobileNavToggles = document.querySelectorAll('[data-mobile-nav-toggle]');
  const mobileNav = document.querySelector('[data-mobile-nav]');

  mobileNavToggles.forEach((toggle) => {
    toggle.addEventListener('click', () => {
      mobileNav.classList.toggle('translate-x-full');
      // Toggle body scroll
      if (!mobileNav.classList.contains('translate-x-full')) {
        document.body.style.overflow = 'hidden';
      } else {
        document.body.style.overflow = '';
      }
    });
  });

  // Close menu on link click
  if (mobileNav) {
    mobileNav.querySelectorAll('a[href^="#"]').forEach((link) => {
      link.addEventListener('click', () => {
        mobileNav.classList.add('translate-x-full');
      });
    });
  }

  /**
   * Accordions
   */
  const accordions = document.querySelectorAll('[data-accordion]');

  accordions.forEach((accordion) => {
    const triggers = accordion.querySelectorAll('[data-accordion-trigger]');

    triggers.forEach((trigger) => {
      trigger.addEventListener('click', () => {
        const targetId = trigger.getAttribute('aria-controls');
        const content = document.getElementById(targetId);

        if (!content) return;

        const isOpen = !content.classList.contains('hidden');

        // Close all in this accordion
        accordion.querySelectorAll('[data-accordion-content]').forEach((c) => {
          c.classList.add('hidden');
        });

        // Toggle current
        if (!isOpen) {
          content.classList.remove('hidden');
        }
      });
    });
  });

  /**
   * Programs wizard
   */
  /**
   * Programs wizard
   */
  const wizard = document.querySelector('[data-program-wizard]');
  if (wizard) {
    const options = safeParseJSON(wizard.getAttribute('data-options') || '[]', []);
    const optionButtons = wizard.querySelectorAll('[data-program-wizard-option]');
    const result = wizard.querySelector('[data-program-wizard-result]');
    const title = wizard.querySelector('[data-program-wizard-title]');
    const desc = wizard.querySelector('[data-program-wizard-description]');
    const image = wizard.querySelector('[data-program-wizard-image]');
    const learnLink = wizard.querySelector('[data-program-wizard-link]');
    const resetBtn = wizard.querySelector('[data-program-wizard-reset]');

    const showResult = (selected) => {
      if (!result) return;

      // Populate data
      if (title) title.textContent = selected.label;
      if (desc) desc.textContent = selected.description;
      if (learnLink && selected.link) {
        learnLink.setAttribute('href', selected.link);
        learnLink.setAttribute('aria-label', 'Learn more about ' + selected.label);
      }
      if (image && selected.image) image.src = selected.image;

      // Hide options
      wizard.querySelector('[data-program-wizard-options]')?.classList.add('hidden');

      // Show result with animation
      result.classList.remove('hidden');
      // Small delay to allow display:grid to apply before transitioning opacity
      requestAnimationFrame(() => {
        result.classList.remove('opacity-0', 'translate-y-4');
        result.classList.add('opacity-100', 'translate-y-0');
      });
    };

    const resetWizard = () => {
      if (!result) return;

      // Hide result with animation
      result.classList.add('opacity-0', 'translate-y-4');
      result.classList.remove('opacity-100', 'translate-y-0');

      // Wait for transition to finish before hiding
      setTimeout(() => {
        result.classList.add('hidden');
        wizard.querySelector('[data-program-wizard-options]')?.classList.remove('hidden');
      }, 500); // Matches duration-500
    };

    optionButtons.forEach((btn) => {
      btn.addEventListener('click', () => {
        const key = btn.getAttribute('data-program-wizard-option');
        const selected = options.find((o) => o.key === key);
        if (selected) showResult(selected);
      });
    });

    resetBtn?.addEventListener('click', resetWizard);
  }

  /**
   * Curriculum radar chart
   */
  const curriculumConfigEl = document.querySelector('[data-curriculum-config]');
  const curriculumChartEl = document.querySelector('[data-curriculum-chart]');
  const curriculumButtons = document.querySelectorAll('[data-curriculum-button]');

  if (curriculumConfigEl && curriculumChartEl) {
    const config = safeParseJSON(curriculumConfigEl.textContent || '{}', {});
    const profiles = config.profiles || [];
    const labels = config.labels || [];
    const defaultProfile = profiles[0];
    let chartInstance = null;

    const setActiveProfile = (key) => {
      const profile = profiles.find((p) => p.key === key) || defaultProfile;
      if (!profile) return;

      curriculumButtons.forEach((btn) => {
        const isActive = btn.getAttribute('data-curriculum-button') === profile.key;
        if (isActive) {
          btn.classList.add('bg-chroma-blue', 'text-white', 'shadow-soft');
          btn.classList.remove('text-brand-ink/70', 'bg-white');
        } else {
          btn.classList.remove('bg-chroma-blue', 'text-white', 'shadow-soft');
          btn.classList.add('text-brand-ink/70', 'bg-white');
        }
      });

      const title = document.querySelector('[data-curriculum-title]');
      const description = document.querySelector('[data-curriculum-description]');
      if (title) title.textContent = profile.title;
      if (description) description.textContent = profile.description;

      if (window.Chart && chartInstance) {
        chartInstance.data.datasets[0].data = profile.data;
        chartInstance.data.datasets[0].borderColor = profile.color;
        chartInstance.data.datasets[0].backgroundColor = `${profile.color}33`;
        chartInstance.data.datasets[0].pointBorderColor = profile.color;
        chartInstance.update();
      }
    };

    const initChart = () => {
      // Use double requestAnimationFrame to prevent forced reflow during chart initialization
      requestAnimationFrame(() => {
        requestAnimationFrame(() => {
          chartInstance = new Chart(curriculumChartEl.getContext('2d'), {
            type: 'radar',
            data: {
              labels,
              datasets: [
                {
                  label: 'Focus',
                  data: (defaultProfile && defaultProfile.data) || [],
                  borderColor: defaultProfile?.color || '#4A6C7C',
                  backgroundColor: `${defaultProfile?.color || '#4A6C7C'}33`,
                  borderWidth: 2,
                  pointBackgroundColor: '#ffffff',
                  pointBorderColor: defaultProfile?.color || '#4A6C7C',
                  pointRadius: 4,
                },
              ],
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: { legend: { display: false } },
              scales: {
                r: {
                  angleLines: { color: '#e5e7eb' },
                  grid: { color: '#e5e7eb' },
                  suggestedMin: 0,
                  suggestedMax: 100,
                  ticks: { display: false },
                  pointLabels: {
                    font: { family: 'Outfit, system-ui, sans-serif', size: 12 },
                    color: '#263238',
                  },
                },
              },
            },
          });
        });
      });
    };

    if (window.Chart) {
      initChart();
    } else {
      // Lazy load Chart.js
      const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            observer.disconnect();
            if (window.chromaData && window.chromaData.themeUrl) {
              const script = document.createElement('script');
              script.src = `${window.chromaData.themeUrl}/assets/js/chart.min.js`;
              script.async = true;
              script.onload = initChart;
              document.body.appendChild(script);
            }
          }
        });
      }, { rootMargin: '200px' });
      observer.observe(curriculumChartEl);
    }

    curriculumButtons.forEach((btn) => {
      btn.addEventListener('click', () => {
        setActiveProfile(btn.getAttribute('data-curriculum-button'));
      });
    });

    if (defaultProfile) {
      setActiveProfile(defaultProfile.key);
    }
  }

  /**
   * Schedule tabs
   */
  const schedule = document.querySelector('[data-schedule]');
  if (schedule) {
    const panels = schedule.querySelectorAll('[data-schedule-panel]');
    const tabs = schedule.querySelectorAll('[data-schedule-tab]');
    const defaultKey = tabs[0]?.getAttribute('data-schedule-tab');

    const activate = (key) => {
      tabs.forEach((btn) => {
        const isActive = btn.getAttribute('data-schedule-tab') === key;
        btn.classList.toggle('bg-chroma-blue', isActive);
        btn.classList.toggle('text-white', isActive);
        btn.classList.toggle('shadow-soft', isActive);
        btn.classList.toggle('text-brand-ink/60', !isActive);
        // Fix for hover state on active tab (prevents blue text on blue bg)
        btn.classList.toggle('hover:text-chroma-blue', !isActive);

        // Remove inline styles to let CSS classes handle colors
        btn.style.backgroundColor = '';
        btn.style.color = '';
        btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
      });

      panels.forEach((panel) => {
        const isMatch = panel.getAttribute('data-schedule-panel') === key;
        panel.classList.toggle('hidden', !isMatch);
        panel.classList.toggle('active', isMatch);
      });
    };

    tabs.forEach((btn) => {
      btn.addEventListener('click', () => {
        activate(btn.getAttribute('data-schedule-tab'));
      });
    });

    if (defaultKey) {
      activate(defaultKey);
    }

    // Handle internal step clicks (Time buttons)
    const stepTriggers = schedule.querySelectorAll('[data-schedule-step-trigger]');
    stepTriggers.forEach((trigger) => {
      trigger.addEventListener('click', function () {
        // Find parent panel
        const panel = this.closest('[data-schedule-panel]');
        if (!panel) return;

        // Reset all triggers in this panel
        const panelTriggers = panel.querySelectorAll('[data-schedule-step-trigger]');
        panelTriggers.forEach(t => {
          t.classList.remove('bg-brand-ink', 'text-white', 'shadow-md', 'scale-105');
          t.classList.add('bg-white', 'text-brand-ink/70', 'hover:text-brand-ink', 'hover:bg-white/80');
        });

        // Activate clicked trigger
        this.classList.remove('bg-white', 'text-brand-ink/70', 'hover:text-brand-ink', 'hover:bg-white/80');
        this.classList.add('bg-brand-ink', 'text-white', 'shadow-md', 'scale-105');

        // Update content
        const title = this.getAttribute('data-title');
        const copy = this.getAttribute('data-copy');
        const contentTitle = panel.querySelector('[data-content-title]');
        const contentCopy = panel.querySelector('[data-content-copy]');

        if (contentTitle) contentTitle.textContent = title;
        if (contentCopy) contentCopy.textContent = copy;
      });
    });
  }

  /**
   * Parent Reviews Carousel
   */
  const reviewsCarousel = document.querySelector('[data-reviews-carousel]');
  if (reviewsCarousel) {
    const track = reviewsCarousel.querySelector('[data-reviews-track]');
    const dots = reviewsCarousel.querySelectorAll('[data-review-dot]');
    const prevBtn = reviewsCarousel.querySelector('[data-review-prev]');
    const nextBtn = reviewsCarousel.querySelector('[data-review-next]');
    const slides = reviewsCarousel.querySelectorAll('[data-review-slide]');

    let currentIndex = 0;
    const totalSlides = slides.length;
    let autoplayInterval = null;

    const goToSlide = (index) => {
      if (index < 0) index = totalSlides - 1;
      if (index >= totalSlides) index = 0;

      currentIndex = index;
      track.style.transform = `translateX(-${currentIndex * 100}%)`;

      // Update dots
      dots.forEach((dot, i) => {
        if (i === currentIndex) {
          dot.classList.remove('bg-chroma-blue/30', 'hover:bg-chroma-blue/50', 'w-3');
          dot.classList.add('bg-chroma-red', 'w-8');
        } else {
          dot.classList.remove('bg-chroma-red', 'w-8');
          dot.classList.add('bg-chroma-blue/30', 'hover:bg-chroma-blue/50', 'w-3');
        }
      });
    };

    const nextSlide = () => goToSlide(currentIndex + 1);
    const prevSlide = () => goToSlide(currentIndex - 1);

    // Dot navigation
    dots.forEach((dot, index) => {
      dot.addEventListener('click', () => {
        goToSlide(index);
        resetAutoplay();
      });
    });

    // Arrow navigation
    if (prevBtn) {
      prevBtn.addEventListener('click', () => {
        prevSlide();
        resetAutoplay();
      });
    }

    if (nextBtn) {
      nextBtn.addEventListener('click', () => {
        nextSlide();
        resetAutoplay();
      });
    }

    // Autoplay
    const startAutoplay = () => {
      if (totalSlides > 1) {
        autoplayInterval = setInterval(nextSlide, 6000);
      }
    };

    const stopAutoplay = () => {
      if (autoplayInterval) {
        clearInterval(autoplayInterval);
        autoplayInterval = null;
      }
    };

    const resetAutoplay = () => {
      stopAutoplay();
      startAutoplay();
    };

    // Start autoplay on load
    startAutoplay();

    // Pause on hover
    reviewsCarousel.addEventListener('mouseenter', stopAutoplay);
    reviewsCarousel.addEventListener('mouseleave', startAutoplay);

    // Touch/swipe support
    let touchStartX = 0;
    let touchEndX = 0;

    track.addEventListener('touchstart', (e) => {
      touchStartX = e.changedTouches[0].screenX;
    });

    track.addEventListener('touchend', (e) => {
      touchEndX = e.changedTouches[0].screenX;
      handleSwipe();
    });

    const handleSwipe = () => {
      const swipeThreshold = 50;
      if (touchStartX - touchEndX > swipeThreshold) {
        nextSlide();
        resetAutoplay();
      } else if (touchEndX - touchStartX > swipeThreshold) {
        prevSlide();
        resetAutoplay();
      }
    };
  }

  /**
   * Smooth Scrolling for Anchor Links
   */
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener('click', function (e) {
      const targetId = this.getAttribute('href');
      if (targetId === '#') return;

      const targetElement = document.querySelector(targetId);
      if (targetElement) {
        e.preventDefault();
        targetElement.scrollIntoView({ behavior: 'smooth' });
      }
    });
  });
  /**
   * Location Hero Carousel
   */
  const locationCarousel = document.querySelector('[data-location-carousel]');
  if (locationCarousel) {
    const track = locationCarousel.querySelector('[data-location-carousel-track]');
    const slides = locationCarousel.querySelectorAll('[data-location-slide]');
    const prevBtn = locationCarousel.querySelector('[data-location-prev]');
    const nextBtn = locationCarousel.querySelector('[data-location-next]');
    const dots = locationCarousel.querySelectorAll('[data-location-dot]');

    let currentIndex = 0;
    const totalSlides = slides.length;
    let autoplayInterval = null;

    const updateCarousel = (index) => {
      if (index < 0) index = totalSlides - 1;
      if (index >= totalSlides) index = 0;

      currentIndex = index;
      track.style.transform = `translateX(-${currentIndex * 100}%)`;

      // Update dots
      if (dots.length) {
        dots.forEach((dot, i) => {
          if (i === currentIndex) {
            dot.classList.remove('bg-white/50');
            dot.classList.add('bg-white', 'w-6');
          } else {
            dot.classList.remove('bg-white', 'w-6');
            dot.classList.add('bg-white/50');
          }
        });
      }
    };

    const nextSlide = () => updateCarousel(currentIndex + 1);
    const prevSlide = () => updateCarousel(currentIndex - 1);

    // Event Listeners
    if (prevBtn) {
      prevBtn.addEventListener('click', () => {
        prevSlide();
        resetAutoplay();
      });
    }

    if (nextBtn) {
      nextBtn.addEventListener('click', () => {
        nextSlide();
        resetAutoplay();
      });
    }

    if (dots.length) {
      dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
          updateCarousel(index);
          resetAutoplay();
        });
      });
    }

    // Autoplay
    const startAutoplay = () => {
      if (totalSlides > 1) {
        autoplayInterval = setInterval(nextSlide, 5000);
      }
    };

    const stopAutoplay = () => {
      if (autoplayInterval) {
        clearInterval(autoplayInterval);
        autoplayInterval = null;
      }
    };

    const resetAutoplay = () => {
      stopAutoplay();
      startAutoplay();
    };

    // Start
    startAutoplay();

    // Pause on hover
    locationCarousel.addEventListener('mouseenter', stopAutoplay);
    locationCarousel.addEventListener('mouseleave', startAutoplay);
  }

  /**
   * Enhanced Lazy Loading with IntersectionObserver
   * - Uses data-lazy-src for deferred images
   * - Adds smooth fade-in animation
   * - Falls back to native loading="lazy" for unsupported browsers
   */
  const initEnhancedLazyLoading = () => {
    // All images with data-lazy-src attribute
    const lazyImages = document.querySelectorAll('img[data-lazy-src]');

    // Also handle images with loading="lazy" that aren't above-the-fold
    const nativeLazyImages = document.querySelectorAll('img[loading="lazy"]:not(.no-lazy)');

    if ('IntersectionObserver' in window) {
      const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const img = entry.target;

            // Handle data-lazy-src images
            if (img.dataset.lazySrc) {
              img.src = img.dataset.lazySrc;
              if (img.dataset.lazySrcset) {
                img.srcset = img.dataset.lazySrcset;
              }
              delete img.dataset.lazySrc;
              delete img.dataset.lazySrcset;
            }

            // Add fade-in effect
            img.style.opacity = '0';
            img.style.transition = 'opacity 0.3s ease-in-out';

            img.onload = () => {
              img.style.opacity = '1';
              img.classList.add('lazy-loaded');
            };

            // If image is already cached, trigger load immediately
            if (img.complete) {
              img.style.opacity = '1';
              img.classList.add('lazy-loaded');
            }

            observer.unobserve(img);
          }
        });
      }, {
        rootMargin: '50px 0px', // Start loading 50px before entering viewport
        threshold: 0.01
      });

      // Observe data-lazy-src images
      lazyImages.forEach(img => imageObserver.observe(img));

      // Observe native lazy images for fade-in effect
      nativeLazyImages.forEach(img => {
        // Add fade-in for when they load
        if (!img.complete) {
          img.style.opacity = '0';
          img.style.transition = 'opacity 0.3s ease-in-out';
          img.onload = () => {
            img.style.opacity = '1';
            img.classList.add('lazy-loaded');
          };
        }
      });

    } else {
      // Fallback for browsers without IntersectionObserver
      lazyImages.forEach(img => {
        if (img.dataset.lazySrc) {
          img.src = img.dataset.lazySrc;
          if (img.dataset.lazySrcset) {
            img.srcset = img.dataset.lazySrcset;
          }
        }
      });
    }
  };

  // Initialize lazy loading
  initEnhancedLazyLoading();

  // Also handle dynamically added images (for AJAX/SPA scenarios)
  const lazyLoadObserver = new MutationObserver((mutations) => {
    mutations.forEach(mutation => {
      mutation.addedNodes.forEach(node => {
        if (node.nodeType === 1) { // Element node
          const newLazyImages = node.querySelectorAll ?
            node.querySelectorAll('img[data-lazy-src]') : [];
          if (newLazyImages.length > 0) {
            initEnhancedLazyLoading();
          }
        }
      });
    });
  });

  lazyLoadObserver.observe(document.body, { childList: true, subtree: true });

  /**
   * Scroll Reveal Color Animation
   * Transitions elements from grayscale to color when they enter the viewport
   */
  const revealColorImages = document.querySelectorAll('[data-reveal-color]');
  if ('IntersectionObserver' in window && revealColorImages.length > 0) {
    const revealObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          // Add a small delay for a more natural feel
          setTimeout(() => {
            entry.target.classList.remove('grayscale');
          }, 200);
          observer.unobserve(entry.target);
        }
      });
    }, {
      rootMargin: '-50px', // Trigger slightly after entering viewport
      threshold: 0.2 // Trigger when 20% visible
    });

    revealColorImages.forEach(img => revealObserver.observe(img));
  }

  /**
   * Sticky CTA Bar Scroll Logic (Performance Optimized)
   * - Uses one-time trigger to avoid continuous scroll processing
   * - Removes listener after activation for zero ongoing cost
   */
  const stickyCta = document.getElementById('sticky-cta');
  if (stickyCta) {
    // Double RAF: The "Deep" Fix for Layout Thrashing
    // Frame 1: Styles are invalidated by Lazy Load initialization
    // Frame 2: Browser paints
    // Frame 3: WE READ SCROLL. (Guarantees zero reflow cost)
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        // Check if already scrolled on page load
        if (window.scrollY > 300) {
          stickyCta.classList.remove('translate-y-full');
          stickyCta.classList.add('translate-y-0');
        } else {
          // Only add listener if not already past threshold
          const showStickyCta = () => {
            if (window.scrollY > 300) {
              stickyCta.classList.remove('translate-y-full');
              stickyCta.classList.add('translate-y-0');
              // Remove listener after showing - no ongoing cost
              window.removeEventListener('scroll', showStickyCta);
            }
          };
          window.addEventListener('scroll', showStickyCta, { passive: true });
        }
      });
    });
  }
});
