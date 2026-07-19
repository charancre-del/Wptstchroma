const fs = require('fs');
const path = require('path');
const { chromium } = require('../../plugins/chroma-parent-portal/build-env/node_modules/playwright');

const base = process.env.CHROMA_QA_BASE || 'https://x3yyntt5tp-staging.wpdns.site';
const outputRoot = process.env.CHROMA_QA_OUTPUT_DIR
  ? path.resolve(process.env.CHROMA_QA_OUTPUT_DIR)
  : path.join(__dirname, 'qa');
const screenshotRoot = path.join(outputRoot, 'screenshots');
fs.mkdirSync(screenshotRoot, { recursive: true });

const devices = {
  desktop: { width: 1440, height: 1000 },
  laptop: { width: 1366, height: 768 },
  tabletLandscape: { width: 1024, height: 768 },
  tabletPortrait: { width: 834, height: 1112 },
  tabletSmall: { width: 768, height: 1024 },
  mobile: { width: 390, height: 844 },
};

const routes = [
  ['home', '/'],
  ['about', '/about/'],
  ['curriculum', '/curriculum/'],
  ['parents', '/parents/'],
  ['programs', '/programs/'],
  ['program-infant', '/programs/infant-care/'],
  ['program-pre-k', '/programs/ga-pre-k/'],
  ['program-kindergarten', '/programs/kindergarten/'],
  ['program-rising-pre-k', '/programs/rising-pre-k/'],
  ['program-rising-kindergarten', '/programs/rising-kindergarten/'],
  ['early-start', '/chroma-early-start/'],
  ['summer-camp', '/summer-camp-discover-go/'],
  ['locations', '/locations/'],
  ['location-single', '/locations/cherokee-campus/'],
  ['communities', '/communities/'],
  ['contact', '/contact-us/'],
  ['schedule-tour', '/schedule-a-tour/'],
  ['careers', '/careers/'],
  ['employers', '/employers/'],
  ['newsroom', '/newsroom/'],
  ['stories', '/stories/'],
  ['parent-portal', '/parent-portal/'],
  ['post-single', '/2026/05/22/georgia-pre-k-parent-guide/'],
  ['privacy', '/privacy-policy/'],
  ['terms', '/terms-of-service/'],
  ['spanish-home', '/es/'],
  ['not-found', '/codex-template-qa-not-found/'],
];

const requestedDevices = (process.env.CHROMA_QA_DEVICES || '')
  .split(',')
  .map((value) => value.trim())
  .filter(Boolean);
const selectedDevices = requestedDevices.length
  ? Object.fromEntries(Object.entries(devices).filter(([name]) => requestedDevices.includes(name)))
  : devices;
const requestedRoutes = (process.env.CHROMA_QA_ROUTES || '')
  .split(',')
  .map((value) => value.trim())
  .filter(Boolean);
const selectedRoutes = requestedRoutes.length
  ? routes.filter(([name]) => requestedRoutes.includes(name))
  : routes;
const runInteractionChecks = process.env.CHROMA_QA_INTERACTIONS !== '0';
const fullPageDevices = new Set(
  (process.env.CHROMA_QA_FULL_PAGE_DEVICES || 'desktop,tabletPortrait,mobile')
    .split(',')
    .map((value) => value.trim())
    .filter(Boolean)
);

const results = [];

