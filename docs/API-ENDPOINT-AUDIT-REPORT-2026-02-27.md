# API Endpoint Audit Report (Plan + Execution)

Date: 2026-02-27
Repo: `C:\Users\chara\Documents\wptheme\Wptstchroma`

## Objective
Audit all discoverable API surfaces (REST + AJAX), identify write-path blockers, and verify that API writes persist as expected.

## Plan (Executed)
1. Inventory all REST and AJAX endpoints across theme + plugins.
2. Trace write-capable endpoints to persistence code paths.
3. Identify blockers (allowlists, sanitizers, permission gates, key normalization).
4. Patch critical write-integrity issues in `chroma-agent-api`.
5. Validate syntax and produce endpoint/write-path report.

Status: Completed (static code audit + code patch execution in repo).

## Execution Evidence
- Endpoint inventory command output captured at: `reports/api-endpoint-audit-raw.txt`
- Totals from code scan:
  - REST route registrations: `76`
  - AJAX hook registrations: `74`
  - Unique AJAX actions: `73`

## API Surface Inventory (By Namespace)

### `chroma-agent/v1` (machine-to-machine automation)
Primary route files:
- `plugins/chroma-agent-api/includes/routes/class-content-routes.php`
- `plugins/chroma-agent-api/includes/routes/class-theme-routes.php`
- `plugins/chroma-agent-api/includes/routes/class-seo-routes.php`
- `plugins/chroma-agent-api/includes/routes/class-media-routes.php`
- `plugins/chroma-agent-api/includes/routes/class-key-routes.php`
- `plugins/chroma-agent-api/includes/routes/class-audit-routes.php`
- `plugins/chroma-agent-api/includes/routes/class-discovery-routes.php`

Write-capable endpoints verified:
- `/content` `POST` -> `wp_insert_post`, `update_post_meta`, `wp_set_object_terms`
- `/content/{id}` `PATCH|POST|PUT|DELETE` -> `wp_update_post`, `update_post_meta`, `wp_delete_post`
- `/content/{id}/rollback` `POST` -> `wp_restore_post_revision`
- `/theme/options` `PATCH|POST` -> `update_option` (allowlist-gated)
- `/theme/mods` `PATCH|POST` -> `set_theme_mod` (allowlist-gated)
- `/seo/options` `PATCH|POST` -> `update_option` (allowlist-gated)
- `/seo/meta/{post_id}` `PATCH|POST` -> `update_post_meta` (allowlist-gated)
- `/seo/schema/{post_id}` `PATCH|POST` -> `update_post_meta` (schema-key-gated)
- `/schema/seo/{post_id}` `PATCH|POST` -> alias of above
- `/media` `POST` -> media upload + attachment creation
- `/media/attach` `POST` -> `wp_update_post(post_parent)`
- `/keys` `POST`, `/keys/{id}/revoke` `POST`, `/keys/{id}/rotate` `POST`
- `/rollback/snapshot` `POST` -> option/theme_mod rollback

### `chroma-portal/v1` (parent portal)
File:
- `plugins/chroma-parent-portal/includes/class-api-routes.php`

Write-capable endpoints:
- `/content/create` `POST` -> `wp_insert_post`, taxonomy/meta writes
- `/content/update/{id}` `POST` -> `wp_update_post`, taxonomy/meta writes
- `/content/delete/{id}` `DELETE` -> `wp_delete_post`

### `chroma/v1` (school dashboard + SEO translation)
Files:
- `plugins/chroma-school-dashboard/inc/class-api-routes.php`
- `plugins/chroma-seo-pro-reset/inc/class-translation-api.php`
- `plugins/chroma-seo-pro-reset/inc/class-seo-dashboard.php` (`/validate` read-only)
- `plugins/chroma-seo-pro-reset/inc/class-citation-datasets.php` (read-only)

