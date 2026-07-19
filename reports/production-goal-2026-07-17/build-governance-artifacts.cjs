const fs = require('fs');
const path = require('path');

const reportDir = __dirname;

function readJson(name) {
  return JSON.parse(fs.readFileSync(path.join(reportDir, name), 'utf8'));
}

function csvEscape(value) {
  const text = value == null ? '' : String(value);
  return `"${text.replace(/"/g, '""')}"`;
}

function writeCsv(name, rows, headers) {
  const body = [headers.map(csvEscape).join(',')]
    .concat(rows.map((row) => headers.map((header) => csvEscape(row[header])).join(',')))
    .join('\n');
  fs.writeFileSync(path.join(reportDir, name), `${body}\n`);
}

function parseTsv(name) {
  const lines = fs.readFileSync(path.join(reportDir, name), 'utf8').trim().split(/\r?\n/);
  const records = [];
  let headers = [];
  for (const line of lines) {
    const cells = line.split('\t');
    if (cells[0] === 'record_type') {
      headers = cells;
      continue;
    }
    const record = {};
    headers.forEach((header, index) => {
      record[header] = cells[index] || '';
    });
    records.push(record);
  }
  return records;
}

function normalizePathname(rawUrl) {
  try {
    const segments = new URL(rawUrl).pathname.split('/').filter(Boolean);
    if (segments[0] === 'es') segments.shift();
    return `/${segments.join('/')}${segments.length ? '/' : ''}`;
  } catch {
    return '';
  }
}

function extractLocationLinks(rawLinks) {
  return String(rawLinks || '')
    .split(' | ')
    .filter((url) => /\/locations\/[^/]+\/?$/i.test(url));
}

function contentDisposition(record) {
  const pathname = normalizePathname(record.url);
  const noindex = /noindex/i.test(record.robots || '');
  const isCommunity = record.pageType === 'community-single';
  const isGeneratedNearMe = /^\/(daycare|preschool|childcare|pre-k|infant-care)-near-/i.test(pathname);
  const isLegacyEditorial = /^\/2025\//.test(pathname);
  const hasTechnicalIssues = String(record.technicalIssues || '').trim().length > 0;

  if (record.status >= 300 && record.status < 400) {
    return {
      proposedDisposition: 'Redirect',
      rationale: 'URL currently redirects; confirm the destination is intentional and record it in the redirect map.',
      approvalStatus: 'Technical review required',
      nextAction: 'Verify redirect target, hop count, and internal links.'
    };
  }

  if (record.status >= 400 || record.error) {
    return {
      proposedDisposition: 'Delete',
      rationale: 'URL is unavailable or errored in the crawl.',
      approvalStatus: 'Management and technical review required',
      nextAction: 'Choose restore, redirect, or permanent removal based on historical value and backlinks.'
    };
  }

  if (noindex) {
    let rationale = 'Currently noindexed; keep excluded until a human usefulness and accuracy review approves indexation.';
    if (isGeneratedNearMe) rationale = 'Generated service-city page is noindexed and should not return to the index without unique, verified local usefulness.';
    if (isLegacyEditorial) rationale = 'Legacy editorial page is noindexed pending quality, accuracy, and link review.';
    if (isCommunity) rationale = 'Community page is noindexed pending real-campus attachment, distance, and uniqueness verification.';
    return {
      proposedDisposition: 'Noindex',
      rationale,
      approvalStatus: 'Management review required',
      nextAction: 'Approve noindex, rewrite for indexation, merge, redirect, or delete.'
    };
  }

  if (isCommunity) {
    return {
      proposedDisposition: 'Improve',
      rationale: 'Indexable community page requires verified nearby-campus relationship, useful local detail, and uniqueness review.',
      approvalStatus: 'Management fact review required',
      nextAction: 'Verify campus attachment and distance; rewrite or remove if the relationship is not real.'
    };
  }

  if (record.wordCount < 200 || hasTechnicalIssues || record.imagesMissingAlt > 0) {
    return {
      proposedDisposition: 'Improve',
      rationale: 'Indexable page has thin-content, technical, or image-accessibility signals that need review.',
      approvalStatus: 'Editorial or technical review required',
      nextAction: 'Resolve the listed content and technical issues, then recrawl.'
    };
  }

  return {
    proposedDisposition: 'Keep',
    rationale: 'Indexable page passed the automated availability and baseline content checks.',
    approvalStatus: 'Human fact and editorial review required',
    nextAction: 'Confirm facts, grammar, usefulness, and visual/mobile quality before final approval.'
  };
}

