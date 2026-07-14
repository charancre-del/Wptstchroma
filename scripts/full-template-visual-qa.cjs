const fs = require('fs');
const path = require('path');
const puppeteer = require('../chroma-excellence-theme/node_modules/puppeteer-core');

const baseUrl = 'https://x3yyntt5tp-staging.wpdns.site';
const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const outputDir = path.resolve(__dirname, '..', 'qa-screenshots', 'visual-qa-2026-07-13-postfix');

const routes = [
  ['home', '/'],
  ['about', '/about/'],
  ['curriculum', '/curriculum/'],
  ['parents', '/parents/'],
  ['programs-archive', '/programs/'],
  ['program-single', '/programs/ga-pre-k/'],
  ['locations-archive', '/locations/'],
  ['location-single', '/locations/chroma-early-learning-academy-stockbridge/'],
  ['communities-archive', '/communities/'],
  ['city-single', '/city/kennesaw/'],
  ['contact', '/contact-us/'],
  ['schedule-tour', '/schedule-a-tour/'],
  ['early-start', '/chroma-early-start/'],
  ['summer-camp', '/summer-camp-discover-go/'],
  ['careers', '/careers/'],
  ['employers', '/employers/'],
  ['newsroom', '/newsroom/'],
  ['stories-archive', '/stories/'],
  ['story-single', '/georgia-pre-k-parent-guide/'],
  ['parent-portal', '/parent-portal/'],
  ['privacy', '/privacy-policy/'],
  ['terms', '/terms-of-service/'],
  ['404', '/missing-page-for-template-qa/'],
];

const devices = [
  ['desktop', 1440, 1000],
  ['tablet', 820, 1180],
  ['mobile', 390, 844],
];

const pause = (milliseconds) => new Promise((resolve) => setTimeout(resolve, milliseconds));

async function collectPageMetrics(page, label, device) {
  return page.evaluate(({ label: pageLabel, device: deviceName }) => {
    const root = document.documentElement;
    const body = document.body;
    const isVisible = (element) => {
      const rect = element.getBoundingClientRect();
      const styles = window.getComputedStyle(element);
      return rect.width > 1 && rect.height > 1 && styles.display !== 'none' && styles.visibility !== 'hidden';
    };
    const describe = (element) => {
      const rect = element.getBoundingClientRect();
      const styles = window.getComputedStyle(element);
      return {
        tag: element.tagName.toLowerCase(),
        id: element.id || '',
        className: String(element.className || '').slice(0, 180),
        text: String(element.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 120),
        x: Math.round(rect.x),
        y: Math.round(rect.y),
        width: Math.round(rect.width),
        height: Math.round(rect.height),
        scrollWidth: element.scrollWidth,
        scrollHeight: element.scrollHeight,
        overflowX: styles.overflowX,
        overflowY: styles.overflowY,
        fontSize: styles.fontSize,
        lineHeight: styles.lineHeight,
      };
    };

    const visibleElements = Array.from(document.querySelectorAll('body *')).filter(isVisible);
    const horizontalOverflow = visibleElements
      .filter((element) => {
        const rect = element.getBoundingClientRect();
        const styles = window.getComputedStyle(element);
        if (styles.position === 'fixed') return false;
        return rect.right > window.innerWidth + 4 || rect.left < -4;
      })
      .slice(0, 15)
      .map(describe);
    const clipped = visibleElements
      .filter((element) => {
        const styles = window.getComputedStyle(element);
        const clippedY = ['hidden', 'clip'].includes(styles.overflowY) && element.scrollHeight > element.clientHeight + 24;
        const clippedX = ['hidden', 'clip'].includes(styles.overflowX) && element.scrollWidth > element.clientWidth + 24;
        return clippedY || clippedX;
      })
      .slice(0, 18)
      .map(describe);
    const scrollRegions = visibleElements
      .filter((element) => {
        const styles = window.getComputedStyle(element);
        return ['auto', 'scroll'].includes(styles.overflowY) && element.scrollHeight > element.clientHeight + 30 && element.clientHeight > 80;
      })
      .slice(0, 15)
      .map(describe);
    const headings = Array.from(document.querySelectorAll('h1,h2,h3'))
      .filter(isVisible)
      .slice(0, 30)
      .map(describe);
    const brokenImages = Array.from(document.images)
      .filter((image) => isVisible(image) && (!image.complete || image.naturalWidth === 0))
      .slice(0, 20)
      .map((image) => ({ src: image.currentSrc || image.src, alt: image.alt || '' }));
    const iframes = Array.from(document.querySelectorAll('iframe')).filter(isVisible).map(describe);
    const map = document.querySelector('.leaflet-container');
    const locationList = document.querySelector('.chroma-location-list-panel');
    const locationCards = document.querySelector('.chroma-location-card-scroll');
    const reviewSide = document.querySelector('.reviewSide');
    const reviewCard = document.querySelector('.chroma-review-card');
    const reviewViewport = document.querySelector('.chroma-review-viewport');
    const header = document.querySelector('header, .site-header');

    return {
      label: pageLabel,
      device: deviceName,
      url: window.location.href,
      title: document.title,
      statusHeading: String(document.querySelector('main h1, h1')?.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 180),
      viewport: { width: window.innerWidth, height: window.innerHeight },
      document: {
        clientWidth: root.clientWidth,
        scrollWidth: root.scrollWidth,
        scrollHeight: Math.max(root.scrollHeight, body?.scrollHeight || 0),
        horizontalOverflow: root.scrollWidth > root.clientWidth + 4,
      },
      header: header && isVisible(header) ? describe(header) : null,
      horizontalOverflow,
      clipped,
      scrollRegions,
      headings,
      brokenImages,
      iframes,
      map: map && isVisible(map) ? describe(map) : null,
      locationList: locationList && isVisible(locationList) ? describe(locationList) : null,
      locationCards: locationCards && isVisible(locationCards) ? describe(locationCards) : null,
      reviewSide: reviewSide && isVisible(reviewSide) ? describe(reviewSide) : null,
      reviewCard: reviewCard && isVisible(reviewCard) ? describe(reviewCard) : null,
      reviewViewport: reviewViewport && isVisible(reviewViewport) ? describe(reviewViewport) : null,
      sectionCount: document.querySelectorAll('main section, body > section').length,
    };
  }, { label, device });
}

