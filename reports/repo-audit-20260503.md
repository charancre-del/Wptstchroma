# Whole-Repo Audit Report - 20260503

## Executive Summary

- Branch: `claude/create-wordpress-theme-01N9YNziMjoPyBiwj3iLobdB`
- Commit: `b370a2d7bc85bd56fd4988a6edec0767138b9b0f`
- Tracked files: 90774
- Owned files audited: 834
- Owned code files inventoried: 550
- Untracked files inventoried: 17

- Findings: 75 total; high: 6, medium: 43, low: 26


This pass reflects the roadmap implementation verification. Source fixes were applied separately; this report keeps runtime/browser checks pending where no WordPress URL or WP-CLI is available.


## Command Evidence

- PHP CLI: available. PHP files checked: 328. PHP lint failures: 0.

- Node CLI: available. Plain JS/MJS syntax checks were run outside generated/dependency folders.

- WP-CLI/runtime: not available.


## Findings By Severity


### Critical (0)

None.


### High (6)

- **AUD-0001 [broken-code / js-syntax]** chroma-excellence-theme/assets/js/admin.2de473bb488f.js

  - Status: `confirmed`

  - Evidence: node:internal/modules/cjs/loader:1423   throw err;   ^  Error: Cannot find module 'C:\Users\chara\Documents\wptheme\Wptstchroma\chroma-excellence-theme\assets\js\admin.2de473bb488f.js'     at node:internal/modules/cjs/loader:1420:15     at node:internal/main/check_syntax:33:20 {   code: 'MODULE_NOT_FOUND',   requireStack: [] }  Node.js v25.2.1

  - Repro: `node --check "chroma-excellence-theme/assets/js/admin.2de473bb488f.js"`

  - Recommendation: Fix JavaScript syntax or document parser requirements.

- **AUD-0002 [security / ajax-nonce]** plugins/chroma-parent-portal/includes/class-bulk-uploader.php:26

  - Status: `likely`

  - Evidence: AJAX action chroma_portal_bulk_run callback ajax_run has no nonce/state verification.

  - Repro: `Search wp_ajax_chroma_portal_bulk_run in plugins/chroma-parent-portal/includes/class-bulk-uploader.php`

  - Recommendation: Add nonce/state verification or prove read-only public behavior.

- **AUD-0003 [security / ajax-nonce]** plugins/chroma-parent-portal/includes/class-bulk-uploader.php:27

  - Status: `likely`

  - Evidence: AJAX action chroma_portal_bulk_rollback callback ajax_rollback has no nonce/state verification.

  - Repro: `Search wp_ajax_chroma_portal_bulk_rollback in plugins/chroma-parent-portal/includes/class-bulk-uploader.php`

  - Recommendation: Add nonce/state verification or prove read-only public behavior.

- **AUD-0004 [security / ajax-nonce]** plugins/chroma-parent-portal/includes/class-bulk-uploader.php:28

  - Status: `likely`

  - Evidence: AJAX action chroma_portal_bulk_assign_files callback ajax_assign_files has no nonce/state verification.

  - Repro: `Search wp_ajax_chroma_portal_bulk_assign_files in plugins/chroma-parent-portal/includes/class-bulk-uploader.php`

  - Recommendation: Add nonce/state verification or prove read-only public behavior.

- **AUD-0005 [security / ajax-capability]** plugins/chroma-seo-pro-reset/inc/class-llm-bulk-processor.php:26

  - Status: `likely`

  - Evidence: AJAX action chroma_bulk_generate_status callback ajax_get_status has no capability/public-gate check.

  - Repro: `Search wp_ajax_chroma_bulk_generate_status in plugins/chroma-seo-pro-reset/inc/class-llm-bulk-processor.php`

  - Recommendation: Add capability checks in privileged AJAX callbacks.

- **AUD-0006 [security / ajax-capability]** plugins/chroma-seo-pro-reset/inc/seo-automations/class-combo-ai-generator.php:21

  - Status: `likely`

  - Evidence: AJAX action chroma_combo_get_data callback ajax_get_data has no capability/public-gate check.

  - Repro: `Search wp_ajax_chroma_combo_get_data in plugins/chroma-seo-pro-reset/inc/seo-automations/class-combo-ai-generator.php`

  - Recommendation: Add capability checks in privileged AJAX callbacks.


### Medium (43)

