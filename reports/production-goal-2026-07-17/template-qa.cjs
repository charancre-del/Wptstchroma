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
      frames,
      mainPresent: Boolean(document.querySelector('main')),
      footerPresent: Boolean(document.querySelector('footer')),
      mapPresent: Boolean(document.querySelector('.leaflet-container')),
      locationCards: document.querySelectorAll('[data-location-card]').length,
      programTabs: document.querySelectorAll('[data-program-tab]').length,
      reviewControls: document.querySelectorAll('[data-review-prev],[data-review-next]').length,
    };
  });

  const screenshotPath = path.join(screenshotRoot, `${deviceName}-${routeName}.png`);
  await page.screenshot({ path: screenshotPath, fullPage: false });
  return {
    routeName, routePath, deviceName, status: response?.status() || null, finalUrl: page.url(), screenshotPath, metrics,
    consoleMessages: [...new Set(consoleMessages)].filter((message) => !/doubleclick|googletagmanager|google-analytics|facebook|clarity/i.test(message)).slice(0, 20),
    requestFailures: [...new Set(requestFailures)].filter((message) => !/doubleclick|googletagmanager|google-analytics|facebook|clarity|openstreetmap/i.test(message)).slice(0, 20),
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
    missingFooters: results.filter((result) => result.metrics && !result.metrics.footerPresent),
    consoleIssues: results.filter((result) => result.consoleMessages?.length),
    requestFailures: results.filter((result) => result.requestFailures?.length),
    interactions,
  };
  fs.writeFileSync(path.join(outputRoot, 'template-qa-results.json'), JSON.stringify(results, null, 2));
  fs.writeFileSync(path.join(outputRoot, 'template-qa-summary.json'), JSON.stringify(summary, null, 2));
  console.log(JSON.stringify({ totalChecks: summary.totalChecks, failures: summary.failures.length, horizontalOverflow: summary.horizontalOverflow.length, brokenImages: summary.brokenImages.length, clippedText: summary.clippedText.length, missingFooters: summary.missingFooters.length, consoleIssues: summary.consoleIssues.length, requestFailures: summary.requestFailures.length, interactions }, null, 2));
})().catch((error) => { console.error(error); process.exitCode = 1; });
