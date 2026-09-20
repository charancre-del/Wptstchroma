const fs = require('fs');
const path = require('path');
const crypto = require('crypto');

const reportDir = __dirname;
const sitemapPath = path.join(reportDir, 'staging-sitemap.xml');
const stagingOrigin = 'https://x3yyntt5tp-staging.wpdns.site';
let sitemapUrls = [];
let baseOrigin = stagingOrigin;
const concurrency = 10;

async function loadCurrentSitemap() {
  let sitemap = '';

  try {
    const response = await fetch(`${stagingOrigin}/sitemap.xml?codex_inventory_scope=20260718`, {
      headers: { 'user-agent': 'ChromaFullPromptInventory/1.0' },
      signal: AbortSignal.timeout(30000),
    });

    if (!response.ok) throw new Error(`Sitemap returned HTTP ${response.status}`);
    sitemap = await response.text();
    fs.writeFileSync(sitemapPath, sitemap);
  } catch (error) {
    if (!fs.existsSync(sitemapPath)) throw error;
    process.stderr.write(`Live sitemap unavailable; using saved fallback: ${error.message}\n`);
    sitemap = fs.readFileSync(sitemapPath, 'utf8');
  }

  sitemapUrls = [...sitemap.matchAll(/<loc>([^<]+)<\/loc>/g)].map((match) => match[1].trim());
  sitemapUrls = [...new Set(sitemapUrls)];
  baseOrigin = new URL(sitemapUrls[0] || stagingOrigin).origin;

  if (!sitemapUrls.length) throw new Error('The staging sitemap did not contain any URLs.');
}

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

const unique = (values) => [...new Set(values.filter(Boolean))];