- **AUD-0007 [animation-ux / intersection-observer]** chroma-excellence-theme/assets/js/main.6c57b1ceef20.js:3

  - Status: `likely`

  - Evidence: Animation/observer behavior found without local reduced-motion fallback.

  - Repro: `Search animation`

  - Recommendation: Verify global reduced-motion behavior or add local fallback.

- **AUD-0008 [animation-ux / intersection-observer]** chroma-excellence-theme/assets/js/main.js:3

  - Status: `likely`

  - Evidence: Animation/observer behavior found without local reduced-motion fallback.

  - Repro: `Search animation`

  - Recommendation: Verify global reduced-motion behavior or add local fallback.

- **AUD-0009 [animation-ux / intersection-observer]** chroma-excellence-theme/assets/js/map-facade.fba2ea596ead.js:35

  - Status: `likely`

  - Evidence: Animation/observer behavior found without local reduced-motion fallback.

  - Repro: `Search animation`

  - Recommendation: Verify global reduced-motion behavior or add local fallback.

- **AUD-0010 [animation-ux / intersection-observer]** chroma-excellence-theme/assets/js/map-facade.js:35

  - Status: `likely`

  - Evidence: Animation/observer behavior found without local reduced-motion fallback.

  - Repro: `Search animation`

  - Recommendation: Verify global reduced-motion behavior or add local fallback.

- **AUD-0011 [animation-ux / keyframes]** chroma-excellence-theme/purged-analysis.css:1140

  - Status: `likely`

  - Evidence: Animation/observer behavior found without local reduced-motion fallback.

  - Repro: `Search animation`

  - Recommendation: Verify global reduced-motion behavior or add local fallback.

- **AUD-0012 [animation-ux / css-animation]** chroma-excellence-theme/purged-analysis.css:1155

  - Status: `likely`

  - Evidence: Animation/observer behavior found without local reduced-motion fallback.

  - Repro: `Search animation`

  - Recommendation: Verify global reduced-motion behavior or add local fallback.

- **AUD-0013 [animation-ux / css-animation]** plugins/chroma-parent-portal/build-env/src/components/MainLayout.jsx:56

  - Status: `likely`

  - Evidence: Animation/observer behavior found without local reduced-motion fallback.

  - Repro: `Search animation`

  - Recommendation: Verify global reduced-motion behavior or add local fallback.

- **AUD-0014 [animation-ux / keyframes]** plugins/chroma-parent-portal/build-env/src/components/MainLayout.jsx:59

  - Status: `likely`

  - Evidence: Animation/observer behavior found without local reduced-motion fallback.

  - Repro: `Search animation`

  - Recommendation: Verify global reduced-motion behavior or add local fallback.

- **AUD-0015 [animation-ux / css-animation]** plugins/chroma-parent-portal/build-env/src/components/dashboard/FeedbackSection.jsx:39

  - Status: `likely`

  - Evidence: Animation/observer behavior found without local reduced-motion fallback.

  - Repro: `Search animation`

  - Recommendation: Verify global reduced-motion behavior or add local fallback.

- **AUD-0016 [animation-ux / intersection-observer]** plugins/chroma-school-dashboard/assets/dist/portal.js:1

  - Status: `likely`

  - Evidence: Animation/observer behavior found without local reduced-motion fallback.

  - Repro: `Search animation`

  - Recommendation: Verify global reduced-motion behavior or add local fallback.

- **AUD-0017 [animation-ux / intersection-observer]** plugins/chroma-school-dashboard/assets/src/portal.jsx:216

  - Status: `likely`

  - Evidence: Animation/observer behavior found without local reduced-motion fallback.

  - Repro: `Search animation`

  - Recommendation: Verify global reduced-motion behavior or add local fallback.

- **AUD-0018 [missing-item / zero-byte-file]** chroma-excellence-theme/page-schedule-tour.php.backup

  - Status: `confirmed`

  - Evidence: Tracked file is zero bytes.

  - Repro: `Get-Item "chroma-excellence-theme/page-schedule-tour.php.backup"`

  - Recommendation: Remove, restore content, or document why this placeholder is intentionally shipped.

- **AUD-0019 [runtime-pending / wordpress-runtime]** workspace

  - Status: `pending`

  - Evidence: wp-cli not available; live route/theme/plugin validation was not executable in this workspace.

  - Repro: `wp --info`

  - Recommendation: Run pending runtime checklist against local/staging WordPress URL.

