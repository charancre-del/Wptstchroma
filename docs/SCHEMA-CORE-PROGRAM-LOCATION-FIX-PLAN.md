# Schema Remediation Plan: Core, Program, and Location Pages

## Objective
Eliminate remaining schema validation errors and warnings on `chromaela.com` for:
- Core pages
- Program pages
- Location-related pages

This plan is based on the latest full sitemap crawl and schema snapshot:
- Crawl DB: `reports/active_schema_20260225_013745.sqlite`
- Validation report: `reports/schema_validation_audit_20260225_014750.json`

## Current State Summary
- Sitemap URLs audited: `357`
- HTTP 200 pages: `344`
- Non-200 pages in sitemap: `13` (all 404 legacy `?post_type=team_member&p=...`)
- Schema nodes validated: `2302`
- Errors: `5`
- Warnings: `609`

### Bucket-level findings
- Core pages: `1` error, `28` warnings
- Program pages (`/programs/*`): `0` errors, `39` warnings
- Campus location pages (`/locations/*`): `0` errors, `0` warnings
- Geo landing pages (`/childcare/*`): `0` errors, `527` warnings

### What is already fixed
These previously critical items are now clean in the latest snapshot:
- LocalBusiness/ChildCare flat address without nested `address`
- Review missing `author`/`itemReviewed`
- Service missing `provider`
- Event missing required fields
- Course Offer missing `@type`
- ItemList element missing `@type`

## Root Cause
Primary residual issue is schema-type misuse by route:
- `ChildCare` and/or `LocalBusiness` are still emitted on non-campus pages (core pages, program pages, geo landing pages) without full physical location data.
- Those pages often do not have complete `PostalAddress`, `telephone`, and `geo`, which drives warning volume.

Campus detail pages (`/locations/*`) already have complete data and should keep LocalBusiness/ChildCare.

## Remediation Strategy
Use route-aware schema type selection:
1. Keep `LocalBusiness`/`ChildCare` only on true campus location detail pages.
2. Use `Organization`, `Service`, `WebPage`, and `ItemList` on non-campus pages.
3. Prevent fallback builders from emitting incomplete LocalBusiness/ChildCare on non-campus routes.
4. Remove invalid 404 URLs from sitemap.

## Files and Components to Update

## 1) Schema Output Path Control (Primary)
File: `plugins/chroma-seo-pro-reset/inc/schema-builders/class-schema-injector.php`
- Function: `Chroma_Schema_Injector::output_location_schema()`
- Action:
  - Confirm output remains restricted to `is_singular('location')` only.
  - Keep strong address/telephone/geo normalization here.
  - Add explicit guard: no registration if required location fields are absent.

File: `plugins/chroma-seo-pro-reset/inc/class-theme-schema-compat.php`
- Functions to audit/adjust:
  - `chroma_location_schema_pro()`
  - `chroma_city_schema_pro()`
  - `chroma_program_schema_pro()`
  - Any helper emitting ChildCare/LocalBusiness on city/program pages
- Action:
  - For `city` and `program`, emit `Service` + `Organization` only.
  - Remove/disable child objects typed as `ChildCare` when they only represent references.
  - Ensure OfferCatalog item references use `Organization` or `Place` references unless full campus fields are available.

## 2) Registry/Validation Policy Hardening
File: `plugins/chroma-seo-pro-reset/inc/class-schema-validator.php`
- Action:
  - Add route context validation hooks:
    - If page is not singular `location`, block/warn against `ChildCare`/`LocalBusiness` without complete campus data.
  - Elevate key warning patterns to actionable debug logs (only under debug mode).

File: `plugins/chroma-seo-pro-reset/inc/class-schema-registry.php`
- Action:
  - Add optional source-policy gate:
    - Deny registration of incomplete LocalBusiness/ChildCare from non-location sources.
  - Keep dedupe behavior intact.

## 3) SEO Dashboard and Builder Safeguards
File: `plugins/chroma-seo-pro-reset/inc/class-seo-dashboard.php`
- Action:
  - Add validation notice when users attempt to save `ChildCare`/`LocalBusiness` schema for non-location pages without required fields.
  - Preserve manual override capability but show risk warnings.