function buildContentDispositions(inventory) {
  return inventory.map((record) => ({
    url: record.url,
    language: record.language,
    pageType: record.pageType,
    status: record.status,
    robots: record.robots,
    title: record.title,
    wordCount: record.wordCount,
    duplicateContentGroupSize: record.duplicateContentGroupSize,
    currentInventoryDisposition: record.disposition,
    ...contentDisposition(record)
  }));
}

function buildCommunityDispositions(inventory) {
  return inventory
    .filter((record) => record.pageType === 'community-single')
    .map((record) => {
      const pathname = normalizePathname(record.url);
      const locationLinks = extractLocationLinks(record.internalLinks);
      const explicitRisk = /lafayette|walker/i.test(pathname);
      const noindex = /noindex/i.test(record.robots || '');
      let proposedDisposition = noindex ? 'Noindex' : 'Improve';
      let reason = noindex
        ? 'Preserve noindex until the community is attached to a verified nearby campus and provides unique parent value.'
        : 'Retain only after verifying the nearby-campus relationship, realistic distance, and unique local usefulness.';
      if (!locationLinks.length) {
        proposedDisposition = noindex ? 'Noindex' : 'Redirect';
        reason = 'No real campus link was detected; the page cannot satisfy the required campus-attachment rule without correction.';
      }
      if (explicitRisk) {
        proposedDisposition = 'Noindex';
        reason = 'LaFayette/Walker County is explicitly named in the governing prompt as a misleading-location risk and needs management verification.';
      }
      return {
        url: record.url,
        language: record.language,
        title: record.title,
        robots: record.robots,
        wordCount: record.wordCount,
        duplicateContentGroupSize: record.duplicateContentGroupSize,
        linkedCampusCount: locationLinks.length,
        linkedCampuses: locationLinks.join(' | '),
        verifiedDistance: 'Not available in current evidence',
        uniqueLocalValue: record.duplicateContentGroupSize > 1 ? 'Duplicate-content risk' : 'Human review required',
        proposedDisposition,
        reason,
        approvalStatus: 'Management review required',
        nextAction: 'Verify real campus relationship and distance; approve keep/rewrite, merge, redirect, noindex, or delete.'
      };
    });
}

const programRequirements = [
  ['Clear page title', 'post_title'],
  ['Age range', 'age_range'],
  ['Brief parent-centered introduction', 'parent_intro'],
  ['What children experience', 'experience'],
  ['Developmental priorities', 'priorities'],
  ['Example daily rhythm', 'schedule'],
  ['PrismPath at this stage', 'prism_stage'],
  ['Teacher and family partnership', 'manual'],
  ['Classroom environment', 'manual'],
  ['Meals and rest where applicable', 'manual'],
  ['Safety and supervision', 'manual'],
  ['Transition to the next stage', 'manual'],
  ['Campus availability', 'campus_links'],
  ['Parent FAQs', 'faq'],
  ['Schedule-a-tour CTA', 'cta']
];

