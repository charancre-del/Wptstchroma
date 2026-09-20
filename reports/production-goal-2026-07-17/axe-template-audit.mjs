import fs from 'node:fs/promises';
import path from 'node:path';
import process from 'node:process';
import puppeteer from '../../lighthouse-automation/node_modules/puppeteer-core/lib/cjs/puppeteer/puppeteer-core.js';
import { launch as launchChrome } from '../../lighthouse-automation/node_modules/chrome-launcher/dist/index.js';

const root = process.cwd();
const baseUrl = process.env.CHROMA_QA_BASE_URL || 'https://x3yyntt5tp-staging.wpdns.site';
const outputDir = process.env.CHROMA_QA_OUTPUT_DIR
  ? path.resolve(root, process.env.CHROMA_QA_OUTPUT_DIR)
  : path.join(root, 'reports', 'production-goal-2026-07-17', 'qa', 'axe-current');
const axePath = path.join(root, 'lighthouse-automation', 'node_modules', 'axe-core', 'axe.min.js');
const allRoutes = [
  ['home', '/'],
  ['about', '/about/'],
  ['curriculum', '/curriculum/'],
  ['parents', '/parents/'],
  ['programs', '/programs/'],
  ['program-single', '/programs/infant-care/'],
  ['program-pre-k', '/programs/ga-pre-k/'],
  ['program-kindergarten', '/programs/kindergarten/'],
  ['program-rising-pre-k', '/programs/rising-pre-k/'],
  ['program-rising-kindergarten', '/programs/rising-kindergarten/'],
  ['locations', '/locations/'],
  ['location-single', '/locations/parklake-campus/'],
  ['contact', '/contact-us/'],
  ['schedule-tour', '/schedule-a-tour/'],
  ['summer-camp', '/summer-camp-discover-go/'],
  ['early-start', '/chroma-early-start/'],
  ['careers', '/careers/'],
  ['career-single', '/career/assistant-director-childcare-center/'],
  ['employers', '/employers/'],
  ['communities', '/communities/'],
  ['parent-portal', '/parent-portal/'],
  ['post-single', '/2026/05/22/georgia-pre-k-parent-guide/'],
  ['newsroom', '/newsroom/'],
  ['stories', '/stories/'],
  ['privacy', '/privacy-policy/'],
  ['terms', '/terms-of-service/'],
  ['not-found', '/codex-production-qa-not-found/'],
  ['spanish-home', '/es/'],
];
const allViewports = [
  ['desktop', 1440, 1000],
  ['tablet-landscape', 1024, 768],
  ['tablet-portrait', 834, 1112],
  ['tablet-small', 768, 1024],
  ['mobile', 390, 844],
];
const routeFilter = new Set((process.env.CHROMA_QA_ROUTES || '').split(',').map((value) => value.trim()).filter(Boolean));
const viewportFilter = new Set((process.env.CHROMA_QA_VIEWPORTS || '').split(',').map((value) => value.trim()).filter(Boolean));
const routes = routeFilter.size ? allRoutes.filter(([name]) => routeFilter.has(name)) : allRoutes;
const viewports = viewportFilter.size ? allViewports.filter(([name]) => viewportFilter.has(name)) : allViewports;

async function revealPage(page) {
  await page.evaluate(async () => {
    const step = Math.max(500, Math.floor(window.innerHeight * 0.8));
    for (let top = 0; top < document.documentElement.scrollHeight; top += step) {
      window.scrollTo(0, top);
      await new Promise((resolve) => setTimeout(resolve, 30));
    }
    window.scrollTo(0, 0);
    await new Promise((resolve) => setTimeout(resolve, 150));
  });
}

await fs.mkdir(outputDir, { recursive: true });
const chrome = await launchChrome({
  chromeFlags: ['--headless=new', '--disable-gpu', '--no-sandbox', '--disable-dev-shm-usage'],
  logLevel: 'silent',
});
const browser = await puppeteer.connect({ browserURL: `http://127.0.0.1:${chrome.port}` });
const results = [];

try {
  for (const [viewport, width, height] of viewports) {
    for (const [name, route] of routes) {
      const page = await browser.newPage();
      await page.setViewport({ width, height, deviceScaleFactor: 1 });
      await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
      const url = `${baseUrl}${route}${route.includes('?') ? '&' : '?'}codex_axe=${Date.now()}`;
      let status = 0;
      let violations = [];
      let error = '';
      try {
        const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 45000 });
        status = response?.status() || 0;
        await revealPage(page);
        await page.addScriptTag({ path: axePath });
        violations = await page.evaluate(async () => {
          const report = await window.axe.run(document, {
            runOnly: {
              type: 'tag',
              values: ['wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa', 'best-practice'],
            },
          });
          return report.violations.map((violation) => ({
            id: violation.id,
            impact: violation.impact,
            help: violation.help,
            helpUrl: violation.helpUrl,
            nodes: violation.nodes.map((node) => ({
              target: node.target,
              html: node.html,
              failureSummary: node.failureSummary,
            })),
          }));
        });
      } catch (caught) {
        error = caught instanceof Error ? caught.message : String(caught);
      }
      results.push({ name, route, viewport, width, height, url, status, error, violations });
      await fs.writeFile(path.join(outputDir, 'axe-template-results-partial.json'), JSON.stringify(results, null, 2));
      process.stdout.write(`${viewport.padEnd(7)} ${String(status).padEnd(3)} ${name}: ${violations.length} violation groups${error ? ` ERROR ${error}` : ''}\n`);
      await page.close();
    }
  }
} finally {
  try {
    await browser.disconnect();
  } catch {}
  try {
    await chrome.kill();
  } catch {}
}

const generatedAt = new Date().toISOString();
const summary = {
  generatedAt,
  baseUrl,
  checks: results.length,
  failedChecks: results.filter((result) => (result.status >= 400 && result.name !== 'not-found') || result.error).length,
  checksWithViolations: results.filter((result) => result.violations.length).length,
  violationGroups: results.reduce((total, result) => total + result.violations.length, 0),
  violationNodes: results.reduce((total, result) => total + result.violations.reduce((count, violation) => count + violation.nodes.length, 0), 0),
  rules: [...new Set(results.flatMap((result) => result.violations.map((violation) => violation.id)))].sort(),
};
const stamp = generatedAt.replace(/[-:.TZ]/g, '').slice(0, 14);
await fs.writeFile(path.join(outputDir, `axe-template-results-${stamp}.json`), JSON.stringify(results, null, 2));
await fs.writeFile(path.join(outputDir, 'axe-template-summary.json'), JSON.stringify(summary, null, 2));
console.log(JSON.stringify(summary, null, 2));
