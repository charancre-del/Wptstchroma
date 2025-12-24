#!/usr/bin/env node
/**
 * Cache Warmer Script (Node.js)
 * 
 * Parses a sitemap and requests each URL to warm up the cache.
 * 
 * Usage:
 *   node cache-warmer.mjs <sitemap_url> [options]
 *   node cache-warmer.mjs https://example.com/sitemap.xml
 *   node cache-warmer.mjs https://example.com/sitemap.xml --passes=2
 */

import https from 'https';
import http from 'http';

// Configuration defaults
const config = {
    delay: 100,           // Delay between requests in ms
    timeout: 30000,       // Request timeout in ms
    userAgent: 'CacheWarmer/1.0 (+https://chromaela.com)',
    verbose: false,
    dryRun: false,
    passes: 1,            // Number of warm-up passes
};

// Parse command line arguments
const args = process.argv.slice(2);
let sitemapUrl = null;

for (const arg of args) {
    if (arg.startsWith('--delay=')) {
        config.delay = parseInt(arg.substring(8), 10);
    } else if (arg.startsWith('--timeout=')) {
        config.timeout = parseInt(arg.substring(10), 10) * 1000;
    } else if (arg.startsWith('--user-agent=')) {
        config.userAgent = arg.substring(13);
    } else if (arg === '--verbose') {
        config.verbose = true;
    } else if (arg === '--dry-run') {
        config.dryRun = true;
    } else if (arg.startsWith('--passes=')) {
        config.passes = parseInt(arg.substring(9), 10);
    } else if (!arg.startsWith('--')) {
        sitemapUrl = arg;
    }
}

// Validate sitemap URL
if (!sitemapUrl) {
    console.log('Usage: node cache-warmer.mjs <sitemap_url> [options]');
    console.log('\nOptions:');
    console.log('  --delay=<ms>       Delay between requests in milliseconds (default: 100)');
    console.log('  --timeout=<sec>    Request timeout in seconds (default: 30)');
    console.log('  --passes=<num>     Number of warm-up passes (default: 1)');
    console.log('  --user-agent=<ua>  Custom user agent string');
    console.log('  --verbose          Show detailed output for each request');
    console.log('  --dry-run          Parse sitemap but don\'t make requests');
    process.exit(1);
}

/**
 * Fetch URL content
 */
function fetchUrl(url, warmCache = false) {
    return new Promise((resolve) => {
        const protocol = url.startsWith('https') ? https : http;
        const headers = {
            'User-Agent': config.userAgent,
        };

        if (warmCache) {
            headers['Accept'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
            headers['Cache-Control'] = 'no-cache';
            headers['Pragma'] = 'no-cache';
        }

        const startTime = Date.now();

        const req = protocol.get(url, { headers, timeout: config.timeout }, (res) => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => {
                resolve({
                    content: data,
                    httpCode: res.statusCode,
                    totalTime: (Date.now() - startTime) / 1000,
                    size: Buffer.byteLength(data, 'utf8'),
                    error: null,
                });
            });
        });

        req.on('error', (err) => {
            resolve({
                content: null,
                httpCode: 0,
                totalTime: (Date.now() - startTime) / 1000,
                size: 0,
                error: err.message,
            });
        });

        req.on('timeout', () => {
            req.destroy();
            resolve({
                content: null,
                httpCode: 0,
                totalTime: config.timeout / 1000,
                size: 0,
                error: 'Request timeout',
            });
        });
    });
}

/**
 * Parse sitemap XML and extract URLs
 */
function parseSitemap(xmlContent) {
    const urls = [];
    const sitemaps = [];

    // Simple and reliable: extract all <loc> tags directly
    const locRegex = /<loc>([^<]+)<\/loc>/gi;
    let match;
    while ((match = locRegex.exec(xmlContent)) !== null) {
        const loc = match[1].trim();
        // Check if this looks like a sitemap URL
        if (loc.includes('sitemap') && loc.endsWith('.xml')) {
            sitemaps.push(loc);
        } else {
            urls.push({ loc });
        }
    }

    return { urls, sitemaps, error: null };
}

