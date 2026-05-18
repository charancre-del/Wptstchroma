# Repo Audit Fix Roadmap - 20260503

Branch: `claude/create-wordpress-theme-01N9YNziMjoPyBiwj3iLobdB`  Commit: `b370a2d7bc85bd56fd4988a6edec0767138b9b0f`

## Phase 1 - Fatal Errors And Missing Runtime Assets

- [HIGH] AUD-0001 broken-code / js-syntax - chroma-excellence-theme/assets/js/admin.2de473bb488f.js: Fix JavaScript syntax or document parser requirements.

## Phase 2 - APIs, Security, SEO, Schema, Runtime

- [HIGH] AUD-0002 security / ajax-nonce - plugins/chroma-parent-portal/includes/class-bulk-uploader.php:26: Add nonce/state verification or prove read-only public behavior.

- [HIGH] AUD-0003 security / ajax-nonce - plugins/chroma-parent-portal/includes/class-bulk-uploader.php:27: Add nonce/state verification or prove read-only public behavior.

- [HIGH] AUD-0004 security / ajax-nonce - plugins/chroma-parent-portal/includes/class-bulk-uploader.php:28: Add nonce/state verification or prove read-only public behavior.

- [HIGH] AUD-0005 security / ajax-capability - plugins/chroma-seo-pro-reset/inc/class-llm-bulk-processor.php:26: Add capability checks in privileged AJAX callbacks.

- [HIGH] AUD-0006 security / ajax-capability - plugins/chroma-seo-pro-reset/inc/seo-automations/class-combo-ai-generator.php:21: Add capability checks in privileged AJAX callbacks.

- [MEDIUM] AUD-0019 runtime-pending / wordpress-runtime - workspace: Run pending runtime checklist against local/staging WordPress URL.

- [MEDIUM] AUD-0020 security / remote-request-validation - chroma-excellence-theme/fetch_jobs_wp.php: Confirm SSRF-safe URL validation or add allowlist/validation.

- [MEDIUM] AUD-0021 security / sql-prepare - plugins/QA-Report-App/chroma-qa-reports/includes/ai/class-comparative-insights.php: Review SQL calls for safe preparation/escaping.

- [MEDIUM] AUD-0022 security / sql-prepare - plugins/QA-Report-App/chroma-qa-reports/includes/ai/class-executive-summary.php: Review SQL calls for safe preparation/escaping.

- [MEDIUM] AUD-0023 security / remote-request-validation - plugins/QA-Report-App/chroma-qa-reports/includes/ai/class-gemini-service.php: Confirm SSRF-safe URL validation or add allowlist/validation.

- [MEDIUM] AUD-0024 security / remote-request-validation - plugins/QA-Report-App/chroma-qa-reports/includes/ai/class-photo-analyzer.php: Confirm SSRF-safe URL validation or add allowlist/validation.

- [MEDIUM] AUD-0025 security / sql-prepare - plugins/QA-Report-App/chroma-qa-reports/includes/class-plugin.php: Review SQL calls for safe preparation/escaping.

- [MEDIUM] AUD-0026 security / remote-request-validation - plugins/QA-Report-App/chroma-qa-reports/includes/integrations/class-google-drive.php: Confirm SSRF-safe URL validation or add allowlist/validation.

- [MEDIUM] AUD-0027 security / remote-request-validation - plugins/QA-Report-App/chroma-qa-reports/includes/integrations/class-monday.php: Confirm SSRF-safe URL validation or add allowlist/validation.

- [MEDIUM] AUD-0028 security / sql-prepare - plugins/QA-Report-App/chroma-qa-reports/includes/services/class-cleanup-service.php: Review SQL calls for safe preparation/escaping.

- [MEDIUM] AUD-0029 security / sql-prepare - plugins/QA-Report-App/chroma-qa-reports/includes/utils/class-integrity-checker.php: Review SQL calls for safe preparation/escaping.

- [MEDIUM] AUD-0030 security / remote-request-validation - plugins/QA-Report-App/chroma-qa-reports/includes/utils/class-location.php: Confirm SSRF-safe URL validation or add allowlist/validation.

- [MEDIUM] AUD-0031 security / sql-prepare - plugins/QA-Report-App/chroma-qa-reports/uninstall.php: Review SQL calls for safe preparation/escaping.

- [MEDIUM] AUD-0032 security / sql-prepare - plugins/QA-Report-App/cqa-doctor.php: Review SQL calls for safe preparation/escaping.

- [MEDIUM] AUD-0033 security / remote-request-validation - plugins/QA-Report-App/cqa-doctor.php: Confirm SSRF-safe URL validation or add allowlist/validation.

- [MEDIUM] AUD-0034 security / remote-request-validation - plugins/chroma-school-dashboard/inc/class-api-routes.php: Confirm SSRF-safe URL validation or add allowlist/validation.