function buildProgramMatrix(records) {
  const programs = records.filter((record) => record.record_type === 'program');
  const templateCoveredRequirements = new Set([
    'Teacher and family partnership',
    'Classroom environment',
    'Meals and rest where applicable',
    'Safety and supervision',
    'Transition to the next stage'
  ]);
  const scheduleFallbackSlugs = new Set(['kindergarten-1', 'rising-pre-k', 'rising-kindergarten']);
  const rows = [];
  for (const program of programs) {
    for (const [requirement, field] of programRequirements) {
      const value = field === 'post_title' ? program.post_title : program[field];
      const direct = field !== 'manual';
      const present = direct && value && !/^(missing|fallback)$/i.test(value);
      const fallback = direct && /fallback/i.test(value || '');
      const templateSupported = field === 'manual' && templateCoveredRequirements.has(requirement);
      const scheduleTemplateFallback = field === 'schedule' && !present && scheduleFallbackSlugs.has(program.post_name);
      const faqTemplateFallback = field === 'faq' && fallback;
      rows.push({
        programId: program.ID,
        program: program.post_title,
        slug: program.post_name,
        requirement,
        evidenceField: templateSupported ? 'template-required-details' : scheduleTemplateFallback ? 'template-schedule-fallback' : faqTemplateFallback ? 'template-program-faq' : field,
        evidenceValue: field === 'manual' ? (templateSupported ? 'Reusable program family-expectations section' : '') : faqTemplateFallback ? 'Program-specific visible FAQ fallback; editable program meta overrides it.' : value,
        status: templateSupported || faqTemplateFallback ? 'Template fallback present' : scheduleTemplateFallback ? 'Present via template fallback' : field === 'manual' ? 'Not proven' : present ? 'Present' : fallback ? 'Fallback requires review' : 'Missing',
        ownerGate: /GA Pre-K|Kindergarten/i.test(program.post_title) && /Age range|Campus availability|Meals|Safety|Teacher|FAQ/i.test(requirement) ? 'Yes' : 'No',
        nextAction: templateSupported
          ? 'Owner may replace fallback with distinct program-specific operating copy.'
          : faqTemplateFallback
            ? 'Owner may replace the visible program-specific FAQ fallback with program meta.'
          : field === 'manual'
          ? 'Verify distinct program-specific copy and add the missing section if absent.'
          : present
            ? 'Human fact and uniqueness review.'
            : scheduleTemplateFallback
              ? 'Human review of template-level sample rhythm.'
              : 'Add or replace fallback with verified program-specific content.'
      });
    }
  }
  return rows;
}

const campusRequirements = [
  ['Campus name', 'post_title', false],
  ['City and neighborhood', 'address', true],
  ['Full address', 'address', true],
  ['Phone number', 'phone', true],
  ['Campus email', 'email', true],
  ['Current operating hours', 'hours', true],
  ['Directions and map', 'coordinates', false],
  ['Real campus photography', 'images', true],
  ['Short campus-specific introduction', 'intro', true],
  ['Current director biography', 'director', true],
  ['Programs offered', 'programs', true],
  ['Ages served', 'ages', true],
  ['Verified campus features', 'features', true],
  ['Current school pickups', 'school_pickups', true],
  ['GA Pre-K availability', 'ga_pre_k', true],
  ['CAPS participation', 'caps', true],
  ['Quality Rated status', 'quality_rated', true],
  ['Licensing information', 'licensing', true],
  ['Parent testimonials for that campus', 'testimonial', true],
  ['Tuition inquiry', 'tuition', true],
  ['Availability or waitlist information', 'availability', true],
  ['Schedule-a-tour form', 'tour_link', false],
  ['Local FAQ', 'faq', true],
  ['Nearby communities served', 'nearby_communities', true],
  ['Nearby landmarks or access information', 'nearby_access', true],
  ['Date the page was last reviewed', 'last_reviewed', true]
];

function buildCampusMatrix(records) {
  const campuses = records.filter((record) => record.record_type === 'location');
  const rows = [];
  for (const campus of campuses) {
    for (const [requirement, field, ownerGate] of campusRequirements) {
      const value = field === 'post_title' ? campus.post_title : campus[field];
      const direct = field !== 'manual';
      const present = direct && value && !/^(missing|fallback|optional)$/i.test(value);
      const partial = direct && /^(fallback|optional)$/i.test(value || '');
      rows.push({
        campusId: campus.ID,
        campus: campus.post_title,
        slug: campus.post_name,
        requirement,
        evidenceField: field,
        evidenceValue: field === 'manual' ? '' : value,
        status: field === 'manual' ? 'Not proven' : present ? 'Present' : partial ? 'Optional or fallback' : 'Missing',
        ownerGate: ownerGate ? 'Yes' : 'No',
        nextAction: field === 'manual'
          ? 'Obtain campus-specific source data and verify before publication.'
          : present
            ? 'Confirm current accuracy against the campus source of truth.'
            : partial
              ? 'Field is intentionally omitted or uses a safe template fallback; confirm whether campus-specific publication is required.'
              : 'Supply and verify the missing campus field.'
      });
    }
  }
  return rows;
}

