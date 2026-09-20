import fs from 'node:fs/promises';
import path from 'node:path';
import process from 'node:process';
import puppeteer from '../lighthouse-automation/node_modules/puppeteer-core/lib/cjs/puppeteer/puppeteer-core.js';
import { launch as launchChrome } from '../lighthouse-automation/node_modules/chrome-launcher/dist/index.js';

const root = process.cwd();
const outputDir = path.join(root, 'reports', 'launch-readiness');
const screenshotDir = path.join(root, 'qa-screenshots', 'production');
const axePath = path.join(root, 'lighthouse-automation', 'node_modules', 'axe-core', 'axe.min.js');
const baseUrl = process.env.CHROMA_QA_BASE_URL || 'https://x3yyntt5tp-staging.wpdns.site';

const routes = [
  ['home', '/'],
  ['about', '/about/'],
  ['curriculum', '/curriculum/'],
  ['parents', '/parents/'],
  ['programs', '/programs/'],
  ['program-single', '/programs/rising-kindergarten/'],
  ['locations', '/locations/'],
  ['location-single', '/locations/parklake-campus/'],
  ['city-single', '/childcare/lafayette/'],
  ['contact', '/contact-us/'],
  ['schedule-tour', '/schedule-a-tour/'],
  ['summer-camp', '/summer-camp-discover-go/'],
  ['early-start', '/chroma-early-start/'],
  ['careers', '/careers/'],
  ['career-single', '/career/assistant-director-childcare-center/'],
  ['employers', '/employers/'],
  ['communities', '/communities/'],
  ['blog', '/blog/'],
  ['post-single', '/2026/05/22/georgia-pre-k-parent-guide/'],
  ['newsroom', '/newsroom/'],
  ['stories', '/stories/'],
  ['privacy', '/privacy-policy/'],
  ['terms', '/terms-of-service/'],
  ['parent-portal', '/parent-portal/'],
  ['director-portal', '/portal/'],
  ['qa-reports', '/qa-reports/'],
  ['not-found', '/codex-production-qa-not-found/'],
];

const viewports = [
  ['desktop', 1440, 1000],
  ['tablet', 834, 1112],
  ['mobile', 390, 844],
];

const ignoredRequestHosts = [
  'google-analytics.com',
  'googletagmanager.com',
  'doubleclick.net',
  'facebook.com',
  'connect.facebook.net',
  'googleadservices.com',
];

function isIgnoredRequest(url) {
  return ignoredRequestHosts.some((host) => url.includes(host));
}

async function scrollPage(page) {
  await page.evaluate(async () => {
    const reduceMotion = document.createElement('style');
    reduceMotion.textContent = '*{scroll-behavior:auto!important;animation-duration:.001ms!important;animation-delay:0s!important;transition-duration:.001ms!important}';
    document.head.appendChild(reduceMotion);
    const step = Math.max(500, Math.floor(window.innerHeight * 0.8));
    for (let top = 0; top < document.documentElement.scrollHeight; top += step) {
      window.scrollTo(0, top);
      await new Promise((resolve) => setTimeout(resolve, 35));
    }
    window.scrollTo(0, 0);
  });
}

async function inspectPage(page) {
  return page.evaluate(() => {
    const visible = (element) => {
      const style = getComputedStyle(element);
      const rect = element.getBoundingClientRect();
      return style.display !== 'none' && style.visibility !== 'hidden' && Number(style.opacity || 1) > 0 && rect.width > 1 && rect.height > 1;
    };
    const selector = (element) => {
      if (element.id) return `#${CSS.escape(element.id)}`;
      const classes = [...element.classList].slice(0, 3).map((name) => `.${CSS.escape(name)}`).join('');
      return `${element.tagName.toLowerCase()}${classes}`;
    };
    const viewportWidth = window.innerWidth;
    const horizontalOffenders = [...document.body.querySelectorAll('*')]
      .filter(visible)
      .map((element) => ({ element, rect: element.getBoundingClientRect() }))
      .filter(({ element, rect }) => {
        const position = getComputedStyle(element).position;
        if (position === 'fixed' && element.closest('[data-chroma-chat], .lc_text-widget, .ghl-chat-widget')) return false;
        return rect.right > viewportWidth + 3 || rect.left < -3;
      })
      .slice(0, 12)
      .map(({ element, rect }) => ({ selector: selector(element), left: Math.round(rect.left), right: Math.round(rect.right), width: Math.round(rect.width) }));
    const clippedText = [...document.body.querySelectorAll('h1,h2,h3,h4,h5,h6,p,li,a,button,span')]
      .filter((element) => visible(element) && element.textContent.trim())
      .filter((element) => {
        const style = getComputedStyle(element);
        const clips = ['hidden', 'clip'].includes(style.overflow) || ['hidden', 'clip'].includes(style.overflowX) || ['hidden', 'clip'].includes(style.overflowY);
        const lineClamp = style.webkitLineClamp && style.webkitLineClamp !== 'none';
        return clips && !lineClamp && (element.scrollWidth > element.clientWidth + 3 || element.scrollHeight > element.clientHeight + 3);
      })
      .slice(0, 12)
      .map((element) => ({ selector: selector(element), text: element.textContent.trim().replace(/\s+/g, ' ').slice(0, 120) }));
    const brokenImages = [...document.images]
      .filter((image) => image.complete && image.naturalWidth === 0)
      .map((image) => image.currentSrc || image.src || image.alt)
      .slice(0, 12);
    const duplicateIds = [...document.querySelectorAll('[id]')]
      .map((element) => element.id)
      .filter((id, index, ids) => id && ids.indexOf(id) !== index)
      .filter((id, index, ids) => ids.indexOf(id) === index);
    const emptyLinks = [...document.querySelectorAll('a[href]')]
      .filter(visible)
      .filter((link) => {
        const text = (link.getAttribute('aria-label') || link.textContent || '').trim();
        const imageAlt = [...link.querySelectorAll('img')].map((image) => image.alt || '').join('').trim();
        return !text && !imageAlt;
      })
      .map(selector)
      .slice(0, 12);
    return {
      title: document.title,
      statusText: document.body.innerText.trim().slice(0, 80),
      documentWidth: document.documentElement.scrollWidth,
      viewportWidth,
      horizontalOverflow: document.documentElement.scrollWidth > viewportWidth + 3,
      horizontalOffenders,
      clippedText,
      brokenImages,
      duplicateIds,
      emptyLinks,
      h1Count: document.querySelectorAll('h1').length,
      mainCount: document.querySelectorAll('main').length,
    };
  });
}