const indexKey = (value) => {
  try {
    const url = new URL(value);
    url.hash = '';
    url.search = '';
    url.pathname = url.pathname.replace(/\/$/, '') || '/';
    return url.href.replace(/\/$/, '');
  } catch (_) {
    return String(value || '').replace(/[?#].*$/, '').replace(/\/$/, '');
  }
};

const isHtmlPageTarget = (value) => {
  try {
    const url = new URL(value);
    return !/^\/wp-content\//i.test(url.pathname) &&
      !/\.(?:pdf|jpe?g|png|gif|webp|svg|avif|mp4|webm|zip|docx?|xlsx?)$/i.test(url.pathname);
  } catch (_) {
    return false;
  }
};

const inferPageType = (pathname) => {
  const pathName = pathname.replace(/^\/es(?=\/|$)/, '') || '/';
  if (pathName === '/') return 'homepage';
  if (/^\/about\/?$/.test(pathName)) return 'about';
  if (/^\/curriculum\/?$/.test(pathName)) return 'curriculum';
  if (/^\/programs\/?$/.test(pathName)) return 'program-archive';
  if (/^\/programs\/[^/]+\/?$/.test(pathName)) return 'program-single';
  if (/^\/locations\/?$/.test(pathName)) return 'location-directory';
  if (/^\/locations\/[^/]+\/?$/.test(pathName)) return 'location-single';
  if (/^\/communities\/?$/.test(pathName)) return 'community-archive';
  if (/^\/(childcare|city)\//.test(pathName)) return 'community-single';
  if (/^\/parents\/?$/.test(pathName)) return 'parents';
  if (/^\/careers\/?$/.test(pathName)) return 'careers';
  if (/^\/contact(?:-us)?\/?$/.test(pathName)) return 'contact';
  if (/^\/schedule-a-tour\/?$/.test(pathName)) return 'schedule-tour';
  if (/^\/chroma-early-start\/?$/.test(pathName)) return 'early-start';
  if (/^\/summer-camp/.test(pathName)) return 'summer-camp';
  if (/^\/(privacy-policy|terms-of-service|hipaa)\/?$/.test(pathName)) return 'legal';
  if (/^\/(stories|blog|newsroom)\/?$/.test(pathName)) return 'editorial-archive';
  if (/^\/\d{4}\/\d{2}\/\d{2}\//.test(pathName)) return 'editorial-single';
  if (/thank|confirmation|success/.test(pathName)) return 'thank-you';
  return 'general-page';
};

const pageProfile = (pageType) => {
  const profiles = {
    homepage: ['Introduce Chroma and route families to programs, campuses, and tours', 'Prospective families', 'Schedule a tour'],
    about: ['Explain Chroma history, standards, and team', 'Prospective families and partners', 'Explore programs or schedule a tour'],
    curriculum: ['Explain PrismPath curriculum and developmental continuum', 'Prospective and current families', 'Explore programs or schedule a tour'],
    'program-archive': ['Help families compare age-based programs', 'Prospective families', 'View a program or schedule a tour'],
    'program-single': ['Explain one program and connect families to participating campuses', 'Families with children in the relevant age range', 'Schedule a tour'],
    'location-directory': ['Help families find and compare campuses', 'Local prospective families', 'View a campus or schedule a tour'],
    'location-single': ['Convert local families for one campus', 'Families near the campus', 'Schedule a campus tour'],
    'community-archive': ['Route local searches to real nearby campuses', 'Families researching childcare by community', 'Find a nearby campus'],
    'community-single': ['Explain proximity to verified Chroma campuses', 'Families in the named community', 'View a nearby campus'],
    parents: ['Provide resources and support for enrolled families', 'Current and prospective families', 'Open a resource or contact Chroma'],
    careers: ['Explain employment opportunities and accept applications', 'Job candidates', 'View or apply for a role'],
    contact: ['Accept general inquiries and provide corporate contacts', 'Families, partners, media, and community members', 'Submit an inquiry'],
    'schedule-tour': ['Collect tour preferences and route an enrollment lead', 'Prospective families', 'Submit a tour request'],
    'early-start': ['Explain Chroma Early Start services', 'Families seeking pediatric support', 'Contact or request services'],
    'summer-camp': ['Explain seasonal camp and participating campuses', 'Families with school-age children', 'Find camp details or schedule a tour'],
    legal: ['Publish legal, privacy, consent, or service terms', 'All visitors', 'Review policy or contact Chroma'],
    'editorial-archive': ['Help families discover educational resources and company news', 'Families and community members', 'Read an article'],
    'editorial-single': ['Answer a family question or share company news', 'Families and community members', 'Explore related resources or a campus'],
    'thank-you': ['Confirm a successful form submission', 'Recent form submitters', 'Continue to a relevant next step'],
    'general-page': ['Support a Chroma informational or conversion journey', 'Families and community members', 'Use the primary page CTA'],
  };
  const [purpose, audience, conversion] = profiles[pageType] || profiles['general-page'];
  return { purpose, audience, conversion };
};

const normalizeLink = (href, pageUrl) => {
  if (!href || /^(#|javascript:|mailto:|tel:|sms:)/i.test(href)) return null;
  try {
    const url = new URL(href, pageUrl);
    url.hash = '';
    return url;
  } catch (_) {
    return null;
  }
};

async function inspect(url) {
  const started = Date.now();
  try {
    const response = await fetch(`${url}${url.includes('?') ? '&' : '?'}codex_inventory=20260718`, {
      redirect: 'follow',
      headers: { 'user-agent': 'ChromaFullPromptInventory/1.0' },
      signal: AbortSignal.timeout(30000),
    });
    const contentType = response.headers.get('content-type') || '';
    const html = contentType.includes('text/html') ? await response.text() : '';
    const finalUrl = response.url.replace(/[?&]codex_inventory=20260718$/, '');
    const parsedUrl = new URL(finalUrl || url);
    const pageType = inferPageType(parsedUrl.pathname);
    const profile = pageProfile(pageType);
    const title = decode((html.match(/<title[^>]*>([\s\S]*?)<\/title>/i) || [])[1] || '');
    const descriptionTag = (html.match(/<meta\s+[^>]*name=["']description["'][^>]*>/i) || [])[0] ||
      (html.match(/<meta\s+[^>]*content=["'][^"']*["'][^>]*name=["']description["'][^>]*>/i) || [])[0] || '';
    const robotsTag = (html.match(/<meta\s+[^>]*name=["']robots["'][^>]*>/i) || [])[0] || '';
    const canonicalTag = (html.match(/<link\s+[^>]*rel=["']canonical["'][^>]*>/i) || [])[0] || '';
    const h1 = [...html.matchAll(/<h1\b[^>]*>([\s\S]*?)<\/h1>/gi)].map((match) => decode(match[1]));
    const secondaryHeadings = [...html.matchAll(/<h[2-4]\b[^>]*>([\s\S]*?)<\/h[2-4]>/gi)].map((match) => decode(match[1]));
    const anchorTags = [...html.matchAll(/<a\b[^>]*>[\s\S]*?<\/a>/gi)].map((match) => match[0]);
    const buttonTexts = [...html.matchAll(/<button\b[^>]*>([\s\S]*?)<\/button>/gi)].map((match) => decode(match[1]));
    const internalLinks = [];
    const externalLinks = [];
    const contactLinks = [];
    const ctaLabels = [];
    const ctaPattern = /\b(schedule|tour|enroll|apply|contact|call|directions|view campus|find|request|download|learn more|explore|submit|book|programar|visita|reservar|encontrar|ubicaci[oó]n|llamar|direcciones|ver campus|solicitar|descargar|aprender m[aá]s|explorar|enviar|contacto|cont[aá]ctenos|inscribirse)\b/i;

    for (const tag of anchorTags) {
      const href = attr(tag, 'href');
      const text = decode(tag);
      if (/^(mailto:|tel:|sms:)/i.test(href)) contactLinks.push(href);
      const linked = normalizeLink(href, finalUrl || url);
      if (linked) {
        if (linked.origin === baseOrigin) internalLinks.push(linked.href.replace(/\/$/, ''));
        else externalLinks.push(linked.href);
      }
      if (ctaPattern.test(text)) ctaLabels.push(text);
    }
    for (const text of buttonTexts) if (ctaPattern.test(text)) ctaLabels.push(text);

    const cleanHtml = html
      .replace(/<script\b[\s\S]*?<\/script>/gi, ' ')
      .replace(/<style\b[\s\S]*?<\/style>/gi, ' ')
      .replace(/<svg\b[\s\S]*?<\/svg>/gi, ' ');
    const visibleText = decode(cleanHtml);
    const mainMatch = html.match(/<main\b[^>]*>([\s\S]*?)<\/main>/i);
    const mainText = decode(mainMatch ? mainMatch[1] : cleanHtml);
    const contentFingerprint = crypto.createHash('sha256')
      .update(mainText.toLowerCase().replace(/\b\d+\b/g, '#').replace(/\s+/g, ' ').trim())
      .digest('hex');
    const footerEnd = html.toLowerCase().lastIndexOf('</footer>');
    const rawAfterFooterText = footerEnd >= 0 ? decode(html.slice(footerEnd + 9).replace(/<script\b[\s\S]*?<\/script>/gi, ' ').replace(/<style\b[\s\S]*?<\/style>/gi, ' ')) : '';
    const afterFooterText = rawAfterFooterText
      .replace(/\bSchedule Your Visit\b/gi, ' ')
      .replace(/\bOpen in new tab\b/gi, ' ')
      .replace(/\bPrograme su Visita\b/gi, ' ')
      .replace(/\bAbrir en pesta[ñn]a nueva\b/gi, ' ')
      .replace(/Pro Viewer Document 1 \/ -- Enhancing Document\.\.\./gi, ' ')
      .replace(/\s+/g, ' ')
      .trim();
    const hiddenContentSignals = (html.match(/(?:\bhidden\b|aria-hidden=["']true["']|display\s*:\s*none|visibility\s*:\s*hidden)/gi) || []).length;
    const forms = [...html.matchAll(/<form\b[^>]*>/gi)].map((match) => match[0]);
    const iframes = [...html.matchAll(/<iframe\b[^>]*>/gi)].map((match) => match[0]);
    const imageTags = [...html.matchAll(/<img\b[^>]*>/gi)].map((match) => match[0]);
    const imagesMissingAlt = imageTags.filter((tag) => !/\balt\s*=/.test(tag)).length;
    const suspicious = [];
    const patterns = [
      ['placeholder', /\b(lorem ipsum|placeholder text|coming soon|replace me)\b/i],
      ['placeholder', /\bTODO\b/],
      ['mojibake', /[\u00c2\u00c3\ufffd]|Ã¢|â€™|â€œ|â€|â€“|â€”/],
      ['unsupported-accreditation', /\b(NAEYC accredited|GAC accredited|all campuses are accredited)\b/i],
      ['superlative-needs-context-review', /\b(best daycare|#1 daycare|top-rated daycare|award-winning)\b/i],
      ['keyword-stuffing', /\b(daycare|childcare)\b(?:[^.]{0,100}\b(daycare|childcare)\b){4,}/i],
    ];
    for (const [label, pattern] of patterns) if (pattern.test(visibleText)) suspicious.push(label);

    const wordCount = mainText ? mainText.split(/\s+/).filter(Boolean).length : 0;
    const robots = attr(robotsTag, 'content') || response.headers.get('x-robots-tag') || '';
    const canonical = attr(canonicalTag, 'href');
    const initialIssues = [];
    if (!title) initialIssues.push('missing-title');
    if (!attr(descriptionTag, 'content')) initialIssues.push('missing-meta-description');
    if (h1.length === 0) initialIssues.push('missing-h1');
    if (h1.length > 1) initialIssues.push('multiple-h1');
    if (!canonical) initialIssues.push('missing-canonical');
    if (wordCount > 0 && wordCount < 180 && !['thank-you', 'legal'].includes(pageType)) initialIssues.push('thin-content');
    if (!ctaLabels.length && !['legal', 'thank-you'].includes(pageType)) initialIssues.push('no-clear-cta');
    if (suspicious.length) initialIssues.push(...suspicious);
    if (imagesMissingAlt) initialIssues.push('images-missing-alt');
    if (afterFooterText.length > 30) initialIssues.push('content-after-footer');

    let disposition = 'Keep';
    let indexingRecommendation = 'Index';
    if (/noindex/i.test(robots)) {
      disposition = 'Noindex';
      indexingRecommendation = 'Noindex';
    } else if (['community-single'].includes(pageType) && suspicious.includes('keyword-stuffing')) {
      disposition = 'Merge or Redirect';
    } else if (initialIssues.length) {
      disposition = 'Improve';
    }

    return {
      url,
      finalUrl,
      status: response.status,
      language: parsedUrl.pathname.startsWith('/es/') ? 'es' : 'en',
      pageType,
      pagePurpose: profile.purpose,
      intendedAudience: profile.audience,
      primaryConversionAction: profile.conversion,
      title,
      metaDescription: attr(descriptionTag, 'content'),
      robots,
      canonical,
      h1: h1.join(' | '),
      secondaryHeadings: secondaryHeadings.join(' | '),
      wordCount,
      internalLinkCount: unique(internalLinks).length,
      internalLinks: unique(internalLinks).join(' | '),
      externalLinkCount: unique(externalLinks).length,
      externalLinks: unique(externalLinks).join(' | '),
      contactLinks: unique(contactLinks).join(' | '),
      formCount: forms.length,
      embeddedFormCount: iframes.filter((tag) => /form|leadconnector|jotform|ghl/i.test(tag)).length,
      ctaCount: unique(ctaLabels).length,
      ctaLabels: unique(ctaLabels).join(' | '),
      imageCount: imageTags.length,
      imagesMissingAlt,
      hiddenContentSignals,
      afterFooterTextLength: afterFooterText.length,
      contentFingerprint,
      duplicateContentGroupSize: 1,
      duplicateTitleGroupSize: 1,
      technicalIssues: initialIssues.join(' | '),
      inaccurateClaims: suspicious.filter((label) => /unsupported/.test(label)).join(' | '),
      spellingGrammarReview: 'Human review required',
      outdatedInformationReview: 'Owner review required',
      brokenLayoutReview: 'Covered only by representative template QA',
      brokenLinkReview: 'Internal targets reconciled after crawl; external links require separate validation',
      mobileReview: 'Covered only by representative template QA',
      searchIndexStatus: 'Requires Search Console verification',
      indexingRecommendation,
      disposition,
      durationMs: Date.now() - started,
      error: '',
    };
  } catch (error) {
    const parsedUrl = new URL(url);
    const pageType = inferPageType(parsedUrl.pathname);
    const profile = pageProfile(pageType);
    return {
      url,
      finalUrl: '',
      status: 0,
      language: parsedUrl.pathname.startsWith('/es/') ? 'es' : 'en',
      pageType,
      pagePurpose: profile.purpose,
      intendedAudience: profile.audience,
      primaryConversionAction: profile.conversion,
      disposition: 'Improve',
      indexingRecommendation: 'Review',
      technicalIssues: 'fetch-error',
      error: error.message,
      durationMs: Date.now() - started,
    };
  }
}

async function main() {
  await loadCurrentSitemap();
  const results = new Array(sitemapUrls.length);
  let cursor = 0;
  const workers = Array.from({ length: concurrency }, async () => {
    while (true) {
      const index = cursor++;
      if (index >= sitemapUrls.length) return;
      results[index] = await inspect(sitemapUrls[index]);
      if ((index + 1) % 50 === 0) process.stdout.write(`Inventoried ${index + 1}/${sitemapUrls.length}\n`);
    }
  });
  await Promise.all(workers);

  const titleGroups = new Map();
  const contentGroups = new Map();
  const knownInternalTargets = new Set(results.map((row) => indexKey(row.finalUrl || row.url)));
  for (const row of results) {
    if (row.title) titleGroups.set(row.title, (titleGroups.get(row.title) || 0) + 1);
    if (row.contentFingerprint) contentGroups.set(row.contentFingerprint, (contentGroups.get(row.contentFingerprint) || 0) + 1);
  }

  for (const row of results) {
    row.duplicateTitleGroupSize = titleGroups.get(row.title) || 1;
    row.duplicateContentGroupSize = contentGroups.get(row.contentFingerprint) || 1;
    const links = String(row.internalLinks || '').split(' | ').filter(Boolean);
    const outsideSitemap = links
      .filter(isHtmlPageTarget)
      .filter((link) => !knownInternalTargets.has(indexKey(link)));
    row.outsideSitemapInternalLinkCount = outsideSitemap.length;
    row.outsideSitemapInternalLinks = outsideSitemap.join(' | ');
    row.unresolvedInternalLinkCount = outsideSitemap.length;
    row.unresolvedInternalLinks = outsideSitemap.join(' | ');
    const issues = String(row.technicalIssues || '').split(' | ').filter(Boolean);
    if (row.duplicateTitleGroupSize > 1) issues.push('duplicate-title');
    if (row.duplicateContentGroupSize > 1) issues.push('duplicate-content');
    if (outsideSitemap.length) issues.push('outside-sitemap-internal-links');
    row.technicalIssues = unique(issues).join(' | ');
    if (row.disposition === 'Keep' && issues.length) row.disposition = 'Improve';
  }

  const columns = unique(results.flatMap((row) => Object.keys(row)));
  const csv = [
    columns.map(csvEscape).join(','),
    ...results.map((row) => columns.map((key) => csvEscape(row[key])).join(',')),
  ].join('\n');
  const issues = results.filter((row) => row.error || row.technicalIssues || row.disposition !== 'Keep');
  const issueColumns = ['url', 'pageType', 'status', 'technicalIssues', 'disposition', 'indexingRecommendation', 'error'];
  const issueCsv = [
    issueColumns.map(csvEscape).join(','),
    ...issues.map((row) => issueColumns.map((key) => csvEscape(row[key])).join(',')),
  ].join('\n');
  const byDisposition = results.reduce((acc, row) => {
    acc[row.disposition] = (acc[row.disposition] || 0) + 1;
    return acc;
  }, {});
  const byPageType = results.reduce((acc, row) => {
    acc[row.pageType] = (acc[row.pageType] || 0) + 1;
    return acc;
  }, {});
  const summary = {
    generatedAt: new Date().toISOString(),
    source: 'Current staging XML sitemap plus live staging HTML',
    auditedUrls: results.length,
    errors: results.filter((row) => row.error).length,
    byDisposition,
    byPageType,
    duplicateTitleUrls: results.filter((row) => row.duplicateTitleGroupSize > 1).length,
    duplicateContentUrls: results.filter((row) => row.duplicateContentGroupSize > 1).length,
    thinContentUrls: results.filter((row) => String(row.technicalIssues).includes('thin-content')).length,
    noClearCtaUrls: results.filter((row) => String(row.technicalIssues).includes('no-clear-cta')).length,
    outsideSitemapInternalLinkUrls: results.filter((row) => row.outsideSitemapInternalLinkCount > 0).length,
    unresolvedInternalLinkUrls: results.filter((row) => row.outsideSitemapInternalLinkCount > 0).length,
    manualReviewRequired: true,
    limitations: [
      'Search index status requires Google Search Console.',
      'Grammar, accuracy, outdated facts, and legal claims require human or owner review.',
      'Layout/mobile findings are template-level unless separately inspected per URL.',
      'External-link response validation is a separate audit.',
      'Outside-sitemap internal links are architecture signals, not broken-link findings; HTTP validity is covered by the technical crawl.',
    ],
  };

  fs.writeFileSync(path.join(reportDir, 'content-inventory.json'), JSON.stringify(results, null, 2));
  fs.writeFileSync(path.join(reportDir, 'content-inventory.csv'), csv);
  fs.writeFileSync(path.join(reportDir, 'content-issue-list.csv'), issueCsv);
  fs.writeFileSync(path.join(reportDir, 'content-inventory-summary.json'), JSON.stringify(summary, null, 2));
  console.log(JSON.stringify(summary, null, 2));
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