- **AUD-0020 [security / remote-request-validation]** chroma-excellence-theme/fetch_jobs_wp.php

  - Status: `likely`

  - Evidence: Outbound HTTP request found without obvious URL validation in same file.

  - Repro: `Search wp_remote_* in chroma-excellence-theme/fetch_jobs_wp.php`

  - Recommendation: Confirm SSRF-safe URL validation or add allowlist/validation.

- **AUD-0021 [security / sql-prepare]** plugins/QA-Report-App/chroma-qa-reports/includes/ai/class-comparative-insights.php

  - Status: `likely`

  - Evidence: Direct $wpdb read/write usage found but no prepare() call in file.

  - Repro: `Search $wpdb in plugins/QA-Report-App/chroma-qa-reports/includes/ai/class-comparative-insights.php`

  - Recommendation: Review SQL calls for safe preparation/escaping.

- **AUD-0022 [security / sql-prepare]** plugins/QA-Report-App/chroma-qa-reports/includes/ai/class-executive-summary.php

  - Status: `likely`

  - Evidence: Direct $wpdb read/write usage found but no prepare() call in file.

  - Repro: `Search $wpdb in plugins/QA-Report-App/chroma-qa-reports/includes/ai/class-executive-summary.php`

  - Recommendation: Review SQL calls for safe preparation/escaping.

- **AUD-0023 [security / remote-request-validation]** plugins/QA-Report-App/chroma-qa-reports/includes/ai/class-gemini-service.php

  - Status: `likely`

  - Evidence: Outbound HTTP request found without obvious URL validation in same file.

  - Repro: `Search wp_remote_* in plugins/QA-Report-App/chroma-qa-reports/includes/ai/class-gemini-service.php`

  - Recommendation: Confirm SSRF-safe URL validation or add allowlist/validation.

- **AUD-0024 [security / remote-request-validation]** plugins/QA-Report-App/chroma-qa-reports/includes/ai/class-photo-analyzer.php

  - Status: `likely`

  - Evidence: Outbound HTTP request found without obvious URL validation in same file.

  - Repro: `Search wp_remote_* in plugins/QA-Report-App/chroma-qa-reports/includes/ai/class-photo-analyzer.php`

  - Recommendation: Confirm SSRF-safe URL validation or add allowlist/validation.

- **AUD-0025 [security / sql-prepare]** plugins/QA-Report-App/chroma-qa-reports/includes/class-plugin.php

  - Status: `likely`

  - Evidence: Direct $wpdb read/write usage found but no prepare() call in file.

  - Repro: `Search $wpdb in plugins/QA-Report-App/chroma-qa-reports/includes/class-plugin.php`

  - Recommendation: Review SQL calls for safe preparation/escaping.

- **AUD-0026 [security / remote-request-validation]** plugins/QA-Report-App/chroma-qa-reports/includes/integrations/class-google-drive.php

  - Status: `likely`

  - Evidence: Outbound HTTP request found without obvious URL validation in same file.

  - Repro: `Search wp_remote_* in plugins/QA-Report-App/chroma-qa-reports/includes/integrations/class-google-drive.php`

  - Recommendation: Confirm SSRF-safe URL validation or add allowlist/validation.

- **AUD-0027 [security / remote-request-validation]** plugins/QA-Report-App/chroma-qa-reports/includes/integrations/class-monday.php

  - Status: `likely`

  - Evidence: Outbound HTTP request found without obvious URL validation in same file.

  - Repro: `Search wp_remote_* in plugins/QA-Report-App/chroma-qa-reports/includes/integrations/class-monday.php`

  - Recommendation: Confirm SSRF-safe URL validation or add allowlist/validation.

- **AUD-0028 [security / sql-prepare]** plugins/QA-Report-App/chroma-qa-reports/includes/services/class-cleanup-service.php

  - Status: `likely`

  - Evidence: Direct $wpdb read/write usage found but no prepare() call in file.

  - Repro: `Search $wpdb in plugins/QA-Report-App/chroma-qa-reports/includes/services/class-cleanup-service.php`

  - Recommendation: Review SQL calls for safe preparation/escaping.

- **AUD-0029 [security / sql-prepare]** plugins/QA-Report-App/chroma-qa-reports/includes/utils/class-integrity-checker.php

  - Status: `likely`

  - Evidence: Direct $wpdb read/write usage found but no prepare() call in file.

  - Repro: `Search $wpdb in plugins/QA-Report-App/chroma-qa-reports/includes/utils/class-integrity-checker.php`

  - Recommendation: Review SQL calls for safe preparation/escaping.