async function auditDevice(browser, deviceName, width, height) {
  const page = await browser.newPage();
  await page.setViewport({ width, height, deviceScaleFactor: 1 });
  page.setDefaultNavigationTimeout(45000);
  const results = [];

  for (const [label, route] of routes) {
    const consoleErrors = [];
    const onConsole = (message) => {
      if (['error', 'warning'].includes(message.type())) {
        consoleErrors.push(`${message.type()}: ${message.text()}`);
      }
    };
    const onPageError = (error) => consoleErrors.push(`pageerror: ${error.message}`);
    page.on('console', onConsole);
    page.on('pageerror', onPageError);

    try {
      const separator = route.includes('?') ? '&' : '?';
      const response = await page.goto(`${baseUrl}${route}${separator}codex_cache=fullqa0713e`, { waitUntil: 'domcontentloaded' });
      await pause(650);
      await page.evaluate(() => window.scrollTo(0, document.documentElement.scrollHeight));
      await pause(450);
      await page.evaluate(() => window.scrollTo(0, 0));
      await pause(2600);
      const metrics = await collectPageMetrics(page, label, deviceName);
      metrics.httpStatus = response ? response.status() : null;
      metrics.consoleErrors = consoleErrors.slice(0, 20);
      results.push(metrics);
      await page.screenshot({
        path: path.join(outputDir, `${deviceName}-${label}.png`),
        fullPage: true,
      });
    } catch (error) {
      results.push({ label, device: deviceName, error: String(error), consoleErrors });
    } finally {
      page.off('console', onConsole);
      page.off('pageerror', onPageError);
    }
  }

  await page.close();
  return results;
}

(async () => {
  fs.mkdirSync(outputDir, { recursive: true });
  const browser = await puppeteer.launch({
    executablePath: chromePath,
    headless: true,
    args: ['--disable-dev-shm-usage', '--no-first-run', '--disable-background-networking'],
  });

  try {
    const nestedResults = await Promise.all(devices.map((device) => auditDevice(browser, ...device)));
    const results = nestedResults.flat();
    fs.writeFileSync(path.join(outputDir, 'results.json'), JSON.stringify(results, null, 2));
    const summary = results.map((result) => ({
      page: result.label,
      device: result.device,
      status: result.httpStatus,
      h1: result.statusHeading,
      h1Size: result.headings?.find((heading) => heading.tag === 'h1')?.fontSize || null,
      horizontalOverflow: result.document?.horizontalOverflow || false,
      offscreenCount: result.horizontalOverflow?.length || 0,
      clippedCount: result.clipped?.length || 0,
      scrollRegionCount: result.scrollRegions?.length || 0,
      brokenImageCount: result.brokenImages?.length || 0,
      iframeHeights: result.iframes?.map((iframe) => iframe.height) || [],
      map: result.map ? { width: result.map.width, height: result.map.height } : null,
      locationList: result.locationList ? { width: result.locationList.width, height: result.locationList.height, scrollHeight: result.locationList.scrollHeight } : null,
      review: result.reviewCard ? { height: result.reviewCard.height, scrollHeight: result.reviewCard.scrollHeight, fontSize: result.reviewCard.fontSize } : null,
      error: result.error || null,
    }));
    fs.writeFileSync(path.join(outputDir, 'summary.json'), JSON.stringify(summary, null, 2));
    process.stdout.write(JSON.stringify(summary, null, 2));
  } finally {
    await browser.close();
  }
})().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
