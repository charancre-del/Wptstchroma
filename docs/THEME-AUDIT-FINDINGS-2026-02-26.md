# Theme + Plugin Audit Findings (Code Audit)

Date: 2026-02-26
Scope: `chroma-excellence-theme`, `plugins/chroma-seo-pro-reset`, `chroma-plugins/*`
Method: static code audit (no live browser trace in this pass)

## Top Findings (Ranked)

### 1) HIGH - Duplicate canonical tag sources are active
- Files:
  - `chroma-excellence-theme/inc/cleanup.php:128`
  - `plugins/chroma-seo-pro-reset/inc/seo-automations/class-canonical-enforcer.php:21`
- Root cause:
  - Theme prints canonical via `chroma_enforce_canonical()`.
  - SEO plugin also prints canonical via `Chroma_Canonical_Enforcer::output_canonical()`.
- Risk:
  - Conflicting/duplicate canonicals in head, unstable canonicalization signals.
- Repro:
  1. Load any public page source.
  2. Count `<link rel="canonical" ...>` tags.
- Minimal fix:
  - Keep exactly one canonical owner. Prefer plugin canonical; remove/disable theme canonical hook.

### 2) HIGH - Speculation Rules are contradictory and likely duplicated
- Files:
  - `chroma-excellence-theme/header.php:50`
  - `plugins/chroma-seo-pro-reset/inc/seo-automations/class-speculation-rules.php:17`
  - `chroma-excellence-theme/functions.php:590`
- Root cause:
  - Header hardcodes a speculationrules script.
  - Plugin also injects speculationrules when enabled.
  - Theme attempts to disable WP speculation rules (core/plugin filter), but hardcoded header script bypasses that.
- Risk:
  - Duplicate prerender rules, unpredictable preloading behavior, extra background work.
- Repro:
  1. View page source.
  2. Search for `type="speculationrules"` and count scripts.
- Minimal fix:
  - Remove hardcoded header speculation script and let one controlled source (plugin option) own it.

### 3) HIGH - Blog template has unsized images causing CLS
- Files:
  - `chroma-excellence-theme/single.php:137`
  - `chroma-excellence-theme/single.php:149`
- Root cause:
  - Author avatar and featured image are rendered without explicit `width`/`height`.
- Risk:
  - Large layout shifts on blog pages (matches your Lighthouse CLS symptom).
- Repro:
  1. Run Lighthouse on a blog URL.
  2. CLS diagnostics show avatar/hero shifts.
- Minimal fix:
  - Add explicit width/height to avatar and featured image (or use `wp_get_attachment_image()` to emit intrinsic dimensions).

### 4) HIGH - FAQ from meta is effectively disabled unless modular schema exists
- Files:
  - `plugins/chroma-seo-pro-reset/inc/bootstrap.php:311`
  - `plugins/chroma-seo-pro-reset/inc/schema-builders/class-universal-faq-builder.php:20`
- Root cause:
  - Universal FAQ builder class exists, but its `output()` is not hooked to `wp_head`.
  - Current comment says FAQ auto-injection intentionally disabled.
- Risk:
  - FAQ content in `chroma_faq_items` will not render as JSON-LD unless explicitly present in `_chroma_post_schemas` modular entries.
- Repro:
  1. Add FAQ items in meta (`chroma_faq_items`) on a page without modular FAQ schema.
  2. Inspect head JSON-LD; FAQPage block absent.
- Minimal fix:
  - Decide one source of truth:
    - If modular-only: keep as-is and document strict requirement.
    - If meta fallback needed: hook `Chroma_Universal_FAQ_Builder::output` behind a flag and dedupe.

### 5) MEDIUM - Async CSS filter can duplicate `onload` attribute
- File:
  - `chroma-excellence-theme/inc/enqueue.php:449`
- Root cause:
  - `chroma_async_styles()` runs multiple `str_replace()` passes (`media='all'` then `media='print'`) on the same string, potentially appending `onload` more than once.
- Risk:
  - Invalid/fragile link tags, inconsistent async stylesheet behavior, render-path regressions.
- Repro:
  1. Inspect generated `<link>` for `chroma-page-effects`.
  2. Verify if `onload` appears multiple times.
- Minimal fix:
  - Use a single regex/DOM-safe transform and bail if `onload` already present.

