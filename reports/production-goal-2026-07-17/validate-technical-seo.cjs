const fs = require('fs');
const path = require('path');

const reportDir = __dirname;
const inventory = JSON.parse(fs.readFileSync(path.join(reportDir, 'content-inventory.json'), 'utf8'));
const baseOrigin = new URL(inventory[0]?.url || 'https://x3yyntt5tp-staging.wpdns.site/').origin;
const concurrency = 6;

const unique = (values) => [...new Set(values.filter(Boolean))];
const sleep = (milliseconds) => new Promise((resolve) => setTimeout(resolve, milliseconds));
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
const normalize = (value) => {
  try {
    const url = new URL(value, baseOrigin);
    url.hash = '';
    for (const key of [...url.searchParams.keys()]) {
      if (/^(codex_|utm_|gclid|fbclid)/i.test(key)) url.searchParams.delete(key);
    }
    const search = url.searchParams.toString();
    url.search = search ? `?${search}` : '';
    if (url.pathname !== '/') {
      const pathname = url.pathname.replace(/\/+$/, '');
      const isFile = /\/[^/]+\.[a-z0-9]{2,8}$/i.test(pathname);
      const isInternal = url.origin === baseOrigin;
      url.pathname = isFile || !isInternal ? pathname : `${pathname}/`;
    }
    return url.href;
  } catch (_) {
    return '';
  }
};
const splitLinks = (value = '') => value.split(' | ').map((item) => normalize(item)).filter(Boolean);
const csvEscape = (value) => `"${String(value ?? '').replace(/"/g, '""')}"`;

function extractInternalPageLinks(html, pageUrl) {
  const links = [];
  for (const match of html.matchAll(/<a\b[^>]*href=["']([^"']+)["'][^>]*>/gi)) {
    const target = normalize(new URL(match[1], pageUrl).href);
    if (target && new URL(target).origin === baseOrigin) links.push(target);
  }
  return unique(links);
}

function isStoriesPaginationUrl(value) {
  try {
    const url = new URL(value);
    const pathName = url.pathname.replace(/^\/es(?=\/|$)/, '');
    return /^\/stories\/?$/.test(pathName) && Number(url.searchParams.get('paged')) > 1;
  } catch (_) {
    return false;
  }
}

function metaTag(html, key, value) {
  const byKey = new RegExp(`<meta\\s+[^>]*${key}=["']${value}["'][^>]*>`, 'i');
  const reverse = new RegExp(`<meta\\s+[^>]*content=["'][^"']*["'][^>]*${key}=["']${value}["'][^>]*>`, 'i');
  return (html.match(byKey) || html.match(reverse) || [])[0] || '';
}

