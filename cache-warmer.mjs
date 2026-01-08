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
    mobileUserAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
    verbose: false,
    dryRun: false,
    passes: 1,            // Number of warm-up passes
    mobile: false,        // Also warm up mobile cache
};

// Parse command line arguments
const args = process.argv.slice(2);
const sitemapUrls = [];

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
    } else if (arg === '--mobile') {
        config.mobile = true;
    } else if (arg.startsWith('--passes=')) {
        config.passes = parseInt(arg.substring(9), 10);
    } else if (!arg.startsWith('--')) {
        // Support comma-separated URLs
        const urls = arg.split(',');
        for (const url of urls) {
            if (url.trim()) sitemapUrls.push(url.trim());
        }
    }
}

// Validate sitemap URL
if (sitemapUrls.length === 0) {
    console.log('Usage: node cache-warmer.mjs <sitemap_url> [sitemap_url_2...] [options]');
    console.log('\nOptions:');
    console.log('  --delay=<ms>       Delay between requests in milliseconds (default: 100)');
    console.log('  --timeout=<sec>    Request timeout in seconds (default: 30)');
    console.log('  --passes=<num>     Number of warm-up passes (default: 1)');
    console.log('  --user-agent=<ua>  Custom user agent string');
    console.log('  --mobile           Also warm up mobile cache (simulates iPhone)');
    console.log('  --verbose          Show detailed output for each request');
    console.log('  --dry-run          Parse sitemap but don\'t make requests');
    process.exit(1);
}

// ... fetchUrl ... (unchanged)

/**
 * Fetch and parse a sitemap (including recursion for index)
 */
async function processSitemap(url) {
    console.log(`📥 Processing sitemap: ${url}`);
    const result = await fetchUrl(url);

    if (result.httpCode !== 200) {
        console.log(`❌ Failed to fetch sitemap (HTTP ${result.httpCode})`);
        if (result.error) {
            console.log(`   Error: ${result.error}`);
        }
        return [];
    }

    const parsed = parseSitemap(result.content);
    let urls = parsed.urls;

    if (parsed.sitemaps.length > 0) {
        console.log(`📚 Found sitemap index with ${parsed.sitemaps.length} child sitemaps`);

        for (let i = 0; i < parsed.sitemaps.length; i++) {
            const childSitemap = parsed.sitemaps[i];
            const basename = childSitemap.split('/').pop();
            console.log(`  📄 Parsing child sitemap ${i + 1}/${parsed.sitemaps.length}: ${basename}`);

            const childResult = await fetchUrl(childSitemap);

            if (childResult.httpCode === 200) {
                const childParsed = parseSitemap(childResult.content);
                urls = urls.concat(childParsed.urls);
                console.log(`     Found ${childParsed.urls.length} URLs`);
            } else {
                console.log(`     ⚠️  Failed to fetch (HTTP ${childResult.httpCode})`);
            }

            await sleep(config.delay);
        }
    }
    
    return urls;
}

// ... (helpers) ...

// Main execution
async function main() {
    console.log('\n');
    console.log('╔══════════════════════════════════════════════════════════════════╗');
    console.log('║                      CACHE WARMER v1.1                           ║');
    console.log('╚══════════════════════════════════════════════════════════════════╝');
    console.log('');

    console.log(`📍 Sitemaps: ${sitemapUrls.length} URL(s)`);
    console.log(`⏱️  Delay: ${config.delay}ms | Timeout: ${config.timeout / 1000}s | Passes: ${config.passes}`);
    if (config.dryRun) {
        console.log('🔍 DRY RUN MODE - No requests will be made');
    }
    console.log('');

    // Collect all URLs
    let allUrls = [];
    
    for (const url of sitemapUrls) {
        const found = await processSitemap(url);
        allUrls = allUrls.concat(found);
        console.log('');
    }

    const totalUrls = allUrls.length;
    console.log(`🔗 Total unique URLs found: ${totalUrls}`);

    if (totalUrls === 0) {
        console.log('❌ No URLs found in any sitemap');
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
