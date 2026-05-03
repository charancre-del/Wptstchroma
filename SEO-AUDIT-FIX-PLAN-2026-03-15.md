# SEO Audit Fix Plan - 2026-03-15

## Scope

Audit exports reviewed:

- `chromaela_14-mar-2026_multiple-meta-descripti_2026-03-15_01-25-55.csv`
- `chromaela_14-mar-2026_non-canonical-page-in-s_2026-03-15_01-25-52.csv`
- `chromaela_14-mar-2026_orphan-page-(has-no-inc_2026-03-15_01-26-03.csv`

Live spot checks performed on:

- `https://chromaela.com/daycare-near-locust-grove-ga/`
- `https://chromaela.com/preschool-in-lilburn-ga/`
- `https://chromaela.com/sitemap.xml`

## What The Audit Actually Shows

There are two URL clusters, not three unrelated problems.

### Cluster A: `near` pages

- Count: `221`
- Pattern: `/{keyword}-near-{city}-{state}/` and `/es/{keyword}-near-{city}-{state}/`
- Issues:
  - multiple meta descriptions: `221`
  - orphan pages: `221`
  - non-canonical pages in sitemap: `221`
- Overlap:
  - all `221` pages appear in all three issue sets

Observed live behavior on `https://chromaela.com/daycare-near-locust-grove-ga/`:

- two `<meta name="description">` tags
- canonical points to homepage: `https://chromaela.com/`
- page is present in `https://chromaela.com/sitemap.xml`

### Cluster B: `in-city` combo pages

- Count: `784`
- Pattern: `/{program}-in-{city}-{state}/` and `/es/{program}-in-{city}-{state}/`
- Issues:
  - non-canonical pages in sitemap only

Observed live behavior on `https://chromaela.com/preschool-in-lilburn-ga/`:

- canonical points to uppercase state variant: `https://chromaela.com/preschool-in-lilburn-GA/`
- sitemap contains lowercase and uppercase forms

## Root Causes

### 1. Theme always outputs a meta description

The theme prints a raw meta description in [`chroma-excellence-theme/inc/customizer-seo.php`](c:\Users\chara\Documents\wptheme\Wptstchroma\chroma-excellence-theme\inc\customizer-seo.php) at lines `267` and `283`.

That creates duplicate descriptions anywhere an SEO plugin or another head layer also prints one.

### 2. Dynamic route canonicals are inconsistent

The theme has a fallback canonical output in [`chroma-excellence-theme/inc/cleanup.php`](c:\Users\chara\Documents\wptheme\Wptstchroma\chroma-excellence-theme\inc\cleanup.php) at lines `229` to `258`, and the theme header expects a separate canonical layer at [`chroma-excellence-theme/header.php`](c:\Users\chara\Documents\wptheme\Wptstchroma\chroma-excellence-theme\header.php) line `7`.

Live output shows dynamic routes are not getting stable self-canonicals:

- `near` sample canonicalizes to home
- `in-city` sample canonicalizes to a case-variant URL

### 3. Sitemap includes URLs that are not canonical

Dynamic routes are recognized in [`chroma-excellence-theme/functions.php`](c:\Users\chara\Documents\wptheme\Wptstchroma\chroma-excellence-theme\functions.php) around lines `628` to `657`.

The unified sitemap is served from the same file at lines `688` to `746`, and standard URL generation is at lines `751` to `780`.

Current sitemap behavior is including URLs that:

- canonicalize elsewhere
- are not internally linked
- in at least some cases appear under multiple sitemap variants

## Recommended Decision

Treat the two clusters differently.

### Keep and repair: `in-city` combo pages

These look like the main program-location landing set. Fix their canonical normalization and keep them indexed.

### Prune or deindex unless strategically important: `near` pages

These are the least defensible set:

- zero href inlinks
- duplicate meta descriptions
- currently canonicalizing to home
- still present in sitemap

If these pages are not a deliberate pSEO program with supporting internal links and unique copy, they should be removed from the sitemap and indexation path.

## Fix Plan

### Phase 1. Stop emitting conflicting SEO signals

Owner: theme/plugin engineering

