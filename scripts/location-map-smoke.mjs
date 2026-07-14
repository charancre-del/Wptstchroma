import fs from 'node:fs/promises';
import path from 'node:path';
import process from 'node:process';
import puppeteer from '../lighthouse-automation/node_modules/puppeteer-core/lib/cjs/puppeteer/puppeteer-core.js';
import { launch as launchChrome } from '../lighthouse-automation/node_modules/chrome-launcher/dist/index.js';

const baseUrl = process.env.CHROMA_QA_BASE_URL || 'https://x3yyntt5tp-staging.wpdns.site';
const outputPath = path.join(process.cwd(), 'reports', 'launch-readiness', 'location-map-smoke-20260714.json');
const origin = new URL(baseUrl).origin;
const profiles = [
  { name: 'desktop', width: 1440, height: 1000 },
  { name: 'mobile', width: 390, height: 900 },
];

const chrome = await launchChrome({ chromeFlags: ['--headless=new', '--no-sandbox', '--disable-gpu'] });
const browser = await puppeteer.connect({ browserURL: `http://127.0.0.1:${chrome.port}` });
const results = [];

try {
  const context = browser.defaultBrowserContext();
  await context.overridePermissions(origin, ['geolocation']);

  for (const profile of profiles) {
    const page = await browser.newPage();
    const requestedUrls = [];
    page.on('request', (request) => requestedUrls.push(request.url()));
    await page.setViewport({ width: profile.width, height: profile.height, deviceScaleFactor: 1 });
    await page.setGeolocation({ latitude: 34.0232, longitude: -84.3616, accuracy: 25 });
    await page.goto(`${baseUrl}/locations/?codex_cache=mapsmoke0714-${profile.name}`, {
      waitUntil: 'domcontentloaded',
      timeout: 60000,
    });

    await page.waitForSelector('.leaflet-container', { timeout: 30000 });
    await page.waitForSelector('.leaflet-tile-loaded', { timeout: 30000 });
    await page.waitForSelector('[data-location-card]', { timeout: 30000 });
    await page.click('[data-location-card]');
    await page.waitForSelector('.leaflet-popup', { visible: true, timeout: 30000 });
    await new Promise((resolve) => setTimeout(resolve, 350));

    const popupState = await page.evaluate(() => {
      const map = document.querySelector('[data-chroma-map]');
      const popup = document.querySelector('.leaflet-popup');
      const header = document.querySelector('header');
      const markerImage = document.querySelector('.chroma-map-marker-target img');
      const mapRect = map?.getBoundingClientRect();
      const popupRect = popup?.getBoundingClientRect();
      const headerRect = header?.getBoundingClientRect();
      const rect = (value) => value ? {
        top: Math.round(value.top),
        right: Math.round(value.right),
        bottom: Math.round(value.bottom),
        left: Math.round(value.left),
        width: Math.round(value.width),
        height: Math.round(value.height),
      } : null;

      return {
        mapReady: Boolean(map && map._leaflet_id),
        popupText: popup?.textContent.trim().replace(/\s+/g, ' ').slice(0, 180) || '',
        popupStyle: popup?.getAttribute('style') || '',
        popupParentClass: popup?.parentElement?.className || '',
        popupParentStyle: popup?.parentElement?.getAttribute('style') || '',
        mapRect: rect(mapRect),
        popupRect: rect(popupRect),
        headerRect: rect(headerRect),
        popupInsideMap: Boolean(mapRect && popupRect
          && popupRect.left >= mapRect.left - 1
          && popupRect.right <= mapRect.right + 1
          && popupRect.top >= mapRect.top - 1
          && popupRect.bottom <= mapRect.bottom + 1),
        popupBelowHeader: Boolean(!headerRect || !popupRect || popupRect.top >= headerRect.bottom - 1),
        markerImage: markerImage?.src || '',
        tileCount: document.querySelectorAll('.leaflet-tile-loaded').length,
      };
    });

    await page.click('[data-location-filter="closest"]');
    await page.waitForFunction(() => {
      const distance = document.querySelector('[data-location-distance]:not(.hidden)');
      return Boolean(distance && /mi away/i.test(distance.textContent || ''));
    }, { timeout: 30000 });

    const geolocationState = await page.evaluate(() => ({
      status: document.querySelector('[data-location-status]')?.textContent.trim() || '',
      firstDistance: document.querySelector('[data-location-distance]:not(.hidden)')?.textContent.trim() || '',
    }));

    const localLeafletRequests = requestedUrls.filter((url) => url.includes('/assets/vendor/leaflet-1.9.4/'));
    const unpkgRequests = requestedUrls.filter((url) => url.includes('unpkg.com'));
    results.push({
      profile: profile.name,
      ...popupState,
      ...geolocationState,
      localLeafletRequests,
      unpkgRequests,
      pass: popupState.mapReady
        && popupState.popupInsideMap
        && popupState.popupBelowHeader
        && popupState.tileCount > 0
        && popupState.markerImage.includes('/assets/vendor/leaflet-1.9.4/')
        && /mi away/i.test(geolocationState.firstDistance)
        && localLeafletRequests.length >= 3
        && unpkgRequests.length === 0,
    });
    await page.close();
  }
} finally {
  await fs.mkdir(path.dirname(outputPath), { recursive: true });
  await fs.writeFile(outputPath, JSON.stringify(results, null, 2));
  await browser.disconnect();
  try {
    await chrome.kill();
  } catch (error) {
    console.warn(`Chrome cleanup warning: ${error.message}`);
  }
}

console.log(JSON.stringify(results, null, 2));
if (results.some((result) => !result.pass)) {
  process.exitCode = 1;
}