- **AUD-0030 [security / remote-request-validation]** plugins/QA-Report-App/chroma-qa-reports/includes/utils/class-location.php

  - Status: `likely`

  - Evidence: Outbound HTTP request found without obvious URL validation in same file.

  - Repro: `Search wp_remote_* in plugins/QA-Report-App/chroma-qa-reports/includes/utils/class-location.php`

  - Recommendation: Confirm SSRF-safe URL validation or add allowlist/validation.

- **AUD-0031 [security / sql-prepare]** plugins/QA-Report-App/chroma-qa-reports/uninstall.php

  - Status: `likely`

  - Evidence: Direct $wpdb read/write usage found but no prepare() call in file.

  - Repro: `Search $wpdb in plugins/QA-Report-App/chroma-qa-reports/uninstall.php`

  - Recommendation: Review SQL calls for safe preparation/escaping.

- **AUD-0032 [security / sql-prepare]** plugins/QA-Report-App/cqa-doctor.php

  - Status: `likely`

  - Evidence: Direct $wpdb read/write usage found but no prepare() call in file.

  - Repro: `Search $wpdb in plugins/QA-Report-App/cqa-doctor.php`

  - Recommendation: Review SQL calls for safe preparation/escaping.

- **AUD-0033 [security / remote-request-validation]** plugins/QA-Report-App/cqa-doctor.php

  - Status: `likely`

  - Evidence: Outbound HTTP request found without obvious URL validation in same file.

  - Repro: `Search wp_remote_* in plugins/QA-Report-App/cqa-doctor.php`

  - Recommendation: Confirm SSRF-safe URL validation or add allowlist/validation.

- **AUD-0034 [security / remote-request-validation]** plugins/chroma-school-dashboard/inc/class-api-routes.php

  - Status: `likely`

  - Evidence: Outbound HTTP request found without obvious URL validation in same file.

  - Repro: `Search wp_remote_* in plugins/chroma-school-dashboard/inc/class-api-routes.php`

  - Recommendation: Confirm SSRF-safe URL validation or add allowlist/validation.

- **AUD-0035 [security / remote-request-validation]** plugins/chroma-school-dashboard/inc/class-weather.php

  - Status: `likely`

  - Evidence: Outbound HTTP request found without obvious URL validation in same file.

  - Repro: `Search wp_remote_* in plugins/chroma-school-dashboard/inc/class-weather.php`

  - Recommendation: Confirm SSRF-safe URL validation or add allowlist/validation.

- **AUD-0036 [security / remote-request-validation]** plugins/chroma-seo-pro-reset/inc/class-careers-api.php

  - Status: `likely`

  - Evidence: Outbound HTTP request found without obvious URL validation in same file.

  - Repro: `Search wp_remote_* in plugins/chroma-seo-pro-reset/inc/class-careers-api.php`

  - Recommendation: Confirm SSRF-safe URL validation or add allowlist/validation.

- **AUD-0037 [security / remote-request-validation]** plugins/chroma-seo-pro-reset/inc/class-google-places-client.php

  - Status: `likely`

  - Evidence: Outbound HTTP request found without obvious URL validation in same file.

  - Repro: `Search wp_remote_* in plugins/chroma-seo-pro-reset/inc/class-google-places-client.php`

  - Recommendation: Confirm SSRF-safe URL validation or add allowlist/validation.

- **AUD-0038 [security / sql-prepare]** plugins/chroma-seo-pro-reset/inc/class-seo-dashboard.php

  - Status: `likely`

  - Evidence: Direct $wpdb read/write usage found but no prepare() call in file.

  - Repro: `Search $wpdb in plugins/chroma-seo-pro-reset/inc/class-seo-dashboard.php`

  - Recommendation: Review SQL calls for safe preparation/escaping.

- **AUD-0039 [security / sql-prepare]** plugins/chroma-seo-pro-reset/inc/class-validation-logger.php

  - Status: `likely`

  - Evidence: Direct $wpdb read/write usage found but no prepare() call in file.

  - Repro: `Search $wpdb in plugins/chroma-seo-pro-reset/inc/class-validation-logger.php`

  - Recommendation: Review SQL calls for safe preparation/escaping.