### 6) MEDIUM - Inline accessibility CSS block has malformed brace
- File:
  - `chroma-excellence-theme/inc/enqueue.php:274`
- Root cause:
  - Inline CSS string includes an unmatched closing `}` after CTA visibility rule.
- Risk:
  - Rule parsing ambiguity; later CSS may be ignored by browser parser depending on recovery.
- Repro:
  1. Copy inline CSS emitted for `chroma-main`.
  2. Run through CSS validator; unmatched block error appears.
- Minimal fix:
  - Remove the stray brace and revalidate emitted CSS.

### 7) MEDIUM - Fixed logo preload is not LCP-aware and can be unused
- File:
  - `chroma-excellence-theme/functions.php:489`
- Root cause:
  - Always preloads `/assets/images/logo_chromacropped_140x140.webp` as image LCP candidate.
  - Actual LCP is often hero/featured image (not logo).
- Risk:
  - Unused preload warnings and wasted bandwidth.
- Repro:
  1. Lighthouse on blog/location pages.
  2. Observe "preloaded but not used" warnings.
- Minimal fix:
  - Remove global logo preload; only preload route-specific true LCP assets.

### 8) MEDIUM - Website schema has two generators competing
- Files:
  - `plugins/chroma-seo-pro-reset/inc/bootstrap.php:316`
  - `plugins/chroma-seo-pro-reset/inc/class-theme-schema-compat.php:716`
  - `plugins/chroma-seo-pro-reset/inc/class-schema-registry.php:131`
- Root cause:
  - Both `Chroma_Schema_Injector::output_website_schema()` and `chroma_website_schema_pro()` register `WebSite`.
  - Registry type dedupe keeps one and blocks the other.
- Risk:
  - Non-deterministic schema content depending on hook order and future changes.
- Repro:
  1. Enable schema debug in admin tools.
  2. Observe one WebSite schema blocked as duplicate type.
- Minimal fix:
  - Keep one WebSite generator only.

### 9) LOW - Mojibake/encoding corruption in UI strings
- Files:
  - `chroma-excellence-theme/template-parts/home/hero.php:55`
  - `chroma-excellence-theme/template-parts/home/hero.php:61`
  - `plugins/chroma-seo-pro-reset/inc/class-seo-dashboard.php:4380`
- Root cause:
  - Non-UTF-8 encoded strings committed as garbled bytes.
- Risk:
  - Broken symbols in UI/admin copy, trust/quality impact.
- Repro:
  1. Open home/admin dashboard.
  2. Observe garbled characters like `â˜…`, `ðŸ`.
- Minimal fix:
  - Normalize files to UTF-8 and replace corrupted literals.

## Animation-Specific Notes

1. `main.js` starts autoplay intervals for carousels without reduced-motion check.
- File: `chroma-excellence-theme/assets/js/main.js:324`, `chroma-excellence-theme/assets/js/main.js:362`
- Recommendation: gate autoplay on `matchMedia('(prefers-reduced-motion: reduce)')`.

2. Sticky CTA animation is scroll-triggered and not route-scoped.
- File: `chroma-excellence-theme/assets/js/main.js:377`
- Recommendation: keep, but test overlap with mobile viewport controls and keyboard focus flow.

## Careers Sync (Current State)

- Weekly schedule is implemented in code.
- Files:
  - `plugins/chroma-seo-pro-reset/inc/class-career-sync.php:22`
  - `plugins/chroma-seo-pro-reset/inc/class-career-sync.php:60`
- JSON feed parsing is implemented.
- File:
  - `plugins/chroma-seo-pro-reset/inc/class-careers-api.php:71`
- Dashboard status fields read same options that sync writes.
- File:
  - `plugins/chroma-seo-pro-reset/inc/class-seo-dashboard.php:4375`

Operational caveat:
- WP-Cron requires traffic (or a real server cron trigger) to run on time.

## Recommended Execution Order
1. Resolve canonical + speculation ownership conflicts.
2. Fix blog image intrinsic sizing (CLS).
3. Decide FAQ source-of-truth and re-enable intended path.
4. Fix async CSS tag transformer and inline CSS brace error.
5. Remove global logo preload and keep route-specific LCP preload only.
6. Consolidate WebSite schema generator to one path.
7. UTF-8 cleanup.