/**
 * Format bytes to human readable
 */
function formatBytes(bytes) {
    if (bytes >= 1048576) {
        return (bytes / 1048576).toFixed(2) + ' MB';
    } else if (bytes >= 1024) {
        return (bytes / 1024).toFixed(2) + ' KB';
    }
    return bytes + ' B';
}

/**
 * Format time duration
 */
function formatDuration(seconds) {
    if (seconds >= 3600) {
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = Math.floor(seconds % 60);
        return `${h}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    } else if (seconds >= 60) {
        const m = Math.floor(seconds / 60);
        const s = Math.floor(seconds % 60);
        return `${m}:${String(s).padStart(2, '0')}`;
    }
    return seconds.toFixed(2) + 's';
}

/**
 * Sleep for a given number of milliseconds
 */
function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

/**
 * Run a single pass of cache warming
 */
async function runWarmUpPass(allUrls, passNumber) {
    console.log(`\n🔥 Pass ${passNumber}/${config.passes}: Starting cache warm-up...`);
    console.log('─'.repeat(70));

    const stats = {
        success: 0,
        failed: 0,
        retries: 0,
        totalTime: 0,
        totalSize: 0,
        startTime: Date.now(),
    };

    const totalUrls = allUrls.length;
    const maxRetries = 3;

    for (let i = 0; i < totalUrls; i++) {
        const url = allUrls[i];
        const current = i + 1;
        const progress = `[${current}/${totalUrls}]`.padEnd(12);
        const shortUrl = url.loc.length > 50 ? '...' + url.loc.slice(-47) : url.loc;

        process.stdout.write(`${progress} ${shortUrl} `);

        let result;
        let retryCount = 0;
        let delay = config.delay;

        // Retry loop with exponential backoff for 429 errors
        do {
            if (retryCount > 0) {
                process.stdout.write(`⏳ `);
                await sleep(delay);
                delay = Math.min(delay * 2, 5000); // Double delay, max 5 seconds
                stats.retries++;
            }
            result = await fetchUrl(url.loc, true);
            retryCount++;
        } while (result.httpCode === 429 && retryCount < maxRetries);

        if (result.httpCode >= 200 && result.httpCode < 400) {
            stats.success++;
            stats.totalTime += result.totalTime;
            stats.totalSize += result.size;

            const timeStr = Math.round(result.totalTime * 1000) + 'ms';
            const sizeStr = formatBytes(result.size);

            console.log(`✅ ${result.httpCode} (${timeStr}, ${sizeStr})`);
        } else {
            stats.failed++;
            let errorMsg = `❌ ${result.httpCode}`;
            if (result.error) {
                errorMsg += ` - ${result.error}`;
            }
            if (retryCount > 1) {
                errorMsg += ` (after ${retryCount} tries)`;
            }
            console.log(errorMsg);

            // Extra sleep on failure to ease rate limits
            if (result.httpCode === 429) {
                await sleep(2000);
            }
        }

        // Delay between requests
        if (i < totalUrls - 1) {
            await sleep(config.delay);
        }
    }

    stats.elapsed = (Date.now() - stats.startTime) / 1000;
    return stats;
}

// Main execution
async function main() {
    console.log('\n');
    console.log('╔══════════════════════════════════════════════════════════════════╗');
    console.log('║                      CACHE WARMER v1.0                           ║');
    console.log('╚══════════════════════════════════════════════════════════════════╝');
    console.log('');

    console.log(`📍 Sitemap: ${sitemapUrl}`);
    console.log(`⏱️  Delay: ${config.delay}ms | Timeout: ${config.timeout / 1000}s | Passes: ${config.passes}`);
    if (config.dryRun) {
        console.log('🔍 DRY RUN MODE - No requests will be made');
    }
    console.log('');

    // Fetch and parse sitemap
    console.log('📥 Fetching sitemap...');
    const result = await fetchUrl(sitemapUrl);

    if (result.httpCode !== 200) {
        console.log(`❌ Failed to fetch sitemap (HTTP ${result.httpCode})`);
        if (result.error) {
            console.log(`   Error: ${result.error}`);
        }
        process.exit(1);
    }

    const parsed = parseSitemap(result.content);

    // Collect all URLs (handle sitemap index)
    let allUrls = parsed.urls;

    if (parsed.sitemaps.length > 0) {
        console.log(`📚 Found sitemap index with ${parsed.sitemaps.length} sitemaps\n`);

        for (let i = 0; i < parsed.sitemaps.length; i++) {
            const childSitemap = parsed.sitemaps[i];
            const basename = childSitemap.split('/').pop();
            console.log(`  📄 Parsing sitemap ${i + 1}/${parsed.sitemaps.length}: ${basename}`);

            const childResult = await fetchUrl(childSitemap);

            if (childResult.httpCode === 200) {
                const childParsed = parseSitemap(childResult.content);
                allUrls = allUrls.concat(childParsed.urls);
                console.log(`     Found ${childParsed.urls.length} URLs`);
            } else {
                console.log(`     ⚠️  Failed to fetch (HTTP ${childResult.httpCode})`);
            }

            await sleep(config.delay);
        }
        console.log('');
    }

    const totalUrls = allUrls.length;
    console.log(`🔗 Total URLs found: ${totalUrls}`);

    if (totalUrls === 0) {
        console.log('❌ No URLs found in sitemap');
        process.exit(1);
    }

    if (config.dryRun) {
        console.log('\nURLs that would be warmed:');
        console.log('-'.repeat(70));
        for (const url of allUrls) {
            console.log(`  ${url.loc}`);
        }
        console.log('-'.repeat(70));
        console.log(`\n✅ Dry run complete. Found ${totalUrls} URLs.`);
        process.exit(0);
    }

    // Run multiple passes
    const allStats = [];
    const overallStartTime = Date.now();

    for (let pass = 1; pass <= config.passes; pass++) {
        const stats = await runWarmUpPass(allUrls, pass);
        allStats.push(stats);

        if (pass < config.passes) {
            console.log(`\n⏳ Waiting before next pass...`);
            await sleep(1000);
        }
    }

    const overallElapsed = (Date.now() - overallStartTime) / 1000;

    // Print summary
    console.log('\n' + '─'.repeat(70));
    console.log('\n');
    console.log('╔══════════════════════════════════════════════════════════════════╗');
    console.log('║                          SUMMARY                                 ║');
    console.log('╚══════════════════════════════════════════════════════════════════╝');
    console.log('');

    // Per-pass stats
    for (let i = 0; i < allStats.length; i++) {
        const stats = allStats[i];
        const avgTime = stats.success > 0 ? Math.round((stats.totalTime / stats.success) * 1000) : 'N/A';
        console.log(`📊 Pass ${i + 1}: ✅ ${stats.success} | ❌ ${stats.failed} | Avg: ${avgTime}ms | Data: ${formatBytes(stats.totalSize)}`);
    }

    // Overall stats
    const totalSuccess = allStats.reduce((sum, s) => sum + s.success, 0);
    const totalFailed = allStats.reduce((sum, s) => sum + s.failed, 0);
    const totalSize = allStats.reduce((sum, s) => sum + s.totalSize, 0);

    console.log('');
    console.log(`⏱️  Total elapsed time: ${formatDuration(overallElapsed)}`);
    console.log(`📦 Total data transferred: ${formatBytes(totalSize)}`);
    console.log(`📈 Total requests: ${totalSuccess + totalFailed} (${totalSuccess} success, ${totalFailed} failed)`);
    console.log('');

    if (totalFailed === 0) {
        console.log('🎉 Cache warm-up completed successfully!');
    } else {
        console.log(`⚠️  Cache warm-up completed with ${totalFailed} failures.`);
    }

    console.log('');
    process.exit(totalFailed > 0 ? 1 : 0);
}

main().catch(err => {
    console.error('Fatal error:', err);
    process.exit(1);
});
