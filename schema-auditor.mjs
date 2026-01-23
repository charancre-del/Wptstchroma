#!/usr/bin/env node
/**
 * Schema Auditor Script
 * 
 * Parses sitemaps and audits JSON-LD schema on each page.
 * Generates a detailed report of schema compliance.
 * 
 * Usage:
 *   node schema-auditor.mjs <sitemap_url> [sitemap_url_2...] [options]
 *   node schema-auditor.mjs https://chromaela.com/sitemap.xml
 *   node schema-auditor.mjs https://chromaela.com/sitemap.xml --output=report.json
 */

import https from 'https';
import http from 'http';
import fs from 'fs';

// Configuration defaults
const config = {
    delay: 200,           // Delay between requests in ms
    timeout: 30000,       // Request timeout in ms
    userAgent: 'SchemaAuditor/1.0 (+https://chromaela.com)',
    verbose: false,
    limit: 0,             // Limit number of URLs to audit (0 = all)
    output: null,         // Output file path
    format: 'json',       // Output format: json, csv, markdown
};

// Schema requirements per page type
const SCHEMA_REQUIREMENTS = {
    homepage: {
        pattern: /^https:\/\/[^\/]+\/?$/,
        required: ['Organization', 'WebSite'],
        recommended: ['LocalBusiness', 'FAQPage'],
        properties: {
            Organization: ['name', 'url', 'logo'],
            WebSite: ['name', 'url', 'potentialAction'],
        }
    },
    location: {
        pattern: /\/locations\/[^\/]+\/?$/,
        required: ['ChildCare', 'LocalBusiness'],
        recommended: ['Preschool', 'EducationalOrganization', 'AggregateRating'],
        properties: {
            ChildCare: ['name', 'address', 'telephone', 'geo'],
            LocalBusiness: ['name', 'address', 'telephone'],
        }
    },
    career: {
        pattern: /\/career\/[^\/]+\/?$/,
        required: ['JobPosting'],
        recommended: [],
        properties: {
            JobPosting: ['title', 'description', 'datePosted', 'hiringOrganization', 'jobLocation'],
        }
    },
    blog: {
        pattern: /\/\d{4}\/\d{2}\/\d{2}\/[^\/]+\/?$/,
        required: ['Article', 'BlogPosting'],
        requireOneOf: true,
        recommended: ['Person'],
        properties: {
            Article: ['headline', 'datePublished', 'author', 'publisher'],
            BlogPosting: ['headline', 'datePublished', 'author', 'publisher'],
        }
    },
    program: {
        pattern: /\/programs\/[^\/]+\/?$/,
        required: ['Service'],
        recommended: ['Course'],
        properties: {
            Service: ['name', 'description', 'provider'],
        }
    },
    combo: {
        pattern: /-in-[a-z-]+-ga\/?$/,
        required: ['Service'],
        recommended: ['FAQPage', 'LocalBusiness'],
        properties: {
            Service: ['name', 'description', 'areaServed'],
        }
    },
    spanish: {
        pattern: /\/es\/|chromaela\.com\/[^\/]+-es\/?$/,
        required: [],
        recommended: [],
        checkLanguage: true
    }
};

// Parse command line arguments
const args = process.argv.slice(2);
const sitemapUrls = [];

for (const arg of args) {
    if (arg.startsWith('--delay=')) {
        config.delay = parseInt(arg.substring(8), 10);
    } else if (arg.startsWith('--timeout=')) {
        config.timeout = parseInt(arg.substring(10), 10) * 1000;
    } else if (arg === '--verbose') {
        config.verbose = true;
    } else if (arg.startsWith('--limit=')) {
        config.limit = parseInt(arg.substring(8), 10);
    } else if (arg.startsWith('--output=')) {
        config.output = arg.substring(9);
    } else if (arg.startsWith('--format=')) {
        config.format = arg.substring(9);
    } else if (!arg.startsWith('--')) {
        const urls = arg.split(',');
        for (const url of urls) {
            if (url.trim()) sitemapUrls.push(url.trim());
        }
    }
}

