
import fs from 'fs';
import path from 'path';

// --- Configuration ---
const ARTIFACTS_DIR = path.resolve('lighthouse-out/artifacts');
const MOBILE_DIR = path.join(ARTIFACTS_DIR, 'mobile');
const DESKTOP_DIR = path.join(ARTIFACTS_DIR, 'desktop');
const REPORT_MD = 'lighthouse-out/report.md';
const REPORT_CSV = 'lighthouse-out/report.csv';
const ISSUES_CSV = 'lighthouse-out/issues.csv';

function loadJson(filepath) {
    try {
        if (!fs.existsSync(filepath)) return null;
        return JSON.parse(fs.readFileSync(filepath, 'utf-8'));
    } catch (e) {
        return null;
    }
}

// Helper to get audit result
const getAudit = (lhr, id) => lhr.audits[id];
const getScore = (lhr, cat) => lhr.categories[cat]?.score || 0;

function main() {
    console.log('Generating Reports...');

    if (!fs.existsSync(MOBILE_DIR)) {
        console.error('No artifacts found. Run audit-runner.mjs first.');
        return;
    }

    const files = fs.readdirSync(MOBILE_DIR).filter(f => f.endsWith('.json'));
    const combinedData = [];
    const allIssues = [];

    // --- Processing Loop ---
    for (const file of files) {
        const mobilePath = path.join(MOBILE_DIR, file);
        const desktopPath = path.join(DESKTOP_DIR, file);

        const m = loadJson(mobilePath);
        const d = loadJson(desktopPath);

        if (!m) continue; // Skip if mobile fail, primary key is file name

        const url = m.finalUrl;

        // Data Structure for Report
        const entry = {
            url,
            file,
            title: m.audits?.['document-title']?.details?.title || 'Unknown Title', // Fix accessing title safely
            mobile: {
                perf: getScore(m, 'performance'),
                a11y: getScore(m, 'accessibility'),
                bp: getScore(m, 'best-practices'),
                seo: getScore(m, 'seo'),
                lcp: m.audits['largest-contentful-paint']?.numericValue || 0,
                tbt: m.audits['total-blocking-time']?.numericValue || 0,
                cls: m.audits['cumulative-layout-shift']?.numericValue || 0,
                fcp: m.audits['first-contentful-paint']?.numericValue || 0,
                si: m.audits['speed-index']?.numericValue || 0,
            },
            desktop: d ? {
                perf: getScore(d, 'performance'),
                a11y: getScore(d, 'accessibility'),
                bp: getScore(d, 'best-practices'),
                seo: getScore(d, 'seo'),
                lcp: d.audits['largest-contentful-paint']?.numericValue || 0,
                tbt: d.audits['total-blocking-time']?.numericValue || 0,
                cls: d.audits['cumulative-layout-shift']?.numericValue || 0,
                fcp: d.audits['first-contentful-paint']?.numericValue || 0,
                si: d.audits['speed-index']?.numericValue || 0,
            } : null,
            issues: []
        };

        // Extract High Priority Issues (Mobile Focused primarily, but include Desktop if unique?) 
        // We will focus on Mobile issues for the per-page detail as it's the constraint usually.
        // We will just verify if Desktop is significantly worse (rare).

        const relevantAudits = Object.values(m.audits).filter(a => {
            return (a.score !== null && a.score < 0.9 && a.details); // Only score < 0.9 and actionable
        });

        for (const audit of relevantAudits) {
            // Rough categorization based on ID or Group
            // Lighthouse has 'groups' internally but we might not have the map handy in raw audit.
            // We'll rely on manual mapping or just list them.

            // Calc priority/impact
            // High impact = low score + high weight (not easily visible in JSON without calculations)
            // Heuristic: numericValue savings?

            const issueObj = {
                id: audit.id,
                title: audit.title,
                score: audit.score,
                displayValue: audit.displayValue,
                description: audit.description,
                severity: audit.score < 0.5 ? 'High' : 'Medium',
                impact: audit.numericValue || 0, // Raw impact if available
            };
            entry.issues.push(issueObj);

            allIssues.push({
                url,
                ...issueObj
            });
        }

        entry.issues.sort((a, b) => a.score - b.score);
        combinedData.push(entry);
    }

    // --- Sort Pages by Performance (Worst first) ---
    combinedData.sort((a, b) => a.mobile.perf - b.mobile.perf);


    // --- 1. Generate CSV Report ---
    const csvHeader = [
        'URL',
        'Mobile Perf', 'Mobile A11y', 'Mobile SEO', 'Mobile LCP (ms)', 'Mobile TBT (ms)', 'Mobile CLS',
        'Desktop Perf', 'Desktop A11y', 'Desktop SEO'
    ].join(',');

    const csvRows = combinedData.map(d => [
        `"${d.url}"`,
        (d.mobile.perf * 100).toFixed(0),
        (d.mobile.a11y * 100).toFixed(0),
        (d.mobile.seo * 100).toFixed(0),
        d.mobile.lcp.toFixed(0),
        d.mobile.tbt.toFixed(0),
        d.mobile.cls.toFixed(3),
        d.desktop ? (d.desktop.perf * 100).toFixed(0) : 'N/A',
        d.desktop ? (d.desktop.a11y * 100).toFixed(0) : 'N/A',
        d.desktop ? (d.desktop.seo * 100).toFixed(0) : 'N/A',
    ].join(','));

    fs.writeFileSync(REPORT_CSV, [csvHeader, ...csvRows].join('\n'));


    // --- 2. Generate Issues CSV ---
    const issuesHeader = ['URL', 'Audit ID', 'Title', 'Severity', 'Score', 'Display Value'];
    const issuesRows = allIssues.map(i => [
        `"${i.url}"`, i.id, `"${i.title.replace(/"/g, '""')}"`, i.severity, i.score, `"${(i.displayValue || '').replace(/"/g, '""')}"`
    ].join(','));
    fs.writeFileSync(ISSUES_CSV, [issuesHeader, ...issuesRows].join('\n'));


    // --- 3. Generate Markdown Report ---
    let md = `# Comprehensive Lighthouse Audit Report\n\n`;
    md += `**Date:** ${new Date().toLocaleString()}\n`;
    md += `**Total Pages Audited:** ${combinedData.length}\n\n`;

    // A) Executive Summary
    md += `## A. Executive Summary\n\n`;

    // Lowest Performance
    const lowest = combinedData.slice(0, 5);
    md += `### 📉 Bottom 5 Performing Pages (Mobile)\n`;
    lowest.forEach(p => {
        md += `- **${(p.mobile.perf * 100).toFixed(0)}** - [${p.url}](${p.url})\n`;
    });

    // Sitewide Patterns (Mocked logic for now, real analysis would aggregate counts)
    const issueCounts = {};
    allIssues.forEach(i => {
        issueCounts[i.id] = (issueCounts[i.id] || 0) + 1;
    });
    const topSystemic = Object.entries(issueCounts)
        .sort((a, b) => b[1] - a[1])
        .slice(0, 5);

    md += `\n### 🌍 Top Sitewide Issues\n`;
    topSystemic.forEach(([id, count]) => {
        const sample = allIssues.find(i => i.id === id);
        md += `- **${id}** (Affects ${count} pages): ${sample ? sample.title : id}\n`;
    });

    // B) Page-by-Page
    md += `\n## B. Page-by-Page Analysis\n\n`;
    combinedData.forEach(page => {
        md += `### [${page.url}](${page.url})\n`;
        md += `**Page Title:** ${page.title}\n\n`;

        md += `| Metric | Mobile | Desktop |\n|---|---|---|\n`;
        md += `| **Performance** | ${curateScore(page.mobile.perf)} | ${page.desktop ? curateScore(page.desktop.perf) : 'N/A'} |\n`;
        md += `| LCP | ${page.mobile.lcp.toFixed(0)}ms | ${page.desktop ? page.desktop.lcp.toFixed(0) : '-'}ms |\n`;
        md += `| TBT | ${page.mobile.tbt.toFixed(0)}ms | ${page.desktop ? page.desktop.tbt.toFixed(0) : '-'}ms |\n`;
        md += `| CLS | ${page.mobile.cls.toFixed(3)} | ${page.desktop ? page.desktop.cls.toFixed(3) : '-'} |\n\n`;

        if (page.issues.length > 0) {
            md += `#### Top Issues\n`;
            // Top 5 issues
            page.issues.slice(0, 5).forEach(i => {
                md += `- ${i.severity === 'High' ? '🔴' : 'Vk'} **${i.title}** (Score: ${(i.score * 100).toFixed(0)})\n`;
                md += `  - *Val:* ${i.displayValue}\n`;
            });
        }

        md += `\n[View Raw Artifacts](./artifacts/mobile/${page.file})\n`;
        md += `---\n\n`;
    });

    // C) Cross-site Backlog
    md += `## C. Prioritized Backlog\n\n`;
    md += `(See 'issues.csv' for full export)\n`;

    // topSystemic is a good proxy for backlog
    md += `### Recommended Attack Plan\n`;
    topSystemic.forEach(([id, count], idx) => {
        const sample = allIssues.find(i => i.id === id);
        md += `${idx + 1}. **Fix ${sample.title}**\n`;
        md += `   - Impact: Systemic (${count} pages)\n`;
        md += `   - Description: ${sample.description}\n`;
    });

    fs.writeFileSync(REPORT_MD, md);
    console.log(`Report Generated: ${REPORT_MD}`);
}

function curateScore(score) {
    const val = Math.round(score * 100);
    if (val >= 90) return `🟢 ${val}`;
    if (val >= 50) return `🟠 ${val}`;
    return `🔴 ${val}`;
}

main();
