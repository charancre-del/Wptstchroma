/**
 * Main JavaScript - Performance Optimized Version
 * Modular, lazy-loaded approach using IntersectionObserver
 * 
 * @package Chroma_Excellence
 */

(function () {
  'use strict';

  // Helper: Idle callback with polyfill
  const runIdle = (fn) => {
    if ('requestIdleCallback' in window) {
      window.requestIdleCallback(fn, { timeout: 2000 });
    } else {
      setTimeout(fn, 1);
    }
  };

  // Helper: Safe JSON Parse
  const safeParseJSON = (value, fallback) => {
    try {
      return JSON.parse(value);
    } catch (e) {
      console.warn('Failed to parse JSON payload', e);
      return fallback;
    }
  };

  /**
   * Component: Mobile Navigation
   * Loaded immediately as it's critical for mobile UX
   */
  const initMobileNav = () => {
    const mobileNavToggles = document.querySelectorAll('[data-mobile-nav-toggle]');
    const mobileNav = document.querySelector('[data-mobile-nav]');
    if (!mobileNav || !mobileNavToggles.length) return;

    mobileNavToggles.forEach((toggle) => {
      toggle.addEventListener('click', () => {
        mobileNav.classList.toggle('translate-x-full');
        document.body.style.overflow = mobileNav.classList.contains('translate-x-full') ? '' : 'hidden';
      });
    });

    mobileNav.querySelectorAll('a[href^="#"]').forEach((link) => {
      link.addEventListener('click', () => {
        mobileNav.classList.add('translate-x-full');
        document.body.style.overflow = '';
      });
    });
  };

  /**
   * Component: Accordions
   */
  const initAccordions = (container) => {
    const accordions = container.querySelectorAll('[data-accordion]');
    accordions.forEach((accordion) => {
      const triggers = accordion.querySelectorAll('[data-accordion-trigger]');
      triggers.forEach((trigger) => {
        trigger.addEventListener('click', () => {
          const targetId = trigger.getAttribute('aria-controls');
          const content = document.getElementById(targetId);
          if (!content) return;

          const isOpen = !content.classList.contains('hidden');
          accordion.querySelectorAll('[data-accordion-content]').forEach(c => c.classList.add('hidden'));
          if (!isOpen) content.classList.remove('hidden');
        });
      });
    });
  };

  /**
   * Component: Program Wizard
   */
  const initProgramWizard = (wizard) => {
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
      if (title) title.textContent = selected.label;
      if (desc) desc.textContent = selected.description;
      if (learnLink && selected.link) {
        learnLink.setAttribute('href', selected.link);
        learnLink.setAttribute('aria-label', 'Learn more about ' + selected.label);
      }
      if (image && selected.image) image.src = selected.image;

      wizard.querySelector('[data-program-wizard-options]')?.classList.add('hidden');
      result.classList.remove('hidden');
      requestAnimationFrame(() => {
        result.classList.remove('opacity-0', 'translate-y-4');
        result.classList.add('opacity-100', 'translate-y-0');
      });
    };

    const resetWizard = () => {
      if (!result) return;
      result.classList.add('opacity-0', 'translate-y-4');
      result.classList.remove('opacity-100', 'translate-y-0');
      setTimeout(() => {
        result.classList.add('hidden');
        wizard.querySelector('[data-program-wizard-options]')?.classList.remove('hidden');
      }, 500);
    };

    optionButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        const key = btn.getAttribute('data-program-wizard-option');
        const selected = options.find(o => o.key === key);
        if (selected) showResult(selected);
      });
    });
    resetBtn?.addEventListener('click', resetWizard);
  };

  /**
   * Component: Curriculum Radar Chart
   */
  const initCurriculumChart = (container) => {
    const curriculumConfigEl = container.querySelector('[data-curriculum-config]');
    const curriculumChartEl = container.querySelector('[data-curriculum-chart]');
    const curriculumButtons = container.querySelectorAll('[data-curriculum-button]');
    if (!curriculumConfigEl || !curriculumChartEl) return;

    const config = safeParseJSON(curriculumConfigEl.textContent || '{}', {});
    const profiles = config.profiles || [];
    const labels = config.labels || [];
    const defaultProfile = profiles[0];
    let chartInstance = null;

    const setActiveProfile = (key) => {
      const profile = profiles.find(p => p.key === key) || defaultProfile;
      if (!profile) return;

      curriculumButtons.forEach(btn => {
        const isActive = btn.getAttribute('data-curriculum-button') === profile.key;
        btn.classList.toggle('bg-chroma-blue', isActive);
        btn.classList.toggle('text-white', isActive);
        btn.classList.toggle('shadow-soft', isActive);
        btn.classList.toggle('text-brand-ink/70', !isActive);
        btn.classList.toggle('bg-white', !isActive);
      });

      const title = container.querySelector('[data-curriculum-title]');
      const description = container.querySelector('[data-curriculum-description]');
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

    const createChart = () => {
      chartInstance = new Chart(curriculumChartEl.getContext('2d'), {
        type: 'radar',
        data: {
          labels,
          datasets: [{
            label: 'Focus',
            data: (defaultProfile && defaultProfile.data) || [],
            borderColor: defaultProfile?.color || '#4A6C7C',
            backgroundColor: `${defaultProfile?.color || '#4A6C7C'}33`,
            borderWidth: 2,
            pointBackgroundColor: '#ffffff',
            pointBorderColor: defaultProfile?.color || '#4A6C7C',
            pointRadius: 4,
          }],
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
    };

    if (window.Chart) {
      createChart();
    } else {
      // Lazy Load Chart.js
      if (!document.getElementById('chroma-lazy-chart')) {
        const script = document.createElement('script');
        script.id = 'chroma-lazy-chart';
        script.src = `${window.chromaData.themeUrl}/assets/js/chart.min.js`;
        script.onload = createChart;
        document.body.appendChild(script);
      } else {
        // Script already injecting, wait for it
        const checkChart = setInterval(() => {
          if (window.Chart) {
            clearInterval(checkChart);
            createChart();
          }
        }, 100);
      }
    }

    curriculumButtons.forEach(btn => {
      btn.addEventListener('click', () => setActiveProfile(btn.getAttribute('data-curriculum-button')));
    });
    if (defaultProfile) setActiveProfile(defaultProfile.key);
  };

  /**
   * Component: Schedule Tabs
   */
  const initSchedule = (schedule) => {
    const panels = schedule.querySelectorAll('[data-schedule-panel]');
    const tabs = schedule.querySelectorAll('[data-schedule-tab]');
    const defaultKey = tabs[0]?.getAttribute('data-schedule-tab');

    const activate = (key) => {
      tabs.forEach(btn => {
        const isActive = btn.getAttribute('data-schedule-tab') === key;
        btn.classList.toggle('bg-chroma-blue', isActive);
        btn.classList.toggle('text-white', isActive);
        btn.classList.toggle('shadow-soft', isActive);
        btn.classList.toggle('text-brand-ink/60', !isActive);
        btn.classList.toggle('hover:text-chroma-blue', !isActive);
        btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
      });

      panels.forEach(panel => {
        const isMatch = panel.getAttribute('data-schedule-panel') === key;
        panel.classList.toggle('hidden', !isMatch);
      });
    };

    tabs.forEach(btn => btn.addEventListener('click', () => activate(btn.getAttribute('data-schedule-tab'))));
    if (defaultKey) activate(defaultKey);

    schedule.querySelectorAll('[data-schedule-step-trigger]').forEach(trigger => {
      trigger.addEventListener('click', function () {
        const panel = this.closest('[data-schedule-panel]');
        if (!panel) return;
        panel.querySelectorAll('[data-schedule-step-trigger]').forEach(t => {
          t.classList.remove('bg-brand-ink', 'text-white', 'shadow-md', 'scale-105');
          t.classList.add('bg-white', 'text-brand-ink/70');
        });
        this.classList.add('bg-brand-ink', 'text-white', 'shadow-md', 'scale-105');
        const contentTitle = panel.querySelector('[data-content-title]');
        const contentCopy = panel.querySelector('[data-content-copy]');
        if (contentTitle) contentTitle.textContent = this.getAttribute('data-title');
        if (contentCopy) contentCopy.textContent = this.getAttribute('data-copy');
      });
    });
  };

  /**
   * Component: Reviews Carousel
   */
  const initReviewsCarousel = (carousel) => {
    const track = carousel.querySelector('[data-reviews-track]');
    const dots = carousel.querySelectorAll('[data-review-dot]');
    const slides = carousel.querySelectorAll('[data-review-slide]');
    const prevBtn = carousel.querySelector('[data-review-prev]');
    const nextBtn = carousel.querySelector('[data-review-next]');
    let currentIndex = 0;
    let autoplayInterval = null;

    const goToSlide = (index) => {
      if (index < 0) index = slides.length - 1;
      if (index >= slides.length) index = 0;
      currentIndex = index;
      track.style.transform = `translateX(-${currentIndex * 100}%)`;
      dots.forEach((dot, i) => {
        const isActive = i === currentIndex;
        dot.classList.toggle('bg-chroma-red', isActive);
        dot.classList.toggle('w-8', isActive);
        dot.classList.toggle('bg-chroma-blue/30', !isActive);
        dot.classList.toggle('w-3', !isActive);
      });
    };

    const startAutoplay = () => { autoplayInterval = setInterval(() => goToSlide(currentIndex + 1), 6000); };
    const resetAutoplay = () => { clearInterval(autoplayInterval); startAutoplay(); };

    dots.forEach((dot, i) => dot.addEventListener('click', () => { goToSlide(i); resetAutoplay(); }));
    prevBtn?.addEventListener('click', () => { goToSlide(currentIndex - 1); resetAutoplay(); });
    nextBtn?.addEventListener('click', () => { goToSlide(currentIndex + 1); resetAutoplay(); });

    carousel.addEventListener('mouseenter', () => clearInterval(autoplayInterval));
    carousel.addEventListener('mouseleave', startAutoplay);
    startAutoplay();
  };

  /**
   * Component: Location Carousel
   */
  const initLocationCarousel = (carousel) => {
    const track = carousel.querySelector('[data-location-carousel-track]');
    const dots = carousel.querySelectorAll('[data-location-dot]');
    const slides = carousel.querySelectorAll('[data-location-slide]');
    const prevBtn = carousel.querySelector('[data-location-prev]');
    const nextBtn = carousel.querySelector('[data-location-next]');
    let currentIndex = 0;
    let autoplayInterval = null;

    const update = (index) => {
      if (index < 0) index = slides.length - 1;
      if (index >= slides.length) index = 0;
      currentIndex = index;
      track.style.transform = `translateX(-${currentIndex * 100}%)`;
      dots.forEach((dot, i) => {
        const isActive = i === currentIndex;
        dot.classList.toggle('bg-white', isActive);
        dot.classList.toggle('w-6', isActive);
        dot.classList.toggle('bg-white/50', !isActive);
        dot.classList.toggle('w-3', !isActive);
      });
    };

    const start = () => { autoplayInterval = setInterval(() => update(currentIndex + 1), 5000); };
    const reset = () => { clearInterval(autoplayInterval); start(); };

    prevBtn?.addEventListener('click', () => { update(currentIndex - 1); reset(); });
    nextBtn?.addEventListener('click', () => { update(currentIndex + 1); reset(); });
    dots.forEach((dot, i) => dot.addEventListener('click', () => { update(i); reset(); }));

    carousel.addEventListener('mouseenter', () => clearInterval(autoplayInterval));
    carousel.addEventListener('mouseleave', start);
    start();
  };

  /**
   * Component: Sticky CTA
   */
  const initStickyCta = () => {
    const stickyCta = document.getElementById('sticky-cta');
    if (!stickyCta) return;

    const checkScroll = () => {
      if (window.scrollY > 300) {
        stickyCta.classList.remove('translate-y-full');
        window.removeEventListener('scroll', checkScroll);
      }
    };
    window.addEventListener('scroll', checkScroll, { passive: true });
    checkScroll();
  };

  /**
   * Core Initialization Handler
   */
  document.addEventListener('DOMContentLoaded', () => {
    // 1. Critical Nav (Immediate)
    initMobileNav();

    // 2. Setup Intersection Observer for Lazy Components
    const lazyObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const el = entry.target;
          const type = el.dataset.lazyComponent;

          if (type === 'wizard') initProgramWizard(el);
          if (type === 'chart') initCurriculumChart(el);
          if (type === 'schedule') initSchedule(el);
          if (type === 'reviews') initReviewsCarousel(el);
          if (type === 'location-carousel') initLocationCarousel(el);
          if (type === 'accordions') initAccordions(el);

          lazyObserver.unobserve(el);
        }
      });
    });

    // Identify and Observe components
    document.querySelectorAll('[data-program-wizard]').forEach(el => { el.dataset.lazyComponent = 'wizard'; lazyObserver.observe(el); });

    // Fix: Observe the parent container for the chart so initCurriculumChart can find siblings
    document.querySelectorAll('[data-curriculum-chart]').forEach(el => {
      const container = el.closest('section') || el.closest('.grid') || el.parentElement;
      if (container) {
        container.dataset.lazyComponent = 'chart';
        lazyObserver.observe(container);
      }
    });

    document.querySelectorAll('[data-schedule]').forEach(el => { el.dataset.lazyComponent = 'schedule'; lazyObserver.observe(el); });
    document.querySelectorAll('[data-reviews-carousel]').forEach(el => { el.dataset.lazyComponent = 'reviews'; lazyObserver.observe(el); });
    document.querySelectorAll('[data-location-carousel]').forEach(el => { el.dataset.lazyComponent = 'location-carousel'; lazyObserver.observe(el); });
    document.querySelectorAll('[data-accordion]').forEach(el => { el.dataset.lazyComponent = 'accordions'; lazyObserver.observe(el); });

    // 3. Idle-load non-critical features
    runIdle(() => {
      initStickyCta();

      // Smooth Scroll
      document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
          const target = document.querySelector(this.getAttribute('href'));
          if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth' });
          }
        });
      });

      // Reveal Color Animation (already observer based in logic)
      const revealObserver = new IntersectionObserver(entries => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            setTimeout(() => entry.target.classList.remove('grayscale'), 200);
            revealObserver.unobserve(entry.target);
          }
        });
      }, { rootMargin: '-50px', threshold: 0.2 });
      document.querySelectorAll('[data-reveal-color]').forEach(img => revealObserver.observe(img));
    });
  });

})();
