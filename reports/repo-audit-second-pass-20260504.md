# Repo Audit Second Pass - 20260504

## Executive Summary

- Branch: `claude/create-wordpress-theme-01N9YNziMjoPyBiwj3iLobdB`
- Baseline commit inspected: `bac58e30d23b904be977d0d6d4eaad81d70d6b9e`
- Mode: audit plus safe remediation; tracked source was patched only for confirmed deterministic issues.
- Result: PHP, Composer, source JS syntax, available builds, available lints, QA tests, and asset manifest checks passed.
- Runtime WordPress checks remain pending because no local/staging WordPress runtime, database, wp-config bootstrap, API key, or browser-auth context is present in this workspace.
- Dependency audits are improved but not fully clean: remaining advisories require major upgrades that should be handled as a separate migration with browser/runtime regression coverage.

## Critical And High Findings

### SP-001 - parent portal REST debug routes (fixed)
- Severity: high
- Evidence: /system-check and /cookie-test used __return_true permission callbacks before this pass.
- Location: `plugins/chroma-parent-portal/includes/class-api-routes.php`:68
- Repro: Inspect route registration or request endpoints without admin auth in a WP runtime.
- Expected: Debug endpoints require an administrator.
- Actual: Before fix, the endpoints were public.
- Recommended fix/status: Keep debug routes behind is_admin_check and remove/debug-gate them in production if not needed.

### SP-002 - QA photo analysis remote/local image input (fixed)
- Severity: high
- Evidence: Image analysis accepted local paths and remote URLs without uploads-directory containment, safe HTTP validation, response size limit, or content-type enforcement.
- Location: `plugins/QA-Report-App/chroma-qa-reports/includes/ai/class-photo-analyzer.php`:58
- Repro: Call the photo analyzer with a local path outside uploads or a non-image URL.
- Expected: Only readable uploads-directory image files or safe remote image URLs are analyzed.
- Actual: Before fix, arbitrary local readable paths and unsafe remote URLs could be attempted.
- Recommended fix/status: Keep uploads-directory containment, wp_safe_remote_get, MIME checks, and response limits.

## Fixed Issues

### SP-003 - forms/leads webhooks
- Severity: medium
- Evidence: Contact/career/acquisition/lead webhook senders accepted configured URLs without safe remote post and strict HTTPS validation.
- Location: `chroma-plugins/chroma-contact-form/chroma-contact-form.php`:680
- Repro: Configure a non-HTTPS or unsafe webhook target, then submit the form.
- Recommendation: Continue using esc_url_raw, wp_http_validate_url, normalized HTTPS checks, and wp_safe_remote_post.

### SP-004 - careers feed fetch
- Severity: medium
- Evidence: Careers feed URL option was fetched directly, allowing unexpected hosts if the option changed.
- Location: `plugins/chroma-seo-pro-reset/inc/class-careers-api.php`:31
- Repro: Set chroma_careers_feed_url to an untrusted host and trigger the careers feed fetch.
- Recommendation: Keep the chroma_careers_allowed_feed_hosts filter narrow and audited.

### SP-005 - director portal lint/build
- Severity: medium
- Evidence: Director portal lacked working committed lint dependencies/config and used a vulnerable older Next 14.0.3 line.
- Location: `director-portal/package.json`:16
- Repro: Run npm run lint or npm audit in director-portal on the baseline.
- Recommendation: Plan a separate Next 16 migration for remaining high/moderate advisories.

### SP-006 - Composer metadata
- Severity: low
- Evidence: Composer validate warned that plugin manifests lacked license metadata.
- Location: `plugins/chroma-seo-pro/composer.json`:1
- Repro: Run composer validate --no-check-publish.
- Recommendation: Keep proprietary license metadata on private plugin Composer manifests.

## Remaining Warnings

### SP-007 - remaining JS dependency advisories (open_major_upgrade_required)
- Severity: medium
- Evidence: npm audit still reports advisories in director portal, QA build env, parent portal build env, QA Jest tooling, and Lighthouse automation after non-forced fixes.
- Repro: Run npm audit --json in each listed package directory.
- Recommendation: Handle as a dedicated dependency-modernization project with browser/runtime regression testing.

### SP-008 - WordPress runtime/API/browser checks (runtime_pending)
- Severity: medium
- Evidence: No usable local WordPress URL, wp-config bootstrap, database, or staging credentials were present in the workspace for authenticated REST/browser checks.
- Repro: Try to run REST discovery/resources, sitemap, portal, school dashboard, or forms smoke tests without a WordPress runtime.
- Recommendation: Run the pending WP smoke pack against staging/local WP with Agent API keys and a browser session.

### SP-009 - untracked scratch artifacts (classified_uncommitted)
- Severity: low
- Evidence: Untracked scratch files remain: chromaela.html, diff*.txt, full_diff.txt, old_cleanup.php, old_enforcer.php, temp_canonical.txt.
- Repro: Run git status --short.
- Recommendation: Owner should decide whether to delete, archive, or intentionally commit each scratch artifact.

## Verification Evidence