function schemaTypes(html) {
  const types = [];
  let invalid = 0;
  for (const match of html.matchAll(/<script\b[^>]*type=["']application\/ld\+json["'][^>]*>([\s\S]*?)<\/script>/gi)) {
    try {
      const parsed = JSON.parse(match[1].trim());
      const stack = [parsed];
      while (stack.length) {
        const node = stack.pop();
        if (Array.isArray(node)) {
          stack.push(...node);
          continue;
        }
        if (!node || typeof node !== 'object') continue;
        const type = node['@type'];
        if (Array.isArray(type)) types.push(...type.map(String));
        else if (type) types.push(String(type));
        if (Array.isArray(node['@graph'])) stack.push(...node['@graph']);
      }
    } catch (_) {
      invalid += 1;
    }
  }
  return { types: unique(types).sort(), invalid };
}

async function fetchChain(url, options = {}) {
  const chain = [];
  let current = url;
  const method = options.method || 'GET';
  for (let hop = 0; hop < 8; hop += 1) {
    try {
      const response = await fetch(current, {
        method,
        redirect: 'manual',
        headers: { 'user-agent': 'ChromaTechnicalSEOAudit/1.0' },
        signal: AbortSignal.timeout(options.timeout || 25000),
      });
      chain.push({ url: current, status: response.status, location: response.headers.get('location') || '' });
      if (response.status >= 300 && response.status < 400 && response.headers.get('location')) {
        current = new URL(response.headers.get('location'), current).href;
        continue;
      }
      const contentType = response.headers.get('content-type') || '';
      const readableBody = /(?:text\/(?:html|plain|xml)|application\/xml)/i.test(contentType);
      const html = method === 'GET' && readableBody ? await response.text() : '';
      return { response, html, chain, finalUrl: current, error: '' };
    } catch (error) {
      return { response: null, html: '', chain, finalUrl: current, error: error.message };
    }
  }
  return { response: null, html: '', chain, finalUrl: current, error: 'redirect-limit-exceeded' };
}

async function inspectPage(item) {
  const result = await fetchChain(`${item.url}${item.url.includes('?') ? '&' : '?'}codex_technical=20260718`);
  const html = result.html;
  const response = result.response;
  const title = decode((html.match(/<title[^>]*>([\s\S]*?)<\/title>/i) || [])[1] || '');
  const description = attr(metaTag(html, 'name', 'description'), 'content');
  const robots = attr(metaTag(html, 'name', 'robots'), 'content') || response?.headers.get('x-robots-tag') || '';
  const canonical = attr((html.match(/<link\s+[^>]*rel=["']canonical["'][^>]*>/i) || [])[0] || '', 'href');
  const h1Count = [...html.matchAll(/<h1\b[^>]*>/gi)].length;
  const ogTitle = attr(metaTag(html, 'property', 'og:title'), 'content');
  const ogDescription = attr(metaTag(html, 'property', 'og:description'), 'content');
  const ogImage = attr(metaTag(html, 'property', 'og:image'), 'content');
  const twitterCard = attr(metaTag(html, 'name', 'twitter:card'), 'content');
  const twitterTitle = attr(metaTag(html, 'name', 'twitter:title'), 'content');
  const twitterDescription = attr(metaTag(html, 'name', 'twitter:description'), 'content');
  const favicon = /<link\s+[^>]*rel=["'][^"']*(?:icon|shortcut icon)[^"']*["'][^>]*>/i.test(html);
  const schema = schemaTypes(html);
  const issues = [];
  if (!response || response.status >= 400) issues.push('http-error');
  if (result.chain.length > 1) issues.push('redirect-in-sitemap');
  if (result.chain.length > 2) issues.push('redirect-chain');
  if (!title) issues.push('missing-title');
  if (!description) issues.push('missing-meta-description');
  if (!canonical) issues.push('missing-canonical');
  if (h1Count !== 1) issues.push(`h1-count-${h1Count}`);
  if (!ogTitle || !ogDescription || !ogImage) issues.push('incomplete-open-graph');
  if (!twitterCard || !twitterTitle || !twitterDescription) issues.push('incomplete-twitter-card');
  if (!favicon) issues.push('missing-favicon');
  if (schema.invalid) issues.push('invalid-json-ld');
  if (!schema.types.length) issues.push('missing-json-ld');
  if (/noindex/i.test(robots)) issues.push('noindex-in-sitemap');
  return {
    url: item.url,
    pageType: item.pageType,
    status: response?.status || 0,
    finalUrl: result.finalUrl.replace(/[?&]codex_technical=20260718$/, ''),
    redirectHops: Math.max(0, result.chain.length - 1),
    redirectChain: result.chain.map((hop) => `${hop.status} ${hop.url}`).join(' -> '),
    title,
    description,
    robots,
    canonical,
    h1Count,
    ogTitle: Boolean(ogTitle),
    ogDescription: Boolean(ogDescription),
    ogImage: Boolean(ogImage),
    twitterCard: Boolean(twitterCard),
    twitterTitle: Boolean(twitterTitle),
    twitterDescription: Boolean(twitterDescription),
    favicon,
    schemaTypes: schema.types.join(' | '),
    invalidSchemaBlocks: schema.invalid,
    issues: issues.join(' | '),
    error: result.error,
  };
}

async function mapConcurrent(items, worker, limit = concurrency) {
  const output = new Array(items.length);
  let cursor = 0;
  await Promise.all(Array.from({ length: limit }, async () => {
    while (cursor < items.length) {
      const index = cursor;
      cursor += 1;
      output[index] = await worker(items[index], index);
    }
  }));
  return output;
}

function isGoogleMapsAppUrl(url) {
  try {
    const parsed = new URL(url);
    return parsed.hostname === 'www.google.com'
      && (parsed.pathname.startsWith('/maps/dir') || parsed.pathname.startsWith('/maps/search'))
      && parsed.searchParams.get('api') === '1';
  } catch {
    return false;
  }
}

function classifyTarget(url, status) {
  const rateLimited = status === 429;
  const providerValidated = isGoogleMapsAppUrl(url);
  return {
    rateLimited,
    providerValidated,
    broken: !providerValidated && !rateLimited && (!status || status >= 400),
  };
}

async function validateTarget(url, external = false) {
  let result = await fetchChain(url, { method: 'HEAD', timeout: external ? 15000 : 20000 });
  let status = result.response?.status || 0;
  if (!status || status === 405 || status === 403 || (external && status >= 400)) {
    result = await fetchChain(url, { method: 'GET', timeout: external ? 15000 : 20000 });
    status = result.response?.status || 0;
  }
  for (let attempt = 0; !status && attempt < 2; attempt += 1) {
    await sleep(1000 * (attempt + 1));
    result = await fetchChain(url, { method: 'GET', timeout: external ? 15000 : 20000 });
    status = result.response?.status || 0;
  }
  for (let attempt = 0; status === 429 && attempt < 4; attempt += 1) {
    await sleep(1500 * (attempt + 1));
    result = await fetchChain(url, { method: 'GET', timeout: external ? 15000 : 20000 });
    status = result.response?.status || 0;
  }
  return {
    url,
    status,
    finalUrl: result.finalUrl,
    redirectHops: Math.max(0, result.chain.length - 1),
    ...classifyTarget(url, status),
    error: result.error,
  };
}

async function graphDepth() {
  const sitemapSet = new Set(inventory.map((item) => normalize(item.url)));
  const adjacency = new Map();
  const paginationQueue = [];
  const paginationSeen = new Set();
  for (const item of inventory) {
    const from = normalize(item.url);
    const links = splitLinks(item.internalLinks).filter((link) => sitemapSet.has(link) || isStoriesPaginationUrl(link));
    adjacency.set(from, unique(links));
    for (const link of links) {
      if (isStoriesPaginationUrl(link) && !paginationSeen.has(link)) {
        paginationSeen.add(link);
        paginationQueue.push(link);
      }
    }
  }

  while (paginationQueue.length) {
    const pageUrl = paginationQueue.shift();
    const result = await fetchChain(pageUrl);
    const links = extractInternalPageLinks(result.html, pageUrl)
      .filter((link) => sitemapSet.has(link) || isStoriesPaginationUrl(link));
    adjacency.set(pageUrl, unique(links));
    for (const link of links) {
      if (isStoriesPaginationUrl(link) && !paginationSeen.has(link)) {
        paginationSeen.add(link);
        paginationQueue.push(link);
      }
    }
  }
  const roots = inventory
    .filter((item) => item.pageType === 'homepage')
    .map((item) => normalize(item.url));
  const depths = new Map(roots.map((root) => [root, 0]));
  const queue = [...roots];
  while (queue.length) {
    const current = queue.shift();
    const nextDepth = (depths.get(current) || 0) + 1;
    for (const target of adjacency.get(current) || []) {
      if (!depths.has(target) || depths.get(target) > nextDepth) {
        depths.set(target, nextDepth);
        queue.push(target);
      }
    }
  }
  return inventory.map((item) => ({
    url: item.url,
    pageType: item.pageType,
    depth: depths.has(normalize(item.url)) ? depths.get(normalize(item.url)) : null,
    orphan: !depths.has(normalize(item.url)),
  }));
}

function writeCsv(filename, rows) {
  if (!rows.length) {
    fs.writeFileSync(path.join(reportDir, filename), '');
    return;
  }
  const columns = Object.keys(rows[0]);
  const csv = [columns.map(csvEscape).join(',')]
    .concat(rows.map((row) => columns.map((column) => csvEscape(row[column])).join(',')))
    .join('\n');
  fs.writeFileSync(path.join(reportDir, filename), csv);
}

async function main() {
  const robotsResult = await fetchChain(`${baseOrigin}/robots.txt`);
  const robotsText = robotsResult.html || (robotsResult.response ? await robotsResult.response.text() : '');
  const pageResults = await mapConcurrent(inventory, inspectPage);
  const internalTargets = unique(inventory.flatMap((item) => splitLinks(item.internalLinks)))
    .filter((url) => new URL(url).origin === baseOrigin);
  const externalTargets = unique(inventory.flatMap((item) => splitLinks(item.externalLinks)))
    .filter((url) => new URL(url).origin !== baseOrigin);
  const pageStatus = new Map(pageResults.map((page) => [normalize(page.url), page]));
  const internalValidation = await mapConcurrent(internalTargets, async (url) => {
    const known = pageStatus.get(normalize(url));
    return known
      ? { url, status: known.status, finalUrl: known.finalUrl, redirectHops: known.redirectHops, ...classifyTarget(url, known.status), error: known.error }
      : validateTarget(url, false);
  }, 24);
  const externalValidation = await mapConcurrent(externalTargets, (url) => validateTarget(url, true), 8);
  const depthResults = await graphDepth();
  const issueCounts = {};
  for (const page of pageResults) {
    for (const issue of page.issues.split(' | ').filter(Boolean)) issueCounts[issue] = (issueCounts[issue] || 0) + 1;
  }
  const schemaCounts = {};
  for (const page of pageResults) {
    for (const type of page.schemaTypes.split(' | ').filter(Boolean)) schemaCounts[type] = (schemaCounts[type] || 0) + 1;
  }
  const summary = {
    generatedAt: new Date().toISOString(),
    target: baseOrigin,
    auditedPages: pageResults.length,
    robotsStatus: robotsResult.response?.status || 0,
    robotsHasSitemap: /\bsitemap\s*:/i.test(robotsText),
    pagesWithIssues: pageResults.filter((page) => page.issues).length,
    redirectingSitemapUrls: pageResults.filter((page) => page.redirectHops > 0).length,
    redirectChains: pageResults.filter((page) => page.redirectHops > 1).length,
    noindexInSitemap: pageResults.filter((page) => /noindex/i.test(page.robots)).length,
    invalidSchemaPages: pageResults.filter((page) => page.invalidSchemaBlocks > 0).length,
    internalTargets: internalValidation.length,
    brokenInternalTargets: internalValidation.filter((item) => item.broken).length,
    rateLimitedInternalTargets: internalValidation.filter((item) => item.rateLimited).length,
    externalTargets: externalValidation.length,
    brokenExternalTargets: externalValidation.filter((item) => item.broken).length,
    providerValidatedExternalTargets: externalValidation.filter((item) => item.providerValidated).length,
    rateLimitedExternalTargets: externalValidation.filter((item) => item.rateLimited).length,
    orphanUrls: depthResults.filter((item) => item.orphan).length,
    depthOverThree: depthResults.filter((item) => Number.isInteger(item.depth) && item.depth > 3).length,
    issueCounts,
    schemaCounts,
  };
  fs.writeFileSync(path.join(reportDir, 'technical-seo-audit.json'), JSON.stringify({ summary, robotsText, pages: pageResults, internalLinks: internalValidation, externalLinks: externalValidation, depth: depthResults }, null, 2));
  fs.writeFileSync(path.join(reportDir, 'technical-seo-summary.json'), JSON.stringify(summary, null, 2));
  writeCsv('technical-seo-pages.csv', pageResults);
  writeCsv('technical-seo-links.csv', [...internalValidation.map((item) => ({ ...item, scope: 'internal' })), ...externalValidation.map((item) => ({ ...item, scope: 'external' }))]);
  writeCsv('technical-seo-depth.csv', depthResults);
  console.log(JSON.stringify(summary, null, 2));
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
