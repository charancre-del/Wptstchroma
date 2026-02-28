# Schema Remediation Execution Checklist

## Latest Full Audit Reference
- Full warning/error audit (all issues + decision questions):
  - `docs/SCHEMA-FULL-WARNINGS-ERRORS-AUDIT.md`
- Full meta-box compatibility matrix (source file, keys, sanitizer, overwrite risk, API/GEO compatibility):
  - `docs/METABOX-MATRIX-2026-02-28.md`
- Per-key export sheet (one row per discovered meta key):
  - `reports/meta-box-per-key-matrix-2026-02-28.csv`
- Supporting artifacts:
  - `reports/active_schema_20260225_020508.sqlite`
  - `reports/schema_validation_audit_20260225_020508.json`
  - `reports/schema_validation_audit_20260225_020508.md`
  - `reports/schema_validation_audit_full_20260225_020508.json`

## Pre-Execution
- [ ] Confirm baseline artifacts exist:
  - [ ] `reports/active_schema_20260225_013745.sqlite`
  - [ ] `reports/schema_validation_audit_20260225_014750.json`
- [ ] Create branch for schema remediation.
- [ ] Capture current plugin versions and active modules.

## P0: Route-Type Gating
- [ ] Update `plugins/chroma-seo-pro-reset/inc/class-theme-schema-compat.php`
  - [ ] Keep `ChildCare`/`LocalBusiness` on `is_singular('location')` only.
  - [ ] Ensure city/program outputs stay `Service` + `Organization`.
  - [ ] Remove/replace child item types that incorrectly use `ChildCare` without full address.
- [ ] Update `plugins/chroma-seo-pro-reset/inc/schema-builders/class-schema-injector.php`
  - [ ] Enforce required location fields before registering campus schemas.
  - [ ] Skip registration if critical fields are empty.

## P1: Guardrails
- [ ] Update `plugins/chroma-seo-pro-reset/inc/class-schema-validator.php`
  - [ ] Add route-aware warnings/errors for type/data mismatch.
- [ ] Update `plugins/chroma-seo-pro-reset/inc/class-schema-registry.php`
  - [ ] Add optional gate to reject incomplete LocalBusiness/ChildCare on non-location pages.

## P2: Admin UX
- [ ] Update `plugins/chroma-seo-pro-reset/inc/class-seo-dashboard.php`
  - [ ] Add save-time warning for incomplete LocalBusiness/ChildCare on non-location posts.
- [ ] Update `plugins/chroma-seo-pro-reset/inc/meta-boxes/class-schema-editor-metabox.php`
  - [ ] Add field requirement hints by schema type.

## P3: Sitemap Hygiene
- [ ] Remove legacy `?post_type=team_member&p=...` entries from sitemap feed.
- [ ] Confirm all sitemap URLs return `200`.

## Validation
- [ ] Run:
  - [ ] `python scripts/download_active_schema.py --site https://chromaela.com --delay-ms 350 --retries 4`
  - [ ] `python scripts/audit_schema_from_db.py --db reports/active_schema_<timestamp>.sqlite`
- [ ] Verify:
  - [ ] Error count is `0`
  - [ ] Route-misuse warnings are removed from core/program/childcare pages
  - [ ] `/locations/*` pages remain valid

## Manual Spot Checks
- [ ] Home page schema
- [ ] One `/programs/*` page
- [ ] One `/childcare/*` page
- [ ] One `/locations/*` page
- [ ] One city/service-area page

## Release
- [ ] Commit with schema-focused message.
- [ ] Push branch and open PR.
- [ ] Attach before/after audit summaries.

## Rollback
- [ ] If regression detected, revert only schema-related files touched in this plan.
- [ ] Re-run audit scripts to confirm restored baseline behavior.
