const fs = require('fs');
const path = require('path');

const base = 'https://x3yyntt5tp-staging.wpdns.site';
const outputRoot = __dirname;

const programs = {
  'infant-care': [
    ['Secure relationships', /secure relationship|emotional security|attachment|bond/i],
    ['Responsive caregiving', /responsive caregiv|responsive interaction|responsive care/i],
    ['Sensory exploration', /sensory/i],
    ['Language exposure', /language exposure|songs?|stories|sounds? of language/i],
    ['Safe movement', /safe movement|motor skills?|movement/i],
    ['Individual feeding and rest routines', /feeding|rest routines?|nap(ping)?/i],
    ['Family communication', /family communication|parent communication|daily communication|family partnership/i],
  ],
  'toddler-care': [
    ['Independence', /independen/i],
    ['Language growth', /language growth|vocabulary|communication skills?/i],
    ['Movement', /movement|motor skills?/i],
    ['Routines', /routines?/i],
    ['Early peer interaction', /peer interaction|social interaction|friendship|play alongside/i],
    ['Emotional regulation', /emotional regulation|self-regulation|regulat(e|ion)|feelings/i],
    ['Hands-on exploration', /hands-on|exploration|explore/i],
  ],
  preschool: [
    ['Inquiry', /inquiry|curiosity|questions?/i],
    ['Communication', /communication|language/i],
    ['Early literacy', /early literacy|literacy|letters?|phonics|sound patterns?/i],
    ['Mathematical thinking', /mathematical thinking|math|numeracy|numbers?/i],
    ['Cooperative play', /cooperative play|collaborative play|group play/i],
    ['Problem solving', /problem.solv|critical thinking/i],
    ['Self-regulation', /self-regulation|emotional regulation/i],
    ['Kindergarten readiness', /kindergarten readiness|school readiness/i],
  ],
  'pre-k-prep': [
    ['Inquiry', /inquiry|curiosity|questions?/i],
    ['Communication', /communication|language/i],
    ['Early literacy', /early literacy|literacy|letters?|phonics|sound patterns?/i],
    ['Mathematical thinking', /mathematical thinking|math|numeracy|numbers?/i],
    ['Cooperative play', /cooperative play|collaborative play|group play/i],
    ['Problem solving', /problem.solv|critical thinking/i],
    ['Self-regulation', /self-regulation|emotional regulation/i],
    ['Kindergarten readiness', /kindergarten readiness|school readiness/i],
  ],
  'ga-pre-k': [
    ['State-funded program', /state-funded|lottery-funded|tuition-free/i],
    ['Eligibility', /eligib/i],
    ['Lottery or application process', /lottery|application process|apply/i],
    ['Schedule', /schedule|school day|program day|hours/i],
    ['Wraparound care', /wraparound|before.?and.?after|extended care/i],
    ['Campus availability', /campus|locations?/i],
    ['Curriculum and state standards', /GELDS|state standards|curriculum/i],
  ],
  kindergarten: [
    ['Accreditation', /accredit/i],
    ['Grade-level scope', /kindergarten|grade-level|grade level/i],
    ['Tuition', /tuition|private program/i],
    ['Campus availability', /campus|locations?/i],
    ['Class size', /class size|small class|student.teacher ratio/i],
    ['Academic expectations', /academic|literacy|reading|writing|math|science/i],
    ['Progress reporting', /progress report|progress update|assessment|family update/i],
  ],
};

function decode(value) {
  return String(value || '')
    .replace(/&nbsp;/gi, ' ')
    .replace(/&amp;/gi, '&')
    .replace(/&quot;/gi, '"')
    .replace(/&#39;|&apos;/gi, "'")
    .replace(/&lt;/gi, '<')
    .replace(/&gt;/gi, '>')
    .replace(/&#(\d+);/g, (_, code) => String.fromCodePoint(Number(code)))
    .replace(/&#x([0-9a-f]+);/gi, (_, code) => String.fromCodePoint(parseInt(code, 16)));
}

function visibleMainText(html) {
  const main = html.match(/<main\b[^>]*>([\s\S]*?)<\/main>/i)?.[1] || html;
  return decode(main
    .replace(/<script\b[\s\S]*?<\/script>/gi, ' ')
    .replace(/<style\b[\s\S]*?<\/style>/gi, ' ')
    .replace(/<[^>]+>/g, ' '))
    .replace(/\s+/g, ' ')
    .trim();
}

function csv(value) {
  const text = String(value ?? '');
  return /[",\r\n]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text;
}

(async () => {
  const rows = [];
  for (const [slug, requirements] of Object.entries(programs)) {
    const url = `${base}/programs/${slug}/?codex_program_audit=20260718`;
    const response = await fetch(url, { redirect: 'follow' });
    const html = await response.text();
    const text = visibleMainText(html);
    for (const [requirement, pattern] of requirements) {
      const match = text.match(pattern);
      const index = match ? text.search(pattern) : -1;
      const evidence = index >= 0 ? text.slice(Math.max(0, index - 90), Math.min(text.length, index + 220)) : '';
      rows.push({ program: slug, url: response.url, statusCode: response.status, requirement, status: match ? 'Present' : 'Missing', evidence });
    }
    const expansionPattern = /future (grade|program) expansion|expanding (to|through) (first|1st|elementary)/i;
    const expansionMatch = text.match(expansionPattern);
    rows.push({
      program: slug,
      url: response.url,
      statusCode: response.status,
      requirement: 'No unapproved future grade expansion claim',
      status: slug !== 'kindergarten' || !expansionMatch ? 'Present' : 'Owner review required',
      evidence: expansionMatch ? expansionMatch[0] : 'No future expansion claim found.',
    });
  }

  const headers = ['program', 'url', 'statusCode', 'requirement', 'status', 'evidence'];
  const csvText = [headers.join(','), ...rows.map((row) => headers.map((key) => csv(row[key])).join(','))].join('\n');
  const summary = {
    generatedAt: new Date().toISOString(),
    auditedPrograms: Object.keys(programs).length,
    requirementRows: rows.length,
    statusCounts: Object.fromEntries([...new Set(rows.map((row) => row.status))].map((status) => [status, rows.filter((row) => row.status === status).length])),
    missing: rows.filter((row) => row.status === 'Missing').map(({ program, requirement }) => ({ program, requirement })),
    ownerReview: rows.filter((row) => row.status === 'Owner review required').map(({ program, requirement, evidence }) => ({ program, requirement, evidence })),
  };

  fs.writeFileSync(path.join(outputRoot, 'PROGRAM-EMPHASIS-MATRIX.csv'), csvText);
  fs.writeFileSync(path.join(outputRoot, 'program-emphasis-summary.json'), JSON.stringify(summary, null, 2));
  console.log(JSON.stringify(summary, null, 2));
})().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