- **AUD-0040 [security / sql-prepare]** plugins/chroma-seo-pro-reset/schema-cleanup.php

  - Status: `likely`

  - Evidence: Direct $wpdb read/write usage found but no prepare() call in file.

  - Repro: `Search $wpdb in plugins/chroma-seo-pro-reset/schema-cleanup.php`

  - Recommendation: Review SQL calls for safe preparation/escaping.

- **AUD-0041 [workspace-hygiene / untracked-file]** chroma-excellence-theme/assets/css/main.b991d09993c1.css

  - Status: `cleanup`

  - Evidence: Untracked file, 239658 bytes.

  - Repro: `git status --short`

  - Recommendation: Classify as commit-worthy, ignore, archive, or delete separately.

- **AUD-0042 [workspace-hygiene / untracked-file]** chromaela.html

  - Status: `cleanup`

  - Evidence: Untracked file, 661999 bytes.

  - Repro: `git status --short`

  - Recommendation: Classify as commit-worthy, ignore, archive, or delete separately.

- **AUD-0043 [workspace-hygiene / standalone-wp-loader]** debug-meta.php:3

  - Status: `cleanup`

  - Evidence: Standalone/debug script references wp-load.php, absent in extracted workspace.

  - Repro: `Search include in debug-meta.php`

  - Recommendation: Run only from a WordPress root or move/debug-ignore this helper.

- **AUD-0044 [workspace-hygiene / standalone-wp-loader]** diag-rewrite.php:3

  - Status: `cleanup`

  - Evidence: Standalone/debug script references wp-load.php, absent in extracted workspace.

  - Repro: `Search include in diag-rewrite.php`

  - Recommendation: Run only from a WordPress root or move/debug-ignore this helper.

- **AUD-0045 [workspace-hygiene / untracked-file]** full_diff.txt

  - Status: `cleanup`

  - Evidence: Untracked file, 8720444 bytes.

  - Repro: `git status --short`

  - Recommendation: Classify as commit-worthy, ignore, archive, or delete separately.

- **AUD-0046 [workspace-hygiene / standalone-wp-loader]** plugins/QA-Report-App/cqa-doctor.php:6

  - Status: `cleanup`

  - Evidence: Standalone/debug script references plugins/QA-Report-App/wp-load.php, absent in extracted workspace.

  - Repro: `Search include in plugins/QA-Report-App/cqa-doctor.php`

  - Recommendation: Run only from a WordPress root or move/debug-ignore this helper.

- **AUD-0047 [workspace-hygiene / standalone-wp-loader]** purge_caches.php:2

  - Status: `cleanup`

  - Evidence: Standalone/debug script references wp-load.php, absent in extracted workspace.

  - Repro: `Search include in purge_caches.php`

  - Recommendation: Run only from a WordPress root or move/debug-ignore this helper.

- **AUD-0048 [workspace-hygiene / untracked-file]** reports/repo-audit-inventory-20260503.csv

  - Status: `cleanup`

  - Evidence: Untracked file, 188920 bytes.

  - Repro: `git status --short`

  - Recommendation: Classify as commit-worthy, ignore, archive, or delete separately.

- **AUD-0049 [workspace-hygiene / standalone-wp-loader]** test_sitemap_fetch2.php:2

  - Status: `cleanup`

  - Evidence: Standalone/debug script references wp-load.php, absent in extracted workspace.

  - Repro: `Search include in test_sitemap_fetch2.php`

  - Recommendation: Run only from a WordPress root or move/debug-ignore this helper.


### Low (26)

- **AUD-0050 [docs-drift / documented-asset]** AUDIT-2-IMAGE-LOADING-ISSUES.md:215

  - Status: `cleanup`

  - Evidence: Asset not found: ['assets/video/hero-classroom.mp4']

  - Repro: `Search asset reference in AUDIT-2-IMAGE-LOADING-ISSUES.md`

  - Recommendation: Restore/build asset or update reference.

- **AUD-0051 [docs-drift / documented-asset]** COMPREHENSIVE-FIX-PLAN.md:208

  - Status: `cleanup`

  - Evidence: Asset not found: ['assets/video/hero-classroom.mp4']

  - Repro: `Search asset reference in COMPREHENSIVE-FIX-PLAN.md`

  - Recommendation: Restore/build asset or update reference.

