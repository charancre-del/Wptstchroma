
import fs from 'fs';
import path from 'path';
import axios from 'axios';
import { XMLParser } from 'fast-xml-parser';
import yargs from 'yargs/yargs';
import { hideBin } from 'yargs/helpers';
import pLimit from 'p-limit';
import * as chromeLauncher from 'chrome-launcher';
import lighthouse from 'lighthouse';
import { URL } from 'url';

// --- Error Handling for Setup/Teardown ---
process.on('uncaughtException', (err) => {
    if (err.code === 'EPERM' && err.syscall === 'rm') {
        // Ignore permission errors during temp file cleanup on Windows
        // These are benign as the OS will clean them up later or they are locked by a dying process
        return;
    }
    console.error('Uncaught Exception:', err);
    process.exit(1);
});

// --- Configuration ---
const argv = yargs(hideBin(process.argv))
    .option('sitemap', { type: 'string', description: 'URL to the sitemap.xml' })
    .option('file', { type: 'string', description: 'Path to a text file with URLs' })
    .option('concurrency', { type: 'number', default: 2, description: 'Number of concurrent Lighthouse runs' }) // Lower concurrency to be safe with double pass
    .option('retries', { type: 'number', default: 2, description: 'Max retries per URL per strategy' })
    .option('output-dir', { type: 'string', default: 'lighthouse-out/artifacts' })
    .option('headless', { type: 'boolean', default: true })
    .argv;

const OUTPUT_BASE = path.resolve(argv['output-dir']);
const MOBILE_DIR = path.join(OUTPUT_BASE, 'mobile');
const DESKTOP_DIR = path.join(OUTPUT_BASE, 'desktop');

// Ensure directories
[MOBILE_DIR, DESKTOP_DIR].forEach(dir => {
    if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
});

// --- Sitemap ---
async function fetchSitemapUrls(sitemapUrl) {
    console.log(`Fetching sitemap: ${sitemapUrl}`);
    const parser = new XMLParser({
        ignoreAttributes: false,
        isArray: (name, jpath) => ['sitemapindex.sitemap', 'urlset.url'].includes(jpath)
    });

    try {
        const { data } = await axios.get(sitemapUrl, { timeout: 15000 });
        const parsed = parser.parse(data);
        let urls = [];

        if (parsed.sitemapindex?.sitemap) {
            const children = Array.isArray(parsed.sitemapindex.sitemap) ? parsed.sitemapindex.sitemap : [parsed.sitemapindex.sitemap];
            for (const child of children) {
                if (child.loc) urls.push(...await fetchSitemapUrls(child.loc));
            }
        } else if (parsed.urlset?.url) {
            const entries = Array.isArray(parsed.urlset.url) ? parsed.urlset.url : [parsed.urlset.url];
            urls = entries.map(e => e.loc).filter(Boolean);
        }
        return urls;
    } catch (e) {
        console.error(`Sitemap error (${sitemapUrl}): ${e.message}`);
        return [];
    }
}

// --- Lighthouse ---
async function runAudit(url, strategy, retryCount = 0) {
    const safeName = url.replace(/[^a-z0-9]/gi, '_').replace(/_+/g, '_').replace(/^_|_$/g, '');
    const outDir = strategy === 'mobile' ? MOBILE_DIR : DESKTOP_DIR;
    const outFile = path.join(outDir, `${safeName}.json`);

    if (fs.existsSync(outFile)) {
        console.log(`[SKIP] ${strategy} for ${url} (exists)`);
        return;
    }

    console.log(`[START] ${strategy} (${retryCount}/${argv.retries}) for ${url}`);

    let chrome;
    try {
        chrome = await chromeLauncher.launch({
            chromeFlags: ['--no-sandbox', argv.headless ? '--headless' : '', '--disable-gpu'].filter(Boolean)
        });

        const config = {
            extends: 'lighthouse:default',
            settings: {
                formFactor: strategy,
                throttlingMethod: 'simulate',
                screenEmulation: strategy === 'mobile' ? {
                    mobile: true,
                    width: 360, height: 800, deviceScaleFactor: 2.625, disabled: false
                } : {
                    mobile: false,
                    width: 1350, height: 940, deviceScaleFactor: 1, disabled: false
                },
                onlyCategories: ['performance', 'accessibility', 'best-practices', 'seo']
            }
        };

        const runnerResult = await lighthouse(url, {
            port: chrome.port,
            output: 'json',
            logLevel: 'error'
        }, config);

        if (!runnerResult || !runnerResult.report) throw new Error('No report generated');

        fs.writeFileSync(outFile, runnerResult.report);
        console.log(`[DONE] ${strategy} for ${url}`);

    } catch (e) {
        console.error(`[FAIL] ${strategy} for ${url}: ${e.message}`);

        // Safer kill
        if (chrome) {
            try { await chrome.kill(); } catch (k) { }
            chrome = null;
        }

        if (retryCount < argv.retries) {
            console.log(`[RETRY] Retrying ${url} in 5s...`);
            await new Promise(r => setTimeout(r, 5000));
            return runAudit(url, strategy, retryCount + 1);
        } else {
            // Write error file
            fs.writeFileSync(outFile.replace('.json', '.error.txt'), e.message);
        }
    } finally {
        if (chrome) {
            try { await chrome.kill(); } catch (k) { }
        }
    }
}

// --- Main ---
async function main() {
    let urls = [];

    if (argv.file) {
        if (fs.existsSync(argv.file)) {
            console.log(`Reading URLs from file: ${argv.file}`);
            const content = fs.readFileSync(argv.file, 'utf-8');
            urls = content.split('\n').map(l => l.trim()).filter(Boolean);
        } else {
            console.error(`File not found: ${argv.file}`);
        }
    } else if (argv.sitemap) {
        let retrySitemap = 0;
        while (retrySitemap < 3 && urls.length === 0) {
            urls = await fetchSitemapUrls(argv.sitemap);
            if (urls.length === 0) {
                console.log('Sitemap fetch URL list empty or failed, retrying...');
                retrySitemap++;
                await new Promise(r => setTimeout(r, 2000));
            }
        }
    } else {
        console.error('Usage: node audit-runner.mjs --sitemap <url> OR --file <path>');
        return;
    }

    // De-duplicate
    urls = [...new Set(urls)];

    console.log(`Targeting ${urls.length} unique URLs.`);

    // Save URL list
    fs.writeFileSync(path.join(OUTPUT_BASE, 'urls.txt'), urls.join('\n'));

    const limit = pLimit(argv.concurrency);
    const tasks = [];

    for (const url of urls) {
        // Queue Mobile
        tasks.push(limit(() => runAudit(url, 'mobile')));
        // Queue Desktop
        tasks.push(limit(() => runAudit(url, 'desktop')));
    }

    await Promise.all(tasks);
    console.log('Audit Run Complete.');
}

main().catch(console.error);