- [MEDIUM] AUD-0035 security / remote-request-validation - plugins/chroma-school-dashboard/inc/class-weather.php: Confirm SSRF-safe URL validation or add allowlist/validation.

- [MEDIUM] AUD-0036 security / remote-request-validation - plugins/chroma-seo-pro-reset/inc/class-careers-api.php: Confirm SSRF-safe URL validation or add allowlist/validation.

- [MEDIUM] AUD-0037 security / remote-request-validation - plugins/chroma-seo-pro-reset/inc/class-google-places-client.php: Confirm SSRF-safe URL validation or add allowlist/validation.

- [MEDIUM] AUD-0038 security / sql-prepare - plugins/chroma-seo-pro-reset/inc/class-seo-dashboard.php: Review SQL calls for safe preparation/escaping.

- [MEDIUM] AUD-0039 security / sql-prepare - plugins/chroma-seo-pro-reset/inc/class-validation-logger.php: Review SQL calls for safe preparation/escaping.

- [MEDIUM] AUD-0040 security / sql-prepare - plugins/chroma-seo-pro-reset/schema-cleanup.php: Review SQL calls for safe preparation/escaping.

## Phase 3 - Animations, Responsive UX, Accessibility, Performance

- [MEDIUM] AUD-0007 animation-ux / intersection-observer - chroma-excellence-theme/assets/js/main.6c57b1ceef20.js:3: Verify global reduced-motion behavior or add local fallback.

- [MEDIUM] AUD-0008 animation-ux / intersection-observer - chroma-excellence-theme/assets/js/main.js:3: Verify global reduced-motion behavior or add local fallback.

- [MEDIUM] AUD-0009 animation-ux / intersection-observer - chroma-excellence-theme/assets/js/map-facade.fba2ea596ead.js:35: Verify global reduced-motion behavior or add local fallback.

- [MEDIUM] AUD-0010 animation-ux / intersection-observer - chroma-excellence-theme/assets/js/map-facade.js:35: Verify global reduced-motion behavior or add local fallback.

- [MEDIUM] AUD-0011 animation-ux / keyframes - chroma-excellence-theme/purged-analysis.css:1140: Verify global reduced-motion behavior or add local fallback.

- [MEDIUM] AUD-0012 animation-ux / css-animation - chroma-excellence-theme/purged-analysis.css:1155: Verify global reduced-motion behavior or add local fallback.

- [MEDIUM] AUD-0013 animation-ux / css-animation - plugins/chroma-parent-portal/build-env/src/components/MainLayout.jsx:56: Verify global reduced-motion behavior or add local fallback.

- [MEDIUM] AUD-0014 animation-ux / keyframes - plugins/chroma-parent-portal/build-env/src/components/MainLayout.jsx:59: Verify global reduced-motion behavior or add local fallback.

- [MEDIUM] AUD-0015 animation-ux / css-animation - plugins/chroma-parent-portal/build-env/src/components/dashboard/FeedbackSection.jsx:39: Verify global reduced-motion behavior or add local fallback.

- [MEDIUM] AUD-0016 animation-ux / intersection-observer - plugins/chroma-school-dashboard/assets/dist/portal.js:1: Verify global reduced-motion behavior or add local fallback.

- [MEDIUM] AUD-0017 animation-ux / intersection-observer - plugins/chroma-school-dashboard/assets/src/portal.jsx:216: Verify global reduced-motion behavior or add local fallback.

## Phase 4 - Cleanup, Tooling, Docs Drift, Workspace Hygiene

- [MEDIUM] AUD-0041 workspace-hygiene / untracked-file - chroma-excellence-theme/assets/css/main.b991d09993c1.css: Classify as commit-worthy, ignore, archive, or delete separately.

- [MEDIUM] AUD-0042 workspace-hygiene / untracked-file - chromaela.html: Classify as commit-worthy, ignore, archive, or delete separately.

- [MEDIUM] AUD-0043 workspace-hygiene / standalone-wp-loader - debug-meta.php:3: Run only from a WordPress root or move/debug-ignore this helper.

- [MEDIUM] AUD-0044 workspace-hygiene / standalone-wp-loader - diag-rewrite.php:3: Run only from a WordPress root or move/debug-ignore this helper.

- [MEDIUM] AUD-0045 workspace-hygiene / untracked-file - full_diff.txt: Classify as commit-worthy, ignore, archive, or delete separately.

- [MEDIUM] AUD-0046 workspace-hygiene / standalone-wp-loader - plugins/QA-Report-App/cqa-doctor.php:6: Run only from a WordPress root or move/debug-ignore this helper.

- [MEDIUM] AUD-0047 workspace-hygiene / standalone-wp-loader - purge_caches.php:2: Run only from a WordPress root or move/debug-ignore this helper.

- [MEDIUM] AUD-0048 workspace-hygiene / untracked-file - reports/repo-audit-inventory-20260503.csv: Classify as commit-worthy, ignore, archive, or delete separately.