await fs.mkdir(outputDir, { recursive: true });
await fs.mkdir(screenshotDir, { recursive: true });

const chrome = await launchChrome({
  chromeFlags: ['--headless=new', '--disable-gpu', '--no-sandbox', '--disable-dev-shm-usage', '--window-size=1440,1000'],
  logLevel: 'silent',
});
const browser = await puppeteer.connect({ browserURL: `http://127.0.0.1:${chrome.port}` });
const results = [];

try {
  for (const [viewportName, width, height] of viewports) {
    for (const [routeName, route] of routes) {
      const page = await browser.newPage();
      await page.setViewport({ width, height, deviceScaleFactor: 1 });
      await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
      const consoleErrors = [];
      const requestFailures = [];
      page.on('console', (message) => {
        if (message.type() === 'error') consoleErrors.push(message.text().slice(0, 400));
      });
      page.on('requestfailed', (request) => {
        if (!isIgnoredRequest(request.url())) requestFailures.push(`${request.failure()?.errorText || 'failed'} ${request.url()}`.slice(0, 500));
      });
      const url = `${baseUrl}${route}${route.includes('?') ? '&' : '?'}codex_prodqa=0713`;
      let status = 0;
      let inspection = {};
      let axeViolations = [];
      let loadError = '';
      try {
        const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 45000 });
        status = response?.status() || 0;
        await scrollPage(page);
        await page.addScriptTag({ path: axePath });
        inspection = await inspectPage(page);
        axeViolations = await page.evaluate(async () => {
          const result = await window.axe.run(document, { runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21aa'] } });
          return result.violations.map((violation) => ({
            id: violation.id,
            impact: violation.impact,
            description: violation.description,
            nodes: violation.nodes.slice(0, 5).map((node) => node.target.join(' ')),
          }));
        });
      } catch (error) {
        loadError = error instanceof Error ? error.message : String(error);
      }
      const failures = status >= 400 || loadError || inspection.horizontalOverflow || inspection.brokenImages?.length || inspection.clippedText?.length || axeViolations.length;
      let screenshot = '';
      if (failures) {
        screenshot = path.join(screenshotDir, `${routeName}-${viewportName}.png`);
        await page.screenshot({ path: screenshot, fullPage: true, captureBeyondViewport: true });
      }
      results.push({
        routeName,
        route,
        viewport: viewportName,
        width,
        height,
        url,
        status,
        loadError,
        ...inspection,
        axeViolations,
        consoleErrors: [...new Set(consoleErrors)].slice(0, 12),
        requestFailures: [...new Set(requestFailures)].slice(0, 12),
        screenshot: screenshot ? path.relative(root, screenshot) : '',
      });
      process.stdout.write(`${viewportName.padEnd(7)} ${String(status).padEnd(3)} ${routeName}${failures ? ' REVIEW' : ' OK'}\n`);
      await page.close();
    }
  }
} finally {
  try {
    await browser.disconnect();
  } catch (error) {
    console.warn(`Browser disconnect warning: ${error instanceof Error ? error.message : String(error)}`);
  }
  try {
    await chrome.kill();
  } catch (error) {
    console.warn(`Chrome cleanup warning: ${error instanceof Error ? error.message : String(error)}`);
  }
}

const reportPath = path.join(outputDir, 'production-visual-qa-20260713.json');
await fs.writeFile(reportPath, JSON.stringify(results, null, 2));
const summary = results.filter((result) =>
  result.status >= 400 ||
  result.loadError ||
  result.horizontalOverflow ||
  result.brokenImages?.length ||
  result.clippedText?.length ||
  result.axeViolations?.length
);
await fs.writeFile(path.join(outputDir, 'production-visual-qa-summary-20260713.json'), JSON.stringify(summary, null, 2));
console.log(`Saved ${results.length} checks to ${path.relative(root, reportPath)}; ${summary.length} require review.`);