File: `plugins/chroma-seo-pro-reset/inc/meta-boxes/class-schema-editor-metabox.php`
- Action:
  - Add real-time hinting for `address`, `telephone`, and `geo` requirements by type.

## 4) Sitemap Hygiene
Likely file(s): sitemap generation path in theme/plugin stack (depends on active sitemap provider).
- Action:
  - Exclude invalid legacy URLs (`?post_type=team_member&p=...`) from sitemap output.
  - Validate sitemap URLs return `200` before publishing in index.

## Implementation Phases

## Phase P0: Route-Type Gating
- Implement strict gating so only campus pages emit `ChildCare`/`LocalBusiness`.
- Scope: `class-theme-schema-compat.php` and `class-schema-injector.php`.
- Success criteria:
  - `/locations/*` unchanged and valid.
  - `/childcare/*` no longer emits incomplete campus schema.
  - Core/program pages no longer produce LocalBusiness/ChildCare warnings.

## Phase P1: Validator and Registry Guardrails
- Add validation and registration policy checks to prevent future regressions.
- Scope: `class-schema-validator.php`, `class-schema-registry.php`.
- Success criteria:
  - Invalid type/data combos are blocked or clearly logged before output.

## Phase P2: Admin UX Safeguards
- Add schema type/data guidance in dashboard/meta box.
- Scope: `class-seo-dashboard.php`, `class-schema-editor-metabox.php`.
- Success criteria:
  - Editors can’t accidentally publish incomplete LocalBusiness/ChildCare data without explicit warning.

## Phase P3: Sitemap Cleanup
- Remove 404 links from sitemap source.
- Success criteria:
  - `0` sitemap URLs returning 404.

## Detailed Acceptance Criteria

## Core pages
- Home, about, parents, contact, curriculum, schedule-tour:
  - No `missing_address` errors.
  - No ChildCare/LocalBusiness warnings due to incomplete fields.

## Program pages (`/programs/*`)
- Should emit `Service` schema with valid provider.
- Should not emit partial `ChildCare` schema.

## Location pages (`/locations/*`)
- Keep existing valid output:
  - `LocalBusiness` and `ChildCare` with nested `PostalAddress`, `telephone`, `geo`.

## Childcare geo pages (`/childcare/*`)
- If no full campus data: do not emit `ChildCare`/`LocalBusiness`.
- Use `Service`/`WebPage` context instead.

## Validation and QA Plan

## Automated checks
1. Rebuild snapshot:
   - `python scripts/download_active_schema.py --site https://chromaela.com --delay-ms 350 --retries 4`
2. Re-run audit:
   - `python scripts/audit_schema_from_db.py --db reports/active_schema_<timestamp>.sqlite`
3. Compare deltas:
   - Error count target: `0`
   - Warning reduction target: remove route-misuse warnings (`missing_telephone`, `address_missing_*`, `missing_geo`) from non-location routes.

## Manual checks
1. Validate representative URLs in Google Rich Results Test:
   - Home page
   - 1 program page
   - 1 `/childcare/*` page
   - 1 `/locations/*` page
2. Confirm no schema duplicates in page source.
3. Confirm manual schema override still works where set.

## Rollback Plan
1. Keep changes isolated to schema output and validation classes.
2. If issue appears, rollback by file:
   - `class-theme-schema-compat.php`
   - `class-schema-injector.php`
   - `class-schema-validator.php`
   - `class-schema-registry.php`
3. Re-run audit scripts to verify rollback state.

## Performance and Safety Notes
- No new frontend assets/scripts required.
- No DB migrations required.
- Keep all new debug logging behind `WP_DEBUG`.
- Preserve backward compatibility for existing schema overrides.

## Deliverables
1. Code patch set with route-aware schema gating and validation guardrails.
2. Updated audit artifacts showing reduced warnings and zero errors.
3. Updated sitemap with broken URLs removed.
