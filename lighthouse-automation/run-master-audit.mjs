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

// --- Configuration ---
const argv = yargs(hideBin(process.argv))
    .option('sitemap', { type: 'string', description: 'URL to the sitemap.xml' })
    .option('file', { type: 'string', description: 'Path to a text file with URLs' })
    .option('concurrency', { type: 'number', default: 1, description: 'Number of concurrent Lighthouse runs (keep low for accuracy)' })
    .option('retries', { type: 'number', default: 2 })
    .option('output-dir', { type: 'string', default: 'lighthouse-master-out' })
    .option('headless', { type: 'boolean', default: true })
    .argv;

const OUTPUT_BASE = path.resolve(argv['output-dir']);
const REPORT_DIR = path.join(OUTPUT_BASE, 'reports');

// Ensure directories
if (!fs.existsSync(OUTPUT_BASE)) fs.mkdirSync(OUTPUT_BASE, { recursive: true });
if (!fs.existsSync(REPORT_DIR)) fs.mkdirSync(REPORT_DIR, { recursive: true });

// --- Sitemap Fetcher (Reused) ---
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

// --- Report Generator ---
function formatOutcome(score) {
    if (score === null) return 'Info';
    if (score >= 0.9) return 'Pass';
    if (score >= 0.5) return 'Average';
    return 'Fail';
}

function generateMarkdownReport(url, mobileLhr, desktopLhr) {
    const date = new Date().toISOString();

    // Helper to get audit rows
    const getAuditRows = (lhr, categories) => {
        let content = '';

        categories.forEach(catId => {
            const category = lhr.categories[catId];
            if (!category) return;

            content += `### ${category.title} (${(category.score * 100).toFixed(0)})\n\n`;

            // Get audits for this category
            const auditRefs = category.auditRefs;
            const failingAudits = auditRefs.filter(ref => {
                const audit = lhr.audits[ref.id];
                // Include if it's a failure (< 0.9) or an interesting diagnostic/insight regardless of score sometimes,
                // but usually we want < 1 or < 0.9. User asked for "everything failing".
                return audit.score !== 1 && audit.scoreDisplayMode !== 'notApplicable' && audit.scoreDisplayMode !== 'manual';
            });

            if (failingAudits.length === 0) {
                content += `*No significant issues found.*\n\n`;
            } else {
                failingAudits.forEach(ref => {
                    const audit = lhr.audits[ref.id];
                    content += `#### ${audit.title} \n`;
                    content += `**Score:** ${audit.score === null ? 'N/A' : (audit.score * 100).toFixed(0)} | **Severity:** ${formatOutcome(audit.score)}\n\n`;
                    content += `> ${audit.description}\n\n`;

                    if (audit.displayValue) {
                        content += `**Value:** ${audit.displayValue}\n\n`;
                    }

                    // Render Details (Table or List)
                    if (audit.details) {
                        if (audit.details.type === 'table' && audit.details.items.length > 0) {
                            // Render simple table
                            const headings = audit.details.headings || [];
                            if (headings.length > 0) {
                                content += `| ${headings.map(h => h.label || h.text).join(' | ')} |\n`;
                                content += `| ${headings.map(() => '---').join(' | ')} |\n`;

                                // Limit to 10 items to prevent massive files
                                audit.details.items.slice(0, 10).forEach(item => {
                                    const row = headings.map(h => {
                                        const key = h.key;
                                        let val = item[key];
                                        if (typeof val === 'object' && val !== null) {
                                            // Handle node/url/source objects
                                            if (val.snippet) val = `\`${val.snippet.trim().replace(/`/g, '')}\``;
                                            else if (val.url) val = `[Link](${val.url})`;
                                            else if (val.selector) val = `\`${val.selector}\``;
                                            else val = JSON.stringify(val);
                                        }
                                        return String(val || '-').replace(/\|/g, '\\|').replace(/\n/g, ' ');
                                    });
                                    content += `| ${row.join(' | ')} |\n`;
                                });
                                if (audit.details.items.length > 10) content += `*...and ${audit.details.items.length - 10} more items.*\n`;
                                content += `\n`;
                            }
                        } else if (audit.details.type === 'debugdata') {
                            // Skip debug data usually
                        } else if (audit.details.items) {
                            // List items
                            audit.details.items.forEach(item => {
                                content += `- ${JSON.stringify(item)}\n`;
                            });
                            content += `\n`;
                        }
                    }
                    content += `---\n\n`;
                });
            }
        });
        return content;
    };

    const categories = ['performance', 'accessibility', 'best-practices', 'seo'];

    let md = `# Sensitivity Audit Report for ${url}\n`;
    md += `**Date:** ${date}\n\n`;

    md += `## Executive Summary\n\n`;
    md += `| Category | Mobile Score | Desktop Score |\n`;
    md += `|---|---|---|\n`;
    categories.forEach(cat => {
        const mScore = mobileLhr.categories[cat] ? (mobileLhr.categories[cat].score * 100).toFixed(0) : '-';
        const dScore = desktopLhr.categories[cat] ? (desktopLhr.categories[cat].score * 100).toFixed(0) : '-';
        md += `| ${mobileLhr.categories[cat]?.title || cat} | ${mScore} | ${dScore} |\n`;
    });
    md += `\n`;

    md += `## Mobile Insights (Moto G Power / Slow 4G)\n`;
    md += getAuditRows(mobileLhr, categories);

    md += `## Desktop Insights\n`;
    md += getAuditRows(desktopLhr, categories);

    return md;
}