Write-capable endpoints:
- `/portal/school/{id}` `PATCH` -> writes whitelisted `_chroma_school_*` meta fields
- `/translations/{id}` `POST|DELETE` -> writes/deletes `_chroma_es_*` meta
- `/translate` `POST` -> AI translation, writes `_chroma_es_*` meta

### `cqa/v1` (QA Report App)
Files:
- `plugins/QA-Report-App/chroma-qa-reports/includes/api/class-rest-controller.php`
- `plugins/QA-Report-App/chroma-qa-reports/includes/workflow/class-approval-workflow.php`
- `plugins/QA-Report-App/chroma-qa-reports/includes/utils/class-location.php`

Write-capable endpoints include:
- schools CRUD
- reports CRUD
- report responses save
- report photos upload/update/delete
- settings update
- report version restore
- workflow state transitions
- location override logging

## Deep Write-Path Findings

### F1 (Critical, fixed): schema write integrity could be altered by downstream meta sanitizers
Symptoms addressed:
- API write succeeds (`success=true`) but stored schema can be altered by external `sanitize_post_meta_*` filters.

Fix implemented:
- `plugins/chroma-agent-api/includes/routes/class-seo-routes.php`
  - Added trusted persistence wrapper for schema payload keys.
  - Added read-after-write mismatch detection (`write_mismatches`).
  - Added optional strict mode (`strict_write=true`) returning `409` on mismatch.

Relevant lines:
- strict write + mismatch tracking in `set_post_seo_meta`: lines ~245-332
- strict write + mismatch tracking in `set_post_schema`: lines ~469-556
- sanitizer bypass wrapper: lines ~598-627

### F2 (High, fixed): `_chroma_schema_override` did not preserve JSON-LD keys
Impact:
- Sending object payloads with `@context` / `@type` for `_chroma_schema_override` could lose special keys due generic key sanitization path.

Fix implemented:
- `_chroma_schema_override` now uses preserve-keys sanitizer path.

Relevant lines:
- `sanitize_meta_value_by_key`: line ~579

### F3 (Expected behavior, not a bug): allowlists intentionally block non-approved keys
Observed in:
- theme options/mods writes
- SEO options/meta writes

Code references:
- theme/SEO allowlists in `plugins/chroma-agent-api/includes/class-utils.php` lines ~190-248
- blocked-key behavior in theme routes `set_options`/`set_mods` lines ~88-99 and ~175-186

### F4 (Design constraint): content meta keys are normalized by `sanitize_key`
Impact:
- `/content` meta writes can rename/drop unsupported meta key formats (uppercase/special chars).

Code reference:
- `apply_meta_and_tax`: `class-content-routes.php` lines ~476-490

### F5 (Design constraint): school dashboard patch endpoint is field-whitelisted
Impact:
- `/chroma/v1/portal/school/{id}` only writes approved keys; all other fields are ignored.

Code reference:
- `allowed_keys` and write loop in `class-api-routes.php` lines ~339-377

## Code Changes Applied

### Modified
- `plugins/chroma-agent-api/includes/routes/class-seo-routes.php`

### Added behavior
- `strict_write` request flag for `/seo/meta/{post_id}` and `/seo/schema/{post_id}`
- `write_mismatches` response payload for both routes
- filter-bypassed trusted write for schema payload meta keys
- preserve-keys sanitation for `_chroma_schema_override`

## Validation Performed
- Syntax check:
  - `php -l plugins/chroma-agent-api/includes/routes/class-seo-routes.php`
  - Result: no syntax errors.

## Runtime Verification Notes
This run was a repository code audit (no live WP runtime, no production API credentials in this workspace), so endpoint persistence was verified statically by tracing all write code paths and gates.

For live confirmation, run read-after-write integration checks against:
- `/wp-json/chroma-agent/v1/seo/schema/{post_id}` with `strict_write=true`
- `/wp-json/chroma-agent/v1/seo/meta/{post_id}` with `strict_write=true`

Expected live pass condition:
- HTTP `200`
- `write_mismatches: []`
- immediate GET reflects exact payload (modulo intentional sanitization and allowlists).
