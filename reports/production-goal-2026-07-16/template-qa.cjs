const fs = require('fs');
const path = require('path');
const { chromium } = require('../../plugins/chroma-parent-portal/build-env/node_modules/playwright');

const base = 'https://x3yyntt5tp-staging.wpdns.site';
const outputRoot = path.join(__dirname, 'qa');
const screenshotRoot = path.join(outputRoot, 'screenshots');
fs.mkdirSync(screenshotRoot, { recursive: true });

const devices = {
  desktop: { width: 1440, height: 1000 },
  tablet: { width: 834, height: 1112 },
  mobile: { width: 390, height: 844 },
};

const routes = [
  ['home', '/'],
  ['about', '/about/'],
  ['curriculum', '/curriculum/'],
  ['parents', '/parents/'],
  ['programs', '/programs/'],
  ['program-infant', '/program/infant-care/'],
  ['program-pre-k', '/program/ga-pre-k/'],
  ['program-kindergarten', '/program/kindergarten-1/'],
  ['early-start', '/chroma-early-start/'],
  ['summer-camp', '/summer-camp-discover-go/'],
  ['locations', '/locations/'],
  ['location-single', '/location/cherokee-campus/'],
  ['communities', '/communities/'],
  ['city-single', '/city/canton/'],
  ['contact', '/contact-us/'],
  ['schedule-tour', '/schedule-a-tour/'],
  ['careers', '/careers/'],
  ['employers', '/employers/'],
  ['newsroom', '/newsroom/'],
  ['stories', '/stories/'],
  ['blog', '/blog/'],
  ['post-single', '/georgia-pre-k-parent-guide/'],
  ['privacy', '/privacy-policy/'],
  ['terms', '/terms-of-service/'],
  ['parent-portal', '/parent-portal/'],
  ['not-found', '/codex-template-qa-not-found/'],
];

const results = [];

async function inspectPage(page, routeName, routePath, deviceName) {
  const consoleMessages = [];
  const requestFailures = [];
  page.on('console', (message) => {
    if (['error', 'warning'].includes(message.type())) {
      consoleMessages.push(`${message.type()}: ${message.text()}`);
    }
  });
  page.on('pageerror', (error) => consoleMessages.push(`pageerror: ${error.message}`));
  page.on('requestfailed', (request) => requestFailures.push(`${request.failure()?.errorText || 'failed'} ${request.url()}`));

  const response = await page.goto(`${base}${routePath}?codex_qa=20260716`, {
    waitUntil: 'domcontentloaded',
    timeout: 45000,
  });
  await page.waitForTimeout(1800);

  const metrics = await page.evaluate(() => {
    const visible = (element) => {
      const style = getComputedStyle(element);
      const rect = element.getBoundingClientRect();
      return style.display !== 'none' && style.visibility !== 'hidden' && rect.width > 0 && rect.height > 0;
    };
    const label = (element) => {
      const text = (element.getAttribute('aria-label') || element.textContent || '').trim().replace(/\s+/g, ' ');
      return `${element.tagName.toLowerCase()}${element.id ? `#${element.id}` : ''}${element.className && typeof element.className === 'string' ? `.${element.className.trim().split(/\s+/).slice(0, 3).join('.')}` : ''} ${text.slice(0, 90)}`;
    };
    const overflowing = [...document.querySelectorAll('body *')]
      .filter(visible)
      .filter((element) => {
        const rect = element.getBoundingClientRect();
        return rect.left < -3 || rect.right > innerWidth + 3;
      })
      .slice(0, 20)
      .map((element) => ({ label: label(element), rect: element.getBoundingClientRect().toJSON() }));
    const clippedText = [...document.querySelectorAll('main *')]
      .filter(visible)
      .filter((element) => {
        if (element.classList.contains('sr-only') || element.closest('.sr-only')) return false;
        const style = getComputedStyle(element);
        const intentionalClamp = style.webkitLineClamp && style.webkitLineClamp !== 'none';
        const hasText = [...element.childNodes].some((node) => node.nodeType === Node.TEXT_NODE && node.textContent.trim());
        const clips = ['hidden', 'clip'].includes(style.overflowY) || ['hidden', 'clip'].includes(style.overflow);
        return hasText && clips && !intentionalClamp && element.scrollHeight > element.clientHeight + 4;
      })
      .slice(0, 20)
      .map((element) => ({ label: label(element), clientHeight: element.clientHeight, scrollHeight: element.scrollHeight }));
    const brokenImages = [...document.images]
      .filter((image) => image.complete && image.naturalWidth === 0)
      .map((image) => image.currentSrc || image.src || image.alt)
      .slice(0, 20);
    const duplicateIds = [...document.querySelectorAll('[id]')]
      .map((element) => element.id)
      .filter((id, index, ids) => id && ids.indexOf(id) !== index)
      .filter((id, index, ids) => ids.indexOf(id) === index);
    const emptyLinks = [...document.querySelectorAll('a[href]')]
      .filter(visible)
      .filter((anchor) => !anchor.textContent.trim() && !anchor.getAttribute('aria-label') && !anchor.querySelector('img[alt]'))
      .length;
    const headings = [...document.querySelectorAll('h1,h2,h3')]
      .filter(visible)
      .map((heading) => ({
        tag: heading.tagName,
        text: heading.textContent.trim().replace(/\s+/g, ' ').slice(0, 120),
        size: parseFloat(getComputedStyle(heading).fontSize),
        lineHeight: getComputedStyle(heading).lineHeight,
      }));
    const frames = [...document.querySelectorAll('iframe')].filter(visible).map((frame) => ({
      title: frame.title,
      src: frame.src,
      width: frame.getBoundingClientRect().width,
      height: frame.getBoundingClientRect().height,
    }));
    const main = document.querySelector('main');
    const footer = document.querySelector('footer');
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
      emptyLinks,
      headings,
      frames,
      mainHeight: main?.getBoundingClientRect().height || 0,
      footerPresent: Boolean(footer),
      mapPresent: Boolean(document.querySelector('.leaflet-container')),
      locationCards: document.querySelectorAll('[data-location-card]').length,
      programTabs: document.querySelectorAll('[data-program-tab]').length,
      reviewControls: document.querySelectorAll('[data-review-prev],[data-review-next]').length,
    };
  });

  const screenshotPath = path.join(screenshotRoot, `${deviceName}-${routeName}.png`);
  await page.screenshot({ path: screenshotPath, fullPage: false });

  return {
    routeName,
    routePath,
    deviceName,
    status: response?.status() || null,
    finalUrl: page.url(),
    screenshotPath,
    metrics,
    consoleMessages: [...new Set(consoleMessages)].slice(0, 20),
    requestFailures: [...new Set(requestFailures)]
      .filter((message) => !/doubleclick|googletagmanager|google-analytics|facebook|clarity|openstreetmap/i.test(message))
      .slice(0, 20),
  };
}