async function inspectPage(page, routeName, routePath, deviceName) {
  const consoleMessages = [];
  const requestFailures = [];
  page.on('console', (message) => {
    if (['error', 'warning'].includes(message.type())) consoleMessages.push(`${message.type()}: ${message.text()}`);
  });
  page.on('pageerror', (error) => consoleMessages.push(`pageerror: ${error.message}`));
  page.on('requestfailed', (request) => requestFailures.push(`${request.failure()?.errorText || 'failed'} ${request.url()}`));

  const response = await page.goto(`${base}${routePath}?codex_qa=20260717`, { waitUntil: 'domcontentloaded', timeout: 45000 });
  await page.waitForTimeout(1600);
  await page.evaluate(async () => {
    const step = Math.max(500, Math.floor(innerHeight * 0.8));
    for (let top = 0; top < document.documentElement.scrollHeight; top += step) {
      scrollTo(0, top);
      await new Promise((resolve) => setTimeout(resolve, 25));
    }
    scrollTo(0, 0);
  });
  await page.waitForTimeout(600);
  await page.evaluate(async () => {
    if (!document.fonts) return;
    await Promise.all([
      document.fonts.load('400 16px "Outfit"'),
      document.fonts.load('600 16px "Playfair Display"'),
      document.fonts.ready,
    ]);
  });

  const metrics = await page.evaluate(() => {
    const visible = (element) => {
      const style = getComputedStyle(element);
      const rect = element.getBoundingClientRect();
      return style.display !== 'none' && style.visibility !== 'hidden' && rect.width > 0 && rect.height > 0;
    };
    const label = (element) => {
      const text = (element.getAttribute('aria-label') || element.textContent || '').trim().replace(/\s+/g, ' ');
      return `${element.tagName.toLowerCase()}${element.id ? `#${element.id}` : ''} ${text.slice(0, 90)}`;
    };
    const overflowing = [...document.querySelectorAll('body *')].filter(visible).filter((element) => {
      const rect = element.getBoundingClientRect();
      const style = getComputedStyle(element);
      if (style.position === 'fixed' && element.closest('[data-chroma-chat], .lc_text-widget, .ghl-chat-widget')) return false;
      return rect.left < -3 || rect.right > innerWidth + 3;
    }).slice(0, 20).map((element) => ({ label: label(element), rect: element.getBoundingClientRect().toJSON() }));
    const clippedText = [...document.querySelectorAll('main h1,main h2,main h3,main h4,main p,main li,main a,main button,main span')]
      .filter(visible)
      .filter((element) => {
        if (!element.textContent.trim() || element.classList.contains('sr-only') || element.closest('.sr-only')) return false;
        const style = getComputedStyle(element);
        const intentionalClamp = style.webkitLineClamp && style.webkitLineClamp !== 'none';
        const clips = ['hidden', 'clip'].includes(style.overflowY) || ['hidden', 'clip'].includes(style.overflow);
        return clips && !intentionalClamp && (element.scrollHeight > element.clientHeight + 4 || element.scrollWidth > element.clientWidth + 4);
      }).slice(0, 20).map((element) => ({ label: label(element), clientHeight: element.clientHeight, scrollHeight: element.scrollHeight }));
    const brokenImages = [...document.images].filter((image) => image.complete && image.naturalWidth === 0).map((image) => image.currentSrc || image.src || image.alt).slice(0, 20);
    const duplicateIds = [...document.querySelectorAll('[id]')].map((element) => element.id).filter((id, index, ids) => id && ids.indexOf(id) !== index).filter((id, index, ids) => ids.indexOf(id) === index);
    const headings = [...document.querySelectorAll('h1,h2,h3')].filter(visible).map((heading) => ({ tag: heading.tagName, text: heading.textContent.trim().replace(/\s+/g, ' ').slice(0, 120), size: parseFloat(getComputedStyle(heading).fontSize), lineHeight: getComputedStyle(heading).lineHeight }));
    const frames = [...document.querySelectorAll('iframe')].filter(visible).map((frame) => ({ title: frame.title, src: frame.src, width: frame.getBoundingClientRect().width, height: frame.getBoundingClientRect().height }));
    const textElements = [...document.querySelectorAll('main h1,main h2,main h3,main h4,main h5,main h6,main p,main li,main a,main button,main label,main summary')]
      .filter(visible)
      .filter((element) => element.textContent.trim() && !element.closest('[aria-hidden="true"],.sr-only'));
    const fontUsage = Object.entries(textElements.reduce((usage, element) => {
      const family = getComputedStyle(element).fontFamily.replace(/\s+/g, ' ').trim();
      usage[family] = (usage[family] || 0) + 1;
      return usage;
    }, {})).sort((left, right) => right[1] - left[1]);
    const unexpectedFonts = fontUsage.filter(([family]) => !/(Outfit|Playfair Display|Caveat|Lucida Console|Arial|Helvetica|sans-serif|serif)/i.test(family));
    const legacySerifUsage = textElements.filter((element) => {
      const family = getComputedStyle(element).fontFamily.replace(/\s+/g, ' ').trim();
      return /(Georgia|Times New Roman|Times,)/i.test(family) && !/Playfair Display/i.test(family);
    }).slice(0, 30).map((element) => ({
      label: label(element),
      family: getComputedStyle(element).fontFamily,
      size: getComputedStyle(element).fontSize,
    }));
    const editorialFontMismatches = textElements.filter((element) => {
      const expectsEditorialFont = element.matches('.font-serif,.chroma-redesign-hero-title,.pp-title-xl,.ces-title,.ces-section-title,.ces-service-card h3');
      return expectsEditorialFont && !/Playfair Display/i.test(getComputedStyle(element).fontFamily);
    }).slice(0, 30).map((element) => ({
      label: label(element),
      family: getComputedStyle(element).fontFamily,
      size: getComputedStyle(element).fontSize,
    }));
    const undersizedText = textElements.filter((element) => {
      const style = getComputedStyle(element);
      return parseFloat(style.fontSize) < 12 && style.textTransform !== 'uppercase';
    }).slice(0, 20).map((element) => ({ label: label(element), size: getComputedStyle(element).fontSize }));
    const oversizedText = textElements.filter((element) => {
      const size = parseFloat(getComputedStyle(element).fontSize);
      const limit = innerWidth <= 480 ? 64 : innerWidth < 1024 ? 88 : 120;
      return size > limit;
    }).slice(0, 20).map((element) => ({ label: label(element), size: getComputedStyle(element).fontSize }));
    const sections = [...document.querySelectorAll('main section')].filter(visible).map((section, index) => {
      const rect = section.getBoundingClientRect();
      const heading = section.querySelector('h1,h2,h3');
      return {
        index,
        id: section.id || '',
        heading: heading?.textContent.trim().replace(/\s+/g, ' ').slice(0, 100) || '',
        height: Math.round(rect.height),
        viewportRatio: Number((rect.height / innerHeight).toFixed(2)),
      };
    });
    const oversizedSections = sections.filter((section) => section.viewportRatio > 2.25);
    const fixedObstructions = [...document.querySelectorAll('body *')].filter(visible).filter((element) => {
      const style = getComputedStyle(element);
      if (!['fixed', 'sticky'].includes(style.position)) return false;
      if (element.matches('header,[data-site-header]') || element.closest('header,[data-site-header]')) return false;
      if (element.closest('[data-chroma-chat], .lc_text-widget, .ghl-chat-widget')) return false;
      const rect = element.getBoundingClientRect();
      return rect.width * rect.height > innerWidth * innerHeight * 0.25;
    }).slice(0, 20).map((element) => ({ label: label(element), rect: element.getBoundingClientRect().toJSON() }));
    const bodyFont = getComputedStyle(document.body).fontFamily;
    const mainFont = document.querySelector('main') ? getComputedStyle(document.querySelector('main')).fontFamily : '';
    const h1Font = document.querySelector('main h1') ? getComputedStyle(document.querySelector('main h1')).fontFamily : '';
    const footer = document.querySelector('footer');
    const footerRect = footer?.getBoundingClientRect();
    const footerReachable = Boolean(footer && footerRect && footerRect.bottom + scrollY <= document.documentElement.scrollHeight + 4);
    const locationList = document.querySelector('[data-location-list], [data-location-results]');
    const programChartSliders = document.querySelectorAll('[data-program-chart-slider]').length;
    const fontAvailability = {
      outfit: document.fonts ? document.fonts.check('16px "Outfit"') : null,
      playfair: document.fonts ? document.fonts.check('600 16px "Playfair Display"') : null,
    };
    const formFrames = frames.map((frame) => {
      const frameElement = [...document.querySelectorAll('iframe')].find((candidate) => candidate.src === frame.src && visible(candidate));
      const host = frameElement?.closest('.chroma-form-scroll-card,.chroma-tour-form-card');
      const hostRect = host?.getBoundingClientRect();
      return {
        ...frame,
        hostWidth: hostRect?.width || null,
        widthRatio: hostRect?.width ? Number((frame.width / hostRect.width).toFixed(3)) : null,
      };
    });
    return {
      title: document.title,
      h1: document.querySelector('h1')?.textContent.trim().replace(/\s+/g, ' ').slice(0, 180) || '',
      viewport: { width: innerWidth, height: innerHeight },
      document: { width: document.documentElement.scrollWidth, height: document.documentElement.scrollHeight },
      horizontalOverflow: document.documentElement.scrollWidth > innerWidth + 3,
      overflowing,
      clippedText,
      brokenImages,
      duplicateIds,
      headings,
      frames: formFrames,
      typography: {
        bodyFont,
        mainFont,
        h1Font,
        fontUsage,
        unexpectedFonts,
        legacySerifUsage,
        editorialFontMismatches,
        fontAvailability,
        expectedBodyFont: /Outfit/i.test(bodyFont) && (!mainFont || /Outfit/i.test(mainFont)),
        undersizedText,
        oversizedText,
      },
      sections,
      oversizedSections,
      fixedObstructions,
      footerReachable,
      mainPresent: Boolean(document.querySelector('main')),
      footerPresent: Boolean(document.querySelector('footer')),
      mapPresent: Boolean(document.querySelector('.leaflet-container')),
      locationCards: document.querySelectorAll('[data-location-card]').length,
      programTabs: document.querySelectorAll('[data-program-tab]').length,
      reviewControls: document.querySelectorAll('[data-review-prev],[data-review-next]').length,
      programChartSliders,
      locationList: locationList ? {
        clientHeight: locationList.clientHeight,
        scrollHeight: locationList.scrollHeight,
        scrollable: locationList.scrollHeight > locationList.clientHeight + 4,
      } : null,
    };
  });

  const screenshotPath = path.join(screenshotRoot, `${deviceName}-${routeName}.jpg`);
  if (fullPageDevices.has(deviceName)) {
    await page.addStyleTag({ content: '* { content-visibility: visible !important; contain-intrinsic-size: none !important; }' });
  }
  await page.screenshot({ path: screenshotPath, fullPage: fullPageDevices.has(deviceName), type: 'jpeg', quality: 62 });
  return {
    routeName, routePath, deviceName, status: response?.status() || null, finalUrl: page.url(), screenshotPath, metrics,
    consoleMessages: [...new Set(consoleMessages)]
      .filter((message) => !(routeName === 'not-found' && /status of 404/i.test(message)))
      .filter((message) => !/doubleclick|googletagmanager|google-analytics|googleadservices|facebook|clarity|leadconnector|msgsndr|deprecated api|@import/i.test(message))
      .slice(0, 20),
    requestFailures: [...new Set(requestFailures)]
      .filter((message) => !/doubleclick|googletagmanager|google-analytics|analytics\.google\.com|googleadservices|facebook|clarity|openstreetmap|leadconnector|msgsndr/i.test(message))
      .slice(0, 20),
  };
}