function buildExternalLinkReview(inventory, technicalAudit) {
  const sourceMap = new Map();
  for (const record of inventory) {
    const links = String(record.externalLinks || '').split(' | ').filter(Boolean);
    for (const link of links) {
      if (!sourceMap.has(link)) sourceMap.set(link, []);
      sourceMap.get(link).push(record.url);
    }
  }

  return technicalAudit.externalLinks
    .filter((link) => link.broken)
    .map((link) => {
      let classification = 'Replace or remove';
      let nextAction = 'Verify in a browser; replace with a current authoritative URL or remove the link.';
      if (/acquire4hire/i.test(link.url)) {
        classification = 'HR source confirmation';
        nextAction = 'Confirm the active recruiting source and remove or replace stale job-detail links.';
      } else if (/chromaela\.com\/post\//i.test(link.url)) {
        classification = 'Retired Chroma article';
        nextAction = 'Map to a relevant retained resource only when equivalent; otherwise unlink the citation.';
      } else if (Number(link.status) === 429) {
        classification = 'Rate-limited; not confirmed broken';
        nextAction = 'Recheck manually outside the bulk audit before changing content.';
      } else if ([401, 403, 500, 502, 503].includes(Number(link.status))) {
        classification = 'Manual browser verification';
        nextAction = 'Confirm whether the authoritative target is publicly reachable before replacing it.';
      }
      return {
        targetUrl: link.url,
        status: link.status,
        error: link.error,
        sourcePageCount: (sourceMap.get(link.url) || []).length,
        sourcePages: (sourceMap.get(link.url) || []).join(' | '),
        classification,
        nextAction,
        approvalStatus: /HR/.test(classification) ? 'HR approval required' : 'Editorial or technical review required'
      };
    });
}

function parsePhaseMatrix() {
  const text = fs.readFileSync(path.join(reportDir, 'MASTER-EXECUTION-MATRIX.md'), 'utf8');
  const map = new Map();
  for (const line of text.split(/\r?\n/)) {
    if (!/^\|\s*\d+\s*\|/.test(line)) continue;
    const cells = line.split('|').slice(1, -1).map((cell) => cell.trim());
    const phase = Number(cells[0]);
    if (!Number.isFinite(phase)) continue;
    map.set(phase, {
      internalResult: cells[1] || '',
      evidence: cells[2] || '',
      openGate: cells[3] || ''
    });
  }
  return map;
}

function requirementVerificationState(phaseEvidence) {
  if (!phaseEvidence.internalResult) {
    return 'Not assessed';
  }
  if (phaseEvidence.openGate && phaseEvidence.openGate !== '-') {
    return 'Phase implementation evidenced; external gate remains; item-level confirmation required';
  }
  if (/complete|implemented|remediation/i.test(phaseEvidence.internalResult)) {
    return 'Phase implementation evidenced; item-level confirmation required';
  }
  return 'Phase-level evidence recorded; item review recommended';
}

