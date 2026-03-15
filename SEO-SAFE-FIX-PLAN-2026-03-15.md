# SEO Safe Fix Plan - 2026-03-15

## Goal

Fix the current audit issues without breaking:

- dynamic combo pages
- dynamic near pages
- sitemap coverage
- canonical redirects
- social metadata
- plugin/theme coordination

This plan assumes the current stack is split across:

- theme SEO output
- `chroma-seo-pro-reset` virtual page generators
- canonical enforcer
- custom sitemap handlers

## Current Risk Summary

### Issue clusters

1. `221` near pages
   - duplicate meta descriptions
   - orphaned
   - non-canonical in sitemap

2. `784` combo pages
   - non-canonical in sitemap
   - canonical case mismatch

### Main breakage vectors

1. Theme and plugin both participate in head output.
2. Combo and near routes are handled differently.
3. `/sitemap.xml` is not the only sitemap source.
4. Canonical settings are partly forced by plugin bootstrap.
5. Near pages currently depend on theme fallback behavior more than combo pages do.

## Non-Negotiable Safety Rules

1. Do not remove any fallback SEO output until the replacement path is verified on both combo and near routes.
2. Do not change sitemap inclusion rules until canonical behavior is correct for the same URL set.
3. Do not deindex, redirect, or remove near pages until the business decision is explicit:
   - keep and support them
   - or prune them
4. Do not change one canonical layer in isolation.
5. Revalidate live HTML head output after each phase, not just PHP logic.

## Definition Of Done

When the work is complete:

- every indexable URL has exactly one canonical
- every indexable URL has exactly one meta description
- `og:url` and canonical match
- no sitemap contains URLs that canonicalize elsewhere
- no kept page is orphaned
- no route type loses title, description, or share tags
- no route type starts redirect-looping or 404ing

## Safe Execution Plan

### Phase 0. Freeze the baseline

Owner: engineering

Before changing code, capture a fixed before-state for these URLs:

- homepage
- one program page
- one location page
- one combo page
- one near page
- one Spanish combo page
- one Spanish near page
- `/sitemap.xml`
- `/sitemap-combos.xml`
- `/sitemap-near-me.xml`

For each URL record:

- status code
- canonical
- meta description count
- title
- `og:url`
- `twitter:description`
- robots meta if present

This becomes the regression reference.

### Phase 1. Decide ownership per SEO signal

Owner: engineering

Assign one source of truth for each signal:

- canonical: plugin canonical enforcer
- title: route-specific plugin filters, with theme fallback only where needed
- meta description: route-specific plugin filters or a controlled fallback
- social URL/description: same route-specific canonical/description source
- sitemap membership: plugin route generators plus unified sitemap layer

Rule:

- the theme should not emit raw SEO tags for pages the plugin stack already governs

Deliverable:

- short ownership table before any code changes

### Phase 2. Add route-safe metadata support before removing fallbacks

Owner: engineering

This is the most important guardrail.

1. Verify combo pages already provide:
   - `wpseo_title`
   - `pre_get_document_title`
   - `wpseo_metadesc`
   - `wpseo_canonical`
   - `wpseo_opengraph_url`
2. Add the same explicit metadata path for near pages before touching theme fallback output.
3. Ensure near pages also provide:
   - stable title
   - stable meta description
   - stable canonical
   - stable Open Graph URL
4. Only after near pages are covered should the theme-level raw meta description be suppressed conditionally.

Validation gate:

- combo and near pages still render correct title/description/canonical with theme fallback disabled in a test branch

### Phase 3. Normalize canonical generation centrally

Owner: engineering

Fix canonical logic in one coordinated pass.

1. Normalize combo URLs to one casing convention:
   - recommended: lowercase slug, lowercase state segment in path
2. Update all canonical producers to use the same form.
3. Update all internal-link producers to emit the same form.
4. Update social URL producers to match canonical.
5. Keep redirects on, but only after canonical output is correct.

Required code surfaces to update together:

- theme request parsing
- canonical enforcer
- combo page generator
- any Yoast canonical/OpenGraph filters
- internal-link generators

Validation gate:

- requesting lowercase combo URL returns `200`
- canonical equals requested lowercase URL
- no redirect to uppercase variant
- internal links point to the same lowercase URL

### Phase 4. Fix duplicate meta descriptions safely

Owner: engineering

1. Make theme meta description output conditional:
   - do not print when route-specific plugin metadata exists
   - do not print when SEO plugin output is active for the current request
2. Keep a narrow fallback for standard pages that truly need it.
3. Confirm near pages are covered first.

Validation gate:

- homepage: one description
- program page: one description
- combo page: one description
- near page: one description
- Spanish variants: one description

### Phase 5. Align sitemap logic with canonical logic

Owner: engineering

Do not do this earlier.

1. Update unified sitemap logic and plugin sitemap providers together.
2. Remove any URL form from sitemaps that is not self-canonical.
3. Ensure there is only one canonical variant per route.
4. Confirm Yoast sitemap index entries remain accurate.
5. Check legacy aliases still resolve cleanly.