async function runInteractions(browser) {
  const context = await browser.newContext({ viewport: devices.desktop, geolocation: { latitude: 34.0289, longitude: -84.1986 }, permissions: ['geolocation'] });
  const page = await context.newPage();
  const interactions = {};
  await page.goto(`${base}/?codex_qa=interactions`, { waitUntil: 'domcontentloaded', timeout: 45000 });
  await page.waitForTimeout(1500);
  const programTabs = page.locator('[data-program-tab]');
  if (await programTabs.count() > 1) {
    const before = await page.locator('[data-program-title]').first().textContent().catch(() => '');
    await programTabs.nth(1).click();
    await page.waitForTimeout(500);
    const after = await page.locator('[data-program-title]').first().textContent().catch(() => '');
    interactions.homeProgramSlider = { before: before?.trim(), after: after?.trim(), changed: before !== after };
  }
  await page.goto(`${base}/locations/?codex_qa=interactions`, { waitUntil: 'domcontentloaded', timeout: 45000 });
  await page.waitForTimeout(1800);
  const locationButton = page.getByRole('button', { name: /use my location/i }).first();
  if (await locationButton.count()) {
    await locationButton.click();
    await page.waitForTimeout(1600);
    const status = await page.locator('[data-location-status]').textContent().catch(() => '');
    const distance = await page.locator('[data-location-distance]:visible').first().textContent().catch(() => '');
    interactions.useMyLocation = { status: status?.trim(), firstDistance: distance?.trim(), passed: /sorted by distance/i.test(status || '') && /mi/i.test(distance || '') };
  }
  const cards = page.locator('[data-location-card]:visible');
  if (await cards.count()) {
    await cards.first().click();
    await page.waitForTimeout(700);
    interactions.locationCardPopup = { popupVisible: await page.locator('.leaflet-popup:visible').count() > 0, popupText: (await page.locator('.leaflet-popup:visible').first().textContent().catch(() => ''))?.trim().replace(/\s+/g, ' ').slice(0, 180) };
  }
  await page.goto(`${base}/curriculum/?codex_qa=interactions`, { waitUntil: 'domcontentloaded', timeout: 45000 });
  await page.waitForTimeout(1400);
  interactions.curriculumProgramSlider = { tabCount: await page.locator('[data-program-chart-slider] [data-program-slider-tick]').count() };
  await context.close();
  return interactions;
}