// --- Lighthouse Runner ---
async function runAuditPair(url, retryCount = 0) {
    const filename = url.replace(/[^a-z0-9]/gi, '_').replace(/_+/g, '_').replace(/^_|_$/g, '');
    const reportPath = path.join(REPORT_DIR, `${filename}.md`);

    if (fs.existsSync(reportPath)) {
        console.log(`[SKIP] Report exists for ${url}`);
        return;
    }

    console.log(`[START] Auditing ${url}`);

    let chrome;
    try {
        chrome = await chromeLauncher.launch({
            chromeFlags: ['--no-sandbox', argv.headless ? '--headless' : '', '--disable-gpu'].filter(Boolean)
        });

        // Mobile Config (Moto G Power, Slow 4G)
        // Lighthouse default 'mobile' preset uses: 
        // 360x640, DPR 2.625, UA: Mozilla/5.0 (Linux; Android 7.0; Moto G (4)) ...
        // We will stick to the 'mobile' preset but ensure throttling is 'simulate' (default) which matches Slow 4G
        const mobileConfig = {
            extends: 'lighthouse:default',
            settings: {
                formFactor: 'mobile',
                screenEmulation: { mobile: true, width: 360, height: 640, deviceScaleFactor: 2.625, disabled: false },
                throttlingMethod: 'simulate', // This applies standard mobile throttling (Slow 4G)
                onlyCategories: ['performance', 'accessibility', 'best-practices', 'seo']
            }
        };

        const desktopConfig = {
            extends: 'lighthouse:default',
            settings: {
                formFactor: 'desktop',
                screenEmulation: { mobile: false, width: 1350, height: 940, deviceScaleFactor: 1, disabled: false },
                throttlingMethod: 'simulate',
                onlyCategories: ['performance', 'accessibility', 'best-practices', 'seo']
            }
        };

        const port = chrome.port;

        // Run Mobile
        const mobileResult = await lighthouse(url, { port, output: 'json', logLevel: 'error' }, mobileConfig);
        const mobileLhr = JSON.parse(mobileResult.report);

        // Run Desktop
        const desktopResult = await lighthouse(url, { port, output: 'json', logLevel: 'error' }, desktopConfig);
        const desktopLhr = JSON.parse(desktopResult.report);

        // Generate MD Report
        const markdown = generateMarkdownReport(url, mobileLhr, desktopLhr);
        fs.writeFileSync(reportPath, markdown);

        console.log(`[DONE] Scored: M(${mobileLhr.categories.performance.score * 100}) D(${desktopLhr.categories.performance.score * 100}) - ${url}`);

        return {
            url,
            filename: `${filename}.md`,
            mobileScores: {
                perf: mobileLhr.categories.performance.score,
                a11y: mobileLhr.categories.accessibility.score,
                seo: mobileLhr.categories.seo.score,
                best: mobileLhr.categories['best-practices'].score
            }
        };

    } catch (e) {
        console.error(`[FAIL] ${url}: ${e.message}`);
        if (chrome) await chrome.kill();

        if (retryCount < argv.retries) {
            console.log(`[RETRY] Retrying ${url}...`);
            await new Promise(r => setTimeout(r, 5000));
            return runAuditPair(url, retryCount + 1);
        }
        return null;
    } finally {
        if (chrome) {
            try { await chrome.kill(); } catch (e) { }
        }
    }
}

// --- Main ---
async function main() {
    let urls = [];
    if (argv.file) {
        if (fs.existsSync(argv.file)) {
            urls = fs.readFileSync(argv.file, 'utf-8').split('\n').map(l => l.trim()).filter(Boolean);
        }
    } else if (argv.sitemap) {
        urls = await fetchSitemapUrls(argv.sitemap);
    } else {
        console.error('Usage: node run-master-audit.mjs --sitemap <url>');
        process.exit(1);
    }

    urls = [...new Set(urls)];
    console.log(`Auditing ${urls.length} URLs...`);

    const limit = pLimit(argv.concurrency);
    const tasks = urls.map(url => limit(() => runAuditPair(url)));

    const results = (await Promise.all(tasks)).filter(Boolean);

    // Generate Master Index
    let indexMd = `# Master Lighthouse Report\n\n`;
    indexMd += `**Date:** ${new Date().toLocaleString()}\n`;
    indexMd += `**Total Pages:** ${results.length}\n\n`;

    indexMd += `| Page | Mobile Perf | Mobile A11y | Mobile SEO | Report |\n`;
    indexMd += `|---|---|---|---|---|\n`;

    results.sort((a, b) => a.mobileScores.perf - b.mobileScores.perf); // Sort by performance ascending (worst first)

    results.forEach(r => {
        indexMd += `| ${r.url} | ${(r.mobileScores.perf * 100).toFixed(0)} | ${(r.mobileScores.a11y * 100).toFixed(0)} | ${(r.mobileScores.seo * 100).toFixed(0)} | [View Report](reports/${r.filename}) |\n`;
    });

    fs.writeFileSync(path.join(OUTPUT_BASE, 'MASTER_LIGHTHOUSE_REPORT.md'), indexMd);
    console.log(`\nAll done! Master report saved to ` + path.join(OUTPUT_BASE, 'MASTER_LIGHTHOUSE_REPORT.md'));
}

main().catch(console.error);