async function runInteractions(browser) {
  const context = await browser.newContext({
    viewport: devices.desktop,
    geolocation: { latitude: 34.0289, longitude: -84.1986 },
    permissions: ['geolocation'],
  });
  const page = await context.newPage();
  const interactions = {};

  await page.goto(`${base}/?codex_qa=interactions`, { waitUntil: 'domcontentloaded', timeout: 45000 });
  await page.waitForTimeout(1600);
  const programTabs = page.locator('[data-program-tab]');
  if (await programTabs.count() > 1) {
    const before = await page.locator('[data-program-title]').first().textContent().catch(() => '');
    await programTabs.nth(1).click();
    await page.waitForTimeout(700);
    const after = await page.locator('[data-program-title]').first().textContent().catch(() => '');
    interactions.homeProgramSlider = { before: before?.trim(), after: after?.trim(), changed: before !== after };
  }

  await page.goto(`${base}/locations/?codex_qa=interactions`, { waitUntil: 'domcontentloaded', timeout: 45000 });
  await page.waitForTimeout(1800);
  const locationButton = page.getByRole('button', { name: /use my location/i }).first();
  if (await locationButton.count()) {
    await locationButton.click();
    await page.waitForTimeout(1800);
    const status = await page.locator('[data-location-status]').textContent().catch(() => '');
    const distance = await page.locator('[data-location-distance]:visible').first().textContent().catch(() => '');
    interactions.useMyLocation = { status: status?.trim(), firstDistance: distance?.trim(), passed: /sorted by distance/i.test(status || '') && /mi/i.test(distance || '') };
  }
  const cards = page.locator('[data-location-card]:visible');
  if (await cards.count()) {
    await cards.first().click();
    await page.waitForTimeout(900);
    interactions.locationCardPopup = {
      popupVisible: await page.locator('.leaflet-popup:visible').count() > 0,
      popupText: (await page.locator('.leaflet-popup:visible').first().textContent().catch(() => ''))?.trim().replace(/\s+/g, ' ').slice(0, 180),
    };
  }

  await page.goto(`${base}/curriculum/?codex_qa=interactions`, { waitUntil: 'domcontentloaded', timeout: 45000 });
  await page.waitForTimeout(1600);
  const curriculumTabs = page.locator('[data-program-chart-slider] [data-program-slider-tick]');
  interactions.curriculumProgramSlider = { tabCount: await curriculumTabs.count() };

  await context.close();
  return interactions;
}

(async () => {
  const browser = await chromium.launch({
    headless: true,
    executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe',
  });
  for (const [deviceName, viewport] of Object.entries(devices)) {
    const context = await browser.newContext({ viewport, reducedMotion: 'no-preference' });
    for (const [routeName, routePath] of routes) {
      const page = await context.newPage();
      try {
        results.push(await inspectPage(page, routeName, routePath, deviceName));
      } catch (error) {
        results.push({ routeName, routePath, deviceName, fatalError: error.message });
      } finally {
        await page.close();
      }
    }
    await context.close();
  }

  const interactions = await runInteractions(browser);
  await browser.close();

  const summary = {
    generatedAt: new Date().toISOString(),
    base,
    deviceCount: Object.keys(devices).length,
    routeCount: routes.length,
    totalChecks: results.length,
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
  console.log(JSON.stringify({
    totalChecks: summary.totalChecks,
    failures: summary.failures.length,
    horizontalOverflow: summary.horizontalOverflow.length,
    brokenImages: summary.brokenImages.length,
    clippedText: summary.clippedText.length,
    missingFooters: summary.missingFooters.length,
    consoleIssues: summary.consoleIssues.length,
    requestFailures: summary.requestFailures.length,
    interactions,
  }, null, 2));
})().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
