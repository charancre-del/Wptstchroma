/**
 * Main JavaScript - Performance Optimized Version
 * Modular, lazy-loaded approach using IntersectionObserver
 * 
 * @package Chroma_Excellence
 */

(function () {
  'use strict';

  document.documentElement.classList.add('chroma-js');

  // Helper: Idle callback with polyfill
  const runIdle = (fn) => {
    if ('requestIdleCallback' in window) {
      window.requestIdleCallback(fn, { timeout: 2000 });
    } else {
      setTimeout(fn, 1);
    }
  };

  // Helper: Safe JSON Parse
  const jsonStartChars = ['{', '['];

  const safeParseJSON = (value, fallback) => {
    if (typeof value !== 'string') {
      return fallback;
    }

    const trimmed = value.trim();
    if (!trimmed || jsonStartChars.indexOf(trimmed.charAt(0)) === -1) {
      return fallback;
    }

    try {
      return JSON.parse(trimmed);
    } catch (e) {
      return fallback;
    }
  };

  const readJSONPayload = (container, selector, legacyAttribute, fallback) => {
    const payload = container.querySelector(selector);
    if (payload && payload.textContent.trim()) {
      return safeParseJSON(payload.textContent, fallback);
    }

    if (legacyAttribute) {
      const legacyValue = container.getAttribute(legacyAttribute);
      if (legacyValue) {
        return safeParseJSON(legacyValue, fallback);
      }
    }

    return fallback;
  };

  const debounce = (callback, wait = 150) => {
    let timeoutId;

    return (...args) => {
      window.clearTimeout(timeoutId);
      timeoutId = window.setTimeout(() => callback(...args), wait);
    };
  };

  /**
   * Component: HTML Preview Motion Layer
   * Restores the finalized preview's reveal-on-scroll and staggered card movement.
   */
  const initRevealMotion = () => {
    if (document.documentElement.dataset.chromaMotionReady === 'true') return;
    document.documentElement.dataset.chromaMotionReady = 'true';

    const selectors = [
      '.reveal',
      '.fade-in-up',
      '[data-motion]',
      '.chroma-redesign-hero-art',
      '.chroma-bento-heading',
      '.chroma-bento-card',
      '.chroma-prism-stack .max-w-6xl',
      '.chroma-prism-card',
      '.chroma-program-tab',
      '.chroma-template-card',
      '.chroma-locations-showcase .text-center',
      '.chroma-location-map-panel',
      '.chroma-location-list-panel',
      '[data-section="stats"] .group',
      '#tour aside',
      '#tour .grid > div',
    ];

    const motionItems = Array.from(new Set(
      selectors.flatMap(selector => Array.from(document.querySelectorAll(selector)))
    )).filter(el => el instanceof HTMLElement);

    if (!motionItems.length) return;

    const delayFromClass = (el) => {
      if (el.classList.contains('delay-300') || el.classList.contains('d4')) return 300;
      if (el.classList.contains('delay-200') || el.classList.contains('d3')) return 200;
      if (el.classList.contains('delay-100') || el.classList.contains('d2')) return 100;
      if (el.classList.contains('d1')) return 80;
      return null;
    };

    const groupedIndex = new WeakMap();
    motionItems.forEach((el) => {
      el.classList.add('chroma-motion-item');

      if (!el.style.getPropertyValue('--reveal-delay')) {
        const explicitDelay = delayFromClass(el);
        if (explicitDelay !== null) {
          el.style.setProperty('--reveal-delay', `${explicitDelay}ms`);
          return;
        }

        const parent = el.parentElement;
        const parentKey = parent || document.body;
        const index = groupedIndex.get(parentKey) || 0;
        groupedIndex.set(parentKey, index + 1);
        el.style.setProperty('--reveal-delay', `${Math.min(index * 75, 450)}ms`);
      }
    });

    const activate = (el) => {
      el.classList.add('active', 'is-visible');
    };

    if (!('IntersectionObserver' in window)) {
      motionItems.forEach(activate);
      return;
    }

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        activate(entry.target);
        observer.unobserve(entry.target);
      });
    }, {
      rootMargin: '0px 0px 8% 0px',
      threshold: 0.01,
    });

    motionItems.forEach(el => observer.observe(el));

    window.setTimeout(() => {
      motionItems.forEach((el) => {
        if (!el.classList.contains('is-visible')) activate(el);
      });
    }, 2400);
  };

  const normalizeGhlFormEmbeds = () => {
    const formCards = document.querySelectorAll('.chroma-form-scroll-card, .chroma-tour-form-card');
    if (!formCards.length) return;

    formCards.forEach((card) => {
      card.querySelectorAll('.chroma-tour-form-wrapper, [data-chroma-ghl-container], .chroma-ghl-iframe-container').forEach((container) => {
        if (!(container instanceof HTMLElement)) return;

        container.style.setProperty('position', 'relative', 'important');
        container.style.setProperty('left', 'auto', 'important');
        container.style.setProperty('right', 'auto', 'important');
        container.style.setProperty('top', 'auto', 'important');
        container.style.setProperty('bottom', 'auto', 'important');
        container.style.setProperty('transform', 'none', 'important');
        container.style.setProperty('width', '100%', 'important');
        container.style.setProperty('max-width', '100%', 'important');
      });

      card.querySelectorAll('iframe[data-src], iframe[src*="leadconnectorhq.com"], iframe[src*="msgsndr.com"], iframe[src*="gohighlevel"]').forEach((iframe) => {
        if (!(iframe instanceof HTMLIFrameElement)) return;

        const dataSource = iframe.getAttribute('data-src');
        if (dataSource && !iframe.getAttribute('src')) {
          iframe.setAttribute('src', dataSource);
        }

        iframe.style.setProperty('position', 'relative', 'important');
        iframe.style.setProperty('inset', 'auto', 'important');
        iframe.style.setProperty('left', '0', 'important');
        iframe.style.setProperty('right', 'auto', 'important');
        iframe.style.setProperty('top', '0', 'important');
        iframe.style.setProperty('bottom', 'auto', 'important');
        iframe.style.setProperty('transform', 'none', 'important');
        iframe.style.setProperty('display', 'block', 'important');
        iframe.style.setProperty('visibility', 'visible', 'important');
        iframe.style.setProperty('opacity', '1', 'important');
        iframe.style.setProperty('width', '100%', 'important');
        iframe.style.setProperty('max-width', '100%', 'important');
        iframe.style.setProperty('min-width', '0', 'important');
      });
    });
  };

  const observeGhlFormEmbeds = () => {
    normalizeGhlFormEmbeds();

    if (!('MutationObserver' in window) || document.documentElement.dataset.chromaGhlEmbedObserver === 'true') {
      return;
    }

    document.documentElement.dataset.chromaGhlEmbedObserver = 'true';
    const refresh = debounce(normalizeGhlFormEmbeds, 50);
    const observer = new MutationObserver(refresh);

    document.querySelectorAll('.chroma-form-scroll-card, .chroma-tour-form-card').forEach((card) => {
      observer.observe(card, {
        attributes: true,
        attributeFilter: ['style', 'src', 'data-src', 'class'],
        childList: true,
        subtree: true,
      });
    });

    window.setTimeout(normalizeGhlFormEmbeds, 100);
    window.setTimeout(normalizeGhlFormEmbeds, 800);
    window.setTimeout(normalizeGhlFormEmbeds, 2200);
  };

  const syncTourFormScroll = () => {
    normalizeGhlFormEmbeds();

    const grids = document.querySelectorAll('[data-tour-scroll-grid]');
    if (!grids.length) return;

    const shouldSync = window.matchMedia('(min-width: 1024px)').matches;

    grids.forEach((grid) => {
      const infoCard = grid.querySelector('[data-tour-info-card]');
      const formCard = grid.querySelector('[data-tour-form-card]');

      if (!infoCard || !formCard || !shouldSync) {
        grid.style.removeProperty('--tour-card-height');
        return;
      }

      const infoHeight = Math.ceil(infoCard.offsetHeight);
      if (infoHeight > 0) {
        grid.style.setProperty('--tour-card-height', `${infoHeight}px`);
      }
    });
  };

  /**
   * Component: Mobile Navigation
   * Loaded immediately as it's critical for mobile UX
   */
  const initMobileNav = () => {
    const mobileNavToggles = document.querySelectorAll('[data-mobile-nav-toggle]');
    const mobileNav = document.querySelector('[data-mobile-nav]');
    if (!mobileNav || !mobileNavToggles.length) return;

    const isOpen = () => !mobileNav.classList.contains('translate-x-full');
    const setExpanded = (expanded) => {
      mobileNavToggles.forEach((toggle) => {
        toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
      });
    };
    const openMobileNav = () => {
      mobileNav.classList.remove('translate-x-full');
      document.body.style.overflow = 'hidden';
      setExpanded(true);
    };
    const closeMobileNav = () => {
      mobileNav.classList.add('translate-x-full');
      document.body.style.overflow = '';
      setExpanded(false);
    };

    closeMobileNav();

    mobileNavToggles.forEach((toggle) => {
      toggle.addEventListener('click', () => {
        if (isOpen()) {
          closeMobileNav();
        } else {
          openMobileNav();
        }
      });
    });

    mobileNav.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', closeMobileNav);
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && isOpen()) {
        closeMobileNav();
      }
    });
  };

  /**
   * Component: Accordions
   */
  const initAccordions = (container) => {
    const accordions = container.matches('[data-accordion]')
      ? [container]
      : container.querySelectorAll('[data-accordion]');

    accordions.forEach((accordion) => {
      const triggers = accordion.querySelectorAll('[data-accordion-trigger]');
      triggers.forEach((trigger) => {
        trigger.addEventListener('click', () => {
          const targetId = trigger.getAttribute('aria-controls');
          const content = document.getElementById(targetId);
          if (!content) return;

          const isOpen = !content.classList.contains('hidden');
          content.classList.toggle('hidden', isOpen);
          accordion.classList.toggle('open', !isOpen);
          trigger.setAttribute('aria-expanded', isOpen ? 'false' : 'true');

          const icon = trigger.querySelector('[data-accordion-icon]');
          if (icon) icon.textContent = isOpen ? '+' : '−';
        });
      });
    });
  };

  /**
   * Component: Program Wizard
   */
  const initProgramWizard = (wizard) => {
    if (wizard.dataset.programWizardReady === 'true') return;
    wizard.dataset.programWizardReady = 'true';

    const options = readJSONPayload(wizard, '[data-program-wizard-payload]', 'data-options', []);
    const chartLabels = readJSONPayload(wizard, '[data-program-chart-labels]', null, []);
    const optionButtons = wizard.querySelectorAll('[data-program-wizard-option]');
    const result = wizard.querySelector('[data-program-wizard-result]');
    const title = wizard.querySelector('[data-program-wizard-title]');
    const age = wizard.querySelector('[data-program-wizard-age]');
    const desc = wizard.querySelector('[data-program-wizard-description]');
    const prismDescription = wizard.querySelector('[data-program-wizard-prism-description]');
    const learnLink = wizard.querySelector('[data-program-wizard-link]');
    const resetBtn = wizard.querySelector('[data-program-wizard-reset]');
    const chartFills = wizard.querySelectorAll('[data-program-chart-fill]');
    const chartStatuses = wizard.querySelectorAll('[data-program-chart-status]');
    const radar = wizard.querySelector('[data-program-radar]');
    const radarGrid = wizard.querySelector('[data-program-radar-grid]');
    const radarArea = wizard.querySelector('[data-program-radar-area]');
    const radarStroke = wizard.querySelector('[data-program-radar-stroke]');
    const radarPoints = wizard.querySelector('[data-program-radar-points]');
    const defaultOption = options.find(option => option && option.key === 'preschool') || options[0];
    let currentRadarValues = Array.isArray(defaultOption?.prism_data)
      ? defaultOption.prism_data.map(value => Math.max(0, Math.min(100, parseInt(value, 10) || 0)))
      : [0, 0, 0, 0, 0];
    let radarAnimationFrame = null;

    const radarCenter = { x: 280, y: 220 };
    const radarRadius = 138;
    const radarAngles = [-90, -18, 54, 126, 198].map(deg => (deg * Math.PI) / 180);

    const radarPointFor = (value, index, radius = radarRadius) => {
      const clamped = Math.max(0, Math.min(100, parseInt(value, 10) || 0));
      const distance = (clamped / 100) * radius;
      return {
        x: radarCenter.x + Math.cos(radarAngles[index] || 0) * distance,
        y: radarCenter.y + Math.sin(radarAngles[index] || 0) * distance,
        value: clamped,
      };
    };

    const pointsToString = (points) => points.map(point => `${point.x.toFixed(2)},${point.y.toFixed(2)}`).join(' ');

    const buildRadarGrid = () => {
      if (!radarGrid || radarGrid.dataset.gridReady === 'true') return;
      radarGrid.dataset.gridReady = 'true';
      [14, 28, 42, 56, 70, 84, 100].forEach((step) => {
        const ringPoints = radarAngles.map((angle) => {
          const distance = (step / 100) * radarRadius;
          return {
            x: radarCenter.x + Math.cos(angle) * distance,
            y: radarCenter.y + Math.sin(angle) * distance,
          };
        });
        const polygon = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
        polygon.setAttribute('points', pointsToString(ringPoints));
        radarGrid.appendChild(polygon);
      });

      radarAngles.forEach((angle) => {
        const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        line.setAttribute('class', 'radarAxis');
        line.setAttribute('x1', radarCenter.x);
        line.setAttribute('y1', radarCenter.y);
        line.setAttribute('x2', radarCenter.x + Math.cos(angle) * radarRadius);
        line.setAttribute('y2', radarCenter.y + Math.sin(angle) * radarRadius);
        radarGrid.appendChild(line);
      });
    };

    const ensureRadarGridOverlay = () => {
      const svg = radarGrid ? radarGrid.closest('svg') : null;
      if (!svg || !radarStroke || svg.querySelector('[data-program-radar-grid-overlay]')) return;
      const overlayGrid = radarGrid.cloneNode(true);
      overlayGrid.classList.add('radarGridOverlay');
      overlayGrid.removeAttribute('data-program-radar-grid');
      overlayGrid.removeAttribute('data-grid-ready');
      overlayGrid.setAttribute('data-program-radar-grid-overlay', 'true');
      svg.insertBefore(overlayGrid, radarStroke);
    };

    const drawRadar = (normalizedValues) => {
      if (!radar || !radarArea || !radarStroke || !radarPoints) return;
      buildRadarGrid();
      ensureRadarGridOverlay();
      const points = normalizedValues.map((value, index) => radarPointFor(value, index));
      const pointString = pointsToString(points);

      radarArea.setAttribute('points', pointString);
      radarStroke.setAttribute('points', pointString);
      radarPoints.innerHTML = '';
      points.forEach((point, index) => {
        const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        circle.setAttribute('class', 'radarPoint');
        circle.setAttribute('cx', point.x.toFixed(2));
        circle.setAttribute('cy', point.y.toFixed(2));
        circle.setAttribute('r', '7');
        circle.setAttribute('aria-hidden', 'true');
        circle.setAttribute('focusable', 'false');
        radarPoints.appendChild(circle);
      });
    };

    const updateRadar = (values, animate = true) => {
      const normalizedValues = Array.from({ length: 5 }, (_, index) => Math.max(0, Math.min(100, parseInt(values[index], 10) || 0)));

      if (!animate || !('requestAnimationFrame' in window)) {
        currentRadarValues = normalizedValues;
        drawRadar(normalizedValues);
        return;
      }

      if (radarAnimationFrame) {
        window.cancelAnimationFrame(radarAnimationFrame);
      }

      const fromValues = currentRadarValues.slice(0, 5);
      const startedAt = performance.now();
      const duration = 760;

      const tick = (now) => {
        const progress = Math.min(1, (now - startedAt) / duration);
        const eased = 1 - Math.pow(1 - progress, 3);
        const frameValues = normalizedValues.map((target, index) => {
          const from = fromValues[index] || 0;
          return from + (target - from) * eased;
        });

        drawRadar(frameValues);

        if (progress < 1) {
          radarAnimationFrame = window.requestAnimationFrame(tick);
          return;
        }

        currentRadarValues = normalizedValues;
        radarAnimationFrame = null;
      };

      radarAnimationFrame = window.requestAnimationFrame(tick);
    };

    const statusForValue = (value) => {
      if (value >= 82) return 'Advanced';
      if (value >= 70) return 'Active';
      return 'Growing';
    };

    const showResult = (selected) => {
      if (!result) return;
      const selectedTitle = selected.program_title || selected.label || '';
      const selectedAge = selected.age_label || '';
      const selectedColor = selected.prism_color || '#4A6C7C';
      const selectedData = Array.isArray(selected.prism_data) ? selected.prism_data : [];
      const resultScroller = result.firstElementChild || result;

      wizard.style.setProperty('--program-accent', selectedColor);
      result.classList.add('is-updating');

      optionButtons.forEach((button) => {
        const isActive = button.getAttribute('data-program-wizard-option') === selected.key;
        button.classList.toggle('is-active', isActive);
        button.setAttribute('aria-pressed', isActive ? 'true' : 'false');

        if (isActive && window.matchMedia('(max-width: 768px)').matches) {
          const optionScroller = button.closest('[data-program-wizard-options]');
          if (optionScroller && optionScroller.scrollWidth > optionScroller.clientWidth) {
            const targetLeft = button.offsetLeft - ((optionScroller.clientWidth - button.offsetWidth) / 2);
            optionScroller.scrollTo({ left: Math.max(0, targetLeft), behavior: 'smooth' });
          }
        }
      });

      if (title) title.textContent = selectedTitle;
      if (age) {
        age.textContent = selectedAge;
        age.classList.toggle('hidden', !selectedAge);
      }
      if (desc) desc.textContent = selected.description;
      if (prismDescription) {
        prismDescription.textContent = selected.prism_description || '';
      }
      if (learnLink && selected.link) {
        learnLink.setAttribute('href', selected.link);
        learnLink.setAttribute('aria-label', 'Learn more about ' + selectedTitle);
      }

      updateRadar(selectedData);

      window.requestAnimationFrame(() => {
        wizard.scrollLeft = 0;
        const shellGrid = wizard.querySelector('.chroma-program-shell-grid');
        if (shellGrid) {
          shellGrid.scrollLeft = 0;
        }
        result.scrollTop = 0;
        if (resultScroller && resultScroller !== result) {
          resultScroller.scrollTop = 0;
        }
      });

      chartFills.forEach((fill, index) => {
        const value = Math.max(0, Math.min(100, parseInt(selectedData[index], 10) || 0));
        fill.style.width = `${value}%`;
        fill.setAttribute('aria-label', `${chartLabels[index] || 'Pillar'} ${value}%`);
      });

      chartStatuses.forEach((status, index) => {
        const value = Math.max(0, Math.min(100, parseInt(selectedData[index], 10) || 0));
        status.textContent = statusForValue(value);
      });

      window.setTimeout(() => {
        result.classList.remove('is-updating');
      }, 260);
    };

    const resetWizard = () => {
      if (defaultOption) {
        showResult(defaultOption);
      }
    };

    optionButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        const key = btn.getAttribute('data-program-wizard-option');
        const selected = options.find(o => o.key === key);
        if (selected) showResult(selected);
      });
    });
    if (resetBtn) resetBtn.addEventListener('click', resetWizard);

    if (defaultOption) {
      updateRadar(currentRadarValues, false);
      showResult(defaultOption);
    }
  };

  /**
   * Component: Program Prism Slider
   */
  const initProgramChartSlider = (slider) => {
    if (slider.dataset.programChartSliderReady === 'true') return;
    slider.dataset.programChartSliderReady = 'true';

    const options = readJSONPayload(slider, '[data-program-slider-payload]', null, []);
    const chartLabels = readJSONPayload(slider, '[data-program-slider-labels]', null, []);
    const range = slider.querySelector('[data-program-slider-range]');
    const ticks = Array.from(slider.querySelectorAll('[data-program-slider-tick]'));
    const tickScroller = slider.querySelector('.chroma-program-slider-ticks');
    const age = slider.querySelector('[data-program-slider-age]');
    const title = slider.querySelector('[data-program-slider-title]');
    const desc = slider.querySelector('[data-program-slider-description]');
    const prism = slider.querySelector('[data-program-slider-prism]');
    const radarGrid = slider.querySelector('[data-program-slider-grid]');
    const radarArea = slider.querySelector('[data-program-slider-area]');
    const radarStroke = slider.querySelector('[data-program-slider-stroke]');
    const radarPoints = slider.querySelector('[data-program-slider-points]');
    const defaultIndex = range ? Math.max(0, Math.min(options.length - 1, parseInt(range.value, 10) || 0)) : 0;
    const defaultOption = options[defaultIndex] || options[0];
    let currentRadarValues = Array.isArray(defaultOption?.prism_data)
      ? defaultOption.prism_data.map(value => Math.max(0, Math.min(100, parseInt(value, 10) || 0)))
      : [0, 0, 0, 0, 0];
    let radarAnimationFrame = null;

    if (!options.length || !range || !radarGrid || !radarArea || !radarStroke || !radarPoints) return;

    const radarCenter = { x: 280, y: 220 };
    const radarRadius = 138;
    const radarAngles = [-90, -18, 54, 126, 198].map(deg => (deg * Math.PI) / 180);
    const clampValue = (value) => Math.max(0, Math.min(100, parseInt(value, 10) || 0));
    const pointFor = (value, index, radius = radarRadius) => {
      const distance = (clampValue(value) / 100) * radius;
      return {
        x: radarCenter.x + Math.cos(radarAngles[index] || 0) * distance,
        y: radarCenter.y + Math.sin(radarAngles[index] || 0) * distance,
      };
    };
    const pointsToString = (points) => points.map(point => `${point.x.toFixed(2)},${point.y.toFixed(2)}`).join(' ');

    const buildRadarGrid = () => {
      if (radarGrid.dataset.gridReady === 'true') return;
      radarGrid.dataset.gridReady = 'true';

      [14, 28, 42, 56, 70, 84, 100].forEach((step) => {
        const ringPoints = radarAngles.map((angle) => {
          const distance = (step / 100) * radarRadius;
          return {
            x: radarCenter.x + Math.cos(angle) * distance,
            y: radarCenter.y + Math.sin(angle) * distance,
          };
        });
        const polygon = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
        polygon.setAttribute('points', pointsToString(ringPoints));
        radarGrid.appendChild(polygon);
      });

      radarAngles.forEach((angle) => {
        const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        line.setAttribute('class', 'radarAxis');
        line.setAttribute('x1', radarCenter.x);
        line.setAttribute('y1', radarCenter.y);
        line.setAttribute('x2', radarCenter.x + Math.cos(angle) * radarRadius);
        line.setAttribute('y2', radarCenter.y + Math.sin(angle) * radarRadius);
        radarGrid.appendChild(line);
      });
    };

    const drawRadar = (values) => {
      buildRadarGrid();
      const normalizedValues = Array.from({ length: 5 }, (_, index) => clampValue(values[index]));
      const points = normalizedValues.map((value, index) => pointFor(value, index));
      const pointString = pointsToString(points);
      radarArea.setAttribute('points', pointString);
      radarStroke.setAttribute('points', pointString);
      radarPoints.innerHTML = '';

      points.forEach((point, index) => {
        const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        circle.setAttribute('class', 'radarPoint');
        circle.setAttribute('cx', point.x.toFixed(2));
        circle.setAttribute('cy', point.y.toFixed(2));
        circle.setAttribute('r', '7');
        circle.setAttribute('aria-hidden', 'true');
        circle.setAttribute('focusable', 'false');
        radarPoints.appendChild(circle);
      });
    };

    const updateRadar = (values, animate = true) => {
      const targetValues = Array.from({ length: 5 }, (_, index) => clampValue(values[index]));

      if (!animate || !('requestAnimationFrame' in window)) {
        currentRadarValues = targetValues;
        drawRadar(targetValues);
        return;
      }

      if (radarAnimationFrame) {
        window.cancelAnimationFrame(radarAnimationFrame);
      }

      const fromValues = currentRadarValues.slice(0, 5);
      const startedAt = performance.now();
      const duration = 680;

      const tickFrame = (now) => {
        const progress = Math.min(1, (now - startedAt) / duration);
        const eased = 1 - Math.pow(1 - progress, 3);
        const frameValues = targetValues.map((target, index) => {
          const from = fromValues[index] || 0;
          return from + (target - from) * eased;
        });

        drawRadar(frameValues);

        if (progress < 1) {
          radarAnimationFrame = window.requestAnimationFrame(tickFrame);
          return;
        }

        currentRadarValues = targetValues;
        radarAnimationFrame = null;
      };

      radarAnimationFrame = window.requestAnimationFrame(tickFrame);
    };

    const activate = (index, animate = true) => {
      const safeIndex = Math.max(0, Math.min(options.length - 1, parseInt(index, 10) || 0));
      const selected = options[safeIndex] || options[0];
      if (!selected) return;

      slider.style.setProperty('--program-accent', selected.prism_color || '#4A6C7C');
      range.value = String(safeIndex);
      if (age) {
        age.textContent = selected.age_label || '';
        age.classList.toggle('hidden', !selected.age_label);
      }
      if (title) title.textContent = selected.program_title || selected.label || '';
      if (desc) desc.textContent = selected.description || '';
      if (prism) prism.textContent = selected.prism_description || '';

      let activeTick = null;
      ticks.forEach((tick) => {
        const isActive = parseInt(tick.getAttribute('data-program-slider-tick'), 10) === safeIndex;
        tick.classList.toggle('is-active', isActive);
        tick.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        if (isActive) activeTick = tick;
      });

      if (activeTick && tickScroller && tickScroller.scrollWidth > tickScroller.clientWidth) {
        const targetLeft = activeTick.offsetLeft - ((tickScroller.clientWidth - activeTick.offsetWidth) / 2);
        tickScroller.scrollTo({
          left: Math.max(0, targetLeft),
          behavior: animate ? 'smooth' : 'auto',
        });
      }

      updateRadar(selected.prism_data || [], animate);
    };

    range.addEventListener('input', () => activate(range.value));
    ticks.forEach((tick) => {
      tick.addEventListener('click', () => activate(tick.getAttribute('data-program-slider-tick')));
    });

    activate(defaultIndex, false);
  };

  /**
   * Component: SVG Radar Charts
   */
  const initRadarChart = (chart) => {
    if (chart.dataset.radarReady === 'true') return;
    chart.dataset.radarReady = 'true';

    const grid = chart.querySelector('[data-radar-grid]');
    const area = chart.querySelector('[data-radar-area]');
    const stroke = chart.querySelector('[data-radar-stroke]');
    const pointsGroup = chart.querySelector('[data-radar-points]');
    const values = safeParseJSON(chart.getAttribute('data-radar-values') || '[]', []);
    const color = chart.getAttribute('data-radar-color') || '#A84B38';
    if (!grid || !area || !stroke || !pointsGroup || !values.length) return;

    const center = { x: 280, y: 225 };
    const radius = 150;
    const angles = [-90, -18, 54, 126, 198].map(deg => (deg * Math.PI) / 180);
    const labels = ['Physical', 'Emotional', 'Social', 'Academic', 'Creative'];
    const clampValue = (value) => Math.max(0, Math.min(100, parseInt(value, 10) || 0));
    const pointFor = (value, index, pointRadius = radius) => {
      const distance = (clampValue(value) / 100) * pointRadius;
      return {
        x: center.x + Math.cos(angles[index] || 0) * distance,
        y: center.y + Math.sin(angles[index] || 0) * distance,
      };
    };
    const pointsToString = (points) => points.map(point => `${point.x.toFixed(2)},${point.y.toFixed(2)}`).join(' ');

    [20, 40, 60, 80, 100].forEach((step) => {
      const ringPoints = angles.map((angle) => {
        const distance = (step / 100) * radius;
        return {
          x: center.x + Math.cos(angle) * distance,
          y: center.y + Math.sin(angle) * distance,
        };
      });
      const polygon = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
      polygon.setAttribute('points', pointsToString(ringPoints));
      grid.appendChild(polygon);
    });

    angles.forEach((angle) => {
      const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
      line.setAttribute('class', 'radarAxis');
      line.setAttribute('x1', center.x);
      line.setAttribute('y1', center.y);
      line.setAttribute('x2', center.x + Math.cos(angle) * radius);
      line.setAttribute('y2', center.y + Math.sin(angle) * radius);
      grid.appendChild(line);
    });

    const svg = grid.closest('svg');
    if (svg && !svg.querySelector('[data-radar-grid-overlay]')) {
      const overlayGrid = grid.cloneNode(true);
      overlayGrid.classList.add('radarGridOverlay');
      overlayGrid.removeAttribute('data-radar-grid');
      overlayGrid.setAttribute('data-radar-grid-overlay', 'true');
      svg.insertBefore(overlayGrid, stroke);
    }

    chart.style.setProperty('--program-accent', color);
    chart.style.setProperty('--radar-color', color);

    const normalized = Array.from({ length: 5 }, (_, index) => clampValue(values[index]));
    const initialPoints = normalized.map(() => ({ x: center.x, y: center.y }));
    area.setAttribute('points', pointsToString(initialPoints));
    stroke.setAttribute('points', pointsToString(initialPoints));

    normalized.forEach((value, index) => {
      const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
      circle.setAttribute('class', 'radarPoint');
      circle.setAttribute('cx', center.x);
      circle.setAttribute('cy', center.y);
      circle.setAttribute('r', '10');
      circle.setAttribute('aria-hidden', 'true');
      circle.setAttribute('focusable', 'false');
      pointsGroup.appendChild(circle);
    });

    window.requestAnimationFrame(() => {
      const targetPoints = normalized.map((value, index) => pointFor(value, index));
      area.setAttribute('points', pointsToString(targetPoints));
      stroke.setAttribute('points', pointsToString(targetPoints));
      Array.from(pointsGroup.children).forEach((circle, index) => {
        circle.setAttribute('cx', targetPoints[index].x.toFixed(2));
        circle.setAttribute('cy', targetPoints[index].y.toFixed(2));
      });
    });
  };

  /**
   * Component: Sun Schedule
   */
  const initSunSchedule = (schedule) => {
    if (schedule.dataset.sunScheduleReady === 'true') return;
    schedule.dataset.sunScheduleReady = 'true';

    const steps = readJSONPayload(schedule, '[data-sun-steps]', 'data-schedule-steps', []);
    const range = schedule.querySelector('[data-sun-range]');
    const sun = schedule.querySelector('[data-sun-orb]');
    const progress = schedule.querySelector('[data-sun-progress]');
    const time = schedule.querySelector('[data-sun-time]');
    const title = schedule.querySelector('[data-sun-title]');
    const copy = schedule.querySelector('[data-sun-copy]');
    if (!range || !sun || !progress || !time || !title || !copy || !steps.length) return;

    range.max = String(Math.max(0, steps.length - 1));

    const update = (index) => {
      const safeIndex = Math.max(0, Math.min(steps.length - 1, parseInt(index, 10) || 0));
      const step = steps[safeIndex];
      const percent = steps.length <= 1 ? 0 : (safeIndex / (steps.length - 1)) * 100;
      const arc = Math.sin((percent / 100) * Math.PI);

      schedule.style.setProperty('--sun-progress', `${percent}%`);
      sun.style.left = `calc(${8 + percent * 0.78}% - 36px)`;
      sun.style.top = `${64 - arc * 50}%`;
      progress.style.width = `${percent}%`;

      [time, title, copy].forEach(el => el.classList.add('is-changing'));
      window.setTimeout(() => {
        time.textContent = step.time || '';
        title.textContent = step.title || '';
        copy.textContent = step.copy || '';
        [time, title, copy].forEach(el => el.classList.remove('is-changing'));
      }, 130);
    };

    range.addEventListener('input', () => update(range.value));
    update(range.value || 0);
  };

  /**
   * Component: Moments Carousel
   */
  const initMomentsCarousel = (carousel) => {
    if (carousel.dataset.momentsReady === 'true') return;
    carousel.dataset.momentsReady = 'true';

    const track = carousel.querySelector('[data-moments-track]');
    const slides = Array.from(carousel.querySelectorAll('[data-moments-slide]'));
    const prev = carousel.querySelector('[data-moments-prev]');
    const next = carousel.querySelector('[data-moments-next]');
    const dots = Array.from(carousel.querySelectorAll('[data-moments-dot]'));
    if (!track || slides.length <= 1) return;

    let activeIndex = 0;

    const activate = (index) => {
      activeIndex = (index + slides.length) % slides.length;
      track.style.transform = `translateX(-${activeIndex * 100}%)`;
      dots.forEach((dot, dotIndex) => {
        dot.classList.toggle('is-active', dotIndex === activeIndex);
        dot.setAttribute('aria-current', dotIndex === activeIndex ? 'true' : 'false');
      });
    };

    if (prev) prev.addEventListener('click', () => activate(activeIndex - 1));
    if (next) next.addEventListener('click', () => activate(activeIndex + 1));
    dots.forEach((dot, dotIndex) => dot.addEventListener('click', () => activate(dotIndex)));
    activate(0);
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
            borderColor: (defaultProfile && defaultProfile.color) || '#4A6C7C',
            backgroundColor: `${(defaultProfile && defaultProfile.color) || '#4A6C7C'}33`,
            borderWidth: 2,
            pointBackgroundColor: '#ffffff',
            pointBorderColor: (defaultProfile && defaultProfile.color) || '#4A6C7C',
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
    if (schedule.dataset.scheduleInitialized === 'true') {
      return;
    }
    schedule.dataset.scheduleInitialized = 'true';

    readJSONPayload(schedule, '[data-schedule-tracks]', 'data-tracks', []);
    const panels = schedule.querySelectorAll('[data-schedule-panel]');
    const tabs = schedule.querySelectorAll('[data-schedule-tab]');
    const defaultKey = tabs[0] ? tabs[0].getAttribute('data-schedule-tab') : '';
    const activeTabClasses = ['bg-chroma-red', 'text-white', 'shadow-soft'];
    const inactiveTabClasses = ['text-gray-900', 'hover:text-chroma-red'];
    const activeStepClasses = ['bg-brand-ink', 'text-white', 'shadow-md', 'scale-105'];
    const inactiveStepClasses = ['bg-white', 'text-brand-ink', 'hover:text-brand-ink', 'hover:bg-white/80'];

    const resetClasses = (el, classes) => {
      classes.forEach(className => el.classList.remove(className));
    };

    const applyClasses = (el, classes) => {
      classes.forEach(className => el.classList.add(className));
    };

    const activate = (key) => {
      tabs.forEach(btn => {
        const isActive = btn.getAttribute('data-schedule-tab') === key;
        resetClasses(btn, [...activeTabClasses, ...inactiveTabClasses, 'text-brand-ink/60']);
        applyClasses(btn, isActive ? activeTabClasses : inactiveTabClasses);
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
          resetClasses(t, [...activeStepClasses, ...inactiveStepClasses, 'transform', 'text-brand-ink/70', 'text-brand-ink/80']);
          applyClasses(t, inactiveStepClasses);
        });
        resetClasses(this, [...inactiveStepClasses, 'text-brand-ink/70', 'text-brand-ink/80']);
        applyClasses(this, activeStepClasses);
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
    if (carousel.dataset.reviewsReady === 'true') return;
    carousel.dataset.reviewsReady = 'true';

    const track = carousel.querySelector('[data-reviews-track]');
    const viewport = carousel.querySelector('.chroma-review-viewport');
    const dots = carousel.querySelectorAll('[data-review-dot]');
    const slides = carousel.querySelectorAll('[data-review-slide]');
    const prevBtn = carousel.querySelector('[data-review-prev]');
    const nextBtn = carousel.querySelector('[data-review-next]');
    let currentIndex = 0;
    let autoplayInterval = null;

    slides.forEach((slide) => {
      const quote = slide.querySelector('blockquote');
      const length = quote ? quote.textContent.trim().length : 0;
      slide.classList.toggle('is-long-review', length > 360);
      slide.classList.toggle('is-extra-long-review', length > 620);
    });

    const syncViewportHeight = () => {
      if (!viewport || !slides[currentIndex]) return;
      window.requestAnimationFrame(() => {
        const activeHeight = Math.ceil(slides[currentIndex].scrollHeight);
        if (activeHeight > 0) viewport.style.height = `${activeHeight}px`;
      });
    };

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
      syncViewportHeight();
    };

    const startAutoplay = () => { autoplayInterval = setInterval(() => goToSlide(currentIndex + 1), 6000); };
    const resetAutoplay = () => { clearInterval(autoplayInterval); startAutoplay(); };

    dots.forEach((dot, i) => dot.addEventListener('click', () => { goToSlide(i); resetAutoplay(); }));
    if (prevBtn) prevBtn.addEventListener('click', () => { goToSlide(currentIndex - 1); resetAutoplay(); });
    if (nextBtn) nextBtn.addEventListener('click', () => { goToSlide(currentIndex + 1); resetAutoplay(); });

    carousel.addEventListener('mouseenter', () => clearInterval(autoplayInterval));
    carousel.addEventListener('mouseleave', startAutoplay);
    window.addEventListener('resize', debounce(syncViewportHeight, 120));
    syncViewportHeight();
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

    if (prevBtn) prevBtn.addEventListener('click', () => { update(currentIndex - 1); reset(); });
    if (nextBtn) nextBtn.addEventListener('click', () => { update(currentIndex + 1); reset(); });
    dots.forEach((dot, i) => dot.addEventListener('click', () => { update(i); reset(); }));

    carousel.addEventListener('mouseenter', () => clearInterval(autoplayInterval));
    carousel.addEventListener('mouseleave', start);
    start();
  };

  /**
   * Component: Location Explorer
   */
  const initLocationExplorer = (explorer) => {
    if (explorer.dataset.locationExplorerReady === 'true') return;
    explorer.dataset.locationExplorerReady = 'true';

    const mapId = explorer.getAttribute('data-map-target') || 'chroma-locations-map';
    const filterButtons = explorer.querySelectorAll('[data-location-filter]');
    const cards = Array.from(explorer.querySelectorAll('[data-location-card-wrap]'));
    const list = explorer.querySelector('[data-location-list]');
    const status = explorer.querySelector('[data-location-status]');
    const count = explorer.querySelector('[data-location-summary-count]');
    const summaryLabel = explorer.querySelector('[data-location-summary-label]');
    const closestButton = explorer.querySelector('[data-location-filter="closest"]');
    let userCoords = null;
    let locationRequestId = 0;

    const defaultLocationStatus = 'Share your location to sort campuses by distance, or choose a region to zoom the map.';
    const deniedLocationStatus = 'Location was not shared. Showing all campuses. Choose a region to narrow the map.';
    const blockedLocationStatus = 'Location is blocked in your browser for this site. Click the lock icon in the address bar, allow Location, then try again.';
    const closestLocationStatus = 'Closest campuses sorted by distance.';
    const timeoutLocationStatus = 'We could not get your location in time. Showing all campuses. Try again or choose a region.';

    const setLocationRequestPending = (isPending) => {
      if (!closestButton) return;
      closestButton.disabled = isPending;
      closestButton.setAttribute('aria-busy', isPending ? 'true' : 'false');
      closestButton.classList.toggle('is-loading', isPending);
    };

    const distanceMiles = (lat1, lng1, lat2, lng2) => {
      const toRadians = (value) => value * Math.PI / 180;
      const radiusMiles = 3958.8;
      const deltaLat = toRadians(lat2 - lat1);
      const deltaLng = toRadians(lng2 - lng1);
      const a = Math.sin(deltaLat / 2) * Math.sin(deltaLat / 2)
        + Math.cos(toRadians(lat1)) * Math.cos(toRadians(lat2))
        * Math.sin(deltaLng / 2) * Math.sin(deltaLng / 2);
      return radiusMiles * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
    };

    const setActiveButton = (filter) => {
      filterButtons.forEach((button) => {
        const isActive = button.getAttribute('data-location-filter') === filter;
        button.classList.toggle('is-active', isActive);
        button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
      });
    };

    const updateSummary = (visibleCards, label) => {
      if (count) {
        count.textContent = `${visibleCards.length} ${visibleCards.length === 1 ? 'location' : 'locations'}`;
      }
      if (summaryLabel) {
        summaryLabel.textContent = label;
      }
    };

    const dispatchFilter = (visibleCards) => {
      const ids = visibleCards.map((card) => parseInt(card.getAttribute('data-location-id'), 10)).filter(Boolean);
      window.chromaLocationExplorerState = window.chromaLocationExplorerState || {};
      window.chromaLocationExplorerState[mapId] = Object.assign(
        {},
        window.chromaLocationExplorerState[mapId] || {},
        { ids }
      );
      window.dispatchEvent(new CustomEvent('chroma:locations-filter', {
        detail: { mapId, ids },
      }));
    };

    const focusCardOnMap = (card) => {
      const id = card ? parseInt(card.getAttribute('data-location-id'), 10) : 0;
      if (!id) return;

      cards.forEach((item) => {
        item.classList.toggle('is-active', parseInt(item.getAttribute('data-location-id'), 10) === id);
      });

      window.chromaLocationExplorerState = window.chromaLocationExplorerState || {};
      window.chromaLocationExplorerState[mapId] = Object.assign(
        {},
        window.chromaLocationExplorerState[mapId] || {},
        { focusId: id }
      );
      window.dispatchEvent(new CustomEvent('chroma:locations-focus', {
        detail: { mapId, id },
      }));
    };

    const applyFilter = (filter, labelOverride, statusOverride) => {
      const isClosestWithCoords = filter === 'closest' && userCoords;
      setActiveButton(isClosestWithCoords ? 'closest' : filter);
      let visibleCards = cards.filter((card) => {
        if (filter === 'closest' || filter === 'all') return true;
        const regions = (card.getAttribute('data-location-regions') || '').split(/\s+/);
        return regions.indexOf(filter) !== -1;
      });

      if (isClosestWithCoords) {
        visibleCards.forEach((card) => {
          const lat = parseFloat(card.getAttribute('data-location-lat'));
          const lng = parseFloat(card.getAttribute('data-location-lng'));
          const distance = Number.isFinite(lat) && Number.isFinite(lng)
            ? distanceMiles(userCoords.latitude, userCoords.longitude, lat, lng)
            : Number.POSITIVE_INFINITY;
          card.dataset.locationDistance = String(distance);
          const distanceEl = card.querySelector('[data-location-distance]');
          if (distanceEl && Number.isFinite(distance)) {
            distanceEl.textContent = `${distance.toFixed(1)} mi away`;
            distanceEl.classList.remove('hidden');
          }
        });
        visibleCards.sort((left, right) => {
          return parseFloat(left.dataset.locationDistance || '99999') - parseFloat(right.dataset.locationDistance || '99999');
        });
        if (status) {
          status.textContent = statusOverride || closestLocationStatus;
        }
      } else {
        cards.forEach((card) => {
          const distanceEl = card.querySelector('[data-location-distance]');
          if (distanceEl) distanceEl.classList.add('hidden');
        });
        if (status && statusOverride) {
          status.textContent = statusOverride;
        }
      }

      cards.forEach((card) => {
        const isVisible = visibleCards.indexOf(card) !== -1;
        card.classList.toggle('hidden', !isVisible);
      });

      if (list) {
        visibleCards.forEach((card) => list.appendChild(card));
      }

      const readableFilter = labelOverride || (isClosestWithCoords ? 'Closest to your browser location' : '');
      updateSummary(visibleCards, readableFilter);
      dispatchFilter(visibleCards);

      if (isClosestWithCoords) {
        window.setTimeout(() => focusCardOnMap(visibleCards[0]), 80);
      }
    };

    const requestClosest = () => {
      if (!navigator.geolocation) {
        userCoords = null;
        applyFilter('all', '', deniedLocationStatus);
        return;
      }

      const requestId = ++locationRequestId;
      let settled = false;
      setLocationRequestPending(true);
      if (status) status.textContent = 'Asking your browser for location permission...';

      const finish = (callback) => {
        if (settled || requestId !== locationRequestId) return;
        settled = true;
        window.clearTimeout(watchdog);
        setLocationRequestPending(false);
        callback();
      };

      const watchdog = window.setTimeout(() => {
        finish(() => {
          userCoords = null;
          applyFilter('all', '', timeoutLocationStatus);
        });
      }, 12000);

      navigator.geolocation.getCurrentPosition(
        (position) => {
          finish(() => {
            userCoords = position.coords;
            applyFilter('closest', 'Closest to your browser location', closestLocationStatus);
          });
        },
        (error) => {
          finish(() => {
            userCoords = null;
            const isBlocked = error && error.code === error.PERMISSION_DENIED;
            const isTimeout = error && error.code === error.TIMEOUT;
            applyFilter('all', '', isBlocked ? blockedLocationStatus : (isTimeout ? timeoutLocationStatus : deniedLocationStatus));
          });
        },
        { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 }
      );
    };

    filterButtons.forEach((button) => {
      button.addEventListener('click', () => {
        const filter = button.getAttribute('data-location-filter') || 'closest';
        if (filter === 'closest') {
          requestClosest();
          return;
        }

        if (status) status.textContent = 'Filtered by live campus region.';
        applyFilter(filter, button.textContent.trim(), 'Filtered by live campus region.');
      });
    });

    explorer.querySelectorAll('[data-location-card]').forEach((button) => {
      button.addEventListener('click', () => {
        focusCardOnMap(button.closest('[data-location-card-wrap]'));
      });
    });

    applyFilter('all', '', defaultLocationStatus);
  };

  /**
   * Component: Sticky CTA
   */
  const initStickyCta = () => {
    const stickyCta = document.getElementById('sticky-cta');
    if (!stickyCta) return;

    const offsetFixedWidgets = () => {
      if (window.innerWidth >= 768) {
        return;
      }

      const height = Math.ceil(stickyCta.getBoundingClientRect().height);
      if (!height) {
        return;
      }

      document.documentElement.style.setProperty('--chroma-sticky-cta-height', `${height}px`);
      document.body.classList.add('chroma-sticky-cta-visible');

      const candidates = document.querySelectorAll([
        'iframe[src*="leadconnector"]',
        'iframe[src*="msgsndr"]',
        'iframe[src*="gohighlevel"]',
        'iframe[title*="chat" i]',
        '[id*="leadconnector" i]',
        '[id*="lc-" i]',
        '[id*="chat" i]',
        '[class*="leadconnector" i]',
        '[class*="lc-" i]',
        '[class*="chat" i]',
      ].join(','));

      candidates.forEach(candidate => {
        if (!(candidate instanceof HTMLElement)) {
          return;
        }

        const styles = window.getComputedStyle(candidate);
        if (styles.position !== 'fixed') {
          return;
        }

        const rect = candidate.getBoundingClientRect();
        const nearBottom = rect.bottom > window.innerHeight - 180;
        const nearRight = rect.right > window.innerWidth - 220;
        if (!nearBottom || !nearRight || rect.width < 40 || rect.height < 40) {
          return;
        }

        candidate.style.setProperty('bottom', `calc(${height}px + 12px)`, 'important');
        candidate.dataset.chromaStickyCtaOffset = 'true';
      });
    };

    const startWidgetObserver = () => {
      if (!('MutationObserver' in window)) {
        return;
      }

      const observer = new MutationObserver(offsetFixedWidgets);
      observer.observe(document.body, { childList: true, subtree: true });
      window.addEventListener('resize', offsetFixedWidgets, { passive: true });
      window.setTimeout(() => observer.disconnect(), 15000);
    };

    const checkScroll = () => {
      if (window.scrollY > 300) {
        stickyCta.classList.remove('translate-y-full');
        window.requestAnimationFrame(offsetFixedWidgets);
        startWidgetObserver();
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
    initRevealMotion();
    observeGhlFormEmbeds();
    syncTourFormScroll();
    window.addEventListener('resize', debounce(syncTourFormScroll, 150));
    window.addEventListener('load', () => {
      observeGhlFormEmbeds();
      syncTourFormScroll();
    });
    setTimeout(syncTourFormScroll, 500);
    setTimeout(observeGhlFormEmbeds, 2400);

    const initializeLazyComponent = (el, type) => {
      if (type === 'wizard') initProgramWizard(el);
      if (type === 'program-chart-slider') initProgramChartSlider(el);
      if (type === 'radar') initRadarChart(el);
      if (type === 'sun-schedule') initSunSchedule(el);
      if (type === 'chart') initCurriculumChart(el);
      if (type === 'schedule') initSchedule(el);
      if (type === 'reviews') initReviewsCarousel(el);
      if (type === 'location-carousel') initLocationCarousel(el);
      if (type === 'accordions') initAccordions(el);
    };

    // 2. Setup Intersection Observer for Lazy Components
    const lazyObserver = 'IntersectionObserver' in window ? new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const el = entry.target;
          initializeLazyComponent(el, el.dataset.lazyComponent);
          lazyObserver.unobserve(el);
        }
      });
    }) : null;

    const observeOrInitialize = (el, type) => {
      el.dataset.lazyComponent = type;
      if (lazyObserver) {
        lazyObserver.observe(el);
        return;
      }
      initializeLazyComponent(el, type);
    };

    // Identify and Observe components
    document.querySelectorAll('[data-program-wizard]').forEach(initProgramWizard);
    document.querySelectorAll('[data-program-chart-slider]').forEach(el => observeOrInitialize(el, 'program-chart-slider'));
    document.querySelectorAll('[data-radar-chart]').forEach(el => observeOrInitialize(el, 'radar'));
    document.querySelectorAll('[data-sun-schedule]').forEach(el => observeOrInitialize(el, 'sun-schedule'));
    document.querySelectorAll('[data-moments-carousel]').forEach(initMomentsCarousel);

    // Fix: Observe the parent container for the chart so initCurriculumChart can find siblings
    document.querySelectorAll('[data-curriculum-chart]').forEach(el => {
      const container = el.closest('section') || el.closest('.grid') || el.parentElement;
      if (container) {
        observeOrInitialize(container, 'chart');
      }
    });

    document.querySelectorAll('[data-schedule]').forEach(initSchedule);
    document.querySelectorAll('[data-reviews-carousel]').forEach(el => observeOrInitialize(el, 'reviews'));
    document.querySelectorAll('[data-location-carousel]').forEach(el => observeOrInitialize(el, 'location-carousel'));
    document.querySelectorAll('[data-location-explorer]').forEach(initLocationExplorer);
    document.querySelectorAll('[data-accordion-group]').forEach(el => observeOrInitialize(el, 'accordions'));

    // 3. Idle-load non-critical features
    runIdle(() => {
      initStickyCta();

      // Smooth Scroll
      document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
          const hash = this.getAttribute('href') || '';
          if (hash.length < 2) {
            return;
          }

          let target = null;
          try {
            target = document.getElementById(decodeURIComponent(hash.slice(1)));
          } catch (error) {
            target = document.getElementById(hash.slice(1));
          }

          if (target) {
            e.preventDefault();
            const header = document.querySelector('[data-header]');
            const headerOffset = header ? header.getBoundingClientRect().height + 16 : 16;
            const targetTop = target.getBoundingClientRect().top + window.scrollY - headerOffset;
            window.scrollTo({
              top: Math.max(0, targetTop),
              behavior: 'smooth',
            });
          }
        });
      });

      // Reveal Color Animation (already observer based in logic)
      if (!('IntersectionObserver' in window)) {
        document.querySelectorAll('[data-reveal-color]').forEach(img => img.classList.remove('grayscale'));
        return;
      }

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