// Validate sitemap URL
if (sitemapUrls.length === 0) {
    console.log('Usage: node schema-auditor.mjs <sitemap_url> [sitemap_url_2...] [options]');
    console.log('\nOptions:');
    console.log('  --delay=<ms>       Delay between requests in milliseconds (default: 200)');
    console.log('  --timeout=<sec>    Request timeout in seconds (default: 30)');
    console.log('  --limit=<num>      Limit number of URLs to audit (default: all)');
    console.log('  --output=<file>    Output file path for report');
    console.log('  --format=<fmt>     Output format: json, csv, markdown (default: json)');
    console.log('  --verbose          Show detailed output for each request');
    console.log('\nExample:');
    console.log('  node schema-auditor.mjs https://chromaela.com/sitemap.xml --limit=50 --output=audit.json');
    process.exit(1);
}

/**
 * Fetch URL content
 */
function fetchUrl(url) {
    return new Promise((resolve) => {
        const protocol = url.startsWith('https') ? https : http;
        const headers = {
            'User-Agent': config.userAgent,
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        };

        const startTime = Date.now();

        const req = protocol.get(url, { headers, timeout: config.timeout }, (res) => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => {
                resolve({
                    content: data,
                    httpCode: res.statusCode,
                    totalTime: (Date.now() - startTime) / 1000,
                    error: null,
                });
            });
        });

        req.on('error', (err) => {
            resolve({
                content: null,
                httpCode: 0,
                totalTime: (Date.now() - startTime) / 1000,
                error: err.message,
            });
        });

        req.on('timeout', () => {
            req.destroy();
            resolve({
                content: null,
                httpCode: 0,
                totalTime: config.timeout / 1000,
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

    const locRegex = /<loc>([^<]+)<\/loc>/gi;
    let match;
    while ((match = locRegex.exec(xmlContent)) !== null) {
        const loc = match[1].trim().replace(/&amp;/g, '&');
        if (loc.includes('sitemap') && loc.endsWith('.xml')) {
            sitemaps.push(loc);
        } else {
            urls.push(loc);
        }
    }

    return { urls, sitemaps };
}

/**
 * Extract JSON-LD schema from HTML
 */
function extractSchemas(html) {
    const schemas = [];
    const regex = /<script[^>]*type=["']application\/ld\+json["'][^>]*>([\s\S]*?)<\/script>/gi;

    let match;
    while ((match = regex.exec(html)) !== null) {
        try {
            const jsonContent = match[1].trim();
            const parsed = JSON.parse(jsonContent);

            // Handle @graph format
            if (parsed['@graph']) {
                for (const item of parsed['@graph']) {
                    schemas.push(item);
                }
            } else {
                schemas.push(parsed);
            }
        } catch (e) {
            schemas.push({ parseError: e.message, raw: match[1].substring(0, 200) });
        }
    }

    return schemas;
}

/**
 * Get schema types from schema array
 */
function getSchemaTypes(schemas) {
    const types = new Set();

    for (const schema of schemas) {
        if (schema['@type']) {
            const schemaType = schema['@type'];
            if (Array.isArray(schemaType)) {
                schemaType.forEach(t => types.add(t));
            } else {
                types.add(schemaType);
            }
        }
    }

    return Array.from(types);
}

/**
 * Determine page type from URL
 */
function getPageType(url) {
    for (const [type, config] of Object.entries(SCHEMA_REQUIREMENTS)) {
        if (config.pattern.test(url)) {
            return type;
        }
    }
    return 'unknown';
}

/**
 * Validate schema against requirements
 */
function validateSchema(url, schemas, pageType) {
    const result = {
        url,
        pageType,
        schemaTypes: getSchemaTypes(schemas),
        schemaCount: schemas.length,
        status: 'pass',
        issues: [],
        warnings: [],
        details: {},
        rawSchemas: schemas  // Store full raw schema for debugging
    };

    const requirements = SCHEMA_REQUIREMENTS[pageType];

    if (!requirements) {
        result.status = 'unknown';
        result.warnings.push('Unknown page type - no validation rules defined');
        return result;
    }

    // Check required schema types
    const foundTypes = result.schemaTypes;

    if (requirements.requireOneOf) {
        // At least one of the required types must be present
        const hasOne = requirements.required.some(t => foundTypes.includes(t));
        if (!hasOne) {
            result.status = 'fail';
            result.issues.push(`Missing required schema: one of [${requirements.required.join(', ')}]`);
        }
    } else {
        for (const required of requirements.required) {
            if (!foundTypes.includes(required)) {
                result.status = 'fail';
                result.issues.push(`Missing required schema: ${required}`);
            }
        }
    }

    // Check recommended schema types
    for (const recommended of requirements.recommended || []) {
        if (!foundTypes.includes(recommended)) {
            result.warnings.push(`Missing recommended schema: ${recommended}`);
        }
    }

    // Check required properties for each schema type
    if (requirements.properties) {
        for (const schema of schemas) {
            const type = Array.isArray(schema['@type']) ? schema['@type'][0] : schema['@type'];
            const requiredProps = requirements.properties[type];

            if (requiredProps) {
                const missingProps = [];
                for (const prop of requiredProps) {
                    if (!schema[prop] && !hasNestedProperty(schema, prop)) {
                        missingProps.push(prop);
                    }
                }

                if (missingProps.length > 0) {
                    result.warnings.push(`${type}: missing properties [${missingProps.join(', ')}]`);
                }

                // Store schema details
                result.details[type] = {
                    hasAllRequired: missingProps.length === 0,
                    missingProperties: missingProps,
                    presentProperties: Object.keys(schema).filter(k => !k.startsWith('@'))
                };
            }
        }
    }

    // Check for parse errors
    for (const schema of schemas) {
        if (schema.parseError) {
            result.status = 'fail';
            result.issues.push(`JSON-LD parse error: ${schema.parseError}`);
        }
    }

    // Upgrade status to warning if only warnings exist
    if (result.status === 'pass' && result.warnings.length > 0) {
        result.status = 'warning';
    }

    return result;
}

/**
 * Check for nested property (e.g., 'geo' could be schema.geo or embedded)
 */
function hasNestedProperty(obj, prop) {
    if (obj[prop]) return true;

    // Check common nested structures
    const nestedKeys = ['address', 'geo', 'location', 'author', 'publisher'];
    for (const key of nestedKeys) {
        if (obj[key] && typeof obj[key] === 'object' && obj[key][prop]) {
            return true;
        }
    }

    return false;
}

/**
 * Sleep for a given number of milliseconds
 */
function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

/**
 * Fetch and parse a sitemap
 */
async function processSitemap(url) {
    console.log(`📥 Processing sitemap: ${url}`);
    const result = await fetchUrl(url);

    if (result.httpCode !== 200) {
        console.log(`❌ Failed to fetch sitemap (HTTP ${result.httpCode})`);
        return [];
    }

    const parsed = parseSitemap(result.content);
    let urls = parsed.urls;

    if (parsed.sitemaps.length > 0) {
        console.log(`📚 Found sitemap index with ${parsed.sitemaps.length} child sitemaps`);

        for (const childSitemap of parsed.sitemaps) {
            const childResult = await fetchUrl(childSitemap);
            if (childResult.httpCode === 200) {
                const childParsed = parseSitemap(childResult.content);
                urls = urls.concat(childParsed.urls);
            }
            await sleep(config.delay);
        }
    }

    return urls;
}

/**
 * Generate markdown report
 */
function generateMarkdownReport(results, stats) {
    let md = `# Schema Audit Report\n\n`;
    md += `**Generated:** ${new Date().toISOString()}\n\n`;
    md += `## Summary\n\n`;
    md += `| Metric | Count |\n|--------|-------|\n`;
    md += `| Total Pages | ${stats.total} |\n`;
    md += `| ✅ Pass | ${stats.pass} |\n`;
    md += `| ⚠️ Warning | ${stats.warning} |\n`;
    md += `| ❌ Fail | ${stats.fail} |\n`;
    md += `| ❓ Unknown | ${stats.unknown} |\n\n`;

    // Group by status
    const failed = results.filter(r => r.status === 'fail');
    const warnings = results.filter(r => r.status === 'warning');

    if (failed.length > 0) {
        md += `## ❌ Failed (${failed.length})\n\n`;
        for (const r of failed) {
            md += `### ${r.url}\n`;
            md += `- **Page Type:** ${r.pageType}\n`;
            md += `- **Found Types:** ${r.schemaTypes.join(', ') || 'None'}\n`;
            md += `- **Issues:**\n`;
            for (const issue of r.issues) {
                md += `  - ${issue}\n`;
            }
            md += `\n`;
        }
    }

    if (warnings.length > 0) {
        md += `## ⚠️ Warnings (${warnings.length})\n\n`;
        for (const r of warnings) {
            md += `### ${r.url}\n`;
            md += `- **Page Type:** ${r.pageType}\n`;
            md += `- **Warnings:**\n`;
            for (const w of r.warnings) {
                md += `  - ${w}\n`;
            }
            md += `\n`;
        }
    }

    return md;
}

// Main execution
async function main() {
    console.log('\n');
    console.log('╔══════════════════════════════════════════════════════════════════╗');
    console.log('║                     SCHEMA AUDITOR v1.0                          ║');
    console.log('╚══════════════════════════════════════════════════════════════════╝');
    console.log('');

    // Collect all URLs from sitemaps
    let allUrls = [];
    for (const url of sitemapUrls) {
        const found = await processSitemap(url);
        allUrls = allUrls.concat(found);
    }

    // Remove duplicates
    allUrls = [...new Set(allUrls)];

    console.log(`\n🔗 Total unique URLs: ${allUrls.length}`);

    if (config.limit > 0) {
        allUrls = allUrls.slice(0, config.limit);
        console.log(`📊 Limited to: ${allUrls.length} URLs`);
    }

    // Audit each URL
    console.log('\n🔍 Starting schema audit...\n');
    console.log('─'.repeat(70));

    const results = [];
    const stats = { total: 0, pass: 0, warning: 0, fail: 0, unknown: 0 };

    for (let i = 0; i < allUrls.length; i++) {
        const url = allUrls[i];
        const progress = `[${i + 1}/${allUrls.length}]`.padEnd(12);
        const shortUrl = url.length > 55 ? '...' + url.slice(-52) : url;

        process.stdout.write(`${progress} ${shortUrl} `);

        const response = await fetchUrl(url);

        if (response.httpCode !== 200) {
            console.log(`❌ HTTP ${response.httpCode}`);
            results.push({
                url,
                pageType: getPageType(url),
                status: 'fail',
                issues: [`HTTP error: ${response.httpCode}`],
                warnings: [],
                schemaTypes: [],
                schemaCount: 0
            });
            stats.fail++;
            stats.total++;
            await sleep(config.delay);
            continue;
        }

        const schemas = extractSchemas(response.content);
        const pageType = getPageType(url);
        const validation = validateSchema(url, schemas, pageType);

        results.push(validation);
        stats.total++;
        stats[validation.status]++;

        // Output result
        const icon = validation.status === 'pass' ? '✅' :
            validation.status === 'warning' ? '⚠️' :
                validation.status === 'fail' ? '❌' : '❓';
        const types = validation.schemaTypes.join(', ') || 'None';
        console.log(`${icon} [${pageType}] ${types}`);

        if (config.verbose && validation.issues.length > 0) {
            for (const issue of validation.issues) {
                console.log(`      └─ ❌ ${issue}`);
            }
        }

        await sleep(config.delay);
    }

    // Summary
    console.log('\n' + '─'.repeat(70));
    console.log('\n');
    console.log('╔══════════════════════════════════════════════════════════════════╗');
    console.log('║                          SUMMARY                                 ║');
    console.log('╚══════════════════════════════════════════════════════════════════╝');
    console.log('');
    console.log(`📊 Total Pages Audited: ${stats.total}`);
    console.log(`   ✅ Pass:    ${stats.pass} (${Math.round(stats.pass / stats.total * 100)}%)`);
    console.log(`   ⚠️  Warning: ${stats.warning} (${Math.round(stats.warning / stats.total * 100)}%)`);
    console.log(`   ❌ Fail:    ${stats.fail} (${Math.round(stats.fail / stats.total * 100)}%)`);
    console.log(`   ❓ Unknown: ${stats.unknown}`);
    console.log('');

    // Output to file if specified
    if (config.output) {
        let outputContent;

        if (config.format === 'markdown') {
            outputContent = generateMarkdownReport(results, stats);
        } else {
            // Create a summary version without raw schemas for main report
            const summaryResults = results.map(r => ({
                url: r.url,
                pageType: r.pageType,
                schemaTypes: r.schemaTypes,
                schemaCount: r.schemaCount,
                status: r.status,
                issues: r.issues,
                warnings: r.warnings
            }));
            outputContent = JSON.stringify({ stats, results: summaryResults }, null, 2);
        }

        fs.writeFileSync(config.output, outputContent);
        console.log(`📄 Report saved to: ${config.output}`);

        // Also save full raw schemas by page for debugging
        const schemasByPage = {};
        for (const r of results) {
            schemasByPage[r.url] = {
                pageType: r.pageType,
                schemaCount: r.schemaCount,
                schemaTypes: r.schemaTypes,
                schemas: r.rawSchemas || []
            };
        }

        const schemasFile = config.output.replace(/\.[^.]+$/, '-schemas-by-page.json');
        fs.writeFileSync(schemasFile, JSON.stringify(schemasByPage, null, 2));
        console.log(`📄 Raw schemas saved to: ${schemasFile}`);

        // Generate a bug analysis report (FAQ everywhere, excessive schemas, etc.)
        const bugAnalysis = {
            generated: new Date().toISOString(),
            issues: {
                faqOnAllPages: [],
                excessiveSchemas: [],
                duplicateTypes: [],
                missingRequired: []
            }
        };

        for (const r of results) {
            // Check for FAQ on non-homepage/combo pages
            if (r.schemaTypes.includes('FAQPage') && !['homepage', 'combo'].includes(r.pageType)) {
                bugAnalysis.issues.faqOnAllPages.push(r.url);
            }

            // Check for excessive schemas (more than 8)
            if (r.schemaCount > 8) {
                bugAnalysis.issues.excessiveSchemas.push({
                    url: r.url,
                    count: r.schemaCount,
                    types: r.schemaTypes
                });
            }

            // Check for duplicate schema types
            const typeCounts = {};
            for (const schema of (r.rawSchemas || [])) {
                const type = Array.isArray(schema['@type']) ? schema['@type'].join(',') : schema['@type'];
                if (type) {
                    typeCounts[type] = (typeCounts[type] || 0) + 1;
                }
            }
            const duplicates = Object.entries(typeCounts).filter(([_, count]) => count > 1);
            if (duplicates.length > 0) {
                bugAnalysis.issues.duplicateTypes.push({
                    url: r.url,
                    duplicates: Object.fromEntries(duplicates)
                });
            }

            // Collect failures
            if (r.status === 'fail') {
                bugAnalysis.issues.missingRequired.push({
                    url: r.url,
                    pageType: r.pageType,
                    issues: r.issues
                });
            }
        }

        const bugFile = config.output.replace(/\.[^.]+$/, '-bug-analysis.json');
        fs.writeFileSync(bugFile, JSON.stringify(bugAnalysis, null, 2));
        console.log(`📄 Bug analysis saved to: ${bugFile}`);
    }

    // Exit with error code if failures
    process.exit(stats.fail > 0 ? 1 : 0);
}

main().catch(err => {
    console.error('Fatal error:', err);
    process.exit(1);
});
