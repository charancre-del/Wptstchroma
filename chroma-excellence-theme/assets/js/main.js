/**
 * Main JavaScript
 * Data-attribute based modular approach
 *
 * @package Chroma_Excellence
 */

document.addEventListener('DOMContentLoaded', function () {
  /**
   * Mobile Nav Toggle
   */
  const mobileNavToggles = document.querySelectorAll('[data-mobile-nav-toggle]');
  const mobileNav = document.querySelector('[data-mobile-nav]');

  mobileNavToggles.forEach((toggle) => {
    toggle.addEventListener('click', () => {
      mobileNav.classList.toggle('translate-x-full');
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
   * Programs Wizard
   */
  const wizardButtons = document.querySelectorAll('[data-wizard-age]');
  const wizardResult = document.getElementById('wizard-result');
  const wizardTitle = document.getElementById('wizard-title');
  const wizardDesc = document.getElementById('wizard-desc');
  const wizardLink = document.getElementById('wizard-learn-link');
  const wizardReset = document.querySelector('[data-wizard-reset]');

  if (wizardButtons.length && window.chromaProgramsData) {
    wizardButtons.forEach((btn) => {
      btn.addEventListener('click', () => {
        const age = btn.dataset.wizardAge;
        const program = window.chromaProgramsData[age];
        if (!program) return;

        // Show result
        wizardResult.classList.remove('hidden');
        wizardTitle.textContent = program.title;
        wizardDesc.textContent = program.description;
        wizardLink.href = program.link;

        // Scroll to result
        wizardResult.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      });
    });

    if (wizardReset) {
      wizardReset.addEventListener('click', () => {
        wizardResult.classList.add('hidden');
      });
    }
  }

  /**
   * Curriculum Chart (Chart.js)
   */
  const curriculumCanvas = document.getElementById('curriculumChart');
  const curriculumTabs = document.querySelectorAll('[data-curriculum-tab]');
  const curriculumTitle = document.getElementById('curriculum-title');
  const curriculumDesc = document.getElementById('curriculum-desc');

  if (curriculumCanvas && window.Chart && window.chromaCurriculumData) {
    const ctx = curriculumCanvas.getContext('2d');
    const baseData = window.chromaCurriculumData.infant;

    const curriculumChart = new Chart(ctx, {
      type: 'radar',
      data: {
        labels: window.chromaCurriculumLabels || ['Physical', 'Emotional', 'Social', 'Academic', 'Creative'],
        datasets: [{
          label: 'Focus',
          data: baseData.data,
          borderColor: baseData.color,
          backgroundColor: baseData.color + '33',
          borderWidth: 2,
          pointBackgroundColor: '#ffffff',
          pointBorderColor: baseData.color,
          pointRadius: 4
        }]
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
              font: { family: 'Inter', size: 12 },
              color: '#263238'
            }
          }
        }
      }
    });

    // Tab switching
    curriculumTabs.forEach((tab) => {
      tab.addEventListener('click', () => {
        const age = tab.dataset.curriculumTab;
        const data = window.chromaCurriculumData[age];
        if (!data) return;

        // Update chart
        curriculumChart.data.datasets[0].data = data.data;
        curriculumChart.data.datasets[0].borderColor = data.color;
        curriculumChart.data.datasets[0].backgroundColor = data.color + '33';
        curriculumChart.data.datasets[0].pointBorderColor = data.color;
        curriculumChart.update();

        // Update text
        if (curriculumTitle) curriculumTitle.textContent = data.title;
        if (curriculumDesc) curriculumDesc.textContent = data.desc;

        // Update tab styles
        curriculumTabs.forEach((t) => {
          if (t === tab) {
            t.classList.remove('bg-white', 'text-brand-ink/70', 'border', 'border-brand-navy/20');
            t.classList.add('bg-chroma-teal', 'text-white', 'shadow-soft');
          } else {
            t.classList.remove('bg-chroma-teal', 'text-white', 'shadow-soft');
            t.classList.add('bg-white', 'text-brand-ink/70', 'border', 'border-brand-navy/20');
          }
        });
      });
    });

    // Initialize with infant
    if (curriculumTitle && curriculumDesc) {
      curriculumTitle.textContent = baseData.title;
      curriculumDesc.textContent = baseData.desc;
    }
  }

  /**
   * Schedule Tabs
   */
  const scheduleTabs = document.querySelectorAll('[data-schedule-tab]');
  const schedulePanels = document.querySelectorAll('[data-schedule-panel]');

  if (scheduleTabs.length && schedulePanels.length) {
    scheduleTabs.forEach((tab) => {
      tab.addEventListener('click', () => {
        const target = tab.dataset.scheduleTab;

        // Update tabs
        scheduleTabs.forEach((t) => {
          if (t === tab) {
            t.classList.remove('text-brand-ink/60');
            t.classList.add('bg-chroma-teal', 'text-white', 'shadow-soft');
          } else {
            t.classList.remove('bg-chroma-teal', 'text-white', 'shadow-soft');
            t.classList.add('text-brand-ink/60');
          }
        });

        // Update panels
        schedulePanels.forEach((panel) => {
          if (panel.dataset.schedulePanel === target) {
            panel.classList.remove('hidden');
            panel.classList.add('grid');
          } else {
            panel.classList.remove('grid');
            panel.classList.add('hidden');
          }
        });
      });
    });
  }
});
