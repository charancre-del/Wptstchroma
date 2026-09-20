# Redirect and Indexation Map

## Current verified decisions

| Source / family | Destination / directive | Reason | Current proof |
| --- | --- | --- | --- |
| `/blog/` | `/stories/` | Consolidate editorial archive. | Final URL verified. |
| False LaFayette/Walker County campus route | `/locations/` | Do not imply a campus exists there. | Cross-device final URL verified. |
| Parent Portal | Exclude from sitemap | Utility/login route, not an organic landing page. | Sitemap absence verified. |
| Legacy local doorway articles | Accessible, `noindex,follow`, excluded from sitemap | Preserve continuity while removing search value. | Header and sitemap behavior verified. |
| Auto-generated combo/near-me/geographic families | Noindex; no new publication without approval | Prevent uncontrolled thin local SEO. | Generation and sitemap controls verified. |
| Empty historical records | Noindex and exclude from sitemap | Avoid indexing empty pages without destructive deletion. | Six records governed. |
| Real campus and approved program pages | Indexed and canonical | Maintain real conversion landing pages. | Crawl verified. |

## Crawl result

The current 470-page staging technical crawl found **0 redirecting sitemap URLs and 0 redirect chains**.

## Production gate

Before switching themes, export current production redirect rules and merge rather than overwrite them. After cutover, verify every documented source, high-traffic legacy URL from analytics/Search Console, primary navigation route, canonical, and sitemap entry. Use 410 only after owner approval confirms a URL is obsolete and has no relevant replacement.