- **AUD-0052 [workspace-hygiene / untracked-file]** chroma-excellence-theme/assets/css/font-awesome-subset.b9d3a786ad85.css

  - Status: `cleanup`

  - Evidence: Untracked file, 10129 bytes.

  - Repro: `git status --short`

  - Recommendation: Classify as commit-worthy, ignore, archive, or delete separately.

- **AUD-0053 [workspace-hygiene / untracked-file]** chroma-excellence-theme/assets/css/page-effects.bc9294bb75ba.css

  - Status: `cleanup`

  - Evidence: Untracked file, 540 bytes.

  - Repro: `git status --short`

  - Recommendation: Classify as commit-worthy, ignore, archive, or delete separately.

- **AUD-0054 [workspace-hygiene / untracked-file]** chroma-excellence-theme/assets/images/chroma-logo-highres.png

  - Status: `cleanup`

  - Evidence: Untracked file, 66121 bytes.

  - Repro: `git status --short`

  - Recommendation: Classify as commit-worthy, ignore, archive, or delete separately.

- **AUD-0055 [workspace-hygiene / untracked-file]** chroma-excellence-theme/assets/images/chroma-logo.png

  - Status: `cleanup`

  - Evidence: Untracked file, 66121 bytes.

  - Repro: `git status --short`

  - Recommendation: Classify as commit-worthy, ignore, archive, or delete separately.

- **AUD-0056 [workspace-hygiene / untracked-file]** chroma-excellence-theme/assets/js/admin.b457513d5358.js

  - Status: `cleanup`

  - Evidence: Untracked file, 4345 bytes.

  - Repro: `git status --short`

  - Recommendation: Classify as commit-worthy, ignore, archive, or delete separately.

- **AUD-0057 [workspace-hygiene / tracked-scratch]** chroma-excellence-theme/page-schedule-tour.php.backup

  - Status: `cleanup`

  - Evidence: Tracked file name looks like scratch/debug/backup artifact.

  - Repro: `git ls-files "chroma-excellence-theme/page-schedule-tour.php.backup"`

  - Recommendation: Review whether this should remain tracked, move to docs/tools, or ignore/remove later.

- **AUD-0058 [workspace-hygiene / tracked-scratch]** debug-meta.php

  - Status: `cleanup`

  - Evidence: Tracked file name looks like scratch/debug/backup artifact.

  - Repro: `git ls-files "debug-meta.php"`

  - Recommendation: Review whether this should remain tracked, move to docs/tools, or ignore/remove later.

- **AUD-0059 [workspace-hygiene / tracked-scratch]** debug_translation.php

  - Status: `cleanup`

  - Evidence: Tracked file name looks like scratch/debug/backup artifact.

  - Repro: `git ls-files "debug_translation.php"`

  - Recommendation: Review whether this should remain tracked, move to docs/tools, or ignore/remove later.

- **AUD-0060 [workspace-hygiene / tracked-scratch]** diag-edit-screen.php

  - Status: `cleanup`

  - Evidence: Tracked file name looks like scratch/debug/backup artifact.

  - Repro: `git ls-files "diag-edit-screen.php"`

  - Recommendation: Review whether this should remain tracked, move to docs/tools, or ignore/remove later.

- **AUD-0061 [workspace-hygiene / tracked-scratch]** diag-portal.php

  - Status: `cleanup`

  - Evidence: Tracked file name looks like scratch/debug/backup artifact.

  - Repro: `git ls-files "diag-portal.php"`

  - Recommendation: Review whether this should remain tracked, move to docs/tools, or ignore/remove later.

- **AUD-0062 [workspace-hygiene / tracked-scratch]** diag-rewrite.php

  - Status: `cleanup`

  - Evidence: Tracked file name looks like scratch/debug/backup artifact.

  - Repro: `git ls-files "diag-rewrite.php"`

  - Recommendation: Review whether this should remain tracked, move to docs/tools, or ignore/remove later.

- **AUD-0063 [workspace-hygiene / tracked-scratch]** diag.php

  - Status: `cleanup`

  - Evidence: Tracked file name looks like scratch/debug/backup artifact.

  - Repro: `git ls-files "diag.php"`

  - Recommendation: Review whether this should remain tracked, move to docs/tools, or ignore/remove later.

- **AUD-0064 [workspace-hygiene / untracked-file]** diff.txt

  - Status: `cleanup`

  - Evidence: Untracked file, 32458 bytes.

  - Repro: `git status --short`

  - Recommendation: Classify as commit-worthy, ignore, archive, or delete separately.

