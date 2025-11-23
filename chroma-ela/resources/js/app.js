/**
 * Main JavaScript File
 *
 * @package Chroma_ELA
 * @since 1.0.0
 */

document.addEventListener('DOMContentLoaded', function () {
  /**
   * Mobile Menu Toggle
   */
  const menuBtn = document.getElementById('menu-btn');
  const closeBtn = document.getElementById('close-menu');
  const mobileMenu = document.getElementById('mobile-menu');

  if (menuBtn && closeBtn && mobileMenu) {
    menuBtn.addEventListener('click', () => {
      mobileMenu.classList.remove('translate-x-full');
    });

    closeBtn.addEventListener('click', () => {
      mobileMenu.classList.add('translate-x-full');
    });

    // Close menu when clicking on links
    mobileMenu.querySelectorAll('a[href^="#"]').forEach((link) => {
      link.addEventListener('click', () => {
        mobileMenu.classList.add('translate-x-full');
      });
    });
  }

  /**
   * Program Wizard Data
   */
  const programs = {
    infant: {
      title: 'Infant Care (6 weeks–12 months)',
      desc: 'Low ratios, safe sleep practices, responsive caregiving, and sensory play in a peaceful, predictable environment.',
      link: '/program/infant',
    },
    toddler: {
      title: 'Toddler Program (1 year)',
      desc: 'Curated environments for walkers and explorers with language bursts and social skills.',
      link: '/program/toddler',
    },
    preschool: {
      title: 'Preschool (2 years)',
      desc: 'Early concepts in math, literacy, and science introduced through hands-on centers and guided play.',
      link: '/program/preschool',
    },
    prep: {
      title: 'Pre-K Prep (3 years)',
      desc: 'Structured centers and small-group instruction that build independence before GA Pre-K.',
      link: '/program/pre-k-prep',
    },
    prek: {
      title: 'GA Pre-K (4 years)',
      desc: 'Balanced academic readiness, social-emotional learning, and joyful experiences aligned with GA standards.',
      link: '/program/ga-pre-k',
    },
    afterschool: {
      title: 'After-School Program (5–12 years)',
      desc: 'Transportation from local schools, homework support, clubs, and outdoor play.',
      link: '/program/after-school',
    },
  };

  /**
   * Show Wizard Result
   */
  window.showWizardResult = function (ageKey) {
    const wizardStep = document.getElementById('wizard-step-1');
    const wizardResult = document.getElementById('wizard-result');
    const wizardTitle = document.getElementById('wizard-title');
    const wizardDesc = document.getElementById('wizard-desc');
    const wizardLearnLink = document.getElementById('wizard-learn-link');
    const program = programs[ageKey];

    if (!wizardStep || !wizardResult || !wizardTitle || !wizardDesc || !program) return;

    wizardStep.classList.add('hidden');
    wizardResult.classList.remove('hidden');
    wizardTitle.textContent = program.title;
    wizardDesc.textContent = program.desc;
    if (wizardLearnLink) wizardLearnLink.href = program.link || '/programs';
  };

  /**
   * Reset Wizard
   */
  window.resetWizard = function () {
    const wizardStep = document.getElementById('wizard-step-1');
    const wizardResult = document.getElementById('wizard-result');
    if (!wizardStep || !wizardResult) return;
    wizardStep.classList.remove('hidden');
    wizardResult.classList.add('hidden');
  };

  /**
   * Curriculum Configuration
   */
  const curriculumConfig = {
    infant: {
      title: 'Foundation Phase',
      desc: 'Infant classrooms emphasize emotional security, attachment, physical health, and sensory experiences. Academics are embedded through language-rich interactions.',
      color: '#D67D6B',
      data: [90, 90, 40, 15, 40],
    },
    toddler: {
      title: 'Discovery Phase',
      desc: 'Toddlers explore movement, language, early problem-solving, and social skills through guided play and routines.',
      color: '#4A6C7C',
      data: [85, 75, 65, 30, 70],
    },
    preschool: {
      title: 'Exploration Phase',
      desc: 'Preschoolers work on early literacy, math concepts, dramatic play, and collaborative projects, supported by strong routines.',
      color: '#E6BE75',
      data: [75, 65, 70, 55, 80],
    },
    prep: {
      title: 'Pre-K Prep Phase',
      desc: 'Children build stamina for small-group work, early writing, and multi-step directions while strengthening self-regulation.',
      color: '#2F4858',
      data: [65, 60, 75, 75, 70],
    },
    prek: {
      title: 'GA Pre-K Readiness',
      desc: 'Balanced academic readiness, social-emotional learning, and joyful experiences aligned with GA standards.',
      color: '#4A6C7C',
      data: [60, 60, 80, 90, 70],
    },
    afterschool: {
      title: 'Enrichment Phase',
      desc: 'School-age programming offers homework help, social clubs, athletic play, and creative enrichment for older children.',
      color: '#E6BE75',
      data: [50, 70, 85, 75, 80],
    },
  };

  const curriculumTitle = document.getElementById('curriculum-title');
  const curriculumDesc = document.getElementById('curriculum-desc');
  const chartCanvas = document.getElementById('curriculumChart');
  const curriculumButtons = document.querySelectorAll('[id^="btn-cur-"]');
  const curriculumLabels = ['Physical', 'Emotional', 'Social', 'Academic', 'Creative'];
  let curriculumChart;

  /**
   * Initialize Curriculum Chart
   */
  if (chartCanvas && window.Chart) {
    const base = curriculumConfig.infant;
    curriculumChart = new Chart(chartCanvas.getContext('2d'), {
      type: 'radar',
      data: {
        labels: curriculumLabels,
        datasets: [
          {
            label: 'Focus',
            data: base.data,
            borderColor: base.color,
            backgroundColor: base.color + '33',
            borderWidth: 2,
            pointBackgroundColor: '#ffffff',
            pointBorderColor: base.color,
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
              font: { family: 'Outfit', size: 12 },
              color: '#263238',
            },
          },
        },
      },
    });
  }

  /**
   * Highlight Curriculum Button
   */
  function highlightCurriculumButton(activeKey) {
    curriculumButtons.forEach((btn) => {
      btn.classList.remove('bg-chroma-blue', 'text-white', 'shadow-soft');
      btn.classList.add('bg-white', 'text-brand-ink/70', 'border', 'border-chroma-blue/20');
      if (btn.id === 'btn-cur-' + activeKey) {
        btn.classList.add('bg-chroma-blue', 'text-white', 'shadow-soft');
        btn.classList.remove('text-brand-ink/70');
      }
    });
  }

  /**
   * Update Curriculum
   */
  window.updateCurriculum = function (key) {
    const cfg = curriculumConfig[key];
    if (!cfg) return;

    if (curriculumChart) {
      curriculumChart.data.datasets[0].data = cfg.data;
      curriculumChart.data.datasets[0].borderColor = cfg.color;
      curriculumChart.data.datasets[0].backgroundColor = cfg.color + '33';
      curriculumChart.data.datasets[0].pointBorderColor = cfg.color;
      curriculumChart.update();
    }

    if (curriculumTitle) curriculumTitle.textContent = cfg.title;
    if (curriculumDesc) curriculumDesc.textContent = cfg.desc;
    highlightCurriculumButton(key);
  };

  // Initialize with infant
  if (curriculumChart) {
    updateCurriculum('infant');
  }

  /**
   * Schedule Tab Switching
   */
  function switchTabInternal(tabName) {
    document.querySelectorAll('.tab-content').forEach((el) => el.classList.remove('active'));
    document.querySelectorAll('.schedule-tab').forEach((btn) => {
      btn.classList.remove('bg-chroma-blue', 'text-white', 'shadow-soft');
      btn.classList.add('text-brand-ink/60');
    });

    const activeContent = document.getElementById('tab-' + tabName);
    const activeBtn = document.getElementById('btn-' + tabName);

    if (activeContent) activeContent.classList.add('active');
    if (activeBtn) {
      activeBtn.classList.remove('text-brand-ink/60');
      activeBtn.classList.add('bg-chroma-blue', 'text-white', 'shadow-soft');
    }
  }

  window.switchTab = function (tabName) {
    switchTabInternal(tabName);
  };

  // Initialize first tab
  if (document.getElementById('tab-infant')) {
    switchTabInternal('infant');
  }
});