1. Make one system the source of truth for:
   - canonical
   - meta description
   - title
2. Disable theme meta description output when the SEO plugin or canonical layer is active.
3. Keep social tags, but derive them from the canonical URL for the current route rather than homepage defaults.
4. Verify dynamic routes no longer output homepage canonicals.

Success criteria:

- every page has exactly one canonical
- every page has exactly one meta description
- `og:url` matches canonical for indexable pages

### Phase 2. Normalize dynamic route canonicals

Owner: SEO plugin or route-layer engineering

1. For `in-city` pages, canonicalize to the exact requested lowercase URL slug.
2. Remove any logic that uppercases the state segment in the final canonical URL.
3. For `near` pages:
   - if keeping them, use self-canonical URLs
   - if pruning them, add `noindex,follow` first, then remove from sitemap, then optionally redirect
4. Re-test both English and Spanish variants.

Success criteria:

- `/preschool-in-lilburn-ga/` canonicalizes to `/preschool-in-lilburn-ga/`
- `/daycare-near-locust-grove-ga/` either self-canonicalizes or is explicitly noindexed and removed from sitemap

### Phase 3. Clean the sitemap

Owner: theme engineering

1. Only include URLs that are:
   - indexable
   - canonical to themselves
   - intended to rank
2. Remove `near` pages from sitemap if they are being pruned.
3. Ensure sitemap never includes both lowercase and uppercase route variants.
4. Confirm legacy sitemap aliases do not remain in external references or audit tooling.

Success criteria:

- no sitemap URL canonicalizes to a different URL
- no sitemap URL canonicalizes to homepage
- only one canonical form exists for each dynamic route

### Phase 4. Fix orphan-page status for pages you keep

Owner: content/SEO + theme engineering

1. Add internal links to kept landing pages from:
   - relevant program pages
   - relevant location pages
   - city pages or regional hub pages
2. Use contextual modules, not footer dumps.
3. If a page cannot earn at least a few justified internal links, it should not stay indexable.

Success criteria:

- zero kept landing pages remain orphaned
- internal links are crawlable HTML links, not JS-only UI

### Phase 5. Improve page-level uniqueness if `near` pages stay live

Owner: content/SEO

1. Generate route-specific title and meta templates.
2. Add route-specific on-page intros and local proof points.
3. Avoid reusing homepage description copy across these pages.

Success criteria:

- no multiple-description issues
- no near pages share generic homepage description text

## Recommended Execution Order

1. Fix duplicate meta description output.
2. Fix canonical generation for dynamic routes.
3. Remove non-canonical URLs from sitemap.
4. Decide which `near` pages stay indexed.
5. Add internal links only to the pages that remain indexable.
6. Re-run crawl and compare counts.

## Validation Checklist

- Sample `near` page:
  - one canonical
  - one description
  - canonical not homepage unless intentionally redirected/noindexed
- Sample `in-city` page:
  - one canonical
  - lowercase canonical matches sitemap entry
- `sitemap.xml`:
  - no uppercase state duplicates
  - no URLs that canonicalize elsewhere
- Re-run site audit and confirm:
  - multiple meta descriptions: `0`
  - orphan pages for kept landing pages: `0`
  - non-canonical pages in sitemap: `0`

## Key Code References

- [`chroma-excellence-theme/inc/customizer-seo.php`](c:\Users\chara\Documents\wptheme\Wptstchroma\chroma-excellence-theme\inc\customizer-seo.php)
- [`chroma-excellence-theme/inc/cleanup.php`](c:\Users\chara\Documents\wptheme\Wptstchroma\chroma-excellence-theme\inc\cleanup.php)
- [`chroma-excellence-theme/functions.php`](c:\Users\chara\Documents\wptheme\Wptstchroma\chroma-excellence-theme\functions.php)
- [`chroma-excellence-theme/inc/force-trailing-slashes.php`](c:\Users\chara\Documents\wptheme\Wptstchroma\chroma-excellence-theme\inc\force-trailing-slashes.php)
- [`chroma-excellence-theme/header.php`](c:\Users\chara\Documents\wptheme\Wptstchroma\chroma-excellence-theme\header.php)