- **AUD-0065 [workspace-hygiene / untracked-file]** diff_cleanup.txt

  - Status: `cleanup`

  - Evidence: Untracked file, 32830 bytes.

  - Repro: `git status --short`

  - Recommendation: Classify as commit-worthy, ignore, archive, or delete separately.

- **AUD-0066 [workspace-hygiene / tracked-scratch]** inspect_meta.php

  - Status: `cleanup`

  - Evidence: Tracked file name looks like scratch/debug/backup artifact.

  - Repro: `git ls-files "inspect_meta.php"`

  - Recommendation: Review whether this should remain tracked, move to docs/tools, or ignore/remove later.

- **AUD-0067 [workspace-hygiene / untracked-file]** old_cleanup.php

  - Status: `cleanup`

  - Evidence: Untracked file, 22176 bytes.

  - Repro: `git status --short`

  - Recommendation: Classify as commit-worthy, ignore, archive, or delete separately.

- **AUD-0068 [workspace-hygiene / tracked-scratch]** old_controller.php

  - Status: `cleanup`

  - Evidence: Tracked file name looks like scratch/debug/backup artifact.

  - Repro: `git ls-files "old_controller.php"`

  - Recommendation: Review whether this should remain tracked, move to docs/tools, or ignore/remove later.

- **AUD-0069 [workspace-hygiene / untracked-file]** old_enforcer.php

  - Status: `cleanup`

  - Evidence: Untracked file, 20318 bytes.

  - Repro: `git status --short`

  - Recommendation: Classify as commit-worthy, ignore, archive, or delete separately.

- **AUD-0070 [workspace-hygiene / tracked-scratch]** old_login.php

  - Status: `cleanup`

  - Evidence: Tracked file name looks like scratch/debug/backup artifact.

  - Repro: `git ls-files "old_login.php"`

  - Recommendation: Review whether this should remain tracked, move to docs/tools, or ignore/remove later.

- **AUD-0071 [workspace-hygiene / tracked-scratch]** plugins/QA-Report-App/debug-api-test.php

  - Status: `cleanup`

  - Evidence: Tracked file name looks like scratch/debug/backup artifact.

  - Repro: `git ls-files "plugins/QA-Report-App/debug-api-test.php"`

  - Recommendation: Review whether this should remain tracked, move to docs/tools, or ignore/remove later.

- **AUD-0072 [workspace-hygiene / untracked-file]** reports/repo-audit-fix-roadmap-20260503.md

  - Status: `cleanup`

  - Evidence: Untracked file, 22670 bytes.

  - Repro: `git status --short`

  - Recommendation: Classify as commit-worthy, ignore, archive, or delete separately.

- **AUD-0073 [workspace-hygiene / untracked-file]** temp_canonical.txt

  - Status: `cleanup`

  - Evidence: Untracked file, 20318 bytes.

  - Repro: `git status --short`

  - Recommendation: Classify as commit-worthy, ignore, archive, or delete separately.

- **AUD-0074 [workspace-hygiene / tracked-scratch]** temp_old_controller.php

  - Status: `cleanup`

  - Evidence: Tracked file name looks like scratch/debug/backup artifact.

  - Repro: `git ls-files "temp_old_controller.php"`

  - Recommendation: Review whether this should remain tracked, move to docs/tools, or ignore/remove later.

- **AUD-0075 [workspace-hygiene / tracked-scratch]** test_sitemap_fetch2.php

  - Status: `cleanup`

  - Evidence: Tracked file name looks like scratch/debug/backup artifact.

  - Repro: `git ls-files "test_sitemap_fetch2.php"`

  - Recommendation: Review whether this should remain tracked, move to docs/tools, or ignore/remove later.


## Category Summary

- workspace-hygiene: 33

- security: 26

- animation-ux: 11

- docs-drift: 2

- broken-code: 1

- missing-item: 1

- runtime-pending: 1


## Pending Runtime Checklist
- Start or provide a WordPress URL for full browser/runtime validation.
- Verify homepage, page templates, program/location/city pages, virtual pages, portals, school dashboards, forms/leads, Agent API discovery/resources, sitemaps, schema, and translations.


## Deliverables
- `reports/repo-audit-20260503.json`
- `reports/repo-audit-inventory-20260503.csv`
- `reports/repo-audit-fix-roadmap-20260503.md`