- `PHP lint: git ls-files *.php excluding vendor/node_modules -> php -l`: passed - 336 owned PHP files checked, 0 failures
- `Composer validate: php tools/composer.phar validate --no-check-publish`: passed - 3 manifests checked and valid
- `npm run build (chroma-excellence-theme)`: passed_with_warnings - Build and asset rev passed; manifest wrote 8 entries; Browserslist/baseline data freshness warnings
- `npm run build (plugins/chroma-school-dashboard)`: passed_with_warnings - esbuild/tailwind build passed; Browserslist data freshness warning
- `npm run lint (director-portal)`: passed_with_warnings - Next lint passed; existing @next/next/no-img-element warning at app/dashboard/page.tsx:31
- `npm run build (director-portal)`: passed_with_warnings - Next 14.2.35 production build passed; same img performance warning
- `npm run build (plugins/chroma-parent-portal/build-env)`: passed_with_warnings - Build passed; Sass legacy API warning and 490.js asset size warning
- `npm install && npm run lint:js (plugins/QA-Report-App/chroma-qa-reports/build-env)`: passed - JS lint passed after installing lockfile-described dependencies
- `npm run lint:css (plugins/QA-Report-App/chroma-qa-reports/build-env)`: passed - CSS lint passed
- `npm run build (plugins/QA-Report-App/chroma-qa-reports/build-env)`: passed_with_warnings - Build passed; cqa-ui-vendor chunk size warning
- `npm test -- --runInBand (plugins/QA-Report-App/chroma-qa-reports)`: passed - 2 suites, 15 tests passed
- `node --check on owned source JS excluding dependency backups/build artifacts`: passed - 101 source JS files checked, 0 failures
- `Theme asset manifest validation`: passed - 8 entries checked, 0 missing referenced files
- `Callback-aware AJAX scanner`: reviewed - 76 AJAX hooks found; 7 candidates reviewed as delegated permission/OAuth-state callbacks; no confirmed unpatched gap
- `REST route scanner`: reviewed - 32 route patterns found by scanner; parent portal debug routes hardened; remaining public flags reviewed as scanner limitations or intentional public routes

## Package Audit Summary

| Package | Total | Critical | High | Moderate | Low | Status |
| --- | ---: | ---: | ---: | ---: | ---: | --- |
| `chroma-excellence-theme` | 0 | 0 | 0 | 0 | 0 | clean |
| `plugins/chroma-school-dashboard` | 0 | 0 | 0 | 0 | 0 | clean |
| `director-portal` | 5 | 0 | 4 | 1 | 0 | remaining_major_upgrade_required |
| `plugins/QA-Report-App/chroma-qa-reports` | 4 | 0 | 0 | 0 | 4 | remaining_major_upgrade_required |
| `plugins/QA-Report-App/chroma-qa-reports/build-env` | 26 | 0 | 16 | 3 | 7 | remaining_major_upgrade_required |
| `plugins/chroma-parent-portal/build-env` | 30 | 0 | 20 | 3 | 7 | remaining_major_upgrade_required |
| `lighthouse-automation` | 1 | 0 | 0 | 1 | 0 | remaining_major_upgrade_required |

## Reconciled Prior Findings

- Prior first-pass reports still mention `hero-classroom.mp4`, but current runtime source no longer references it. Remaining mentions are historical docs/sample/report artifacts only.
- Parent portal debug REST routes are no longer public.
- Composer license warnings are resolved for all tracked Composer manifests.
- Director portal lint/build is now executable from committed package metadata and config.
- QA JS lint is green after `npm install` refreshes lockfile-described dependencies; generated `node_modules` churn was intentionally not committed.

## Runtime Pending Checks

- `chroma-agent/v1` discovery/resources and representative dry-run writes.
- SEO virtual page title/meta, canonical, OpenGraph, sitemap inclusion, and Spanish override rendering.
- Portal login/content/taxonomy/bulk import flows and family PIN flows.
- School dashboard/TV endpoints, weather proxy, slideshow/announcement rendering, and browser console/network scans.
- Forms/leads submissions, webhook retry/export, and audit/snapshot behavior.
- Lighthouse/accessibility checks against sitemap samples.

## Changed Files

- `.gitignore`
- `chroma-excellence-theme/package-lock.json`
- `chroma-plugins/chroma-acquisitions-form/chroma-acquisitions-form.php`
- `chroma-plugins/chroma-career-form/chroma-career-form.php`
- `chroma-plugins/chroma-contact-form/chroma-contact-form.php`
- `chroma-plugins/chroma-lead-log/chroma-lead-log.php`
- `director-portal/app/page.tsx`
- `director-portal/next-env.d.ts`
- `director-portal/package-lock.json`
- `director-portal/package.json`
- `lighthouse-automation/package-lock.json`
- `plugins/QA-Report-App/chroma-qa-reports/build-env/package-lock.json`
- `plugins/QA-Report-App/chroma-qa-reports/composer.json`
- `plugins/QA-Report-App/chroma-qa-reports/includes/ai/class-photo-analyzer.php`
- `plugins/QA-Report-App/chroma-qa-reports/package-lock.json`
- `plugins/chroma-parent-portal/build-env/package-lock.json`
- `plugins/chroma-parent-portal/includes/class-api-routes.php`
- `plugins/chroma-school-dashboard/package-lock.json`
- `plugins/chroma-school-dashboard/package.json`
- `plugins/chroma-seo-pro-reset/composer.json`
- `plugins/chroma-seo-pro-reset/inc/class-careers-api.php`
- `plugins/chroma-seo-pro/composer.json`

## Untracked Workspace Items

- `chromaela.html` - classified as scratch/ambiguous and left untouched
- `diff.txt` - classified as scratch/ambiguous and left untouched
- `diff_cleanup.txt` - classified as scratch/ambiguous and left untouched
- `director-portal/.eslintrc.json` - classified as scratch/ambiguous and left untouched
- `full_diff.txt` - classified as scratch/ambiguous and left untouched
- `old_cleanup.php` - classified as scratch/ambiguous and left untouched
- `old_enforcer.php` - classified as scratch/ambiguous and left untouched
- `temp_canonical.txt` - classified as scratch/ambiguous and left untouched