- [MEDIUM] AUD-0049 workspace-hygiene / standalone-wp-loader - test_sitemap_fetch2.php:2: Run only from a WordPress root or move/debug-ignore this helper.

- [LOW] AUD-0050 docs-drift / documented-asset - AUDIT-2-IMAGE-LOADING-ISSUES.md:215: Restore/build asset or update reference.

- [LOW] AUD-0051 docs-drift / documented-asset - COMPREHENSIVE-FIX-PLAN.md:208: Restore/build asset or update reference.

- [LOW] AUD-0052 workspace-hygiene / untracked-file - chroma-excellence-theme/assets/css/font-awesome-subset.b9d3a786ad85.css: Classify as commit-worthy, ignore, archive, or delete separately.

- [LOW] AUD-0053 workspace-hygiene / untracked-file - chroma-excellence-theme/assets/css/page-effects.bc9294bb75ba.css: Classify as commit-worthy, ignore, archive, or delete separately.

- [LOW] AUD-0054 workspace-hygiene / untracked-file - chroma-excellence-theme/assets/images/chroma-logo-highres.png: Classify as commit-worthy, ignore, archive, or delete separately.

- [LOW] AUD-0055 workspace-hygiene / untracked-file - chroma-excellence-theme/assets/images/chroma-logo.png: Classify as commit-worthy, ignore, archive, or delete separately.

- [LOW] AUD-0056 workspace-hygiene / untracked-file - chroma-excellence-theme/assets/js/admin.b457513d5358.js: Classify as commit-worthy, ignore, archive, or delete separately.

- [LOW] AUD-0057 workspace-hygiene / tracked-scratch - chroma-excellence-theme/page-schedule-tour.php.backup: Review whether this should remain tracked, move to docs/tools, or ignore/remove later.

- [LOW] AUD-0058 workspace-hygiene / tracked-scratch - debug-meta.php: Review whether this should remain tracked, move to docs/tools, or ignore/remove later.

- [LOW] AUD-0059 workspace-hygiene / tracked-scratch - debug_translation.php: Review whether this should remain tracked, move to docs/tools, or ignore/remove later.

- [LOW] AUD-0060 workspace-hygiene / tracked-scratch - diag-edit-screen.php: Review whether this should remain tracked, move to docs/tools, or ignore/remove later.

- [LOW] AUD-0061 workspace-hygiene / tracked-scratch - diag-portal.php: Review whether this should remain tracked, move to docs/tools, or ignore/remove later.

- [LOW] AUD-0062 workspace-hygiene / tracked-scratch - diag-rewrite.php: Review whether this should remain tracked, move to docs/tools, or ignore/remove later.

- [LOW] AUD-0063 workspace-hygiene / tracked-scratch - diag.php: Review whether this should remain tracked, move to docs/tools, or ignore/remove later.

- [LOW] AUD-0064 workspace-hygiene / untracked-file - diff.txt: Classify as commit-worthy, ignore, archive, or delete separately.

- [LOW] AUD-0065 workspace-hygiene / untracked-file - diff_cleanup.txt: Classify as commit-worthy, ignore, archive, or delete separately.

- [LOW] AUD-0066 workspace-hygiene / tracked-scratch - inspect_meta.php: Review whether this should remain tracked, move to docs/tools, or ignore/remove later.

- [LOW] AUD-0067 workspace-hygiene / untracked-file - old_cleanup.php: Classify as commit-worthy, ignore, archive, or delete separately.

- [LOW] AUD-0068 workspace-hygiene / tracked-scratch - old_controller.php: Review whether this should remain tracked, move to docs/tools, or ignore/remove later.

- [LOW] AUD-0069 workspace-hygiene / untracked-file - old_enforcer.php: Classify as commit-worthy, ignore, archive, or delete separately.

- [LOW] AUD-0070 workspace-hygiene / tracked-scratch - old_login.php: Review whether this should remain tracked, move to docs/tools, or ignore/remove later.

- [LOW] AUD-0071 workspace-hygiene / tracked-scratch - plugins/QA-Report-App/debug-api-test.php: Review whether this should remain tracked, move to docs/tools, or ignore/remove later.

- [LOW] AUD-0072 workspace-hygiene / untracked-file - reports/repo-audit-fix-roadmap-20260503.md: Classify as commit-worthy, ignore, archive, or delete separately.

- [LOW] AUD-0073 workspace-hygiene / untracked-file - temp_canonical.txt: Classify as commit-worthy, ignore, archive, or delete separately.

- [LOW] AUD-0074 workspace-hygiene / tracked-scratch - temp_old_controller.php: Review whether this should remain tracked, move to docs/tools, or ignore/remove later.

- [LOW] AUD-0075 workspace-hygiene / tracked-scratch - test_sitemap_fetch2.php: Review whether this should remain tracked, move to docs/tools, or ignore/remove later.