(async () => {
  const browser = await chromium.launch({ headless: true, executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe' });
  for (const [deviceName, viewport] of Object.entries(selectedDevices)) {
    const context = await browser.newContext({ viewport, reducedMotion: 'no-preference' });
    for (const [routeName, routePath] of selectedRoutes) {
      const page = await context.newPage();
      try { results.push(await inspectPage(page, routeName, routePath, deviceName)); }
      catch (error) { results.push({ routeName, routePath, deviceName, fatalError: error.message }); }
      finally {
        await page.close();
        fs.writeFileSync(path.join(outputRoot, 'template-qa-results-partial.json'), JSON.stringify(results, null, 2));
      }
    }
    await context.close();
  }
  const interactions = runInteractionChecks ? await runInteractions(browser) : {};
  await browser.close();
  const summary = {
    generatedAt: new Date().toISOString(), base, deviceCount: Object.keys(selectedDevices).length, routeCount: selectedRoutes.length, totalChecks: results.length,
    failures: results.filter((result) => result.fatalError || ![200, 404].includes(result.status)),
    horizontalOverflow: results.filter((result) => result.metrics?.horizontalOverflow),
    brokenImages: results.filter((result) => result.metrics?.brokenImages?.length),
    clippedText: results.filter((result) => result.metrics?.clippedText?.length),
    typographyMismatches: results.filter((result) => result.metrics?.typography && !result.metrics.typography.expectedBodyFont),
    unexpectedFonts: results.filter((result) => result.metrics?.typography?.unexpectedFonts?.length),
    legacySerifUsage: results.filter((result) => result.metrics?.typography?.legacySerifUsage?.length),
    editorialFontMismatches: results.filter((result) => result.metrics?.typography?.editorialFontMismatches?.length),
    unavailableFonts: results.filter((result) => result.metrics?.typography?.fontAvailability && (!result.metrics.typography.fontAvailability.outfit || !result.metrics.typography.fontAvailability.playfair)),
    undersizedText: results.filter((result) => result.metrics?.typography?.undersizedText?.length),
    oversizedText: results.filter((result) => result.metrics?.typography?.oversizedText?.length),
    oversizedSections: results.filter((result) => result.metrics?.oversizedSections?.length),
    fixedObstructions: results.filter((result) => result.metrics?.fixedObstructions?.length),
    unreachableFooters: results.filter((result) => result.metrics && !result.metrics.footerReachable),
    missingFooters: results.filter((result) => result.metrics && !result.metrics.footerPresent),
    consoleIssues: results.filter((result) => result.consoleMessages?.length),
    requestFailures: results.filter((result) => result.requestFailures?.length),
    interactions,
  };
  fs.writeFileSync(path.join(outputRoot, 'template-qa-results.json'), JSON.stringify(results, null, 2));
  fs.writeFileSync(path.join(outputRoot, 'template-qa-summary.json'), JSON.stringify(summary, null, 2));
  console.log(JSON.stringify({ totalChecks: summary.totalChecks, failures: summary.failures.length, horizontalOverflow: summary.horizontalOverflow.length, brokenImages: summary.brokenImages.length, clippedText: summary.clippedText.length, typographyMismatches: summary.typographyMismatches.length, unexpectedFonts: summary.unexpectedFonts.length, legacySerifUsage: summary.legacySerifUsage.length, editorialFontMismatches: summary.editorialFontMismatches.length, unavailableFonts: summary.unavailableFonts.length, undersizedText: summary.undersizedText.length, oversizedText: summary.oversizedText.length, oversizedSections: summary.oversizedSections.length, fixedObstructions: summary.fixedObstructions.length, unreachableFooters: summary.unreachableFooters.length, missingFooters: summary.missingFooters.length, consoleIssues: summary.consoleIssues.length, requestFailures: summary.requestFailures.length, interactions }, null, 2));
})().catch((error) => { console.error(error); process.exitCode = 1; });