function promptStatement(line) {
  if (!line || /^#{1,6}\s/.test(line) || /^[-*_]{3,}$/.test(line)) return null;

  const bullet = line.match(/^[-*+]\s+(.+)$/);
  if (bullet) return { text: bullet[1], sourceType: 'bullet' };

  const numbered = line.match(/^\d+\.\s+(.+)$/);
  if (numbered) return { text: numbered[1], sourceType: 'numbered' };

  return { text: line, sourceType: 'paragraph' };
}

function buildRequirementMatrix() {
  const prompt = fs.readFileSync(path.join(reportDir, 'FULL-GOVERNING-PROMPT.md'), 'utf8').split(/\r?\n/);
  const phases = parsePhaseMatrix();
  const rows = [];
  let phase = 0;
  let section = '';
  let deliverable = '';
  for (let index = 0; index < prompt.length; index += 1) {
    const line = prompt[index].trim();
    const phaseMatch = line.match(/^# Phase (\d+):\s*(.+)$/);
    if (phaseMatch) {
      phase = Number(phaseMatch[1]);
      section = phaseMatch[2];
      deliverable = '';
      continue;
    }
    const sectionMatch = line.match(/^##\s+(.+)$/);
    if (sectionMatch) {
      section = sectionMatch[1];
      if (/^\d+\.\s/.test(section)) deliverable = section;
      continue;
    }
    if (phase < 1 || phase > 25) continue;
    const statement = promptStatement(line);
    if (!statement) continue;
    const phaseEvidence = phases.get(phase) || {};
    rows.push({
      requirementId: `P${String(phase).padStart(2, '0')}-L${String(index + 1).padStart(4, '0')}`,
      phase,
      phaseSection: section,
      sourceLine: index + 1,
      requirement: statement.text,
      sourceType: statement.sourceType,
      evidenceScope: 'Phase-level implementation evidence; exact statement requires direct confirmation',
      phaseStatus: phaseEvidence.internalResult || 'Not assessed',
      currentEvidence: phaseEvidence.evidence || '',
      remainingProofOrAction: phaseEvidence.openGate || '',
      verificationState: requirementVerificationState(phaseEvidence)
    });
  }
  return rows;
}

const inventory = readJson('content-inventory.json');
const technicalAudit = readJson('technical-seo-audit.json');
const readinessRecords = parseTsv('record-readiness.tsv');

const contentDispositions = buildContentDispositions(inventory);
const communityDispositions = buildCommunityDispositions(inventory);
const programMatrix = buildProgramMatrix(readinessRecords);
const campusMatrix = buildCampusMatrix(readinessRecords);
const externalReview = buildExternalLinkReview(inventory, technicalAudit);
const requirementMatrix = buildRequirementMatrix();

writeCsv('CONTENT-DISPOSITION.csv', contentDispositions, [
  'url', 'language', 'pageType', 'status', 'robots', 'title', 'wordCount',
  'duplicateContentGroupSize', 'currentInventoryDisposition', 'proposedDisposition',
  'rationale', 'approvalStatus', 'nextAction'
]);
writeCsv('COMMUNITY-DISPOSITION.csv', communityDispositions, [
  'url', 'language', 'title', 'robots', 'wordCount', 'duplicateContentGroupSize',
  'linkedCampusCount', 'linkedCampuses', 'verifiedDistance', 'uniqueLocalValue',
  'proposedDisposition', 'reason', 'approvalStatus', 'nextAction'
]);
writeCsv('PROGRAM-REQUIREMENTS-MATRIX.csv', programMatrix, [
  'programId', 'program', 'slug', 'requirement', 'evidenceField', 'evidenceValue',
  'status', 'ownerGate', 'nextAction'
]);
writeCsv('CAMPUS-REQUIREMENTS-MATRIX.csv', campusMatrix, [
  'campusId', 'campus', 'slug', 'requirement', 'evidenceField', 'evidenceValue',
  'status', 'ownerGate', 'nextAction'
]);
writeCsv('EXTERNAL-LINK-REVIEW.csv', externalReview, [
  'targetUrl', 'status', 'error', 'sourcePageCount', 'sourcePages',
  'classification', 'nextAction', 'approvalStatus'
]);
writeCsv('REQUIREMENT-EVIDENCE-MATRIX.csv', requirementMatrix, [
  'requirementId', 'phase', 'phaseSection', 'sourceLine', 'sourceType', 'requirement',
  'evidenceScope',
  'phaseStatus', 'currentEvidence', 'remainingProofOrAction', 'verificationState'
]);

const summary = {
  generatedAt: new Date().toISOString(),
  contentDispositions: contentDispositions.reduce((totals, row) => {
    totals[row.proposedDisposition] = (totals[row.proposedDisposition] || 0) + 1;
    return totals;
  }, {}),
  communities: communityDispositions.length,
  programRequirementRows: programMatrix.length,
  campusRequirementRows: campusMatrix.length,
  brokenExternalTargets: externalReview.length,
  promptStatementRows: requirementMatrix.length,
  explicitPromptRequirementRows: requirementMatrix.length,
  note: 'Prompt statements include bullets, numbered items, and paragraph requirements. Phase evidence does not by itself prove each statement. Proposed dispositions are management-review artifacts, not automatic delete or publication approvals.'
};
fs.writeFileSync(path.join(reportDir, 'governance-artifact-summary.json'), `${JSON.stringify(summary, null, 2)}\n`);
console.log(JSON.stringify(summary, null, 2));