Validation gate:

- `/sitemap.xml` has only self-canonical URLs
- `/sitemap-combos.xml` matches the same canonical rules
- `/sitemap-near-me.xml` matches the same canonical rules
- no uppercase/lowercase duplicates remain

### Phase 6. Make the near-page keep-or-prune decision

Owner: SEO + content + engineering

You must choose one path.

#### Option A: Keep near pages

Required work:

- self-canonicalize them
- give each a unique title and description
- keep them in sitemap
- add real internal links
- confirm business value

#### Option B: Prune near pages

Required work:

1. remove from sitemap
2. add `noindex,follow` for a transition period
3. monitor deindexation
4. optionally redirect later if desired

Do not immediately hard-redirect all of them to home unless that is an explicit strategy.

Validation gate:

- no mixed signals remain between canonical, indexability, and sitemap membership

### Phase 7. Fix orphan status only for URLs you keep

Owner: SEO + engineering

For retained pages:

1. add internal links from program pages
2. add internal links from city/location pages where relevant
3. avoid dumping sitewide footer spam
4. verify links are present in server-rendered HTML

Validation gate:

- sample kept pages have at least one crawlable internal link
- audit no longer flags them as orphaned

### Phase 8. Release in stages

Owner: engineering

Recommended rollout order:

1. metadata support for near pages
2. canonical normalization
3. duplicate meta-description removal
4. sitemap cleanup
5. near-page keep/prune execution
6. orphan-linking work

Do not merge all phases blind in one step.

## Test Matrix

Test every phase against these URL types:

- homepage
- standard page
- program singular
- location singular
- combo page EN
- combo page ES
- near page EN
- near page ES
- sitemap endpoints
- legacy sitemap aliases

For each test URL verify:

- HTTP status
- canonical count
- canonical target
- meta description count
- page title
- `og:url`
- `twitter:description`
- robots state
- rendered body still loads

## Rollback Plan

If a phase breaks route rendering or head output:

1. revert only the last phase
2. purge cache
3. compare live HTML head with baseline capture
4. do not continue to later phases until the broken route type is stable

## Recommended Implementation Order In Code

1. Add near-page explicit SEO filters.
2. Refactor canonical URL builder to one normalization function used by combo, near, and fallback paths.
3. Refactor social URL generation to consume canonical URL.
4. Add conditional suppression for theme meta description.
5. Update sitemap producers.
6. Add or remove near-page internal links depending on keep/prune decision.

## Success Metrics

- multiple meta descriptions: `0`
- non-canonical pages in sitemap: `0`
- orphan pages among retained URLs: `0`
- no new 404s on combo or near routes
- no redirect loops
- no missing title/description/canonical on virtual pages

## Files To Touch Carefully

- [`chroma-excellence-theme/inc/customizer-seo.php`](c:\Users\chara\Documents\wptheme\Wptstchroma\chroma-excellence-theme\inc\customizer-seo.php)
- [`chroma-excellence-theme/inc/cleanup.php`](c:\Users\chara\Documents\wptheme\Wptstchroma\chroma-excellence-theme\inc\cleanup.php)
- [`chroma-excellence-theme/functions.php`](c:\Users\chara\Documents\wptheme\Wptstchroma\chroma-excellence-theme\functions.php)
- [`plugins/chroma-seo-pro-reset/inc/seo-automations/class-canonical-enforcer.php`](c:\Users\chara\Documents\wptheme\Wptstchroma\plugins\chroma-seo-pro-reset\inc\seo-automations\class-canonical-enforcer.php)
- [`plugins/chroma-seo-pro-reset/inc/seo-automations/class-combo-page-generator.php`](c:\Users\chara\Documents\wptheme\Wptstchroma\plugins\chroma-seo-pro-reset\inc\seo-automations\class-combo-page-generator.php)
- [`plugins/chroma-seo-pro-reset/inc/seo-automations/class-near-me-pages.php`](c:\Users\chara\Documents\wptheme\Wptstchroma\plugins\chroma-seo-pro-reset\inc\seo-automations\class-near-me-pages.php)
- [`plugins/chroma-seo-pro-reset/inc/seo-automations/class-combo-internal-links.php`](c:\Users\chara\Documents\wptheme\Wptstchroma\plugins\chroma-seo-pro-reset\inc\seo-automations\class-combo-internal-links.php)
- [`plugins/chroma-seo-pro-reset/inc/class-sitemap-integrator.php`](c:\Users\chara\Documents\wptheme\Wptstchroma\plugins\chroma-seo-pro-reset\inc\class-sitemap-integrator.php)
- [`plugins/chroma-seo-pro-reset/inc/seo-automations/bootstrap.php`](c:\Users\chara\Documents\wptheme\Wptstchroma\plugins\chroma-seo-pro-reset\inc\seo-automations\bootstrap.php)
