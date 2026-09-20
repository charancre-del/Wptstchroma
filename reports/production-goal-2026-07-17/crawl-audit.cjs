const fs = require('fs');
const path = require('path');

const reportDir = __dirname;
const sitemapPath = path.join(reportDir, 'staging-sitemap.xml');
const sitemap = fs.readFileSync(sitemapPath, 'utf8');
const urls = [...sitemap.matchAll(/<loc>([^<]+)<\/loc>/g)].map((match) => match[1].trim());
const concurrency = 12;

const decode = (value = '') => value
  .replace(/&nbsp;/gi, ' ')
  .replace(/&amp;/gi, '&')
  .replace(/&quot;/gi, '"')
  .replace(/&#039;|&apos;/gi, "'")
  .replace(/&lt;/gi, '<')
  .replace(/&gt;/gi, '>')
  .replace(/&#(\d+);/g, (_, code) => String.fromCharCode(Number(code)))
  .replace(/<[^>]+>/g, ' ')
  .replace(/\s+/g, ' ')
  .trim();

const attr = (tag = '', name) => {
  const match = tag.match(new RegExp(`${name}\\s*=\\s*["']([^"']*)["']`, 'i'));
  return match ? decode(match[1]) : '';
};

const csvEscape = (value) => `"${String(value ?? '').replace(/"/g, '""')}"`;

async function inspect(url) {
  const started = Date.now();
  try {
    const response = await fetch(`${url}${url.includes('?') ? '&' : '?'}codex_crawl=20260717`, {
      redirect: 'follow',
      headers: { 'user-agent': 'ChromaStagingLaunchAudit/1.0' },
      signal: AbortSignal.timeout(30000),
    });
    const contentType = response.headers.get('content-type') || '';
    const html = contentType.includes('text/html') ? await response.text() : '';
    const title = decode((html.match(/<title[^>]*>([\s\S]*?)<\/title>/i) || [])[1] || '');
    const descriptionTag = (html.match(/<meta\s+[^>]*name=["']description["'][^>]*>/i) || [])[0] ||
      (html.match(/<meta\s+[^>]*content=["'][^"']*["'][^>]*name=["']description["'][^>]*>/i) || [])[0] || '';
    const robotsTag = (html.match(/<meta\s+[^>]*name=["']robots["'][^>]*>/i) || [])[0] || '';
    const canonicalTag = (html.match(/<link\s+[^>]*rel=["']canonical["'][^>]*>/i) || [])[0] || '';
    const h1Matches = [...html.matchAll(/<h1\b[^>]*>([\s\S]*?)<\/h1>/gi)].map((m) => decode(m[1]));
    const jsonLdBlocks = [...html.matchAll(/<script\s+[^>]*type=["']application\/ld\+json["'][^>]*>([\s\S]*?)<\/script>/gi)].map((m) => m[1].trim());
    let invalidJsonLd = 0;
    const schemaTypes = [];
    for (const block of jsonLdBlocks) {
      try {
        const parsed = JSON.parse(block);
        const stack = Array.isArray(parsed) ? parsed : [parsed];
        for (const entry of stack) {
          if (entry && entry['@type']) schemaTypes.push(Array.isArray(entry['@type']) ? entry['@type'].join('|') : entry['@type']);
          if (entry && Array.isArray(entry['@graph'])) {
            for (const graphEntry of entry['@graph']) {
              if (graphEntry && graphEntry['@type']) schemaTypes.push(Array.isArray(graphEntry['@type']) ? graphEntry['@type'].join('|') : graphEntry['@type']);
            }
          }
        }
      } catch (_) {
        invalidJsonLd += 1;
      }
    }
    const stripped = decode(html.replace(/<script\b[\s\S]*?<\/script>/gi, ' ').replace(/<style\b[\s\S]*?<\/style>/gi, ' '));
    const suspicious = [];
    const patterns = [
      ['placeholder', /\b(lorem ipsum|placeholder text|coming soon|replace me)\b/i],
      ['developer-todo', /(?:^|\s)TODO(?:\s*:|\s+-)/],
      ['mojibake', /(?:â€™|â€œ|â€|Ã.|ðŸ)/],
      ['unsupported-accreditation', /\b(NAEYC accredited|GAC accredited|all campuses are accredited)\b/i],
      ['unsupported-superlative', /\b(best daycare|#1 daycare|top-rated daycare)\b/i],
    ];
    for (const [label, pattern] of patterns) if (pattern.test(stripped)) suspicious.push(label);

    return {
      url,
      finalUrl: response.url.replace(/[?&]codex_crawl=20260717$/, ''),
      status: response.status,
      contentType,
      durationMs: Date.now() - started,
      title,
      titleLength: title.length,
      metaDescription: attr(descriptionTag, 'content'),
      metaDescriptionLength: attr(descriptionTag, 'content').length,
      robots: attr(robotsTag, 'content') || response.headers.get('x-robots-tag') || '',
      canonical: attr(canonicalTag, 'href'),
      h1Count: h1Matches.length,
      h1: h1Matches.join(' | '),
      wordCount: stripped ? stripped.split(/\s+/).length : 0,
      linkCount: (html.match(/<a\b/gi) || []).length,
      imageCount: (html.match(/<img\b/gi) || []).length,
      jsonLdCount: jsonLdBlocks.length,
      invalidJsonLd,
      schemaTypes: [...new Set(schemaTypes)].join('|'),
      suspicious: suspicious.join('|'),
      error: '',
    };
  } catch (error) {
    return {
      url,
      finalUrl: '',
      status: 0,
      contentType: '',
      durationMs: Date.now() - started,
      title: '',
      titleLength: 0,
      metaDescription: '',
      metaDescriptionLength: 0,
      robots: '',
      canonical: '',
      h1Count: 0,
      h1: '',
      wordCount: 0,
      linkCount: 0,
      imageCount: 0,
      jsonLdCount: 0,
      invalidJsonLd: 0,
      schemaTypes: '',
      suspicious: '',
      error: error.message,
    };
  }
}

async function main() {
  const results = new Array(urls.length);
  let cursor = 0;
  const workers = Array.from({ length: concurrency }, async () => {
    while (true) {
      const index = cursor++;
      if (index >= urls.length) return;
      results[index] = await inspect(urls[index]);
      if ((index + 1) % 50 === 0) process.stdout.write(`Crawled ${index + 1}/${urls.length}\n`);
    }
  });
  await Promise.all(workers);

  const counts = (predicate) => results.filter(predicate).length;
  const summary = {
    generatedAt: new Date().toISOString(),
    auditedUrls: results.length,
    statusCounts: results.reduce((acc, row) => ((acc[row.status] = (acc[row.status] || 0) + 1), acc), {}),
    errors: counts((row) => row.error),
    non200: counts((row) => row.status !== 200),
    missingTitles: counts((row) => row.status === 200 && !row.title),
    duplicateTitles: 0,
    missingDescriptions: counts((row) => row.status === 200 && !row.metaDescription),
    missingH1: counts((row) => row.status === 200 && row.h1Count === 0),
    multipleH1: counts((row) => row.h1Count > 1),
    missingCanonical: counts((row) => row.status === 200 && !row.canonical),
    canonicalMismatch: counts((row) => row.status === 200 && row.canonical && row.canonical.replace(/\/$/, '') !== row.finalUrl.replace(/\/$/, '')),
    noindexInSitemap: counts((row) => /noindex/i.test(row.robots)),
    invalidJsonLdPages: counts((row) => row.invalidJsonLd > 0),
    suspiciousCopyPages: counts((row) => row.suspicious),
    veryThinPages: counts((row) => row.status === 200 && row.wordCount > 0 && row.wordCount < 180),
  };
  const titleCounts = new Map();
  for (const row of results) if (row.title) titleCounts.set(row.title, (titleCounts.get(row.title) || 0) + 1);
  summary.duplicateTitles = [...titleCounts.values()].filter((count) => count > 1).reduce((sum, count) => sum + count, 0);

  fs.writeFileSync(path.join(reportDir, 'crawl-audit.json'), JSON.stringify(results, null, 2));
  fs.writeFileSync(path.join(reportDir, 'crawl-summary.json'), JSON.stringify(summary, null, 2));
  const columns = Object.keys(results[0]);
  const csv = [columns.map(csvEscape).join(','), ...results.map((row) => columns.map((key) => csvEscape(row[key])).join(','))].join('\n');
  fs.writeFileSync(path.join(reportDir, 'crawl-audit.csv'), csv);
  console.log(JSON.stringify(summary, null, 2));
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
