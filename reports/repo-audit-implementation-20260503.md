# Repo Audit Roadmap Implementation - 2026-05-03

## Executive Summary

Implemented the confirmed high-priority roadmap items that could be source-verified without a running WordPress instance:

- Restored missing theme logo assets referenced by shipped code.
- Removed the missing homepage hero MP4 dependency and replaced it with an existing bundled classroom image fallback.
- Added explicit nonce wiring to the parent portal seed importer AJAX flow.
- Hardened SEO AJAX status/data endpoints with the same admin capability checks used by related write actions.
- Added reduced-motion support across the theme, school dashboard, parent portal, and QA uploader/admin surfaces.
- Rebuilt generated front-end assets for the changed theme/plugin bundles.

## Fixed Findings

### RA-H001: Missing theme logo assets

- Severity: High
- Status: Fixed
- Evidence: `chroma-logo.png` and `chroma-logo-highres.png` now exist under `chroma-excellence-theme/assets/images/`.
- Verification: `Get-Item chroma-excellence-theme\assets\images\chroma-logo.png, chroma-excellence-theme\assets\images\chroma-logo-highres.png`

### RA-H002: Missing homepage hero video asset

- Severity: High
- Status: Fixed
- Evidence: `template-parts/home/hero.php` now renders `assets/images/early-start/synergy-classroom.jpg` when no Customizer hero image is configured.
- Verification: `git grep -n "hero-classroom\.mp4" -- chroma-excellence-theme plugins reports` returns no tracked source matches.

### RA-H003: Parent portal seed importer AJAX nonce gap

- Severity: High
- Status: Fixed
- Evidence: `class-bulk-importer.php` now creates and sends a dedicated `chroma_portal_bulk_importer_nonce` token for scan/import actions and verifies it server-side before the existing `manage_options` check.
- Verification: callback-aware AJAX scan reports nonce plus capability checks on `wp_ajax_chroma_portal_run_seed`.

### RA-H004: SEO AJAX capability gaps

- Severity: High
- Status: Fixed
- Evidence: `Chroma_LLM_Bulk_Processor::ajax_get_status()` and `Chroma_Combo_AI_Generator::ajax_get_data()` now require `manage_options`, matching their related start/save/generate operations.
- Verification: callback-aware AJAX scan reports `0` review items across `76` tracked AJAX hooks.

### RA-M001: Reduced-motion handling

- Severity: Medium
- Status: Fixed
- Evidence: added `prefers-reduced-motion: reduce` handling to theme CSS, school dashboard CSS, QA admin/uploader CSS, and parent portal Framer Motion/CSS.
- Verification: builds completed successfully for the theme, school dashboard, and parent portal.

## Verification Results

- PHP syntax: passed for 336 tracked PHP files outside `vendor` and `node_modules`.
- Theme build: passed via `npm run build` in `chroma-excellence-theme`.
- School dashboard build: passed via `npm run build` in `plugins/chroma-school-dashboard`.
- Parent portal build: passed via `npm run build` in `plugins/chroma-parent-portal/build-env`.
- QA React build: passed via `npm run build` in `plugins/QA-Report-App/chroma-qa-reports/build-env`.
- QA Jest tests: passed, 2 suites and 15 tests, after restoring root test dependencies with `npm install`.
- AJAX security scan: passed, 76 hooks checked, 0 review items.
- Composer validation: passed for all three manifests with non-fatal missing-license warnings.
- QA JS lint: passed via `npm run lint:js` in `plugins/QA-Report-App/chroma-qa-reports/build-env`.
- QA CSS lint: passed via `npm run lint:css` in `plugins/QA-Report-App/chroma-qa-reports/build-env`.

## Remaining Non-Green/Pending Items

### Composer validation

- Status: Passed
- Evidence: installed workspace-local Composer 2.9.7 at `tools/composer.phar`; `composer validate --no-check-publish` passes for `plugins/chroma-seo-pro`, `plugins/chroma-seo-pro-reset`, and `plugins/QA-Report-App/chroma-qa-reports`.
- Remaining warning: each manifest omits a `license` field. For closed-source code, Composer recommends `proprietary`.

### WordPress runtime/browser checks

- Status: Pending runtime
- Evidence: no local WordPress URL or WP-CLI bootstrap is available in this workspace.
- Next step: validate theme boot, plugin boot, REST discovery/resources, virtual SEO pages, sitemaps, forms, portal views, school TV payloads, and browser interactions against a configured WordPress site.

### Build warnings

- Status: Non-failing warnings
- Evidence: theme/school builds warn about stale Browserslist data; parent portal and QA React builds warn about large vendor chunks. Parent portal Sass `lighten()` deprecation was fixed; the remaining Sass warning comes from the loader legacy API.
- Next step: update browser data/dependencies in a dependency-maintenance pass and evaluate vendor chunk splitting/performance budgets.
