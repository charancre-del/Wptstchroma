# Theme + Plugin Endpoint & Meta Box Audit (Plan + Execution)

Date: 2026-02-27  
Repo: `C:\Users\chara\Documents\wptheme\Wptstchroma`

## Scope Executed
- Full PHP source scan across:
  - `chroma-excellence-theme`
  - `plugins/*`
  - `chroma-plugins/*`
- Endpoint inventory (REST + AJAX)
- Meta box inventory and save-path mapping
- API writeability analysis for all discovered meta-box save surfaces
- Code hardening for write-integrity verification and public GEO feed delivery

## Plan vs Execution
1. Inventory all endpoints: completed.
2. Inventory all meta boxes and save hooks: completed.
3. Map meta boxes to API write routes and blockers: completed.
4. Patch write-integrity gaps: completed.
5. Add public GEO read-only route: completed.
6. Validate PHP syntax for all modified files: completed.

## Inventory Totals
- REST route registrations: `77`
- AJAX hook registrations: `74`
- Explicit `add_meta_box(...)` registrations: `75`
- Files containing explicit meta-box registrations: `25`
- SEO plugin meta boxes registered via base class: `17`
- Total meta-box source files audited (explicit + base-class): `42`

Raw artifacts:
- `reports/rest-routes-raw.txt`
- `reports/ajax-hooks-raw.txt`
- `reports/meta-boxes-raw.txt`
- `reports/meta-box-file-matrix.csv`
- `reports/meta-box-key-matrix.csv`
- `reports/full-meta-endpoint-scan.txt`

## API Writeability Result (Every Meta Box)

### Primary write path that covers all meta-box-backed post meta
- `PATCH/POST /wp-json/chroma-agent/v1/content/{id}`
  - writes `meta` payload with arbitrary meta keys
  - now supports strict verification (`strict_write=true`) with read-after-write mismatch reporting

### SEO-specific write paths
- `PATCH/POST /wp-json/chroma-agent/v1/seo/meta/{post_id}`
  - allowlist-gated SEO/meta keys
  - strict verification supported (`strict_write=true`)
- `PATCH/POST /wp-json/chroma-agent/v1/seo/schema/{post_id}`
  - schema key-gated
  - strict verification supported (`strict_write=true`)

### Plugin-specific write paths
- Parent portal admin routes (`/wp-json/chroma-portal/v1/content/update/{id}` etc.) for portal content CRUD.
- School dashboard route (`PATCH /wp-json/chroma/v1/portal/school/{id}`) for a whitelisted subset of `_chroma_school_*`.

## Important Findings

1. Most meta-box keys are not in SEO-meta allowlist by design.
- Explicit meta keys discovered from meta-box save handlers: `71`
- Not in default `/seo/meta` allowlist: `66`
- This is expected; these keys should be written through `/content/{id}` (or plugin-specific routes), not `/seo/meta/{post_id}`.
- Evidence: `reports/meta-box-explicit-keys.txt`, `reports/meta-keys-not-in-seo-allowlist.txt`

2. Direct API meta writes bypass `save_post` form logic.
- This is normal for machine writes.
- Any meta box logic that transforms form inputs (for example PIN hashing flows in parent portal) will not run unless you call that plugin’s dedicated endpoint/logic.

3. Key normalization behavior on `/content/{id}`.
- `sanitize_key` is applied to incoming meta key names.
- Non-standard key formats can be renamed/dropped. Lowercase underscore keys are safe.

## Code Changes Applied

### 1) New public read-only GEO feed
- Added: `plugins/chroma-agent-api/includes/routes/class-geo-routes.php`
- Registered in bootstrap:
  - `plugins/chroma-agent-api/includes/class-bootstrap.php`
- Route:
  - `GET /wp-json/chroma-agent/v1/geo-feed`
  - public (`permission_callback => __return_true`)
  - published-only payload for locations/programs/events + brand context
  - transient cache + invalidation hooks on relevant post/option changes

### 2) Write-integrity hardening for content endpoint
- Updated: `plugins/chroma-agent-api/includes/routes/class-content-routes.php`
- Added:
  - `strict_write` request flag
  - `write_mismatches` response payload
  - read-after-write verification for meta and taxonomy writes
  - `409 caa_write_integrity_failed` when `strict_write=true` and mismatch detected

### 3) Prior schema write-integrity fixes retained
- Existing updates already in:
  - `plugins/chroma-agent-api/includes/routes/class-seo-routes.php`
- Includes strict verification and schema sanitizer bypass for trusted schema meta keys.

## Syntax Validation
- `php -l plugins/chroma-agent-api/includes/routes/class-content-routes.php` -> OK
- `php -l plugins/chroma-agent-api/includes/routes/class-geo-routes.php` -> OK
- `php -l plugins/chroma-agent-api/includes/class-bootstrap.php` -> OK
- `php -l plugins/chroma-agent-api/includes/routes/class-seo-routes.php` -> OK (validated in prior step)

## Live Verification Commands (Use API Key)

### Verify any meta-box-backed meta via content route
```http
PATCH /wp-json/chroma-agent/v1/content/{post_id}
Authorization: Bearer {CHROMA_AGENT_API_KEY_WITH_write:content}
Content-Type: application/json

{
  "strict_write": true,
  "meta": {
    "location_phone": "+1-470-570-1633",
    "location_city": "Tyrone"
  }
}
```
Pass condition:
- HTTP `200`
- `write_mismatches` empty
- Follow-up GET `/content/{post_id}` shows exact persisted values

### Verify schema route integrity
```http
PATCH /wp-json/chroma-agent/v1/seo/schema/{post_id}
Authorization: Bearer {CHROMA_AGENT_API_KEY_WITH_write:seo}
Content-Type: application/json

{
  "strict_write": true,
  "_chroma_post_schemas": [ ... ]
}
```
Pass condition:
- HTTP `200`
- `write_mismatches` empty

## Access Token Clarification
- `chroma-agent/v1` routes use your Agent API key:
  - `Authorization: Bearer {API_KEY}`
  - (also supports `x-api-key` header)
- `chroma/v1/portal/*` (school dashboard) uses session bearer token returned by `/chroma/v1/auth/google`:
  - `Authorization: Bearer {session_token}`
- `chroma-portal/v1/*` (parent portal family content) uses:
  - `X-Portal-Token: {portal_token}` from `/chroma-portal/v1/login`
